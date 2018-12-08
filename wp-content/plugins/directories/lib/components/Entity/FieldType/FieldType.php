<?php
namespace SabaiApps\Directories\Component\Entity\FieldType;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class FieldType extends Field\Type\AbstractType
{
    protected function _fieldTypeInfo()
    {
        switch ($this->_name) {
            case 'entity_bundle_name':
                return array(
                    'label' => 'Content Type',
                    'creatable' => false,
                );
            case 'entity_bundle_type':
                return array(
                    'label' => 'Content Type',
                    'creatable' => false,
                );
            case 'entity_slug':
                return array(
                    'label' => __('Slug', 'directories'),
                    'creatable' => false,
                );
        }
    }
}