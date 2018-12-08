<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldType;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class PostContentFieldType extends Field\Type\AbstractType implements
    Field\Type\IQueryable,
    Field\Type\IOpenGraph,
    Field\Type\IHumanReadable,
    Field\Type\ISchemable,
    Field\Type\IConditionable
{
    use Field\Type\QueryableStringTrait, Field\Type\ConditionableStringTrait;
    
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Body', 'directories'),
            'default_widget' => $this->_name,
            'entity_types' => array('post'),
            'creatable' => false,
            'disablable' => true,
            'icon' => 'fas fa-bars',
        );
    }
    
    public function fieldOpenGraphProperties()
    {
        return array('og:description');
    }
    
    public function fieldOpenGraphRenderProperty(Field\IField $field, $property, Entity\Type\IEntity $entity)
    {
        return array($this->_application->Summarize($entity->getContent(), 300));
    }
    
    public function fieldHumanReadableText(Field\IField $field, Entity\Type\IEntity $entity, $separator = null, $key = null)
    {
        return $this->_application->Summarize($entity->getContent(), 300);
    }
    
    public function fieldSchemaProperties()
    {
        return array('description', 'text', 'reviewBody');
    }
    
    public function fieldSchemaRenderProperty(Field\IField $field, $property, Entity\Type\IEntity $entity)
    {        
        $ret = [];
        switch ($property) {
            case 'description':
                $ret[] = $this->_application->Summarize($entity->getContent(), 300);
                break;
            case 'text':
            case 'reviewBody':
                $ret[] = $this->_application->Summarize($entity->getContent(), 0);
                break;
        }
        
        return $ret;
    }
}