<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldFilter;

use SabaiApps\Directories\Component\Field;

class ImageFieldFilter extends Field\Filter\BooleanFilter
{
    protected $_filterColumn = null, $_nullOnly = true;
    
    protected function _fieldFilterInfo()
    {
        return array(
            'label' => _x('Image', 'field type', 'directories'),
            'field_types' => array($this->_name),
            'default_settings' => array(
                'type' => 'checkbox',
                'inline' => true,
                'labels' => array(
                    'on' => __('Has image', 'directories'),
                    'off' => __('No image', 'directories'),
                    'any' => _x('Any', 'option', 'directories'),
                ),
                'checkbox_label' => __('Show with image only', 'directories'),
            ),
            'facetable' => true,
        );
    }
}