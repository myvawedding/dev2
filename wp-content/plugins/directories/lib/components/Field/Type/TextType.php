<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Application;

class TextType extends StringType
{   
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Paragraph Text', 'directories'),
            'default_widget' => 'textarea',
            'default_renderer' => 'text',
            'default_settings' => array(
                'min_length' => null,
                'max_length' => null,
                'char_validation' => 'none',
                'regex' => null,
            ),
            'icon' => 'fas fa-bars',
        );
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'value' => array(
                    'type' => Application::COLUMN_TEXT,
                    'notnull' => true,
                    'was' => 'value',
                ),
            ),
        );
    }
    
    public function fieldSchemaProperties()
    {
        return array('description', 'text', 'reviewBody');
    }
    
    public function fieldSchemaRenderProperty(IField $field, $property, Entity\Type\IEntity $entity)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return;
        
        $ret = [];
        switch ($property) {
            case 'description':
                foreach ($values as $value) {
                    $ret[] = $this->_application->Summarize(is_array($value) ? $value['value'] : $value, 300);
                }
                break;
            case 'text':
            case 'reviewBody':
                foreach ($values as $value) {
                    $ret[] = $this->_application->Summarize(is_array($value) ? $value['value'] : $value, 0);
                }
                break;
        }
        
        return $ret;
    }
    
    public function fieldOpenGraphProperties()
    {
        return array('og:description');
    }
    
    public function fieldOpenGraphRenderProperty(IField $field, $property, Entity\Type\IEntity $entity)
    {
        if (!$value = $entity->getSingleFieldValue($field->getFieldName())) return;
        
        return array($this->_application->Summarize(is_array($value) ? $value['value'] : $value, 300));
    }
    
    public function fieldHumanReadableText(IField $field, Entity\Type\IEntity $entity, $separator = null, $key = null)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return '';
        
        return implode(isset($separator) ? $separator : PHP_EOL . PHP_EOL, $values);
    }
}