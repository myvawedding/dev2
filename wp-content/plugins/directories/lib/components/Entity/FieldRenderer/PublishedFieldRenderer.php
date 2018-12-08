<?php
namespace SabaiApps\Directories\Component\Entity\FieldRenderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class PublishedFieldRenderer extends Field\Renderer\AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        return array(
            'field_types' => array($this->_name),
            'default_settings' => array(
                'permalink' => true,
                'format' => 'date',
            ),
            'inlineable' => true,
        );
    }

    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        return array(
            'format' => array(
                '#type' => 'select',
                '#title' => __('Date/time format', 'directories'),
                '#options' => $this->_getDateTimeFormatOptions(),
                '#default_value' => $settings['format'],
            ),
            'permalink' => array(
                '#type' => 'checkbox',
                '#title' => __('Link to page', 'directories'),
                '#default_value' => !empty($settings['permalink']),
            ),
        );
    }

    protected function _getDateTimeFormatOptions()
    {
        return [
            'datetime' => __('Show date/time'),
            'date' => __('Show date'),
        ];
    }

    protected function _fieldRendererRenderField(Field\IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        $timestamp = $entity->getTimestamp();
        switch ($settings['format']) {
            case 'datetime':
                $html = $this->_application->System_Date_datetime($timestamp, true);
                break;
            case 'date':
            default:
                $html = $this->_application->System_Date($timestamp, true);
                break;
        }
        return $settings['permalink'] && ($url = $this->_application->Entity_PermalinkUrl($entity))
            ? '<a href="' . $url . '">' . $html . '</a>'
            : $html;
    }

    protected function _fieldRendererReadableSettings(Field\IField $field, array $settings)
    {
        return [
            'format' => [
                'label' => __('Date/time format', 'directories'),
                'value' => $this->_getDateTimeFormatOptions()[$settings['format']],
            ],
            'permalink' => [
                'label' => __('Link to page', 'directories'),
                'value' => !empty($settings['permalink']),
                'is_bool' => true,
            ],
        ];
    }
}
