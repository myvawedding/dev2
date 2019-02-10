<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Application;

class BooleanType extends AbstractValueType
    implements ISchemable, IQueryable, IHumanReadable, IConditionable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('ON/OFF', 'directories'),
            'default_widget' => 'checkbox',
            'default_renderer' => 'boolean',
            'default_settings' => [],
            'icon' => 'fas fa-toggle-on',
        );
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'value' => array(
                    'type' => Application::COLUMN_BOOLEAN,
                    'unsigned' => true,
                    'notnull' => true,
                    'was' => 'value',
                    'default' => false,
                ),
            ),
            'indexes' => array(
                'value' => array(
                    'fields' => array('value' => array('sorting' => 'ascending')),
                    'was' => 'value',
                ),
            ),
        );
    }

    public function fieldTypeOnSave(IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $ret = [];
        foreach ($values as $weight => $value) {
            $ret[]['value'] = is_array($value) ? (bool)$value['value'] : (bool)$value;
        }

        return $ret;
    }
    
    public function fieldSchemaProperties()
    {
        return array('acceptsReservations');
    }
    
    public function fieldSchemaRenderProperty(IField $field, $property, Entity\Type\IEntity $entity)
    {
        $value = $entity->getSingleFieldValue($field->getFieldName());
        if (null === $value) return;
        
        return (bool)$value;
    }
    
    public function fieldQueryableInfo(IField $field)
    {
        return array(
            'example' => __('1 or 0', 'directories'),
            'tip' => __('Enter 1 for true (checked), 0 for false (unchecked).', 'directories'),
        );
    }
    
    public function fieldQueryableQuery(Query $query, $fieldName, $paramStr, Entity\Model\Bundle $bundle = null)
    {
        $query->fieldIs($fieldName, (bool)$paramStr);
    }
    
    public function fieldHumanReadableText(IField $field, Entity\Type\IEntity $entity, $separator = null, $key = null)
    {
        return (bool)$entity->getSingleFieldValue($field->getFieldName()) === true ? __('Yes', 'directories') : __('No', 'directories');
    }
    
    public function fieldConditionableInfo(IField $field)
    {
        return [
            '' => [
                'compare' => ['empty', 'filled'],
            ],
        ];
    }
    
    public function fieldConditionableRule(IField $field, $compare, $value = null, $name = '')
    {
        switch ($compare) {
            case 'empty':
                return ['type' => 'checked', 'value' => false];
            case 'filled':
                return ['type' => 'unchecked', 'value' => false];
            default:
                return;
        }
    }

    public function fieldConditionableMatch(IField $field, array $rule, array $values = null)
    {
        switch ($rule['type']) {
            case 'unchecked':
                return empty($values[0]) === $rule['value'];
            case 'checked':
                return !empty($values[0]) === $rule['value'];
            default:
                return false;
        }
    }
}
