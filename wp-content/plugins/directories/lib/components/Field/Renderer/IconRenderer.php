<?php
namespace SabaiApps\Directories\Component\Field\Renderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class IconRenderer extends AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        return array(
            'field_types' => array($this->_name),
            'default_settings' => array(
                'size' => '',
                'color' => [
                    'type' => '',
                    'custom' => null,
                ],
            ),
        );
    }

    protected function _fieldRendererSettingsForm(IField $field, array $settings, array $parents = [])
    {
        return [
            'size' => [
                '#type' => 'select',
                '#title' => __('Icon size', 'directories'),
                '#default_value' => $settings['size'],
                '#options' => $this->_application->System_Util_iconSizeOptions(),
            ],
            'color' => $this->_application->System_Util_iconColorSettingsForm(
                $field->Bundle,
                isset($settings['color']) && is_array($settings['color']) ? $settings['color'] : [],
                array_merge($parents, ['color'])
            ),
        ];
    }

    protected function _fieldRendererRenderField(IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        if ($settings['color']['type'] === '_custom') {
            $color = $settings['color']['custom'];
        } else {
            if ($settings['color']['type'] !== ''
                && ($_color = $entity->getSingleFieldValue($settings['color']['type']))
            ) {
                $color = $_color;
            }
        }
        $class = $values[0] . ' drts-icon';
        if ($settings['size']) {
            $class .= ' drts-icon-' . $settings['size'];
        }
        $style = empty($color) ? '' : 'style="background-color:' . $color . ';color:#fff;"';
        return '<i ' . $style . ' class="' . $this->_application->H($class) . '"></i>';
    }
    
    protected function _fieldRendererReadableSettings(IField $field, array $settings)
    {
        return [
            'size' => [
                'label' => __('Icon size', 'directories'),
                'value' => $this->_application->System_Util_iconSizeOptions()[$settings['size']],
            ],
        ];
    }
}