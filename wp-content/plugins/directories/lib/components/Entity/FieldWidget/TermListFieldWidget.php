<?php
namespace SabaiApps\Directories\Component\Entity\FieldWidget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class TermListFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Checkboxes', 'directories'),
            'field_types' => array('entity_terms'),
            'accept_multiple' => true,
            'max_num_items' => 0, // unlimited
            'default_settings' => array(
                'num' => 30,
                'depth' => 0,
            ),
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
        
        $default_value = null;
        if (!empty($value)) {
            $default_value = [];
            foreach (array_keys($value) as $key) {
                if (!$value[$key] instanceof \SabaiApps\Directories\Component\Entity\Type\IEntity) continue;
                
                $default_value[] = $value[$key]->getId();
            }
        }

        $can_assign = $this->_application->HasPermission('entity_assign_' . $bundle->name);
        $ret = array(
            '#type' => 'checkboxes',
            '#option_no_escape' => true,
            '#default_value' => $default_value,
            '#multiple' => false,
            '#disabled' => !$can_assign,
            '#skip_validate_option' => $can_assign && $this->_application->getPlatform()->isAdmin(),
        );
        
        if (empty($bundle->info['is_hierarchical'])) {
            $ret['#options'] = $this->_application->Entity_TaxonomyTerms_html(
                $bundle->name,
                array(
                    'limit' => $settings['num'],
                    'depth' => 1,
                    'return_array' => true,
                    'language' => $language,
                )
            );
            return $ret;
        }
        
        $ret['#options'] = $this->_application->Entity_TaxonomyTerms_html(
            $bundle->name,
            array(
                'depth' => $settings['depth'],
                'return_array' => true,
                'language' => $language,
            )
        );
        return $ret;
    }
}