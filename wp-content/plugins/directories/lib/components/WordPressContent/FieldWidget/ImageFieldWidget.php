<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldWidget;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class ImageFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Image upload field', 'directories'),
            'field_types' => array('wp_image'),
            'accept_multiple' => true,
            'default_settings' => array(
                'max_file_size' => 2048,
            ),
        );
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return array(
            'max_file_size' => array(
                '#type' => 'textfield',
                '#title' => __('Maximum file size', 'directories'),
                '#description' => __('The maximum file size of uploaded files in kilobytes. Leave this field blank for no limit.', 'directories'),
                '#size' => 7,
                '#integer' => true,
                '#field_suffix' => 'KB',
                '#default_value' => $settings['max_file_size'],
                '#weight' => 2,
            ),
        );
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        return array(
            '#type' => current_user_can('upload_files') ? 'wp_media_manager' : 'wp_upload',
            '#max_file_size' => $settings['max_file_size'],
            '#multiple' => $field->getFieldMaxNumItems() !== 1,
            '#allow_only_images' => true,
            '#default_value' => $value,
            '#max_num_files' => $field->getFieldMaxNumItems(),
            '#sortable' => true,
        );
    }
}