<?php
namespace SabaiApps\Directories\Component\Field;

use SabaiApps\Framework\Criteria;

class Query
{
    protected $_criteria, $_criteriaOperator, $_criteriaIndex, $_namedCriteriaIndex = [],
        $_extraFields = [], $_sorts = [], $_group, $_distinct = true, $_tableIdColumn, $_tableJoins = [];

    public function __construct($operator = null)
    {
        $this->_criteriaIndex = 0;
        $this->_criteria = array(new Criteria\CompositeCriteria());
        $this->_criteriaOperator = array($operator === 'OR' ? 'OR' : 'AND');
    }
    
    public function __sleep()
    {
        return array('_criteria', '_extraFields', '_sorts', '_group', '_distinct', '_tableIdColumn', '_tableJoins');
    }
    
    public function __clone()
    {
        $this->_criteria[0] = clone $this->_criteria[0];
    }

    public function getCriteria()
    {
        return $this->_criteria[0];
    }

    public function getExtraFields()
    {
        return $this->_extraFields;
    }

    public function getSorts()
    {
        return $this->_sorts;
    }
    
    public function getGroup()
    {
        return $this->_group;
    }
    
    public function getTableIdColumn($default)
    {
        return isset($this->_tableIdColumn) ? sprintf($this->_tableIdColumn, $default) : $default;
    }
    
    public function setTableIdColumn($value)
    {
        $this->_tableIdColumn = $value;
        return $this;
    }
    
    public function getTableJoins()
    {
        return $this->_tableJoins;
    }
    
    public function addTableJoin($tableName, $alias, $on)
    {
        $this->_tableJoins[$tableName] = array('alias' => $alias, 'on' => $on);
        return $this;
    }
    
    public function isDistinct($flag = null)
    {
        if (isset($flag)) {
            $this->_distinct = $flag;
        }
        return $this->_distinct;
    }

    public function addExtraField($column, $fieldName, $sql = null, $query = true)
    {
        $this->_extraFields[$column] = array(
            'field_name' => $fieldName,
            'sql' => $sql,
            'query' => $query,
        );
        return $this;
    }

    public function startCriteriaGroup($inGroupOperator = 'AND')
    {
        ++$this->_criteriaIndex;
        $this->_criteria[$this->_criteriaIndex] = new Criteria\CompositeCriteria();
        $this->_criteriaOperator[$this->_criteriaIndex] = $inGroupOperator === 'OR' ? 'OR' : 'AND';

        return $this;
    }

    public function finishCriteriaGroup($name = null, $operator = null)
    {
        $criteria = $this->_criteria[$this->_criteriaIndex];
        unset($this->_criteria[$this->_criteriaIndex], $this->_criteriaOperator[$this->_criteriaIndex]);
        --$this->_criteriaIndex;
        
        return $this->addCriteria($criteria, $name, $operator);
    }

    public function addCriteria(Criteria\AbstractCriteria $criteria, $name = null, $operator = null)
    {
        if (!isset($operator)) {
            $operator = $this->_criteriaOperator[$this->_criteriaIndex];
        }
        if ($operator === 'OR') {
            $this->_criteria[$this->_criteriaIndex]->addOr($criteria);
        } else {
            $this->_criteria[$this->_criteriaIndex]->addAnd($criteria);
        }
        if (isset($name) && $this->_criteriaIndex === 0) { // only top level criteria may have a name
            if (!isset($this->_namedCriteriaIndex[$name])) {
                $this->_namedCriteriaIndex[$name] = [];
            }
            $this->_namedCriteriaIndex[$name][] = $this->_criteria[0]->getIndex();
        }

        return $this;
    }
    
    public function removeNamedCriteria($name, $return = false)
    {
        $ret = [];
        if (isset($this->_namedCriteriaIndex[$name])) {
            foreach ($this->_namedCriteriaIndex[$name] as $index) {
                $ret[] = $this->_criteria[0]->remove($index, $return);
            }
            unset($this->_namedCriteriaIndex[$name]);
        }
        
        return $return ? $ret : $this;
    }
    
    public function hasNamedCriteria($name)
    {
        return !empty($this->_namedCriteriaIndex[$name]);
    }
    
    public function sortByField($field, $order = 'ASC', $column = 'value', $alias = null, $nullValue = null, $emptyLast = false)
    {
        $this->_sorts[] = array(
            'field_name' => $field instanceof \SabaiApps\Directories\Component\Field\IField ? $field->getFieldName() : $field,
            'column' => $column,
            'table_alias' => $alias,
            'order' => $order === 'DESC' ? 'DESC' : 'ASC',
            'null_value' => $nullValue,
            'empty_last' => $emptyLast,
        );

        return $this;
    }
    
    public function sortById($order = 'ASC')
    {
        $this->_sorts['id'] = array(
            'is_id' => true,
            'order' => $order === 'DESC' ? 'DESC' : 'ASC',
        );

        return $this;
    }
    
    public function sortByRandom($seed = null)
    {
        $this->_sorts['random'] = array(
            'is_random' => true,
            'random_seed' => isset($seed) ? (int)$seed : null,
        );

        return $this;
    }
    
    public function sortByExtraField($fieldName, $order = 'ASC')
    {
        $this->_sorts[] = array(
            'field_name' => $fieldName,
            'is_extra_field' => true,
            'order' => $order === 'DESC' ? 'DESC' : 'ASC',
        );

        return $this;
    }

    public function sortByCustom($func, $order = 'ASC')
    {
        $this->_sorts[] = array(
            'is_custom' => $func,
            'order' => $order === 'DESC' ? 'DESC' : 'ASC',
        );

        return $this;
    }

    public function sortByCases($field, array $cases, $column = 'value', $alias = null)
    {
        $this->_sorts[] = array(
            'field_name' => $field instanceof \SabaiApps\Directories\Component\Field\IField ? $field->getFieldName() : $field,
            'column' => $column,
            'table_alias' => $alias,
            'cases' => $cases,
            'order' => 'ASC',
        );

        return $this;
    }
    
    public function groupByField($field, $order = null, $column = 'value', $alias = null)
    {
        $this->_group = array(
            'field_name' => $field instanceof \SabaiApps\Directories\Component\Field\IField ? $field->getFieldName() : $field,
            'column' => $column,
            'table_alias' => $alias,
            'order' => $order,
        );

        return $this;
    }

    public function groupByExtraField($fieldName)
    {
        return $this->groupByField($fieldName, null, null);
    }
    
    public function fieldIs($field, $value, $column = 'value', $alias = null, $on = null)
    {
        $field_arr = $this->_fieldToArray($field, $column, $alias, $on);
        return $this->addCriteria(new Criteria\IsCriteria($field_arr, $value), $field_arr['field_name']);
    }

    public function fieldIsNot($field, $value, $column = 'value', $alias = null, $on = null)
    {
        $field_arr = $this->_fieldToArray($field, $column, $alias, $on);
        return $this->addCriteria(new Criteria\IsNotCriteria($field_arr, $value), $field_arr['field_name']);
    }

    public function fieldIsNull($field, $column = 'value', $alias = null, $on = null)
    {
        $field_arr = $this->_fieldToArray($field, $column, $alias, $on);
        return $this->addCriteria(new Criteria\IsNullCriteria($field_arr), $field_arr['field_name']);
    }

    public function fieldIsNotNull($field, $column = 'value', $alias = null, $on = null)
    {
        $field_arr = $this->_fieldToArray($field, $column, $alias, $on);
        return $this->addCriteria(new Criteria\IsNotNullCriteria($field_arr), $field_arr['field_name']);
    }

    public function fieldIsIn($field, array $values, $column = 'value', $alias = null, $on = null)
    {
        $field_arr = $this->_fieldToArray($field, $column, $alias, $on);
        return $this->addCriteria(new Criteria\InCriteria($field_arr, $values), $field_arr['field_name']);
    }

    public function fieldIsNotIn($field, array $values, $column = 'value', $alias = null, $on = null)
    {
        $field_arr = $this->_fieldToArray($field, $column, $alias, $on);
        return $this->addCriteria(new Criteria\NotInCriteria($field_arr, $values), $field_arr['field_name']);
    }

    public function fieldIsOrGreaterThan($field, $value, $column = 'value', $alias = null, $on = null)
    {
        $field_arr = $this->_fieldToArray($field, $column, $alias, $on);
        return $this->addCriteria(new Criteria\IsOrGreaterThanCriteria($field_arr, $value), $field_arr['field_name']);
    }

    public function fieldIsOrSmallerThan($field, $value, $column = 'value', $alias = null, $on = null)
    {
        $field_arr = $this->_fieldToArray($field, $column, $alias, $on);
        return $this->addCriteria(new Criteria\IsOrSmallerThanCriteria($field_arr, $value), $field_arr['field_name']);
    }

    public function fieldIsGreaterThan($field, $value, $column = 'value', $alias = null, $on = null)
    {
        $field_arr = $this->_fieldToArray($field, $column, $alias, $on);
        return $this->addCriteria(new Criteria\IsGreaterThanCriteria($field_arr, $value), $field_arr['field_name']);
    }

    public function fieldIsSmallerThan($field, $value, $column = 'value', $alias = null, $on = null)
    {
        $field_arr = $this->_fieldToArray($field, $column, $alias, $on);
        return $this->addCriteria(new Criteria\IsSmallerThanCriteria($field_arr, $value), $field_arr['field_name']);
    }

    public function fieldStartsWith($field, $value, $column = 'value', $alias = null, $on = null)
    {
        $field_arr = $this->_fieldToArray($field, $column, $alias, $on);
        return $this->addCriteria(new Criteria\StartsWithCriteria($field_arr, $value), $field_arr['field_name']);
    }

    public function fieldEndsWith($field, $value, $column = 'value', $alias = null, $on = null)
    {
        $field_arr = $this->_fieldToArray($field, $column, $alias, $on);
        return $this->addCriteria(new Criteria\EndsWithCriteria($field_arr, $value), $field_arr['field_name']);
    }

    public function fieldContains($field, $value, $column = 'value', $alias = null, $on = null)
    {
        $field_arr = $this->_fieldToArray($field, $column, $alias, $on);
        return $this->addCriteria(new Criteria\ContainsCriteria($field_arr, $value), $field_arr['field_name']);
    }
    
    private function _fieldToArray($field, $column, $alias, $on)
    {
        return array(
            'field_name' => $field instanceof \SabaiApps\Directories\Component\Field\IField ? $field->getFieldName() : $field,
            'column' => $column,
            'table_alias' => $alias,
            'on' => $on,
        );
    }
}