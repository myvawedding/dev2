<?php
namespace SabaiApps\Directories\Component\Field\Widget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class DateWidget extends AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Date picker', 'directories'),
            'field_types' => array($this->_name),
            'default_settings' => array(
                'current_date_selected' => false,
            ),
            'repeatable' => true,
        );
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return [
            'current_date_selected' => [
                '#type' => 'checkbox',
                '#title' => __('Set current date selected by default', 'directories'),
                '#default_value' => !empty($settings['current_date_selected']),
            ],
        ];
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        $field_settings = $field->getFieldSettings();
        return array(
            '#type' => 'datepicker',
            '#current_date_selected' => !empty($settings['current_date_selected']),
            '#min_date' => !empty($field_settings['date_range_enable']) ? @$field_settings['date_range'][0] : null,
            '#max_date' => !empty($field_settings['date_range_enable']) ? @$field_settings['date_range'][1] : null,
            '#disable_time' => empty($field_settings['enable_time']),
            '#default_value' => $value,
        );
    }
}
