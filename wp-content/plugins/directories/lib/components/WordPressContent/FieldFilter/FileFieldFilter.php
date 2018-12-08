<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldFilter;

use SabaiApps\Directories\Component\Field;

class FileFieldFilter extends Field\Filter\BooleanFilter
{
    protected $_filterColumn = null, $_nullOnly = true;
    
    protected function _fieldFilterInfo()
    {
        return array(
            'field_types' => array($this->_name),
            'default_settings' => array(
                'type' => 'checkbox',
                'inline' => true,
                'labels' => array(
                    'on' => __('Has File', 'directories'),
                    'off' => __('No File', 'directories'),
                    'any' => _x('Any', 'option', 'directories'),
                ),
                'checkbox_label' => __('Show with file only', 'directories'),
            ),
            'facetable' => true,
        );
    }
}