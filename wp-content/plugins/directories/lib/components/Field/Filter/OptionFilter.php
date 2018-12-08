<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity;

class OptionFilter extends AbstractFilter
{
    protected $_emptyValue = '', $_valueColumn = 'value';
    
    protected function _fieldFilterInfo()
    {
        return array(
            'field_types' => array('choice'),
            'default_settings' => array(
                'type' => 'checkboxes',
                'show_more' => array('num' => 10),
                'andor' => 'AND',
                'default_text' => _x('Any', 'option', 'directories'),
                'show_icon' => true,
                'hide_count' => false,
            ),
            'facetable' => true,
        );
    }

    public function fieldFilterSettingsForm(IField $field, array $settings, array $parents = [])
    {
        $form = array(
            'type' => array(
                '#title' => __('Form field type', 'directories'),
                '#type' => 'select',
                '#options' => array(
                    'checkboxes' => __('Checkboxes', 'directories'),
                    'radios' => __('Radio buttons', 'directories'),
                    'select' => __('Select list', 'directories')
                ),
                '#default_value' => $settings['type'],
                '#weight' => 5,
            ),
            'show_more' => array(
                '#states' => array(
                    'invisible' => array(
                        sprintf('[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'select'),
                    ),
                ),
                'num' => array(
                    '#type' => 'slider',
                    '#integer' => true,
                    '#min_value' => 0,
                    '#max_value' => 50,
                    '#min_text' => __('Show all', 'directories'),
                    '#title' => __('Number of options to display', 'directories'),
                    '#description' => __('If there are more options than the number specified, those options are hidden until "more" link is clicked.', 'directories'),
                    '#default_value' => $settings['show_more']['num'],
                ),
                '#weight' => 15,
            ),
            'andor' => array(
                '#title' => __('Match any or all', 'directories'),
                '#type' => 'radios',
                '#options' => array('OR' => __('Match any', 'directories'), 'AND' => __('Match all', 'directories')),
                '#default_value' => $settings['andor'],
                '#states' => array(
                    'visible' => array(
                        sprintf('[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'checkboxes'),
                    ), 
                ),
                '#inline' => true,
                '#weight' => 20,
            ),
            'default_text' => array(
                '#type' => 'textfield',
                '#title'=> __('Default text', 'directories'),
                '#default_value' => $settings['default_text'],
                '#placeholder' => _x('Any', 'option', 'directories'),
                '#weight' => 25,
                '#states' => array(
                    'invisible' => array(
                        sprintf('[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'checkboxes'),
                    ),
                ),
            ),
        );
        if ($this->_application->getComponent('View')->getConfig('filters', 'facet_count')) {
            $form['hide_count'] = [
                '#type' => 'checkbox',
                '#title' => __('Hide count', 'directories'),
                '#default_value' => $settings['hide_count'],
                '#weight' => 30,
            ];
        }
        return $form;
    }
    
    public function fieldFilterForm(IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, array $parents = [])
    {
        if (isset($query)
            && empty($settings['hide_count'])
        ) {
            if ($settings['type'] !== 'checkboxes' // always OR if not checkboxes
                || $settings['andor'] === 'OR'
            ) {
                // Clone field query and exclude queries for the current choice field and fetch facets
                $field_query = clone $query->getFieldQuery();
                $field_query->removeNamedCriteria($field->getFieldName());
                $facets = $query->facets($field->getFieldName(), $this->_valueColumn, $field_query);
            } else {
                $facets = $query->facets($field->getFieldName(), $this->_valueColumn);
            }
        }
        
        if (!isset($current)) {        
            $option_no_escape = false;
            if (!$options = $this->_getOptions($field, !empty($settings['show_icon']), $option_no_escape)) return; // no options
            
            switch ($settings['type']) {
                case 'radios':
                case 'select':
                    $options[$this->_emptyValue] = $settings['default_text'];
                    $default_value = $this->_emptyValue; 
                    break;
                case 'checkboxes':
                default:
                    $default_value = null;
                    $settings['type'] = 'checkboxes';
            }
            
            $current = array(
                '#type' => $settings['type'],
                '#select2' => true,
                '#placeholder' => $settings['default_text'],
                '#options' => $options,
                '#options_valid' => array_keys($options),
                '#options_visible_count' => $settings['show_more']['num'],
                '#option_no_escape' => !empty($option_no_escape),
                '#default_value' => $default_value,
                '#entity_filter_form_type' => $settings['type'],
                '#options_disabled' => [],
            );
        }
        
        if (isset($facets)) {
            $_request = isset($request) ? (array)$request : [];
            foreach (array_keys($current['#options']) as $value) {
                if (empty($facets[$value])) {
                    if ($value !== $this->_emptyValue) {
                        if (!in_array($value, $_request)) {
                            // Disable only when the option is currently not selected
                            $current['#options_disabled'][] = $value;
                        }
                        $current['#options'][$value] = array(
                            '#title' => $current['#options'][$value],
                            '#count' => 0,
                        );
                    }
                } else {
                    $current['#options'][$value] = array(
                        '#title' => $current['#options'][$value],
                        '#count' => $facets[$value],
                    );
                }
            }
        }
        
        return empty($current['#options']) ? null : $current;
    }
    
    protected function _getOptions(IField $field, $showIcon, &$noEscape = false)
    {
        if (!$options = $this->_application->Field_ChoiceOptions($field)) return; // no options
        
        if (!empty($showIcon) && !empty($options['icons'])) {
            $noEscape = true;
            $ret = [];
            foreach (array_keys($options['options']) as $value) {
                if (isset($options['icons'][$value])) {
                    $ret[$value] = '<i class="fa-fw ' . $options['icons'][$value] . '"></i> ' . $this->_application->H($options['options'][$value]);
                } else {
                    $ret[$value] = $this->_application->H($options['options'][$value]);
                }
            }
        } else {
            $ret = $options['options'];
        }
        return $ret;
    }
    
    public function fieldFilterIsFilterable(IField $field, array $settings, &$value, array $requests = null)
    {
        return $settings['type'] === 'checkboxes' ? !empty($value) : $value != $this->_emptyValue;
    }
    
    public function fieldFilterDoFilter(Query $query, IField $field, array $settings, $value, array &$sorts)
    {
        $value = (array)$value;
        if (count($value) === 1) {
            $query->fieldIs($field, array_shift($value), $this->_valueColumn);
        } elseif ($settings['andor'] === 'OR' || !$this->_isMultipleChoiceField($field)) { // AND query does not make sense for non-multiple choice fields
            $query->fieldIsIn($field, $value, $this->_valueColumn);
        } else {
            $query->startCriteriaGroup('AND')->fieldIs($field, array_shift($value), $this->_valueColumn);
            $i = 1;
            foreach ($value as $_value) {
                $query->fieldIs($field, $_value, $this->_valueColumn, $field->getFieldName() . ++$i);
            }
            $query->finishCriteriaGroup();
        }
    }
    
    public function fieldFilterLabels(IField $field, array $settings, $value, $form, $defaultLabel)
    {
        $ret = [];
        if (empty($form['#option_no_escape'])) {
            foreach ((array)$value as $_value) {
                $ret[$_value] = $this->_application->H($form['#options'][$_value]);
            }
        } else {
            foreach ((array)$value as $_value) {
                $ret[$_value] = $form['#options'][$_value];
            }
        }
        
        return $ret;
    }
    
    protected function _isMultipleChoiceField(IField $field)
    {
        return $field->getFieldWidget() === 'checkboxes'
            || ($field->getFieldWidget() === 'select' && $field->getFieldMaxNumItems() !== 1);
    }
}