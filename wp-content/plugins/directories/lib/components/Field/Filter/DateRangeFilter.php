<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity;

class DateRangeFilter extends AbstractFilter
{
    protected function _fieldFilterInfo()
    {
        return array(
            'label' => __('Date range picker', 'directories'),
            'field_types' => array('date'),
            'default_settings' => [],
        );
    }

    public function fieldFilterForm(IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, array $parents = [])
    {
        $field_settings = $field->getFieldSettings();
        return [
            '#type' => 'datepicker',
            '#enable_range' => true,
            '#min_date' => !empty($field_settings['date_range_enable']) ? @$field_settings['date_range'][0] : null,
            '#max_date' => !empty($field_settings['date_range_enable']) ? @$field_settings['date_range'][1] : null,
        ];
    }

    public function fieldFilterIsFilterable(IField $field, array $settings, &$value, array $requests = null)
    {
        if (empty($value[0]) && empty($value[1])) return false;

        if (!empty($value[0])) {
            $value[0] = is_numeric($value[0]) ? intval($value[0]) : strtotime($value[0]);
        }
        if (!empty($value[1])) {
            $value[1] = is_numeric($value[1]) ? intval($value[1]) : strtotime($value[1]);
        }

        return true;
    }

    public function fieldFilterDoFilter(Query $query, IField $field, array $settings, $value, array &$sorts)
    {
        if (!empty($value[0])) {
            $query->fieldIsOrGreaterThan($field, $value[0]);
        }
        if (!empty($value[1])) {
            $query->fieldIsOrSmallerThan($field, $value[1]);
        }
    }

    public function fieldFilterLabels(IField $field, array $settings, $value, $form, $defaultLabel)
    {
        $from = !empty($value[0]) ? $this->_application->System_Date($value[0], true) : '';
        $to = !empty($value[1]) ? $this->_application->System_Date($value[1], true) : '';
        return array('' => $this->_application->H($defaultLabel) . ': ' . $from . ' - ' . $to);
    }
}
