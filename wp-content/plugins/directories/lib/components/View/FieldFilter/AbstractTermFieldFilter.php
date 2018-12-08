<?php
namespace SabaiApps\Directories\Component\View\FieldFilter;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Search;

abstract class AbstractTermFieldFilter extends Field\Filter\AbstractFilter
{
    protected function _fieldFilterInfo()
    {
        return array(
            'field_types' => array('entity_terms'),
            'default_settings' => array(
                'hide_empty' => false,
                'hide_count' => false,
                'num' => 30,
                'depth' => 0,
            ),
            'facetable' => true,
        );
    }
    
    public function fieldFilterSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        // Get taxonomy bundle associated with the field
        if (!$bundle = $field->getTaxonomyBundle()) {
            return;
        }

        $ret = array(
            'hide_empty' => array(
                '#type' => 'checkbox',
                '#title' => __('Hide empty terms', 'directories'),
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
        if (empty($bundle->info['is_hierarchical'])) {
            return $ret + array(
                'num' => array(
                    '#type' => 'slider',
                    '#title' => __('Number of term options', 'directories'),
                    '#default_value' => $settings['num'],
                    '#min_value' => 1,
                    '#max_value' => 100,
                    '#integer' => true,
                    '#weight' => 1,
                ),
            ); 
        } else {
            return $ret + array(
                'depth' => array(
                    '#type' => 'slider',
                    '#title' => __('Depth of term hierarchy tree', 'directories'),
                    '#default_value' => $settings['depth'],
                    '#min_value' => 0,
                    '#max_value' => 10,
                    '#min_text' => __('Unlimited', 'directories'), 
                    '#integer' => true,
                    '#weight' => 1,
                ),
            );
        }
    }
    
    public function fieldFilterIsFilterable(Field\IField $field, array $settings, &$value, array $requests = null)
    {
        return !empty($value);
    }
    
    public function fieldFilterDoFilter(Field\Query $query, Field\IField $field, array $settings, $value, array &$sorts)
    {
        // Get taxonomy bundle associated with the field
        if (!$bundle = $field->getTaxonomyBundle()) return;
        
        $this->_application->Entity_QueryTaxonomy($field->getFieldName(), $query, $value, array(
            'hierarchical' => !empty($bundle->info['is_hierarchical']),
            'andor' => $this->_getMatchAndOr($field, $settings),
        ));
    }
    
    protected function _getMatchAndOr(Field\IField $field, array $settings)
    {
        return 'OR';
    }
    
    protected function _getCurrentTerm(Entity\Model\Bundle $bundle)
    {
        $ret = 0;
        if (isset($GLOBALS['drts_entity'])) {
            if ($GLOBALS['drts_entity']->getBundleName() === $bundle->name) {
                $ret = $GLOBALS['drts_entity']->getId();
            }
        } else {
            if ($this->_application->isComponentLoaded('Search')
                && ($params = $this->_application->Search_Form_params())
            ) {
                // Is taxonomy term selected through keyword search?
                if (isset($params['search_keyword']['taxonomy'])
                    && $params['search_keyword']['taxonomy'] === $bundle->type
                    && !empty($params['search_keyword']['id'])
                    && $this->_application->Entity_Bundle($params['search_keyword']['taxonomy'], $bundle->component, $bundle->group)
                ) {
                    $ret = (int)$params['search_keyword']['id'];
                }
                
                // Is taxonomy term selected through taxonomy select search?
                $search_key = 'search_taxonomy_select_' . $bundle->type;
                if (isset($params[$search_key])) {
                    if (is_array($params[$search_key])
                        || !empty($ret)
                    ) {
                        // multiple terms selected, so do not show filter
                        return true;
                    }
                
                    $ret = (int)$params[$search_key];
                }
            }
        }
        
        return $this->_application->Filter('view_current_term', $ret, array($bundle));
    }
    
    protected function _getFacets(Field\IField $field, array $settings, Entity\Type\Query $query = null)
    {
        if (!isset($query)
            || !empty($settings['hide_count'])
        ) return;
        
        if ($this->_getMatchAndOr($field, $settings) === 'OR') {
            // Clone field query and exclude queries for the taxonomy field and use it to fetch facets
            $field_query = clone $query->getFieldQuery();
            $field_query->removeNamedCriteria($field->getFieldName());
            $facets = $query->facets($field->getFieldName(), 'value', $field_query);
        } else {
            $facets = $query->facets($field->getFieldName());
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
}