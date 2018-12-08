<?php
namespace SabaiApps\Directories\Component\Review\FieldWidget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class RatingFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Rating Stars', 'directories-reviews'),
            'field_types' => array($this->_name),
            'default_settings' => array('criteria' => [], 'step' => '0.5'),
            'accept_multiple' => true,
            'disable_edit_max_num_items' => true,
        );
    }
    
    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return array(
            'step' => array(
                '#type' => 'select',
                '#title' => __('Rating step', 'directories-reviews'),
                '#default_value' => $settings['step'],
                '#options' => array(
                    '0.5' => '0.5',
                    '1' => '1.0'
                ),
                '#option_no_escape' => true,
            ),
        );
    }
    
    protected function _getCriteria(array $settings)
    {
         if (empty($settings['criteria']['options'])
            || empty($settings['criteria']['default'])
        ) return;
        
        $options = $settings['criteria']['options'];
        foreach (array_keys($options) as $option) {
            if (!in_array($option, $settings['criteria']['default'])) {
                unset($options[$option]);
            }
        }
        return $options;
    }
    
    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        $form = [];
        foreach ($this->_application->Review_Criteria($field->Bundle) as $slug => $label) {
            if (isset($value[0][$slug]['value'])) {
                $_value = $value[0][$slug]['value'];
            } else {
                if (isset($value[0]['_all']['value'])) { // use value from overall rating
                    $_value = $value[0]['_all']['value'];
                } else {
                    $_value = null;
                }
            }
            if (isset($_value)) {
                $_value = $settings['step'] == 1 ? round($_value) : $_value;
            }
            $form[$slug] = array(
                '#type' => 'slider',
                '#title' => $label,
                '#min_value' => 0,
                '#max_value' => 5,
                '#slider_values' => range(0, 5, $settings['step']),
                '#step' => $settings['step'],
                // Specify position in values array when using #slider_values
                '#default_value' => empty($_value) ? 0 : $_value / $settings['step'], 
            );
        }
        
        // Hide rating option label if single criteria
        if (count($form) === 1) {
            unset($form[$slug]['#title']);
        }
        
        return $form;
    }
}