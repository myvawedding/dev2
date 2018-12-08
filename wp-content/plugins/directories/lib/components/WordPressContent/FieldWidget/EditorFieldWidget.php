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
    
    public function isIframeUrlsRequired($form, $parents)
    {
        $values = $form->getValue($parents);
        return !empty($values['iframe']);
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        $field_settings = $field->getFieldSettings();
        return array(
            '#type' => 'wp_editor',
            '#default_value' => isset($value) ? (is_array($value) ? $value['value'] : $value) : null,
            '#rows' => $settings['rows'],
            '#min_length' => isset($field_settings['min']) ? intval($field_settings['min']) : null,
            '#max_length' => isset($field_settings['max']) ? intval($field_settings['max']) : null,
            '#no_tinymce' => !empty($settings['no_tinymce']),
            '#no_quicktags' => !empty($settings['no_quicktags']),
        );
    }
}
