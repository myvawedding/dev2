<?php
namespace SabaiApps\Directories\Component\Field\Renderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class ImageRenderer extends AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        return array(
            'field_types' => array('wp_image', 'file_image'),
            'default_settings' => array(
                'size' => 'thumbnail',
                'width' => 100,
                'height' => 0,
                'cols' => 4,
                'link' => 'photo',
                'link_image_size' => 'large',
            ),
            'separatable' => false,
            'no_imageable' => true,
        );
    }

    protected function _fieldRendererSettingsForm(IField $field, array $settings, array $parents = [])
    {
        $form = array(
            'size' => array(
                '#title' => __('Image size', 'directories'),
                '#type' => 'select',
                '#options' => $this->_getImageSizeOptions(),
                '#default_value' => $settings['size'],
                '#weight' => 1,
            ),
            'width' => array(
                '#title' => __('Image width', 'directories'),
                '#type' => 'slider',
                '#min_value' => 1,
                '#max_value' => 100,
                '#integer' => true,
                '#default_value' => $settings['width'],
                '#weight' => 2,
                '#field_suffix' => '%',
                '#states' => array(
                    'invisible' => array(
                        sprintf('[name="%s[_render_background]"]', $this->_application->Form_FieldName($parents)) => ['type' => 'checked', 'value' => true],
                    ),
                ),
            ),
            'height' => array(
                '#title' => __('Image height', 'directories'),
                '#type' => 'slider',
                '#min_value' => 0,
                '#min_text' => __('Auto', 'directories'),
                '#max_value' => 100,
                '#integer' => true,
                '#default_value' => $settings['height'],
                '#weight' => 2,
                '#field_suffix' => 'px',
            ),
            'link' => array(
                '#type' => 'select',
                '#title' => __('Link image to', 'directories'),
                '#options' => $this->_getImageLinkTypeOptions(),
                '#inline' => true,
                '#default_value' => $settings['link'],
                '#weight' => 5,
            ),
            'link_image_size' => array(
                '#title' => __('Linked image size', 'directories'),
                '#type' => 'select',
                '#options' => $this->_getLinkedImageSizeOptions(),
                '#inline' => true,
                '#default_value' => $settings['link_image_size'],
                '#states' => array(
                    'visible' => array(
                        sprintf('select[name="%s[link]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'photo'),
                    ),
                ),
                '#weight' => 6,
            ),
        );           
        if ($field->getFieldMaxNumItems() !== 1) {
            $form['cols'] = array(
                '#title' => __('Number of columns', 'directories'),
                '#type' => 'select',
                '#options' => array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 6 => 6, 12 => 12),
                '#default_value' => $settings['cols'],
                '#weight' => 3,
            );
        }
        return $form;
    }
    
    protected function _getImageLinkTypeOptions()
    {
        return [
            'none' => __('Do not link', 'directories'),
            'page' => __('Link to page', 'directories'),
            'photo' => __('Single image', 'directories'),
        ];
    }
    
    protected function _getLinkedImageSizeOptions()
    {
        return [
            'medium' => __('Medium size', 'directories'),
            'large' => __('Large size', 'directories'),
            'full' => __('Original size', 'directories'),
        ];
    }

    protected function _fieldRendererRenderField(IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        $permalink_url = $settings['link'] === 'page' ? $this->_application->Entity_PermalinkUrl($entity) : null;
        $target = $this->_getLinkTarget($field, $settings);
        if (empty($values)
            || (!$field_type_impl = $this->_application->Field_Type($field->getFieldType(), true))
        ) {
            return $this->_getEmptyImage($settings, $permalink_url, $target);
        }        
        
        // Return image and link URLs only for rendering field as background image
        if (!empty($settings['_render_background'])) {
            return [
                'html' => $image_url = $this->_getImageUrl($field, $settings, $values[0], $settings['size']),
                'url' => $this->_getImageLinkUrl($field, $settings, $values[0], $permalink_url, $image_url),
                'target' => $target,
            ];
        }
        
        if ($field->getFieldMaxNumItems() !== 1) {
            $col_sm = $col = 12 / $settings['cols'];
            if ($col_sm < 6) {
                $col = 6;
            }
        } else {
            $col_sm = $col = 12;
        }
        if ($col_sm === 12 && count($values) === 1) {
            if (!$image = $this->_getImage($field, $settings, $values[0], $permalink_url, $target)) {
                return $this->_getEmptyImage($settings, $permalink_url, $target);
            }
            return $image;
        }
        
        unset($settings['_hover_zoom'], $settings['_hover_brighten']); // disable hover effects if multiple images
        $ret = array();
        foreach ($values as $value) {
            if (!$image = $this->_getImage($field, $settings, $value, $permalink_url, $target)) continue;
            $ret[] = sprintf(
                '<div class="%1$scol-sm-%2$d %1$scol-%3$d">%4$s</div>',
                DRTS_BS_PREFIX,
                $col_sm,
                $col,
                isset($image['url']) ? '<a href="' . $image['url'] . '">' . $image['html'] . '</a>' : $image['html']
            );
        }
        if (empty($ret)) {
            return $this->_getEmptyImage($settings, $permalink_url, $target);
        }

        return '<div class="' . DRTS_BS_PREFIX . 'row ' . DRTS_BS_PREFIX . 'no-gutters">' . implode(PHP_EOL, $ret) . '</div>';
    }

    protected function _getLinkTarget(IField $field, array $settings)
    {

    }

    protected function _getEmptyImage(array &$settings, $permalinkUrl, $target)
    {
        unset($settings['_hover_zoom'], $settings['_hover_brighten']); // disable hover effects
        $ret = [
            'url' => $permalinkUrl,
            'target' => $target,
        ];
        if (empty($settings['_render_background'])) {
            $ret['html'] = '<div class="drts-no-image">' . $this->_application->NoImage(false) . '</div>';
        }
        return $ret;
    }
    
    protected function _getImage(IField $field, array $settings, $value, $permalinkUrl, $target)
    {
        if (!$url = $this->_getImageUrl($field, $settings, $value, $settings['size'])) return '';

        return [
            'html' => sprintf(
                '<img src="%s" title="%s" alt="" style="width:%d%%;height:%s" />',
                $url,
                $this->_application->H($this->_getImageTitle($field, $settings, $value)),
                $settings['width'],
                empty($settings['height']) ? 'auto' : intval($settings['height']) . 'px'
            ),
            'url' => $this->_getImageLinkUrl($field, $settings, $value, $permalinkUrl, $url),
            'target' => $target,
        ];
    }

    protected function _getImageLinkUrl(IField $field, array $settings, $value, $permalinkUrl, $imageUrl)
    {
        if ($settings['link'] === 'page') return $permalinkUrl;

        if ($settings['link'] === 'photo') {
            if ($settings['size'] == $settings['link_image_size']) return $imageUrl;

            return $this->_getImageUrl($field, $settings, $value, $settings['link_image_size']);
        }
    }

    protected function _getImageUrl(IField $field, array $settings, $value, $size)
    {
        return $this->_application->Field_Type($field->getFieldType())->fieldImageGetUrl($value, $size);
    }

    protected function _getImageTitle(IField $field, array $settings, $value)
    {
        return $this->_application->Field_Type($field->getFieldType())->fieldImageGetTitle($value);
    }
    
    protected function _fieldRendererReadableSettings(IField $field, array $settings)
    {
        $ret = [
            'size' => [
                'label' => __('Image size', 'directories'),
                'value' => $this->_getImageSizeOptions()[$settings['size']],
            ],
            'width' => [
                'label' => __('Image width', 'directories'),
                'value' => $settings['width'] . '%',
            ],
            'height' => [
                'label' => __('Image height', 'directories'),
                'value' => empty($settings['height']) ? 'auto' : $settings['height'] . 'px',
            ],
            'link' => [
                'label' => __('Link image to', 'directories'),
                'value' => $this->_getImageLinkTypeOptions()[$settings['link']],
            ],
        ];
        if ($settings['link'] === 'photo') {
            $ret['link_image_size'] = [
                'label' => __('Linked image size', 'directories'),
                'value' => $this->_getLinkedImageSizeOptions()[$settings['link_image_size']],
            ];
        }          
        if ($field->getFieldMaxNumItems() !== 1) {
            $ret['cols'] = [
                'label' => __('Number of columns', 'directories'),
                'value' => $settings['cols'],
            ];
        }
        
        return $ret;
    }
}
