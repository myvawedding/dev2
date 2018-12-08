<?php
namespace SabaiApps\Directories\Component\Voting\FieldFilter;

use SabaiApps\Directories\Component\Field;

abstract class AbstractFieldFilter extends Field\Filter\AbstractFilter
{
    protected $_valueColumn = 'level';
    
    protected function _fieldFilterInfo()
    {
        return [
            'field_types' => array('voting_vote'),
        ];
    }
    
    public function fieldFilterIsFilterable(Field\IField $field, array $settings, &$value, array $requests = null)
    {
        return !empty($value) && is_numeric($value);
    }
    
    public function fieldFilterDoFilter(Field\Query $query, Field\IField $field, array $settings, $value, array &$sorts)
    {
        $query->fieldIs($field, $this->_getVoteName($settings), 'name')
            ->fieldIsOrGreaterThan($field, $value, $this->_valueColumn);
    }
    
    public function fieldFilterLabels(Field\IField $field, array $settings, $value, $form, $defaultLabel)
    {
        return [
            '' => $this->_application->H(sprintf($value == 5 ? __('%d stars', 'directories') : __('%d+ stars', 'directories'), $value)),
        ];
    }
    
    protected function _getVoteName(array $settings)
    {
        return '';
    }
}