<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldRenderer;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class GalleryFieldRenderer extends Field\Renderer\AbstractRenderer
{   
    protected function _fieldRendererInfo()
    {
        return array(
            'label' => _x('Gallery', 'field renderer', 'directories'),
            'field_types' => array('wp_image'),
            'default_settings' => array(
                'cols' => 4,
                'size' => 'thumbnail',
            ),
            'separatable' => false,
        );
    }

    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        return array(
            'cols' => array(
                '#title' => __('Number of columns', 'directories'),
                '#type' => 'select',
                '#options' => array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 6 => 6, 12 => 12),
                '#default_value' => $settings['cols'],
                '#inline' => true,
            ),
            'size' => array(
                '#title' => __('Image size', 'directories'),
                '#type' => 'select',
                '#options' => $this->_getImageSizeOptions(),
                '#inline' => true,
                '#default_value' => $settings['size'],
            ),
        );
    }

    protected function _fieldRendererRenderField(Field\IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        $attachment_ids = [];
        foreach ($values as $value) {
            $attachment_ids[] = $value['attachment_id'];
        }
        return do_shortcode(sprintf('[gallery columns="%d" size="%s" ids="%s"]', $settings['cols'], $settings['size'], implode(',', $attachment_ids)));
    }
    
    protected function _fieldRendererReadableSettings(Field\IField $field, array $settings)
    {
        return [
            'cols' => [
                'label' => __('Number of columns', 'directories'),
                'value' => $settings['cols'],
            ],
            'size' => [
                'label' => __('Image size', 'directories'),
                'value' => $this->_getImageSizeOptions()[$settings['size']],
            ],
        ];
    }
}