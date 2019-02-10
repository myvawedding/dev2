<?php
namespace SabaiApps\Directories\Component\Field\Filter;

class VideoFilter extends BooleanFilter
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
                    'on' => __('Has video', 'directories'),
                    'off' => __('No video', 'directories'),
                    'any' => _x('Any', 'option', 'directories'),
                ),
                'checkbox_label' => __('Show with video only', 'directories'),
            ),
            'facetable' => true,
        );
    }
}