<?php
namespace SabaiApps\Directories\Component\Field\Widget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class RadioButtonsWidget extends AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Radio buttons', 'directories'),
            'field_types' => array('choice'),
            'default_settings' => array(
                'inline' => false,
            ),
        );
    }
    
    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return array(
            'inline' => array(
                '#type' => 'checkbox',
                '#title' => __('Display inline', 'directories'),
                '#default_value' => !empty($settings['inline']),
            ),
            'columns'  => array(
                '#type' => 'select',
                '#title' => __('Number of columns', 'directories'),
                '#options' => array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 6 => 6, 12 => 12),
                '#default_value' => $settings['columns'],
                '#states' => array(
                    'visible' => array(
                        sprintf('input[name="%s[inline]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => false),
                    ),
                ),
            ),
        );
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        $options = $this->_application->Field_ChoiceOptions($field, $language);
        $default_value = isset($value) ? $value : (empty($options['default']) ? null : array_shift($options['default'])); 
        return array(
            '#type' => 'radios',
            '#options' => $options['options'],
            '#default_value' => $default_value,
            '#inline' => $settings['inline'],
            '#columns' => $settings['columns'],
        );
    }
}