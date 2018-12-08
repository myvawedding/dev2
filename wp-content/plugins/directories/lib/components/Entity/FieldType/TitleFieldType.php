<?php
namespace SabaiApps\Directories\Component\Entity\FieldType;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class TitleFieldType extends Field\Type\AbstractType implements
    Field\Type\ISortable,
    Field\Type\ISchemable,
    Field\Type\IOpenGraph,
    Field\Type\IQueryable,
    Field\Type\IHumanReadable,
    Field\Type\IConditionable
{
    use Field\Type\QueryableStringTrait, Field\Type\ConditionableStringTrait;
    
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Title', 'directories'),
            'default_widget' => $this->_name,
            'default_renderer' => $this->_name,
            'creatable' => false,
            'icon' => 'fas fa-heading',
            'disablable' => true,
            'required' => true,
        );
    }

    public function fieldSortableOptions(Field\IField $field)
    {
        return array(
            array('default' => true),
            array('args' => array('desc'), 'label' => __('%s (desc)', 'directories')),
        );
    }
    
    public function fieldSortableSort(Field\Query $query, $fieldName, array $args = null)
    {
        $query->sortByField('title', isset($args) && $args[0] === 'desc' ? 'DESC' : 'ASC');
    }
    
    public function fieldSchemaProperties()
    {
        return array('name', 'alternateName');
    }
    
    public function fieldSchemaRenderProperty(Field\IField $field, $property, Entity\Type\IEntity $entity)
    {
        return array($this->_application->Entity_Title($entity));
    }
    
    public function fieldOpenGraphProperties()
    {
        return array('og:title');
    }
    
    public function fieldOpenGraphRenderProperty(Field\IField $field, $property, Entity\Type\IEntity $entity)
    {
        return array($this->_application->Entity_Title($entity));
    }
    
    public function fieldHumanReadableText(Field\IField $field, Entity\Type\IEntity $entity, $separator = null, $key = null)
    {
        return $this->_application->Entity_Title($entity);
    }
}