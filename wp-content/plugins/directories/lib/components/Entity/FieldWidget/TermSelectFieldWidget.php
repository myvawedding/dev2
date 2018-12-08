<?php
namespace SabaiApps\Directories\Component\Entity\FieldWidget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class TermSelectFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Select list', 'directories'),
            'field_types' => array('entity_terms'),
            'accept_multiple' => false,
            'default_settings' => array(
                'num' => 30,
                'depth' => 0,
            ),
            'repeatable' => true,
            'max_num_items' => 0, // unlimited
        );
    }
    
    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        // $fieldType is a field object when editing only
        if (!$fieldType instanceof \SabaiApps\Directories\Component\Field\IField
            || (!$taxonomy_bundle = $fieldType->getTaxonomyBundle())
        ) return;
        
        if (empty($taxonomy_bundle->info['is_hierarchical'])) {
            return array(
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
        }
        
        return array(
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

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        if (!$bundle = $field->getTaxonomyBundle()) return;
        
        $default_text = __('— Select —', 'directories');
        
        if (empty($bundle->info['is_hierarchical'])) {
            $options = $this->_application->Entity_TaxonomyTerms_html(
                $bundle->name,
                array(
                    'limit' => $settings['num'],
                    'depth' => 1,
                    'parent' => isset($settings['parent']) ? (int)$settings['parent'] : 0,
                    'language' => $language,
                    'return_array' => true,
                ),
                array('' => $default_text)
            );
        } else {
            $options = $this->_application->Entity_TaxonomyTerms_html(
                $bundle->name,
                array(
                    'prefix' => '—',
                    'depth' => $settings['depth'],
                    'language' => $language,
                    'return_array' => true,
                ),
                array('' => $default_text)
            );
            if (count($options) <= 1) return;
        }

        $can_assign = $this->_application->HasPermission('entity_assign_' . $bundle->name);
        return [
            '#type' => 'select',
            '#select2' => true,
            '#empty_value' => '',
            '#default_value' => isset($value) ? $value->getId() : null,
            '#multiple' => false,
            '#disabled' => !$can_assign,
            '#skip_validate_option' => $can_assign && $this->_application->getPlatform()->isAdmin(),
            '#options' => $options,
        ];
    }

    public function fieldWidgetEditDefaultValueForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        if (!$fieldType instanceof \SabaiApps\Directories\Component\Entity\Model\Field
            || (!$taxonomy_bundle = $fieldType->getTaxonomyBundle())
        ) return;
        
        return array(
            '#type' => 'select',
            '#options' => $this->_application->Entity_TaxonomyTerms_html(
                $taxonomy_bundle->name,
                [],
                array('' => __('— Select —', 'directories'))
            ),
        );
    }
}