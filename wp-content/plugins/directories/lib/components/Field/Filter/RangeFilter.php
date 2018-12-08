<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity;

class RangeFilter extends AbstractFilter
{
    protected function _fieldFilterInfo()
    {
        return array(
            'field_types' => array('number', 'range'),
            'default_settings' => array(
                'ignore_min_max' => true,
            ),
        );
    }

    public function fieldFilterSettingsForm(IField $field, array $settings, array $parents = [])
    {
        $field_settings = $field->getFieldSettings();
        return array(
            'step' => array(
                '#type' => 'number',
                '#title' => __('Range slider step', 'directories'),
                '#default_value' => isset($settings['step']) ? $settings['step'] : $this->_getStep($field),
                '#size' => 5,
                '#numeric' => true,
                '#element_validate' => array(array(array($this, 'validateStep'), array($field_settings))),
                '#min_value' => 0,
            ),
            'ignore_min_max' => array(
                '#type' => 'checkbox',
                '#title' => __('Do not filter if min/max values are selected', 'directories'),
                '#default_value' => !empty($settings['ignore_min_max']),
            ),
        );
    }
    
    public function validateStep($form, &$value, $element, $settings)
    {
        if (empty($value)) return;
        
        $min_value = isset($settings['min']) ? $settings['min'] : 0;
        $max_value = isset($settings['max']) ? $settings['max'] : 100;
        
        $range = $max_value - $min_value;
        $i = $range / $value;
        if ($i <= 0
            || $range - (floor($i) * $value) > 0
        ) {
            $form->setError(sprintf(__('The full specified value range of the slider (%s - %s) should be evenly divisible by the step', 'directories'), $min_value, $max_value), $element);
        }
    }
    
    protected function _getStep(IField $field)
    {
        $settings = $field->getFieldSettings();
        return empty($settings['decimals']) ? 1 : ($settings['decimals'] == 1 ? 0.1 : 0.01);
    }
    
    public function fieldFilterForm(IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, array $parents = [])
    {
        $field_settings = $field->getFieldSettings();        
        return array(
            '#type' => 'range',
            '#min_value' => isset($field_settings['min']) ? $field_settings['min'] : 0,
            '#max_value' => isset($field_settings['max']) ? $field_settings['max'] : 100,
            '#numeric' => true,
            '#field_prefix' => isset($field_settings['prefix']) && strlen($field_settings['prefix']) ? $field_settings['prefix'] : null,
            '#field_suffix' => isset($field_settings['suffix']) && strlen($field_settings['suffix']) ? $field_settings['suffix'] : null,
            '#step' => !empty($settings['step']) ? $settings['step'] : $this->_getStep($field),
            //'#slider_max_postfix' => '+',
            '#entity_filter_form_type' => 'slider',
        );
    }
    
    public function fieldFilterIsFilterable(IField $field, array $settings, &$value, array $requests = null)
    {
        if (!strlen($value)
            || (!$_value = explode(';', $value))
        ) return false;
        
        $_value[0] = (string)@$_value[0];
        $_value[1] = (string)@$_value[1];
        
        if (!empty($settings['ignore_min_max'])) {
            if (strlen($_value[0])
                && strlen($_value[1])
            ) {
                $field_settings = $field->getFieldSettings();
                if (!isset($field_settings['min'])) {
                    $field_settings['min'] = 0;
                }
                if (!isset($field_settings['max'])) {
                    $field_settings['max'] = 100;
                }
                if ($_value[0] == $field_settings['min']
                    && $_value[1] == $field_settings['max']
                ) {
                    return false;
                }
                return true;
            }
        }
        
        return strlen($_value[0]) || strlen($_value[1]);
    }
    
    public function fieldFilterDoFilter(Query $query, IField $field, array $settings, $value, array &$sorts)
    {
        switch ($field->getFieldType()) {
            case 'number':
                if (isset($value['min'])) {
                    $query->fieldIsOrGreaterThan($field, $value['min']);
                }
                if (isset($value['max'])) {
                    $query->fieldIsOrSmallerThan($field, $value['max']);
                }
                break;
            case 'range':
                if (!isset($value['min'])) {
                    $field_settings = $field->getFieldSettings();
                    $value['min'] = isset($field_settings['min']) ? $field_settings['min'] : 0;
                }
                if (!isset($value['max'])) {
                    $field_settings = $field->getFieldSettings();
                    $value['max'] = isset($field_settings['max']) ? $field_settings['max'] : 100;
                }
                $query->fieldIsOrGreaterThan($field, $value['min'], 'min')
                    ->fieldIsOrSmallerThan($field, $value['max'], 'max');
        }
    }
}