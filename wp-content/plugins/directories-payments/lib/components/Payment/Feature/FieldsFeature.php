<?php
namespace SabaiApps\Directories\Component\Payment\Feature;

use SabaiApps\Directories\Component\Entity;

class FieldsFeature extends AbstractFeature
{    
    protected function _paymentFeatureInfo()
    {
        return array(
            'label' => __('Field Settings', 'directories-payments'),
            'weight' => 3,
            'default_settings' => array(
                'all' => true,
                'fields' => null,
                'fields_disabled' => [],
            ),
        );
    }
    
    public function paymentFeatureSettingsForm(Entity\Model\Bundle $bundle, array $settings, $planType = 'base', array $parents = [])
    {
        $fields = $options = [];
        foreach ($this->_application->Entity_Field($bundle->name) as $field_name => $field) {
            if (!$field->getFieldWidget()
                || $field->getFieldData('_no_ui')
                || $field->getFieldData('disabled')
                || (!$field_type = $this->_application->Field_Type($field->getFieldType(), true))
                || $field_type->fieldTypeInfo('admin_only')
                || (!$field->isCustomField() && !$field_type->fieldTypeInfo('disablable'))
            ) continue;

            $weight = $field->getFieldData('weight');
            if (!isset($fields[$weight])) $fields[$weight] = [];
            $fields[$weight][$field_name] = sprintf('%s (%s)', $field, $field_type->fieldTypeInfo('label'));
        }
        ksort($fields);
        foreach ($fields as $_fields) {
            $options += $_fields;
        }
        
        if (empty($options)) return;
        
        if (empty($settings['fields_disabled'])) {
            $values = array_keys($options);
        } else {
            $values = $options;
            foreach ($settings['fields_disabled'] as $field_name) {
                unset($values[$field_name]);
            }
            $values = array_keys($values);
        }
        
        return array( 
            '#element_validate' => array(
                array(array($this, 'submitSettings'), array($options))
            ),
            'all' => array(
                '#title' => __('Allowed fields', 'directories-payments'),
                '#on_label' => __('All fields', 'directories-payments'),
                '#type' => 'checkbox',
                '#switch' => false,
                '#default_value' => !empty($settings['all']),
                '#horizontal' => true,
            ),
            'fields' => array(
                '#type' => 'checkboxes',
                '#options' => $options,
                '#default_value' => $values,
                '#horizontal' => true,
                '#columns' => 2,
                '#states' => array(
                    'invisible' => array(
                        sprintf('input[name="%s[all][]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => true),
                    ),                       
                ),
            ),
        );
    }
    
    public function submitSettings($form, &$value, $element, $options)
    {
        $value['fields_disabled'] = [];
        if (empty($value['all'])) {
            foreach (array_keys($options) as $field_name) {
                if (!in_array($field_name, $value['fields'])) {
                    $value['fields_disabled'][] = $field_name;
                }
            }
        }
    }

    public function paymentFeatureOnEntityForm(Entity\Model\Bundle $bundle, array $settings, array &$form, Entity\Type\IEntity $entity = null, $isAdmin = false, $isEdit = false)
    {
        if ($isAdmin && $this->_application->IsAdministrator()) return; // do not restrict for administrators
        
        if (!empty($settings[0]['all']) || empty($settings[0]['fields_disabled'])) return;
        
        foreach (array_keys($form) as $field_name) {
            if (in_array($field_name, $settings[0]['fields_disabled'])) {
                unset($form[$field_name]);
            }
        }
    }
}