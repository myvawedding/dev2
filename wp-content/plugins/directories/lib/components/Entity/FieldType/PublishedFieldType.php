<?php
namespace SabaiApps\Directories\Component\Entity\FieldType;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class PublishedFieldType extends Field\Type\AbstractType implements
    Field\Type\ISortable,
    Field\Type\ISchemable,
    Field\Type\IOpenGraph
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Publish Date', 'directories'),
            'creatable' => false,
            'icon' => 'far fa-clock',
        );
    }
    
    public function fieldSortableOptions(Field\IField $field)
    {
        return array(
            array('label' => __('Newest First', 'directories'), 'default' => true),
            array('args' => array('asc'), 'label' => __('Oldest First', 'directories'), 'default' => true),
        );
    }

    public function fieldSortableSort(Field\Query $query, $fieldName, array $args = null)
    {
        $query->sortByField('published', isset($args) && $args[0] === 'asc' ? 'ASC' : 'DESC');
    }
    
    public function fieldSchemaProperties()
    {
        return array('datePublished');
    }
    
    public function fieldSchemaRenderProperty(Field\IField $field, $property, Entity\Type\IEntity $entity)
    {
        return array(date('Y-m-d', $entity->getTimestamp()));
    }
    
    public function fieldOpenGraphProperties()
    {
        return array('article:published_time');
    }
    
    public function fieldOpenGraphRenderProperty(Field\IField $field, $property, Entity\Type\IEntity $entity)
    {
        return array(date('c', $entity->getTimestamp()));
    }
}