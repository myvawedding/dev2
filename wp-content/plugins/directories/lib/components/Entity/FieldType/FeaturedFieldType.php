<?php
namespace SabaiApps\Directories\Component\Entity\FieldType;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Application;

class FeaturedFieldType extends Field\Type\AbstractType implements
    Field\Type\ISortable,
    Field\Type\IQueryable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => 'Featured Content',
            'creatable' => false,
            'admin_only' => true,
        );
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'value' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'unsigned' => true,
                    'was' => 'value',
                    'default' => 1,
                    'length' => 1,
                ),
                'featured_at' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'unsigned' => true,
                    'was' => 'featured_at',
                    'default' => 0,
                ),
                'expires_at' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'unsigned' => true,
                    'was' => 'expires_at',
                    'default' => 0,
                ),
            ),
            'indexes' => array(
                'value_featured_at' => array(
                    'fields' => array(
                        'value' => array('sorting' => 'ascending'),
                        'featured_at' => array('sorting' => 'ascending')
                    ),
                    'was' => 'value_featured_at',
                ),
                'expires_at' => array(
                    'fields' => array(
                        'expires_at' => array('sorting' => 'ascending'),
                    ),
                    'was' => 'expires_at',
                ),
            ),
        );
    }

    public function fieldTypeOnSave(Field\IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $value = array_shift($values); // single entry allowed for this field
        if (!is_array($value)
            || empty($value['value'])
        ) {
            $value = false; // delete
        } else {
            if (empty($value['featured_at'])) {
                $value['featured_at'] = time(); 
                if (!empty($currentValues)) {
                    $current_value = array_shift($currentValues);
                    if (!empty($current_value['featured_at'])) {
                        $value['featured_at'] = $current_value['featured_at'];
                    }
                }  
            }
        }
        return array($value);
    }
    
    public function fieldSortableOptions(Field\IField $field)
    {
        return array(
            array('label' => __('Featured First', 'directories')),
        );
    }
    
    public function fieldSortableSort(Field\Query $query, $fieldName, array $args = null)
    {
        $query->sortByField($fieldName, 'DESC');
    }
    
    public function fieldQueryableInfo(Field\IField $field)
    {
        return array(
            'example' => 1,
            'tip' => __('Enter 0 for non-featured items, 1 for all featured items, 5 for items with normal priority or higher, 9 for items with highest priroiriy.', 'directories'),
        );
    }
    
    public function fieldQueryableQuery(Field\Query $query, $fieldName, $paramStr, Entity\Model\Bundle $bundle = null)
    {
        if ($priority = (int)$paramStr) {
            $query->fieldIsOrGreaterThan($fieldName, $priority);
        } else {
            $query->fieldIsNull($fieldName);
        }
    }
    
    public static function priorities()
    {
        return array(
            9 => _x('High', 'priority', 'directories'),
            5 => _x('Normal', 'priority', 'directories'),
            1 => _x('Low', 'priority', 'directories'),
        );
    }
}