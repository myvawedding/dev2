<?php
namespace SabaiApps\Directories\Component\View\FieldFilter;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class TermListFieldFilter extends AbstractTermFieldFilter
{
    protected function _fieldFilterInfo()
    {
        $info = parent::_fieldFilterInfo();
        $info['label'] = __('Checkboxes', 'directories');
        $info['default_settings'] += array(
            'icon' => true,
            'icon_size' => 'sm',
            'andor' => 'OR',
            'visible_count' => 15,
        );
        return $info;
    }

    public function fieldFilterSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        $ret = parent::fieldFilterSettingsForm($field, $settings, $parents);
        
        if (!is_array($ret)) return;
        
        $taxonomy_bundle = $field->getTaxonomyBundle();
        if ($this->_application->Entity_BundleTypeInfo($taxonomy_bundle, 'entity_image')) {
            $ret += array(
                'icon' => array(
                    '#type' => 'checkbox',
                    '#title' => __('Show icon', 'directories'),
                    '#default_value' => !empty($settings['icon']),
                    '#weight' => 6,
                ),
                'icon_size' => array(
                    '#type' => 'select',
                    '#title' => __('Icon size', 'directories'),
                    '#options' => $this->_application->System_Util_iconSizeOptions(),
                    '#default_value' => $settings['icon_size'],
                    '#states' => array(
                        'visible' => array(
                            sprintf('input[name="%s[icon]"]', $this->_application->Form_FieldName($parents)) => array(
                                'type' => 'checked', 
                                'value' => true,
                            ),
                        ),
                    ),
                    '#weight' => 7,
                ),
            );
        }
        
        if (!empty($taxonomy_bundle->info['is_hierarchical'])) {
            $ret += array(
                'andor' => array(
                    '#title' => __('Match any or all', 'directories'),
                    '#type' => 'select',
                    '#options' => array('OR' => __('Match any', 'directories'), 'AND' => __('Match all', 'directories')),
                    '#default_value' => $settings['andor'],
                    '#states' => array(
                        'visible' => array(
                            sprintf('[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'checkboxes'),
                        ), 
                    ),
                    '#weight' => 20,
                ),
            );
        }
        $ret['visible_count'] = array(
            '#type' => 'slider',
            '#integer' => true,
            '#min_value' => 0,
            '#max_value' => 50,
            '#min_text' => __('Show all', 'directories'),
            '#title' => __('Number of options to display', 'directories'),
            '#description' => __('If there are more options than the number specified, those options are hidden until "more" link is clicked.', 'directories'),
            '#default_value' => $settings['visible_count'],
            '#weight' => 30,
            '#states' => array(
                'visible' => array(
                    sprintf('[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => ['checkboxes', 'radios']),
                ),
            ),
        );
        
        return $ret;
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
        
        if (false === $facets = $this->_getFacets($field, $settings, $query)) return;
        
        if (!isset($current)) {
            $options = array(
                'content_bundle' => $field->Bundle->type,
                'hide_empty' => !empty($settings['hide_empty']),
                'hide_count' => !empty($settings['hide_count']),
                'link' => false,
                'icon' => !empty($settings['icon']),
                'icon_size' => $settings['icon_size'],
                'return_array' => true,
                'prefix' => '',
            );
            if (empty($bundle->info['is_hierarchical'])) {
                if (!empty($current_term_id)) return; // term is already selected
            
                $options += array(
                    'limit' => $settings['num'],
                    'depth' => 1,
                );
            } else {                
                // Hierarchical taxonomy
                $options += array(
                    'depth' => $settings['depth'],
                    'parent' => $current_term_id,
                );
            }
            $list = $this->_application->Entity_TaxonomyTerms_html($bundle->name, $options);
            
            if (empty($list)) return;
        
            $current = array(
                '#type' => 'checkboxes',
                '#options' => $list,
                '#option_no_escape' => true,
                '#options_visible_count' => $settings['visible_count'],
                '#options_scroll' => true,
                '#entity_filter_form_type' => 'checkboxes',
            );
        }
        
        if (isset($facets)) {
            $this->_loadFacetCounts($current, $facets, $settings, $request);
        }
        
        return empty($current['#options']) ? null : $current;
    }

    public function fieldFilterLabels(Field\IField $field, array $settings, $value, $form, $defaultLabel)
    {
        // Get taxonomy bundle associated with the field
        if (!$bundle = $field->getTaxonomyBundle()) {
            return;
        }
        
        $ret = [];
        foreach ($this->_application->Entity_Entities($bundle->entitytype_name, (array)$value, false) as $entity) {
            $ret[$entity->getId()] = $this->_application->H($entity->getTitle());
        }
        
        return $ret;
    }
    
    protected function _getMatchAndOr(Field\IField $field, array $settings)
    {
        return $settings['andor'];
    }
}