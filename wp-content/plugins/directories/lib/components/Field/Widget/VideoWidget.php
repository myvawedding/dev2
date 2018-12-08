<?php
namespace SabaiApps\Directories\Component\Field\Widget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class VideoWidget extends AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Video field', 'directories'),
            'field_types' => array('video'),
            'default_settings' => [],
            'repeatable' => true,
        );
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        return array(
            '#type' => 'fieldset',
            '#row' => true,
            'provider' => array(
                '#type' => 'select',
                '#options' => [
                    'youtube' => 'YouTube', 
                    'vimeo' => 'Vimeo',
                ],
                '#default_value' => isset($value['provider']) ? $value['provider'] : 'youtube',
                '#col' => ['xs' => 6, 'md' => 3],
            ),
            'id' => array(
                '#type' => 'textfield',
                '#default_value' => isset($value['id']) ? $value['id'] : null,
                '#states' => [
                    'visible' => [
                        sprintf('select[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['provider']))) => ['type' => 'one', 'value' => ['youtube', 'vimeo']],
                    ],
                ],
                '#col' => ['xs' => 6, 'md' => 9],
                '#placeholder' => __('Enter video ID', 'directories'),
            ),
        );
    }
}