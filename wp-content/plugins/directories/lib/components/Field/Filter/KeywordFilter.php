<?php
namespace SabaiApps\Directories\Component\Field\Filter;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity;

class KeywordFilter extends AbstractFilter
{    
    protected function _fieldFilterInfo()
    {
        return array(
            'label' => __('Keyword input field', 'directories'),
            'field_types' => array('string', 'text', 'wp_post_content', 'entity_title'),
            'default_settings' => array(
                'min_length' => 3,
                'match' => 'all',
                'placeholder' => null,
            ),
        );
    }

    public function fieldFilterSettingsForm(IField $field, array $settings, array $parents = [])
    {
        return array(    
            'min_length' => array(
                '#type' => 'slider',
                '#title' => __('Min. length of keywords in characters', 'directories'),
                '#default_value' => $settings['min_length'],
                '#integer' => true,
                '#min_value' => 1,
                '#max_value' => 10,
            ),
            'match' => array(
                '#type' => 'select',
                '#title' => __('Default match type', 'directories'),
                '#options' => array(
                    'any' => __('Match any', 'directories'),
                    'all' => __('Match all', 'directories'),
                ),
                '#default_value' => $settings['match'],
            ),
            'placeholder' => array(
                '#type' => 'textfield',
                '#title' => __('Placeholder text', 'directories'),
                '#default_value' => $settings['placeholder'],
            ),
        );
    }
    
    public function fieldFilterForm(IField $field, $filterName, array $settings, $request = null, Entity\Type\Query $query = null, array $current = null, array $parents = [])
    {
        return array(
            '#type' => 'search',
            '#placeholder' => $settings['placeholder'],
            '#entity_filter_form_type' => 'textfield',
            '#skip_validate_text' => true,
        );
    }
    
    public function fieldFilterIsFilterable(IField $field, array $settings, &$value, array $requests = null)
    {
        if (!is_string($value) || !strlen($value)) return false;
        
        $keywords = $this->_application->Keywords($value, $settings['min_length']);
        
        if (empty($keywords[0])) return false; // no valid keywords
        
        $value = $keywords[0];
        
        return true;
    }
    
    public function fieldFilterDoFilter(Query $query, IField $field, array $settings, $value, array &$sorts)
    {
        $field_name = $field->isPropertyField() ? 'content' : $field->getFieldName();
        if ($settings['match'] === 'any' && count($value) > 1) {
            $query->startCriteriaGroup('OR');
            foreach ($value as $keyword) {
                $query->fieldContains($field_name, $keyword);
            }
            $query->finishCriteriaGroup();
        } else {
            foreach ($value as $keyword) {
                $query->fieldContains($field_name, $keyword);
            }
        }
    }
    
    public function fieldFilterLabels(IField $field, array $settings, $value, $form, $defaultLabel)
    {
        return array('' => $this->_application->H(count($value) > 1 ? $defaultLabel : '"' . array_shift($value) . '"'));
    }
}