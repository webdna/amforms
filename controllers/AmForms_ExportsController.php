<?php
namespace Craft;

/**
 * AmForms - Exports controller
 */
class AmForms_ExportsController extends BaseController
{
    /**
     * Show exports.
     */
    public function actionIndex()
    {
        $variables = array(
            'exports' => craft()->amForms_exports->getAllExports()
        );
        $this->renderTemplate('amForms/exports/index', $variables);
    }

    /**
     * Create or edit an export.
     *
     * @param array $variables
     */
    public function actionEditExport(array $variables = array())
    {
        // Do we have an export model?
        if (! isset($variables['export'])) {
            // Get export if available
            if (! empty($variables['exportId'])) {
                $variables['export'] = craft()->amForms_exports->getExportById($variables['exportId']);

                if (! $variables['export']) {
                    throw new Exception(Craft::t('No export exists with the ID “{id}”.', array('id' => $variables['exportId'])));
                }
            }
            else {
                $variables['export'] = new AmForms_ExportModel();
            }
        }

        // Get available forms
        $variables['availableForms'] = craft()->amForms_forms->getAllForms();

        // Render template!
        $this->renderTemplate('amforms/exports/_edit', $variables);
    }

    /**
     * Save an export.
     */
    public function actionSaveExport()
    {
        $this->requirePostRequest();

        // Get export if available
        $exportId = craft()->request->getPost('exportId');
        if ($exportId) {
            $export = craft()->amForms_exports->getExportById($exportId);

            if (! $export) {
                throw new Exception(Craft::t('No export exists with the ID “{id}”.', array('id' => $exportId)));
            }
        }
        else {
            $export = new AmForms_ExportModel();
        }

        // Get the chosen form
        $export->formId = craft()->request->getPost('formId');

        // Get proper POST attributes
        $mapping = craft()->request->getPost($export->formId);
        $criteria = isset($mapping['criteria']) ? $mapping['criteria'] : null;
        if ($criteria) {
            // Remove criteria from mapping
            unset($mapping['criteria']);

            // Get criteria field IDs
            foreach ($criteria['fields'] as $key => $field) {
                $splittedField = explode('-', $field);
                $criteria['fields'][$key] = $splittedField[ (count($splittedField) - 1) ];
            }

            // Fix relationship fields
            foreach ($criteria['fields'] as $key => $field) {
                if (! isset($criteria[$field][$key])) {
                    foreach ($criteria[$field] as $subKey => $subValues) {
                        if ($subKey > $key) {
                            $criteria[$field][$key] = $criteria[$field][$subKey];
                            unset($criteria[$field][$subKey]);
                            break;
                        }
                    }
                }
            }

            // Remove unnecessary criteria
            foreach ($criteria as $fieldId => $values) {
                if (is_numeric($fieldId) && ! in_array($fieldId, $criteria['fields'])) {
                    unset($criteria[$fieldId]);
                }
            }
        }

        // Export attributes
        $export->map = $mapping;
        $export->criteria = $criteria;

        // Save export
        if (craft()->amForms_exports->saveExport($export)) {
            craft()->userSession->setNotice(Craft::t('Export saved.'));

            $this->redirectToPostedUrl($export);
        }
        else {
            craft()->userSession->setError(Craft::t('Couldn’t save export.'));

            // Send the export back to the template
            craft()->urlManager->setRouteVariables(array(
                'export' => $export
            ));
        }
    }

    /**
     * Delete an export.
     */
    public function actionDeleteExport()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        $id = craft()->request->getRequiredPost('id');

        $result = craft()->amForms_exports->deleteExportById($id);
        $this->returnJson(array('success' => $result));
    }

    /**
     * Restart an export.
     */
    public function actionRestartExport()
    {
        // Find export ID
        $exportId = craft()->request->getParam('id');
        if (! $exportId) {
            $this->redirect('amforms/exports');
        }

        // Get the export
        $export = craft()->amForms_exports->getExportById($exportId);
        if (! $export) {
            throw new Exception(Craft::t('No export exists with the ID “{id}”.', array('id' => $exportId)));
        }

        // Restart export
        craft()->amForms_exports->restartExport($export);

        // Redirect
        $this->redirect('amforms/exports');
    }

    /**
     * Download an export.
     */
    public function actionDownloadExport()
    {
        // Find export ID
        $exportId = craft()->request->getParam('id');
        if (! $exportId) {
            $this->redirect('amforms/exports');
        }

        // Get the export
        $export = craft()->amForms_exports->getExportById($exportId);
        if (! $export) {
            throw new Exception(Craft::t('No export exists with the ID “{id}”.', array('id' => $exportId)));
        }

        // Is the export finished and do we have a file?
        if (! $export->finished || ! IOHelper::fileExists($export->file)) {
            $this->redirect('amforms/exports');
        }

        // Download file!
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($export->file));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($export->file));
        readfile($export->file);
        die();
    }

    /**
     * Get a criteria row.
     */
    public function actionGetCriteria()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        $return = array(
            'success' => false
        );

        // Get required POST data
        $formId = craft()->request->getRequiredPost('formId');
        $counter = craft()->request->getRequiredPost('counter');

        // Get the form
        $form = craft()->amForms_forms->getFormById($formId);

        if ($form) {
            $fields = array();

            // Get form fields
            foreach ($form->getFieldLayout()->getTabs() as $tab) {
                foreach ($tab->getFields() as $layoutField) {
                    $fields[] = $layoutField->getField();
                }
            }

            // Get HTML
            $variables = array(
                'form' => $form,
                'fields' => $fields,
                'criteriaCounter' => $counter
            );
            $html = craft()->templates->render('amForms/exports/_fields/template', $variables, true);

            $return = array(
                'success' => true,
                'row' => $html,
                'headHtml' => craft()->templates->getHeadHtml(),
                'footHtml' => craft()->templates->getFootHtml()
            );
        }

        $this->returnJson($return);
    }
}
