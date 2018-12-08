<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity;

class NumberFilter extends AbstractFilter
{
    protected function _fieldFilterInfo()
    {
        return array(
            'label' => __('Text input field', 'directories'),
            'field_types' => array('number', 'range'),
            'default_settings' => [],
        );
    }
    
    public function fieldFilterForm(IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, array $parents = [])
    {
        $field_settings = $field->getFieldSettings();
        if ($field_settings['decimals'] > 0) {
            $numeric = true;
            $integer = false;
            $min_value = isset($field_settings['min']) && is_numeric($field_settings['min']) ? $field_settings['min'] : null;
            $max_value = isset($field_settings['max']) && is_numeric($field_settings['max']) ? $field_settings['max'] : null;
            $step = $field_settings['decimals'] == 1 ? 0.1 : 0.01;
        } else {
            $numeric = false;
            $integer = true;
            $min_value = isset($field_settings['min']) ? intval($field_settings['min']) : null;
            $max_value = isset($field_settings['max']) ? intval($field_settings['max']) : null;
            $step = null;
        }
        return array(
            '#type' => 'number',
            '#min_value' => $min_value,
            '#max_value' => $max_value,
            '#integer' => $integer,
            '#numeric' => $numeric,
            '#field_prefix' => isset($field_settings['prefix']) && strlen($field_settings['prefix']) ? $field_settings['prefix'] : null,
            '#field_suffix' => isset($field_settings['suffix']) && strlen($field_settings['suffix']) ? $field_settings['suffix'] : null,
            '#step' => $step,
            '#entity_filter_form_type' => 'textfield',
        );
    }
    
    public function fieldFilterIsFilterable(IField $field, array $settings, &$value, array $requests = null)
    {
        return strlen((string)@$value) > 0;
    }
    
    public function fieldFilterDoFilter(Query $query, IField $field, array $settings, $value, array &$sorts)
    {
        if ($field->getFieldType() === 'number') {
            $query->fieldIs($field, $value);
        } else {
            $query->fieldIsOrSmallerThan($field, $value, 'min')
                ->fieldIsOrGreaterThan($field, $value, 'max');
        }
    }
    
    public function fieldFilterLabels(IField $field, array $settings, $value, $form, $defaultLabel)
    {
        return array('' => $this->_application->H($defaultLabel . ': ' . $value));
    }
}