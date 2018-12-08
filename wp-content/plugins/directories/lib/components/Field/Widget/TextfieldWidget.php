<?php
namespace SabaiApps\Directories\Component\Field\Widget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class TextfieldWidget extends AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Text input field', 'directories'),
            'field_types' => array('string', 'number'),
            'default_settings' => array(
                'autopopulate' => '',
                'field_prefix' => null,
                'field_suffix' => null,
                'mask' => null,
            ),
            'repeatable' => true,
        );
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        $form = [];
        if ($fieldType === 'string') {
            $form += array(
                'autopopulate' => array(
                    '#type' => 'select',
                    '#title' => __('Auto-populate field', 'directories'),
                    '#options' => array(
                        '' => __('Do not auto-populate', 'directories'),
                        'email' => __('E-mail address of current user', 'directories'),
                        'url' => __('Website URL of current user', 'directories'),
                        'username' => __('User name of current user', 'directories'),
                        'name' => __('Display name of current user', 'directories'),
                    ),
                    '#default_value' => $settings['autopopulate'],
                ),
                'field_prefix' => array(
                    '#type' => 'textfield',
                    '#title' => __('Field prefix', 'directories'),
                    '#description' => __('Example: $, #, -', 'directories'),
                    '#size' => 20,
                    '#default_value' => $settings['field_prefix'],
                    '#no_trim' => true,
                ),
                'field_suffix' => array(
                    '#type' => 'textfield',
                    '#title' => __('Field suffix', 'directories'),
                    '#description' => __('Example: km, %, g', 'directories'),
                    '#size' => 20,
                    '#default_value' => $settings['field_suffix'],
                    '#no_trim' => true,
                ),
            );
        }
        if ($fieldType !== 'number') {
            $form['mask'] = array(
                '#type' => 'textfield',
                '#title' => __('Input mask', 'directories'),
                '#description' => __('Use "a" to mask letter inputs (A-Z,a-z), "9" for numbers (0-9) and "*" for both.', 'directories'),
                '#default_value' => $settings['mask'],
                '#placeholder' => '(999) 999-9999',
                '#size' => 20,
            );
        }
        
        return $form;
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        $form = array(
            '#type' => $field->getFieldType(),
            '#default_value' => isset($value) ? $value : null,
        );
        $field_settings = $field->getFieldSettings();
        switch ($field->getFieldType()) {
            case 'number':
                $form['#field_prefix'] = isset($field_settings['prefix']) && strlen($field_settings['prefix']) ? $field_settings['prefix'] : null;
                $form['#field_suffix'] = isset($field_settings['suffix']) && strlen($field_settings['suffix']) ? $field_settings['suffix'] : null;
                if ($field_settings['decimals'] > 0) {
                    $form['#numeric'] = true;
                    $form['#min_value'] = isset($field_settings['min']) && is_numeric($field_settings['min']) ? $field_settings['min'] : null;
                    $form['#max_value'] = isset($field_settings['max']) && is_numeric($field_settings['max']) ? $field_settings['max'] : null;
                    $form['#step'] = $field_settings['decimals'] == 1 ? 0.1 : 0.01;
                } else {
                    $form['#integer'] = true;
                    $form['#min_value'] = isset($field_settings['min']) ? intval($field_settings['min']) : null;
                    $form['#max_value'] = isset($field_settings['max']) ? intval($field_settings['max']) : null;
                }
                if (!isset($form['#size'])) {
                    $form['#size'] = 20;
                }
                break;
            default:
                $form['#min_length'] = isset($field_settings['min_length']) ? $field_settings['min_length'] : null;
                $form['#max_length'] = isset($field_settings['max_length']) ? $field_settings['max_length'] : null;
                $form['#char_validation'] = isset($field_settings['char_validation']) ? $field_settings['char_validation'] : 'none';
                $form['#regex'] = isset($field_settings['regex']) ? $field_settings['regex'] : null;
                $form['#field_prefix'] = isset($settings['field_prefix']) && strlen($settings['field_prefix']) ? $settings['field_prefix'] : null;
                $form['#field_suffix'] = isset($settings['field_suffix']) && strlen($settings['field_suffix']) ? $settings['field_suffix'] : null;
                if ($form['#char_validation'] === 'email') {
                    $form['#type'] = 'email';   
                } elseif ($form['#char_validation'] === 'url') {
                    $form['#type'] = 'url';   
                } else {
                    $form['#type'] = 'textfield'; 
                }
                $form['#mask'] = $settings['mask'];
                $form['#auto_populate'] = $settings['autopopulate'];
        }

        return $form;
    }

    public function fieldWidgetEditDefaultValueForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        $ret = array(
            '#type' => 'textfield',
        );
        if (($fieldType instanceof \SabaiApps\Directories\Component\Entity\Model\Field && $fieldType->getFieldType() === 'number')
            || $fieldType === 'number'
        ) {
            $ret['#numeric'] = true;
        }
        return $ret;
    }
    
    public function fieldWidgetSetDefaultValue(IField $field, array $settings, array &$form, $value)
    {
        if (strlen($value)) {
            $form['#default_value'] = $value;
        }
    }
}