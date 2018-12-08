<?php
namespace SabaiApps\Directories\Component\View\FieldFilter;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class GlossaryFieldFilter extends Field\Filter\AbstractFilter
{
    protected function _fieldFilterInfo()
    {
        return array(
            'field_types' => ['entity_title', 'string'],
            'label' => __('A to Z', 'directories'),
            'default_settings' => array(
                'type' => 'buttons',
                'hide_empty' => false,
                'hide_count' => false,
            ),
            'facetable' => true,
        );
    }

    public function fieldFilterSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        return array(
            'type' => [
                '#type' => 'select',
                '#title' => __('Form field type', 'directories'),
                '#options' => [
                    'buttons' => __('Buttons', 'directories'),
                    'select' => __('Select list', 'directories'),
                    'radios' => __('Radio buttons', 'directories'),
                ],
                '#default_value' => $settings['type'],
                '#weight' => 4,
            ],
            'hide_empty' => array(
                '#type' => 'checkbox',
                '#title' => __('Hide empty', 'directories'),
                '#default_value' => !empty($settings['hide_empty']),
                '#weight' => 5,
            ),
            'hide_count' => array(
                '#type' => 'checkbox',
                '#title' => __('Hide count', 'directories'),
                '#default_value' => !empty($settings['hide_count']),
                '#weight' => 6,
            ),
        );
    }

    public function fieldFilterIsFilterable(Field\IField $field, array $settings, &$value, array $requests = null)
    {
        return !empty($value);
    }

    public function fieldFilterDoFilter(Field\Query $query, Field\IField $field, array $settings, $value, array &$sorts)
    {
        $query->fieldStartsWith(($property = $field->isPropertyField()) ? $property : $field->getFieldName(), $value);
    }

    public function fieldFilterForm(Field\IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, array $parents = [])
    {
        if (false === $facets = $this->_getFacets($field, $settings, $query)) return;

        if (!isset($current)) {
            $current = array(
                '#type' => $settings['type'],
                '#options' => [
                    '' => ['#title' => __('All', 'directories')]
                ] + $this->_getOptions($field, $settings),
                '#entity_filter_form_type' => $settings['type'] === 'buttons' ? 'radios' : $settings['type'],
                '#empty_value' => '',
            );
        }

        if (isset($facets)) {
            $this->_loadFacetCounts($current, $facets, $settings, $request);
        }

        return empty($current['#options']) ? null : $current;
    }

    protected function _getMatchAndOr(Field\IField $field, array $settings)
    {
        return 'OR';
    }

    protected function _getFacets(Field\IField $field, array $settings, Entity\Type\Query $query = null)
    {
        if (!isset($query)
            || !empty($settings['hide_count'])
        ) return;

        if ($this->_getMatchAndOr($field, $settings) === 'OR') {
            // Clone field query and exclude queries for the taxonomy field and use it to fetch facets
            $field_query = clone $query->getFieldQuery();
            $field_query->removeNamedCriteria(($property = $field->isPropertyField()) ? $property : $field->getFieldName());
        } else {
            $field_query = null;
        }
        if ($property = $field->isPropertyField()) {
            $facets = $query->facets(
                $property,
                'SUBSTRING(%1$s, 1, 1)',
                $field_query,
                ['SUBSTRING(%1$s, 1, 1)' => array_keys($this->_getOptions($field, $settings))]
            );
        } else {
            $facets = $query->facets(
                $field->getFieldName(),
                'SUBSTRING(value, 1, 1)',
                $field_query,
                ['SUBSTRING(value, 1, 1)' => array_keys($this->_getOptions($field, $settings))]
            );
        }


        if (!$facets) {
            return empty($settings['hide_empty']) ? [] : false;
        }

        return $facets;
    }

    protected function _loadFacetCounts(array &$form, array $facets, array $settings, $request = null)
    {
        if (empty($form['#options'])) return;

        $_request = isset($request) ? (array)$request : [];
        foreach (array_keys($form['#options']) as $value) {
            if ($value === '') continue;

            if (empty($facets[$value])) {
                if (!empty($settings['hide_empty'])) {
                    unset($form['#options'][$value]);
                } else {
                    if (!is_array($form['#options'][$value])) {
                        $form['#options'][$value] = $form['#options'][$value] . '(0)';
                    } else {
                        $form['#options'][$value]['#count'] = 0;
                    }
                    if (!in_array($value, $_request)) {
                        // Disable only when the option is currently not selected
                        $form['#options_disabled'][] = $value;
                    }
                }
            } else {
                if (!is_array($form['#options'][$value])) {
                    $form['#options'][$value] = $form['#options'][$value] . '(' . $facets[$value] . ')';
                } else {
                    $form['#options'][$value]['#count'] = $facets[$value];
                }
            }
        }
    }

    protected function _getOptions(Field\IField $field, array $settings)
    {
        $ret = [];
        $chars = range('A', 'Z');
        foreach ($chars as $char) {
            $ret[strtolower($char)] = ['#title' => $char];
        }
        return $ret;
    }

    public function fieldFilterLabels(Field\IField $field, array $settings, $value, $form, $defaultLabel)
    {
        $ret = [];
        foreach ((array)$value as $_value) {
            if (is_array($form['#options'][$_value])) {
                $label = $form['#options'][$_value]['#title'];
            } else {
                $label = $form['#options'][$_value];
            }
            $ret[$_value] = $this->_application->H($defaultLabel . ': ' . $label);
        }

        return $ret;
    }
}