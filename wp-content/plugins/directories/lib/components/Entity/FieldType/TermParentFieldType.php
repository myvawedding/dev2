<?php
namespace SabaiApps\Directories\Component\Entity\FieldType;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class TermParentFieldType extends Field\Type\AbstractType implements Field\Type\IQueryable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Parent Term', 'directories'),
            'entity_types' => array('term'),
            'default_widget' => 'entity_term_parent',
            'creatable' => false,
            'icon' => 'fas fa-sitemap',
        );
    }
    
    public function fieldQueryableInfo(Field\IField $field)
    {
        return array(
            'example' => '',
            'tip' => __('Enter a parent ID.', 'directories'),
        );
    }
    
    public function fieldQueryableQuery(Field\Query $query, $fieldName, $paramStr, Entity\Model\Bundle $bundle = null)
    {
        $query->fieldIs($fieldName, trim($paramStr));
    }
}