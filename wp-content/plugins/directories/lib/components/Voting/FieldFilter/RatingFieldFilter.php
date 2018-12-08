<?php
namespace SabaiApps\Directories\Component\Voting\FieldFilter;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Request;

class RatingFieldFilter extends AbstractFieldFilter
{    
    protected function _fieldFilterInfo()
    {
        return parent::_fieldFilterInfo() + array(
            'default_settings' => array(
                'inline' => false,
                'hide_count' => false,
            ),
            'facetable' => true,
        );
    }

    public function fieldFilterSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        $form = [
            'inline' => [
                '#type' => 'checkbox',
                '#title' => __('Display inline', 'directories'),
                '#default_value' => $settings['inline'],
            ],
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
    
    public function fieldFilterForm(Field\IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, array $parents = [])
    {        
        if (isset($query)
            && empty($settings['hide_count'])
        ) {
            // Clone field query and exclude queries for the rating field and use it to fetch facets
            $field_query = clone $query->getFieldQuery();
            $field_query->removeNamedCriteria($field->getFieldName());
            $facets = $query->facets(
                $field->getFieldName(),
                $this->_valueColumn,
                $field_query,
                array('name' => $this->_getVoteName($settings))
            );
        }
        
        if (!isset($current)) {            
            $current = array(
                '#type' => 'radios',
                '#options' => $this->_application->Voting_RenderRating_options(false, ''),
                '#option_no_escape' => true,
                '#inline' => $settings['inline'],
                '#empty_value' => '',
                '#entity_filter_form_type' => 'radios',
            );
        }
        
        if (isset($facets)) {
            $request = (int)$request;
            $sum = 0;
            for ($i = 5; $i >= 1; $i--) {                
                if (!empty($facets[$i])) {
                    $sum += $facets[$i];
                }
                $current['#options'][$i] = array(
                    '#title' => $current['#options'][$i],
                    '#count' => $sum,
                );
                if (empty($sum)
                    && $i !== $request
                ) {
                    $current['#options_disabled'][] = $i;
                }
            }
        }
        
        return $current;
    }
    
    public function fieldFilterSupports(Field\IField $field)
    {
        return $field->getFieldName() === 'voting_rating';
    }
}