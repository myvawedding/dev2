<?php
namespace SabaiApps\Directories\Component\Field\Widget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class IconWidget extends AbstractWidget
{    
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Icon picker', 'directories'),
            'field_types' => array($this->_name),
        );
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        return array(
            '#type' => 'iconpicker',
            '#default_value' => isset($value) ? $value : null,
        );
    }
}