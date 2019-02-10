<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Application;

class NumberType extends AbstractValueType implements
    ISortable,
    IQueryable,
    IOpenGraph,
    IHumanReadable,
    IConditionable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Number', 'directories'),
            'default_widget' => 'textfield',
            'default_renderer' => 'number',
            'default_settings' => array(
                'min' => null,
                'max' => null,
                'decimals' => 0,
                'prefix' => null,
                'suffix' => null,
            ),
            'icon' => 'fas fa-hashtag',
        );
    }

    public function fieldTypeSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        return array(
            '#element_validate' => array(array($this, 'validateMinMaxSettings')),
            'min' => array(
                '#type' => 'number',
                '#title' => __('Minimum', 'directories'),
                '#description' => __('The minimum value allowed in this field.', 'directories'),
                '#size' => 10,
                '#default_value' => $settings['min'],
                '#numeric' => true,
                '#step' => 0.01,
            ),
            'max' => array(
                '#type' => 'number',
                '#title' => __('Maximum', 'directories'),
                '#description' => __('The maximum value allowed in this field.', 'directories'),
                '#size' => 10,
                '#default_value' => $settings['max'],
                '#numeric' => true,
                '#step' => 0.01,
            ),
            'decimals' => array(
                '#type' => 'select',
                '#title' => __('Decimals', 'directories'),
                '#description' => __('The number of digits to the right of the decimal point.', 'directories'),
                '#options' => array(0 => __('0 (no decimals)', 'directories'), 1 => 1, 2 => 2),
                '#default_value' => $settings['decimals'],
            ),
            'prefix' => array(
                '#type' => 'textfield',
                '#title' => __('Field prefix', 'directories'),
                '#description' => __('Example: $, #, -', 'directories'),
                '#size' => 20,
                '#default_value' => $settings['prefix'],
                '#no_trim' => true,
            ),
            'suffix' => array(
                '#type' => 'textfield',
                '#title' => __('Field suffix', 'directories'),
                '#description' => __('Example: km, %, g', 'directories'),
                '#size' => 20,
                '#default_value' => $settings['suffix'],
                '#no_trim' => true,
            ),
        );
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'value' => array(
                    'type' => Application::COLUMN_DECIMAL,
                    'notnull' => true,
                    'length' => 18,
                    'scale' => 2,
                    'unsigned' => false,
                    'was' => 'value',
                    'default' => 0,
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

    protected function _onSaveValue($value, array $settings)
    {
        return is_numeric($value) ? round($value, $settings['decimals']) : null;
    }
    
    public function fieldSortableOptions(IField $field)
    {
        return array(
            [],
            array('args' => array('asc'), 'label' => __('%s (asc)', 'directories'))
        );
    }
    
    public function fieldSortableSort(Query $query, $fieldName, array $args = null)
    {
        $query->sortByField($fieldName, isset($args) && $args[0] === 'asc' ? 'ASC' : 'DESC');
    }
    
    public function fieldQueryableInfo(IField $field)
    {
        return array(
            'example' => '1,10',
            'tip' => __('Enter a single number for exact match, two numbers separated with a comma for range search.', 'directories'),
        );
    }
    
    public function fieldQueryableQuery(Query $query, $fieldName, $paramStr, Entity\Model\Bundle $bundle = null)
    {
        $params = $this->_queryableParams($paramStr, false);
        switch (count($params)) {
            case 0:
                break;
            case 1:
                if (strlen($params[0])) {
                    $query->fieldIs($fieldName, $params[0]);
                }
                break;
            default:
                if (strlen($params[0])) {
                    $query->fieldIsOrGreaterThan($fieldName, $params[0]);
                }
                if (strlen($params[1])) {
                    $query->fieldIsOrSmallerThan($fieldName, $params[1]);
                }
        }
    }
    
    public function fieldOpenGraphProperties()
    {
        return array('books:page_count', 'music:duration', 'video:duration');
    }
    
    public function fieldOpenGraphRenderProperty(IField $field, $property, Entity\Type\IEntity $entity)
    {
        if (!$value = (int)$entity->getSingleFieldValue($field->getFieldName())) return;
        
        switch ($property) {
            case 'music:duration':
            case 'video:duration':
                return $value * 60; // we assume the value is in minutes here, may need a filter to change that
            default:
                return $value;
        }
    }
    
    public function fieldHumanReadableText(IField $field, Entity\Type\IEntity $entity, $separator = null, $key = null)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return '';
        
        return implode(isset($separator) ? $separator : ', ', $values);
    }
    
    public function fieldConditionableInfo(IField $field)
    {
        return [
            '' => [
                'compare' => ['value', '!value', '<value', '>value', 'empty', 'filled'],
                'tip' => __('Enter a single numeric value', 'directories'),
                'example' => 7,
            ],
        ];
    }
    
    public function fieldConditionableRule(IField $field, $compare, $value = null, $name = '')
    {
        switch ($compare) {
            case 'value':
            case '!value':
            case '<value':
            case '>value':
                return is_numeric($value) ? ['type' => $compare, 'value' => $value] : null;
            case 'empty':
                return ['type' => 'filled', 'value' => false];
            case 'filled':
                return ['type' => 'empty', 'value' => false];
            default:
                return;
        }
    }

    public function fieldConditionableMatch(IField $field, array $rule, array $values = null)
    {
        switch ($rule['type']) {
            case 'value':
            case '!value':
                if (empty($values)) return $rule['type'] === '!value';

                foreach ($values as $input) {
                    foreach ((array)$rule['value'] as $rule_value) {
                        if ($input == $rule_value) {
                            if ($rule['type'] === '!value') return false;
                            continue 2;
                        }
                    }
                    // One of rule values did not match
                    if ($rule['type'] === 'value') return false;
                }
                // All matched or did not match.
                return true;
            case '<value':
            case '>value':
                if (empty($values)) return false;

                foreach ($values as $input) {
                    foreach ((array)$rule['value'] as $rule_value) {
                        if ($input > $rule_value) {
                            if ($rule['type'] === '<value') return false;
                        } elseif ($input < $rule_value) {
                            if ($rule['type'] === '>value') return false;
                        } else {
                            return false;
                        }
                    }
                }
                return true;
            case 'empty':
                return empty($values) === $rule['value'];
            case 'filled':
                return !empty($values) === $rule['value'];
            default:
                return false;
        }
    }
}