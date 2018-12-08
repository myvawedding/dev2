<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldRenderer;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class TextFieldRenderer extends Field\Renderer\TextRenderer
{
    protected function _fieldRendererInfo()
    {
        $ret = parent::_fieldRendererInfo();
        $ret['default_settings']['shortcode'] = false;
        return $ret;
    }

    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        $ret = parent::_fieldRendererSettingsForm($field, $settings, $parents);
        $ret['shortcode'] = [
            '#type' => 'checkbox',
            '#title' => __('Process shortcode(s)', 'directories'),
            '#default_value' => !empty($settings['shortcode']),
            '#states' => [
                'visible' => [
                    sprintf('input[name="%s[trim]"]', $this->_application->Form_FieldName($parents)) => ['type' => 'checked', 'value' => false],
                ],
            ],
        ];
        unset($ret['trim_marker'], $ret['trim_link']);
        return $ret;
    }

    protected function _getContent($value, array $settings, Entity\Type\IEntity $entity)
    {
        $value = is_array($value) ? $value['value'] : $value;
        if (empty($settings['shortcode'])) {
            $value = strip_shortcodes($value);
        }

        return parent::_getContent($value, $settings, $entity);
    }

    protected function _getTrimmedContent($value, $length, $marker, $link, array $settings, Entity\Type\IEntity $entity)
    {
        $value = is_array($value) ? $value['value'] : $value;
        // Add WordPress trim marker
        $marker = apply_filters('excerpt_more', ' ' . '[&hellip;]');

        return parent::_getTrimmedContent(strip_shortcodes($value), $length, $marker, $link, $settings, $entity);
    }
}
