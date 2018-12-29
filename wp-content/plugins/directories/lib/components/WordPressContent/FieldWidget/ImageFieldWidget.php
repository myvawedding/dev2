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
        );
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        $field_settings = $field->getFieldSettings();
        return array(
            '#type' => current_user_can('upload_files') ? 'wp_media_manager' : 'wp_upload',
            '#max_file_size' => !empty($field_settings['max_file_size']) ? $field_settings['max_file_size'] : null,
            '#multiple' => $field->getFieldMaxNumItems() !== 1,
            '#allow_only_images' => true,
            '#default_value' => $value,
            '#max_num_files' => $field->getFieldMaxNumItems(),
            '#sortable' => true,
        );
    }
}