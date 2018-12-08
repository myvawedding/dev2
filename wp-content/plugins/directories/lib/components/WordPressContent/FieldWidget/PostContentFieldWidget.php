<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldWidget;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class PostContentFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('WordPress editor', 'directories'),
            'field_types' => array('wp_post_content'),
            'default_settings' => array(
                'rows' => 10,
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
        return array(
            '#type' => 'wp_editor',
            '#default_value' => $value,
            '#rows' => $settings['rows'],
            '#no_tinymce' => !empty($settings['no_tinymce']),
            '#no_quicktags' => !empty($settings['no_quicktags']),
        );
    }
}
