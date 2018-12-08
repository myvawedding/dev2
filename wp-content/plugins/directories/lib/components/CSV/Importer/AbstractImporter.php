<?php
namespace SabaiApps\Directories\Component\CSV\Importer;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Form;

abstract class AbstractImporter implements IImporter
{
    protected $_application, $_name, $_info;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }

    public function csvImporterInfo($key = null)
    {
        if (!isset($this->_info)) {
            $this->_info = (array)$this->_csvImporterInfo();
        }

        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }
    
    public function csvImporterSettingsForm(Entity\Model\Field $field, array $settings, $column, $enclosure, array $parents = []){}
    
    public function csvImporterDoImport(Entity\Model\Field $field, array $settings, $column, $value, &$formStorage)
    {
        return array(array($column => $value));
    }
    
    public function csvImporterSupports(Entity\Model\Bundle $bundle, Entity\Model\Field $field)
    {
        return true;
    }

    public function csvImporterOnComplete(Entity\Model\Field $field, array $settings, $column, &$formStorage){}

    protected function _csvImporterInfo()
    {
        return array(
            'field_types' => array($this->_name),
        );
    }
    
    protected function _acceptMultipleValues(Entity\Model\Field $field, $enclosure, array $parents, array $reserved = [], $defaultSeparator = ';')
    {
        return array(
            '_multiple' => array(
                '#type' => 'checkbox',
                '#title' => __('Column may contain multiple values', 'directories'),
                '#description' => __('Check this option if the CSV column may contain multiple values to be imported. Make sure the field associated accepts multiple values.'),
                '#default_value' => $field->getFieldMaxNumItems() !== 1,
                '#weight' => 100,
            ),
            '_separator' => array(
                '#type' => 'textfield',
                '#title' => __('Column value separator', 'directories'),
                '#size' => 5,
                '#description' => __('Enter the character used to separate multiple values in the column.', 'directories'),
                '#min_length' => 1,
                '#default_value' => $defaultSeparator,
                '#required' => function($form) use ($parents) { return $form->getValue(array_merge($parents, array('_multiple'))) ? true : false;},
                '#element_validate' => array(array(array($this, '_validateSeparator'), array($enclosure, $parents, $reserved))),
                '#states' => array(
                    'visible' => array(
                        sprintf('input[name="%s[_multiple]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => true), 
                    ),
                ),
                '#weight' => 101,
            ),
        );
    }
    
    public function _validateSeparator(Form\Form $form, &$value, $element, $enclosure, array $parents, array $reserved)
    {
        $form_values = $form->getValue($parents);
        if (empty($form_values['_multiple'])) return;
        
        $value = trim($value);
        if ($value == $enclosure) {
            $form->setError(sprintf(__('Column value separator may not be the same as %s.', 'directories'), __('CSV file field enclosure', 'directories')), $element);
        }
        if (!empty($reserved)) {
            foreach ($reserved as $field_name => $field_label) {
                if (isset($form_values[$field_name])
                    && $value == $form_values[$field_name]
                ) {
                    $form->setError(sprintf(__('Column value separator may not be the same as %s.', 'directories'), $field_label), $element);
                }
            }
        }
    }
    
    protected function _getDateFormatSettingsForm()
    {
        return array(
            'date_format' => array(
                '#type' => 'select',
                '#title' => __('Date and time format', 'directories'),
                '#description' => __('Select the format used to represent date and time values in CSV.', 'directories'),
                '#options' => array(
                    'timestamp' => __('Timestamp', 'directories'),
                    'string' => __('Formatted date/time string', 'directories'),
                ),
                '#default_value' => 'timestamp',
            ),
        );
    }
    
    protected function _getUserSettingsForm()
    {
        return array(
            'id_format' => array(
                '#type' => 'select',
                '#title' => __('User identification value format', 'directories'),
                '#description' => __('Select the format used to represent user identification values in CSV.', 'directories'),
                '#options' => array(
                    'id' => __('User ID', 'directories'),
                    'username' => __('Username', 'directories'),
                ),
                '#default_value' => 'username',
            ),
        );
    }

    protected function _addWpAllImportSeparatorSettingsField(\RapidAddon $addon, Entity\Model\Field $field, $column = null, $defaultSeparator = ';')
    {
        $addon->add_field(
            $field->getFieldName() . '-' . $column . '-separator',
            __('Column value separator', 'directories'),
            'text',
            null,
            '',
            true,
            $defaultSeparator
        );
        $addon->add_text(__('Enter the character used to separate multiple values in the column.', 'directories'));
    }

    protected function _addWpAllImportDateFormatSettingsField(\RapidAddon $addon, Entity\Model\Field $field, $column = null)
    {
        $addon->add_field(
            $field->getFieldName() . '-' . $column . '-date_format',
            __('Date and time format', 'directories'),
            'radio',
            [
                'timestamp' => __('Timestamp', 'directories'),
                'string' => __('Formatted date/time string', 'directories'),
            ]
        );
        $addon->add_text(__('Select the format used to represent date and time values in CSV.', 'directories'));
    }
}