<?php
namespace Craft;

class AmForms_FormModel extends BaseElementModel
{
    protected $elementType = AmFormsModel::ElementTypeForm;

    private $_fields;

    /**
     * Use the form handle as the string representation.
     *
     * @return string
     */
    function __toString()
    {
        return Craft::t($this->name);
    }

    /**
     * @access protected
     * @return array
     */
    protected function defineAttributes()
    {
        // Craft email settings
        $settings = craft()->email->getSettings();
        $systemEmail = !empty($settings['emailAddress']) ? $settings['emailAddress'] : '';
        $systemName =  !empty($settings['senderName']) ? $settings['senderName'] : '';

        return array_merge(parent::defineAttributes(), array(
            'id'                       => AttributeType::Number,
            'fieldLayoutId'            => AttributeType::Number,
            'name'                     => AttributeType::String,
            'handle'                   => AttributeType::String,
            'titleFormat'              => array(AttributeType::String, 'default' => "{dateCreated|date('D, d M Y H:i:s')}"),
            'redirectUri'              => AttributeType::String,
            'submitAction'             => AttributeType::String,
            'submitButton'             => AttributeType::String,
            'submissionEnabled'        => array(AttributeType::Bool, 'default' => true),
            'notificationEnabled'      => array(AttributeType::Bool, 'default' => true),
            'notificationRecipients'   => array(AttributeType::String, 'default' => $systemEmail),
            'notificationSubject'      => array(AttributeType::String, 'default' => Craft::t('{formName} form was submitted')),
            'notificationSenderName'   => array(AttributeType::String, 'default' => $systemName),
            'notificationSenderEmail'  => array(AttributeType::String, 'default' => $systemEmail),
            'notificationReplyToEmail' => array(AttributeType::String, 'default' => $systemEmail),
            'notificationTemplate'     => AttributeType::String
        ));
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return array(
            'fieldLayout' => new FieldLayoutBehavior($this->elementType)
        );
    }

    /**
     * Returns the field layout used by this element.
     *
     * @return FieldLayoutModel|null
     */
    public function getFieldLayout()
    {
        return $this->asa('fieldLayout')->getFieldLayout();
    }

    /**
     * Returns the element's CP edit URL.
     *
     * @return string|false
     */
    public function getCpEditUrl()
    {
        return UrlHelper::getCpUrl('amforms/forms/edit/' . $this->id);
    }

    /**
     * Returns the fields associated with this form.
     *
     * @return array
     */
    public function getFields()
    {
        if (! isset($this->_fields)) {
            $this->_fields = array();

            $fieldLayoutFields = $this->getFieldLayout()->getFields();

            foreach ($fieldLayoutFields as $fieldLayoutField) {
                $field = $fieldLayoutField->getField();
                $field->required = $fieldLayoutField->required;
                $this->_fields[] = $field;
            }
        }

        return $this->_fields;
    }

    /**
     * Sets the fields associated with this form.
     *
     * @param array $fields
     */
    public function setFields($fields)
    {
        $this->_fields = $fields;
    }
}