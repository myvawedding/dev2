<?php
namespace SabaiApps\Directories\Component\Field\Widget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class CheckboxWidget extends AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Single checkbox', 'directories'),
            'field_types' => array('boolean'),
            'default_settings' => array(
                'checkbox_label' => null,
                'checked' => false,
            ),
        );
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return array(
            'checkbox_label' => array(
                '#type' => 'textfield',
                '#title' => __('Checkbox label', 'directories'),
                '#description' => __('Enter the label displayed next to the checkbox.', 'directories'),
                '#default_value' => $settings['checkbox_label'],
            ),
            'checked' => array(
                '#type' => 'checkbox',
                '#title' => __('Make this field checked by default', 'directories'),
                '#default_value' => !empty($settings['checked']),
            ),
        );
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        return array(
            '#type' => 'checkbox',
            '#on_value' => 1,
            '#off_value' => 0,
            '#on_label' => $settings['checkbox_label'],
            '#default_value' => isset($value) ? $value : !empty($settings['checked']),
            '#switch' => false,
            '#option_no_escape' => true,
        );
    }
}