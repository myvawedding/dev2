<?php
namespace SabaiApps\Directories\Component\View\FieldFilter;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class TermSelectFieldFilter extends AbstractTermFieldFilter
{
    protected function _fieldFilterInfo()
    {
        $info = parent::_fieldFilterInfo();
        $info['label'] = __('Select list', 'directories');
        $info['default_settings'] += array(
            'default_text' => null,
        );
        return $info;
    }

    public function fieldFilterSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        $ret = parent::fieldFilterSettingsForm($field, $settings, $parents);
        
        if (!is_array($ret)) return;
        
        $bundle = $field->getTaxonomyBundle();
        $ret += array(
            'default_text' => array(
                '#type' => 'textfield',
                '#title'=> __('Default text', 'directories'),
                '#default_value' => $this->_getDefaultText($bundle, $settings),
                '#weight' => 4,
                '#placeholder' => __('— Select —', 'directories'),
            ),
        );
        
        return $ret;
    }
    
    protected function _getDefaultText($bundle, array $settings)
    {
        return strlen((string)$settings['default_text'])
            ? $settings['default_text']
            : __('— Select —', 'directories');
    }

    public function fieldFilterForm(Field\IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, array $parents = [])
    {
        // Get taxonomy bundle associated with the field
        if (!$bundle = $field->getTaxonomyBundle()) {
            return;
        }
        
        if (true === $current_term_id = $this->_getCurrentTerm($bundle)) {
            // Already have selected term(s) or no need to show the filter
            return;
        }
        
        $list_options = array(
            'content_bundle' => $field->Bundle->type,
            'hide_empty' => !empty($settings['hide_empty']),
            'hide_count' => !empty($settings['hide_count']),
            'count_no_html' => true,
            'return_array' => true,
        );

        if (empty($bundle->info['is_hierarchical'])) {
            if (!empty($current_term_id)) return; // term is already selected
            
            if (false === $facets = $this->_getFacets($field, $settings, $query)) return;
            
            if (!isset($current)) {
                $options = $this->_application->Entity_TaxonomyTerms_html(
                    $bundle->name,
                    $list_options + array(
                        'limit' => $settings['num'],
                        'depth' => 1,
                    )
                );
                if (empty($options)) return;
                
                $current = array(
                    '#type' => 'select',
                    '#options' => array('' => $this->_getDefaultText($bundle, $settings)) + $options,
                    '#select2' => true,
                    '#empty_value' => '',
                    '#multiple' => false,
                    '#entity_filter_form_type' => 'select',
                );
            }
            
            if (isset($facets)) {
                $this->_loadFacetCounts($current, $facets, $settings, $request);
            }

            return $current;
        }
                
        // Hierarchical taxonomy

        if (false === $facets = $this->_getFacets($field, $settings, $query)) return;
            
        if (!isset($current)) {
            $options = $this->_application->Entity_TaxonomyTerms_html(
                $bundle->name,
                $list_options + array(
                    'prefix' => '—',
                    'parent' => $current_term_id,
                    'depth' => $settings['depth'],
                )
            );
            if (empty($options)) return;
                
            $current = array(
                '#type' => 'select',
                '#options' => array('' => $this->_getDefaultText($bundle, $settings)) + $options,
                '#select2' => true,
                '#empty_value' => '',
                '#multiple' => false,
                '#entity_filter_form_type' => 'select',
            );
        }
            
        if (isset($facets)) {
            $this->_loadFacetCounts($current, $facets, $settings, $request);
        }
            
        return $current;
    }
    
    public function fieldFilterLabels(Field\IField $field, array $settings, $value, $form, $defaultLabel)
    {
        // Get taxonomy bundle associated with the field
        if (!$bundle = $field->getTaxonomyBundle()) return;

        if (!$entity = $this->_application->Entity_Entity($bundle->entitytype_name, $value, false)) return;
        
        return array($entity->getId() => $this->_application->H($entity->getTitle()));
    }
}