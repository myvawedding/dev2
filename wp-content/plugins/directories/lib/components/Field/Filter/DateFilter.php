<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity;

class DateFilter extends AbstractFilter
{
    protected function _fieldFilterInfo()
    {
        return array(
            'label' => __('Date picker', 'directories'),
            'field_types' => array('date'),
            'default_settings' => [],
        );
    }

    public function fieldFilterForm(IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, array $parents = [])
    {
        $field_settings = $field->getFieldSettings();
        return array(
            '#type' => 'datepicker',
            '#min_date' => !empty($field_settings['date_range_enable']) ? @$field_settings['date_range'][0] : null,
            '#max_date' => !empty($field_settings['date_range_enable']) ? @$field_settings['date_range'][1] : null,
            '#disable_time' => true,
        );
    }

    public function fieldFilterIsFilterable(IField $field, array $settings, &$value, array $requests = null)
    {
        return !empty($value);
    }

    public function fieldFilterDoFilter(Query $query, IField $field, array $settings, $value, array &$sorts)
    {
        $query->fieldIsOrGreaterThan($field, $value)->fieldIsSmallerThan($field, $value + 86400);
    }

    public function fieldFilterLabels(IField $field, array $settings, $value, $form, $defaultLabel)
    {
        return array('' => $this->_application->H($defaultLabel) . ': ' . $this->_application->System_Date($value, true));
    }
}
