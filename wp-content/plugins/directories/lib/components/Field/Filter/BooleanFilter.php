<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity;

class BooleanFilter extends AbstractFilter
{
    protected $_filterColumn = 'value', $_trueValue = true, $_inverse = false, $_nullOnly = false;
    
    protected function _fieldFilterInfo()
    {
        return array(
            'label' => __('Single checkbox', 'directories'),
            'field_types' => array('boolean', 'switch'),
            'default_settings' => array(
                'checkbox_label' => null,
                'hide_count' => false,
            ),
            'facetable' => true,
        );
    }

    public function fieldFilterSettingsForm(IField $field, array $settings, array $parents = [])
    {
        $form = [
            'checkbox_label' => array(
                '#type' => 'textfield',
                '#title' => __('Checkbox label', 'directories'),
                '#description' => __('Enter the label displayed next to the checkbox.', 'directories'),
                '#default_value' => $settings['checkbox_label'],
                '#required' => true,
            ),
        ];
        if ($this->_application->getComponent('View')->getConfig('filters', 'facet_count')) {
            $form['hide_count'] = [
                '#type' => 'checkbox',
                '#title' => __('Hide count', 'directories'),
                '#default_value' => $settings['hide_count'],
            ];
        }
        return $form;
    }
    
    public function fieldFilterForm(IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, array $parents = [])
    {
        if (isset($query)
            && empty($settings['hide_count'])
        ) {
            $facets = $query->facets($field->getFieldName(), $this->_filterColumn);
        }
        
        if (!isset($current)) {
            $current = array(
                '#type' => 'checkbox',
                '#on_value' => 1,
                '#off_value' => '',
                '#on_label' => $settings['checkbox_label'],
                '#switch' => false,
                '#entity_filter_form_type' => 'checkboxes',
            );
        }

        if (isset($facets)) {
            $count = is_array($facets) && !empty($facets) ? array_sum($facets) : 0;
            $current['#option_no_escape'] = true;
            $current['#on_label'] = $this->_application->H($current['#on_label']) . ' <span>(' . $count . ')</span>';
            $current['#disabled'] = $count === 0;
        }
        
        return $current;
    }
    
    public function fieldFilterIsFilterable(IField $field, array $settings, &$value, array $requests = null)
    {
        return isset($value[0]) && is_numeric($value[0]);
    }
    
    public function fieldFilterDoFilter(Query $query, IField $field, array $settings, $value, array &$sorts)
    {
        $value = (bool)$value;
        if ($this->_inverse) {
            $value = !$value;
        }
        if (!$value) {
            if ($this->_nullOnly
                || !isset($this->_filterColumn)
            ) {
                $query->fieldIsNull($field, $this->_filterColumn);
            } else {
                $query->startCriteriaGroup('OR')
                    ->fieldIsNull($field, $this->_filterColumn);
                if (is_array($this->_trueValue)) {
                    $query->fieldIsNotIn($field, $this->_trueValue, $this->_filterColumn);
                } else {
                    $query->fieldIsNot($field, $this->_trueValue, $this->_filterColumn);
                }
                $query->finishCriteriaGroup();
            }
        } else {
            if ($this->_nullOnly
                || !isset($this->_filterColumn)
            ) {
                $query->fieldIsNotNull($field, $this->_filterColumn);
            } else {
                if (is_array($this->_trueValue)) {
                    $query->fieldIsIn($field, $this->_trueValue, $this->_filterColumn);
                } else {
                    $query->fieldIs($field, $this->_trueValue, $this->_filterColumn);
                }
            }
        }
    }
    
    public function fieldFilterLabels(IField $field, array $settings, $value, $form, $defaultLabel)
    {
        return array($value => $this->_application->H($settings['checkbox_label']));
    }
}