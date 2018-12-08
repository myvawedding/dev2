<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Application;

class ChoiceType extends AbstractValueType
    implements ISortable, IQueryable, IHumanReadable, IConditionable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Choice', 'directories'),
            'default_widget' => 'checkboxes',
            'default_renderer' => 'choice',
            'default_settings' => array(
                'options' => null,
            ),
            'icon' => 'far fa-check-square',
        );
    }

    public function fieldTypeSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        return array(
            'options' => array(
                '#type' => 'options',
                '#title' => __('Options', 'directories'),
                '#default_value' => $settings['options'],
                '#multiple' => true,
            ),
        );
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'value' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 255,
                    'notnull' => true,
                    'was' => 'value',
                    'default' => '',
                ),
            ),
            'indexes' => array(
                'value' => array(
                    'fields' => array('value' => array('sorting' => 'ascending', 'length' => 191)),
                    'was' => 'value',
                ),
            ),
        );
    }
    
    public function fieldSortableOptions(IField $field)
    {
        return array(
            [],
            array('args' => array('desc'), 'label' => __('%s (desc)', 'directories'))
        );
    }
    
    public function fieldSortableSort(Query $query, $fieldName, array $args = null)
    {
        $query->sortByField($fieldName, isset($args) && $args[0] === 'desc' ? 'DESC' : 'ASC');
    }
    
    public function fieldQueryableInfo(IField $field)
    {
        return array(
            'example' => $this->_getFieldEntryExample($field),
            'tip' => __('Enter values separated with commas.', 'directories'),
        );
    }
    
    protected function _getFieldEntryExample(IField $field)
    {
        $settings = $field->getFieldSettings();
        if (!empty($settings['options']['options'])) {
            return implode(',', array_slice(array_keys($settings['options']['options']), 0, 4));
        }
        return 'aaa,bb,cccc';
    }
    
    public function fieldQueryableQuery(Query $query, $fieldName, $paramStr, Entity\Model\Bundle $bundle = null)
    {
        if ($params = $this->_queryableParams($paramStr)) {
            $query->fieldIsIn($fieldName, $params);
        }
    }
    
    public function fieldHumanReadableText(IField $field, Entity\Type\IEntity $entity, $separator = null, $key = null)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return '';
        
        $settings = $field->getFieldSettings();
        $ret = [];
        foreach ($values as $value) {
            if (isset($settings['options']['options'][$value])) {
                $ret[] = $settings['options']['options'][$value];
            }
        }
        return implode(isset($separator) ? $separator : ', ', $ret);
    }
    
    public function fieldConditionableInfo(IField $field)
    {        
        return [
            '' => [
                'compare' => ['value', '!value', 'one', 'empty', 'filled'],
                'tip' => __('Enter values separated with commas.', 'directories'),
                'example' => $this->_getFieldEntryExample($field),
            ],
        ];
    }
    
    public function fieldConditionableRule(IField $field, $compare, $value = null, $name = '')
    {
        switch ($compare) {
            case 'value':
            case '!value':
            case 'one':
                $value = trim($value);
                if (strpos($value, ',')) {
                    if (!$value = explode(',', $value)) return;
                    
                    $value = array_map('trim', $value);
                }
                return ['type' => $compare, 'value' => $value];
            case 'empty':
                return ['type' => 'filled', 'value' => false];
            case 'filled':
                return ['type' => 'empty', 'value' => false];
            default:
                return;
        }
    }
}