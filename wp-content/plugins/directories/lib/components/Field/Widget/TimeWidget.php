<?php
namespace SabaiApps\Directories\Component\Field\Widget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class TimeWidget extends AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Time picker', 'directories'),
            'field_types' => array('time'),
            'default_settings' => array(
                'current_time_selected' => false,
            ),
            'repeatable' => true,
        );
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return array(
            'current_time_selected' => array(
                '#type' => 'checkbox',
                '#title' => __('Set current time selected by default', 'directories'),
                '#default_value' => !empty($settings['current_time_selected']),
            ),
        );
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        $field_settings = $field->getFieldSettings();
        return array(
            '#type' => 'timepicker',
            '#current_time_selected' => !empty($settings['current_time_selected']),
            '#default_value' => $value,
            '#disable_day' => empty($field_settings['enable_day']),
            '#disable_end' => empty($field_settings['enable_end']),
            '#start_of_week' => $this->_application->getPlatform()->getStartOfWeek(),
        );
    }
}
