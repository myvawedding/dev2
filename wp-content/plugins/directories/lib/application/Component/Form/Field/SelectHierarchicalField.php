<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class SelectHierarchicalField extends FieldsetField
{
    protected static $_count = 0;
    
    public function formFieldInit($name, array &$data, Form $form)
    {
        if (isset($data['#default_value'])) {
            $data['#default_value'] = (array)$data['#default_value'];
        }
        $data += array(
            '#group' => true,
        );
        if (!isset($data['#id'])) {
            $data['#id'] = 'drts-form-type-selecthierarchical-' . ++self::$_count;
        }
        $data['#children'][0][0] = array(
            '#type' => 'select',
            '#weight' => 0,
            '#class' => 'drts-form-field-select-0',
            '#options' => $data['#options'],
            '#multiple' => false,
            '#attributes' => array(
                'data-default-value' => isset($data['#default_value'][0]) ? $data['#default_value'][0] : '',
                'class' => 'drts-form-selecthierarchical',
            ),
            '#default_value' => isset($data['#default_value'][0]) ? $data['#default_value'][0] : null,
        );
        if (!isset($data['#max_depth'])) {
            $data['#max_depth'] = 5;
        }
        for ($i = 1; $i <= $data['#max_depth']; $i++) {
            $data['#children'][0][$i] = array(
                '#type' => 'select',
                '#multiple' => false,
                '#hidden' => true,
                '#class' => 'drts-form-field-select-' . $i,
                '#attributes' => array(
                    'data-load-url' => $data['#load_options_url'],
                    'data-options-prefix' => isset($data['#load_options_prefix']) && strlen($data['#load_options_prefix']) ? str_repeat($data['#load_options_prefix'], $i) . ' ' : '',
                    'data-default-value' => isset($data['#default_value'][$i]) ? $data['#default_value'][$i] : '',
                    'class' => 'drts-form-selecthierarchical',
                ),
                '#states' => array(
                    'load_options' => array(
                        sprintf('#%s .drts-form-field-select-%d select', $data['#id'], $i - 1) => array('type' => 'selected', 'value' => true),
                    ),
                ),
                '#options' => array('' => isset($data['#options']['']) ? $data['#options'][''] : ''),
                '#states_selector' => '#' . $data['#id'] . ' .drts-form-field-select-' . $i,
                '#skip_validate_option' => true,
                '#weight' => $i,
                '#default_value' => isset($data['#default_value'][$i]) ? $data['#default_value'][$i] : null,
            );
        }
        
        $form->settings['#pre_render'][__CLASS__] = array($this, 'preRenderCallback');
        
        parent::formFieldInit($name, $data, $form);
    }
    
    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        parent::formFieldSubmit($value, $data, $form);
        
        while (null !== $_value = array_pop($value)) {
            if ($_value !== '') {
                $value = $_value;
                return;
            }
        }
        $value = null;
    }
    
    public function preRenderCallback(Form $form)
    {        
        $this->_application->getPlatform()->addJsFile('form-field-selecthierarchical.min.js', 'drts-form-field-selecthierarchical', array('drts-form'));
    }
}