<?php
namespace SabaiApps\Directories\Component\Voting\FieldType;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Application;

class FieldType extends Field\Type\AbstractType implements
    Field\Type\ISortable,
    Field\Type\IColumnable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Votes', 'directories'),
            'creatable' => false,
            'icon' => 'fas fa-star',
        );
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'count' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'was' => 'count',
                    'default' => 0,
                ),
                'sum' => array(
                    'type' => Application::COLUMN_DECIMAL,
                    'notnull' => true,
                    'length' => 5,
                    'scale' => 2,
                    'unsigned' => false,
                    'was' => 'sum',
                    'default' => 0,
                ),
                'average' => array(
                    'type' => Application::COLUMN_DECIMAL,
                    'notnull' => true,
                    'length' => 5,
                    'scale' => 2,
                    'unsigned' => false,
                    'was' => 'average',
                    'default' => 0,
                ),
                'last_voted_at' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'was' => 'last_voted_at',
                    'default' => 0,
                ),
                'name' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 40,
                    'notnull' => true,
                    'was' => 'name',
                    'default' => '',
                ),
                'count_init' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'unsigned' => false,
                    'was' => 'count_init',
                    'default' => 0,
                ),
                'sum_init' => array(
                    'type' => Application::COLUMN_DECIMAL,
                    'notnull' => true,
                    'length' => 5,
                    'scale' => 2,
                    'unsigned' => false,
                    'was' => 'sum_init',
                    'default' => 0,
                ),
                'level' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'was' => 'level',
                    'default' => 0,
                ),
            ),
            'indexes' => array(
                'count' => array(
                    'fields' => array('count' => array('sorting' => 'ascending')),
                    'was' => 'count',
                ),
                'sum' => array(
                    'fields' => array('sum' => array('sorting' => 'ascending')),
                    'was' => 'sum',
                ),
                'average' => array(
                    'fields' => array('average' => array('sorting' => 'ascending')),
                    'was' => 'average',
                ),
                'last_voted_at' => array(
                    'fields' => array('last_voted_at' => array('sorting' => 'ascending')),
                    'was' => 'last_voted_at',
                ),
                'name' => array(
                    'fields' => array('name' => array('sorting' => 'ascending')),
                    'was' => 'name',
                ),
                'level' => array(
                    'fields' => array('level' => array('sorting' => 'ascending')),
                    'was' => 'level',
                ),
            ),
        );
    }

    public function fieldTypeOnSave(Field\IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $ret = [];
        foreach ($values as $value) {
            if (!is_array($value)) continue;
            
            if (!isset($value['name'])
                || !strlen(trim($value['name']))
            ) {
                $value['name'] = '';
            }
            if (empty($value['count_init'])) {
                $value['count_init'] = $value['sum_init'] = 0;
            }
            if (isset($currentValues[0][$value['name']])) {
                if (empty($value['force'])) { // used by Faker generator to force count_init/sum_init 
                    // The following values may not be updated
                    if (isset($currentValues[0][$value['name']]['count_init'])) {
                        $value['count_init'] = $currentValues[0][$value['name']]['count_init'];
                    }
                    if (isset($currentValues[0][$value['name']]['sum_init'])) {
                        $value['sum_init'] = $currentValues[0][$value['name']]['sum_init'];
                    }
                } else {
                    unset($currentValues[0][$value['name']]);
                }
            }
 
            // Increment count/sum
            if (!empty($value['count_init'])) {
                $value['count'] += $value['count_init'];
            }
            if (!empty($value['sum_init'])) {
                $value['sum'] += $value['sum_init'];
            }
            
            if (empty($value['count'])) continue; // no votes
 
            if (empty($value['sum'])) {
                $value['average'] = 0.00;
                $value['level'] = 0;
            } else {
                $value['average'] = round($value['sum'] / $value['count'], 2);
                $value['level'] = round($value['average']);
            }
            
            $ret[$value['name']] = $value;
        }
        
        if (empty($ret)
            && !empty($currentValues[0])
        ) {
            // Preserve current entry if count_init is configured
            foreach ($currentValues[0] as $name => $current) {
                if (empty($current['count_init'])) continue;
                
                $ret[$name] = array(
                    'count' => $current['count_init'],
                    'sum' => $current['sum_init'],
                    'average' => round($current['sum_init'] / $current['count_init'], 2),
                ) + $current;
            }
        }
        
        return array_values($ret);        
    }

    public function fieldTypeOnLoad(Field\IField $field, array &$values, Entity\Type\IEntity $entity)
    {
        $new_values = [];
        foreach ($values as $value) {
            // Index by vote name
            $_value = $value;
            unset($_value['name']);
            $new_values[$value['name']] = $_value;
        }
        $values = array($new_values);
    }
    
    public function fieldTypeIsModified($field, $valueToSave, $currentLoadedValue)
    {
        if (empty($currentLoadedValue[0])) return true;
            
        $current = [];
        foreach (array_keys($currentLoadedValue[0]) as $name) {
            $current[] = $currentLoadedValue[0][$name] + array('name' => $name);
        }
        return $current !== $valueToSave;
    }
    
    public function fieldSortableOptions(Field\IField $field)
    {        
        return [
            ['label' => $label = $field->getFieldLabel(true)],
            ['args' => ['asc'], 'label' => sprintf(__('%s (asc)', 'directories'), $label)],
        ];
    }
    
    public function fieldSortableSort(Field\Query $query, $fieldName, array $args = null)
    {
        switch ($fieldName) {
            case 'voting_updown':
                $column = 'sum';
                break;
            case 'voting_bookmark':
                $column = 'count';
                break;
            case 'voting_rating':
                $column = 'average';
                break;
            default:
                // Todo call Voting_Type and then let the implementation set additional criteria
                $query->fieldIs($fieldName, '_all', 'name');
                $column = 'average';
        }
        $query->sortByField($fieldName, isset($args) && $args[0] === 'asc' ? 'ASC' : 'DESC', $column);
    }
    
    public function fieldColumnableInfo(Field\IField $field)
    {
        if (strpos($field->getFieldName(), 'voting_') !== 0) return;
        
        $type = substr($field->getFieldName(), 7/*strlen('voting_')*/);
        if (!$type_impl = $this->_application->Voting_Types_impl($type, true)) return;
            
        $type_info = $type_impl->votingTypeInfo();
        return array(
            'icon' => $type_info['icon'],
            'label' => $type_info['label'],
            'sortby' => 'count',
        );
    }
    
    public function fieldColumnableColumn(Field\IField $field, $value)
    {
        if (empty($value[0])) return ''; 
        
        $type = substr($field->getFieldName(), 7/*strlen('voting_')*/);
        if (!$type_impl = $this->_application->Voting_Types_impl($type, true)) return;
        
        if (isset($value[0][''])) {
            $value = $value[0][''];
        } else {
            $value = array('count' => 0, 'sum' => 0, 'average' => 0, 'level' => 0);
        }
        echo $type_impl->votingTypeFormat($value, 'column');
    }
}