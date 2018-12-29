<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldWidget;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class EditorFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('WordPress editor', 'directories'),
            'field_types' => array('text'),
            'default_settings' => array(
                'rows' => get_option('default_post_edit_rows', 5),
                'no_tinymce' => false,
                'no_quicktags' => false,
            ),
        );
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return array(
            'no_tinymce' => array(
                '#type' => 'checkbox',
                '#title' => __('Disable Visual mode', 'directories'),
                '#default_value' => $settings['no_tinymce'],
            ),
            'no_quicktags' => array(
                '#type' => 'checkbox',
                '#title' => __('Disable toolbar in Text mode', 'directories'),
                '#default_value' => $settings['no_quicktags'],
            ),
            'rows' => array(
                '#type' => 'slider',
                '#min_value' => 1,
                '#max_value' => 50,
                '#integer' => true,
                '#title' => __('Rows', 'directories'),
                '#default_value' => $settings['rows'],
            ),
        );
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        $min_length = $max_length = null;
        $field_settings = $field->getFieldSettings();
        if (isset($settings['min_length'])) {
            $min_length = $settings['min_length'];
        } elseif (isset($field_settings['min_length'])) {
            $min_length = $field_settings['min_length'];
        }
        if (isset($settings['max_length'])) {
            $max_length = $settings['max_length'];
        } elseif (isset($field_settings['max_length'])) {
            $max_length = $field_settings['max_length'];
        }

        return [
            '#type' => 'wp_editor',
            '#default_value' => isset($value) ? (is_array($value) ? $value['value'] : $value) : null,
            '#rows' => $settings['rows'],
            '#no_tinymce' => !empty($settings['no_tinymce']),
            '#no_quicktags' => !empty($settings['no_quicktags']),
            '#min_length' => $min_length,
            '#max_length' => $max_length,
        ];
    }
}
