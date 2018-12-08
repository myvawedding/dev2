<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Application;

class RangeType extends AbstractType implements IQueryable, ISchemable, IHumanReadable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Range', 'directories'),
            'default_widget' => 'range',
            'default_renderer' => 'range',
            'default_settings' => array(
                'min' => null,
                'max' => null,
                'decimals' => 0,
                'prefix' => null,
                'suffix' => null,
            ),
            'icon' => 'fas fa-sliders-h',
        );
    }

    public function fieldTypeSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        return array(
            '#element_validate' => array(array(array($this, 'validateMinMaxSettings'), array('decimals'))),
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
                'min' => array(
                    'type' => Application::COLUMN_DECIMAL,
                    'notnull' => true,
                    'length' => 18,
                    'scale' => 2,
                    'unsigned' => false,
                    'was' => 'min',
                    'default' => 0,
                ),
                'max' => array(
                    'type' => Application::COLUMN_DECIMAL,
                    'notnull' => true,
                    'length' => 18,
                    'scale' => 2,
                    'unsigned' => false,
                    'was' => 'max',
                    'default' => 0,
                ),
            ),
            'indexes' => array(
                'min' => array(
                    'fields' => array('min' => array('sorting' => 'ascending')),
                    'was' => 'min',
                ),
                'max' => array(
                    'fields' => array('max' => array('sorting' => 'ascending')),
                    'was' => 'max',
                ),
            ),
        );
    }   

    public function fieldTypeOnSave(IField $field, array $values)
    {
        $settings = $field->getFieldSettings();
        $ret = [];
        foreach ($values as $weight => $value) {
            if (!is_array($value)
                || !is_numeric(@$value['min'])
                || !is_numeric(@$value['max'])
            ) continue;

            $ret[] = array(
                'min' => round($value['min'], $settings['decimals']),
                'max' => round($value['max'], $settings['decimals']),
            );
        }

        return $ret;
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
                    $query->fieldIsOrGreaterThan($fieldName, $params[0], 'min')
                        ->fieldIsOrSmallerThan($fieldName, $params[0], 'max');
                }
                break;
            default:
                if (strlen($params[0])) {
                    $query->fieldIsOrGreaterThan($fieldName, $params[0], 'min');
                }
                if (strlen($params[1])) {
                    $query->fieldIsOrSmallerThan($fieldName, $params[1], 'max');
                }
        }
    }
    
    public function fieldSchemaProperties()
    {
        return array('priceRange');
    }
    
    public function fieldSchemaRenderProperty(IField $field, $property, Entity\Type\IEntity $entity)
    {        
        if (!$value = $entity->getSingleFieldValue($field->getFieldName())) return;
        
        return array($this->_valueToString($field, $value));
    }
    
    protected function _valueToString(IField $field, array $value)
    {
        $settings = $field->getFieldSettings();
        if (strlen($settings['prefix'])) {
            $value['min'] = $settings['prefix'] . $value['min'];
            $value['max'] = $settings['prefix'] . $value['max'];
        }
        if (strlen($settings['suffix'])) {
            $value['min'] = $value['min'] . $settings['suffix'];
            $value['max'] = $value['max'] . $settings['suffix'];
        }
        return $value['min'] . ' - ' . $value['max'];
    }
    
    public function fieldHumanReadableText(IField $field, Entity\Type\IEntity $entity, $separator = null, $key = null)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return '';
        
        $ret = [];
        foreach ($values as $value) {
            $ret[] = $this->_valueToString($field, $value);
        }
        return implode(isset($separator) ? $separator : ', ', $ret);
    }
}