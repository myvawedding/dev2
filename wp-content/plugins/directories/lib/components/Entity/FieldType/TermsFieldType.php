<?php
namespace SabaiApps\Directories\Component\Entity\FieldType;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Application;

class TermsFieldType extends Field\Type\AbstractType implements
    Field\Type\IQueryable,
    Field\Type\IHumanReadable,
    Field\Type\IConditionable
{    
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Taxonomy Terms', 'directories'),
            'entity_types' => array('post'),
            'default_renderer' => 'entity_terms',
            'creatable' => false,
            'disablable' => false,
            'icon' => strpos($this->_name, 'tag') !== false ? 'fas fa-tag' : 'far fa-folder-open',
        );
    }
    
    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'value' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'unsigned' => true,
                    'was' => 'value',
                    'default' => 0,
                ),
                'auto' => array(
                    'type' => Application::COLUMN_BOOLEAN,
                    'notnull' => true,
                    'unsigned' => true,
                    'was' => 'auto',
                    'default' => false,
                ),
            ),
        );
    }

    public function fieldTypeOnSave(Field\IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $ret = $term_ids = [];
        if ($field->getFieldWidget() === 'entity_tag_term') {
            foreach ($values as $value) {
                if (is_array($value)) {  // tagging
                    $term_ids = $value;
                } elseif (!empty($value)) {
                    $term_ids[] = $value;
                }
            }
        } else {
            $term_ids = $values;
        }
        foreach ($term_ids as $term_id) {
            if (is_array($term_id)) {
                if (empty($term_id['value'])) continue;
                
                $ret[]['value'] = $term_id['value'];
                $ret[]['auto'] = !empty($term_id['auto']);
            } else {
                if (empty($term_id)) continue;
                
                $ret[]['value'] = $term_id;
            }
        }
        return $ret;
    }

    public function fieldTypeOnLoad(Field\IField $field, array &$values, Entity\Type\IEntity $entity)
    {
        if (!$taxonomy_bundle = $field->getTaxonomyBundle()) return;
        
        $entity_ids = [];
        foreach ($values as $value) {
            if (!empty($value['auto'])) continue; // exclude the ones auto saved for facet counts
            
            $entity_ids[$value['value']] = $value['value'];
        }
        $values = [];
        $is_hierarchical = !empty($taxonomy_bundle->info['is_hierarchical']);
        $fields_to_load = [];
        if (!empty($taxonomy_bundle->info['entity_image'])) {
            $fields_to_load['image'] = $taxonomy_bundle->info['entity_image'];
        } elseif (!empty($taxonomy_bundle->info['entity_icon'])) {
            $fields_to_load['icon'] = $taxonomy_bundle->info['entity_icon'];
        }
        if (!empty($taxonomy_bundle->info['entity_color'])) {
            $fields_to_load['color'] = $taxonomy_bundle->info['entity_color'];
        }
        $entity_type = $taxonomy_bundle->entitytype_name;
        foreach ($this->_application->Entity_Entities($entity_type, $entity_ids, $fields_to_load, true) as $entity_id => $entity) {
            $parent_ids = null;
            if ($is_hierarchical
                && $entity->getParentId()
            ) {
                $parent_ids = $this->_application->Entity_Types_impl($entity_type)->entityTypeParentEntityIds($entity_id, $taxonomy_bundle->name);
            }
            $fields_to_load_from_parent = [];
            if (isset($fields_to_load['image'])) {
                if ($image = $this->_application->Entity_Image($entity, 'icon', $fields_to_load['image'])) {
                    $entity->setCustomProperty('icon_src', $image); // set as custom property so it can be cached
                }
            } elseif (isset($fields_to_load['icon'])) {
                if ($icon = $this->_application->Entity_Icon($entity, false)) {
                    $entity->setCustomProperty('icon', $icon); // set as custom property so it can be cached
                } else {
                    if ($parent_ids) $fields_to_load_from_parent['icon'] = $fields_to_load['icon']; // not found, so will try loading from parent
                }
            }
            if (isset($fields_to_load['color'])) {
                if ($color = $this->_application->Entity_Color($entity)) {
                    $entity->setCustomProperty('color', $color); // set as custom property so it can be cached
                } else {
                    if ($parent_ids) $fields_to_load_from_parent['color'] = $fields_to_load['color']; // not found, so will try loading from parent
                }
            }
            if ($parent_ids) {
                $parent_slugs = $parent_titles = [];
                $parent_ids = array_reverse($parent_ids); // reverse to get data from clsoest parent
                foreach ($this->_application->Entity_Entities($entity_type, $parent_ids, $fields_to_load_from_parent, true) as $parent_id => $parent_entity) {
                    if (isset($fields_to_load_from_parent['icon'])) {
                        if ($icon = $this->_application->Entity_Icon($parent_entity, false)) {
                            $entity->setCustomProperty('parent_icon', $icon); // set as custom property so it can be cached
                            unset($fields_to_load_from_parent['icon']);
                        }
                    }
                    if (isset($fields_to_load_from_parent['color'])) {
                        if ($color = $this->_application->Entity_Color($parent_entity)) {
                            $entity->setCustomProperty('color', $color); // set as custom property so it can be cached
                            unset($fields_to_load_from_parent['color']);
                        }
                    }
                    $parent_slugs[$parent_id] = $parent_entity->getSlug();
                    $parent_titles[$parent_id] = $parent_entity->getTitle();
                }
                $entity->setCustomProperty('parent_slugs', array_reverse($parent_slugs))
                    ->setCustomProperty('parent_titles', array_reverse($parent_titles));
            }
            $values[] = $entity;
        }
    }
    
    public function fieldTypeIsModified($field, $valueToSave, $currentLoadedValue)
    {        
        $current = $new = [];
        if (!empty($currentLoadedValue)) {
            foreach ($currentLoadedValue as $value) {
                $current[] = (int)$value->getId();
            }
        }
        foreach ($valueToSave as $value) {
            $new[] = (int)$value['value'];
        }
        return $current !== $new;
    }
    
    public function fieldQueryableInfo(Field\IField $field)
    {
        return array(
            'example' => 'term,3,another-term,12',
            'tip' => __('Enter taxonomy term IDs or slugs (may be mixed) separated with commas. Enter "_current_" for taxonomy terms of the curernt post if any.', 'directories'),
        );
    }
    
    public function fieldQueryableQuery(Field\Query $query, $fieldName, $paramStr, Entity\Model\Bundle $bundle = null)
    {
        if (!$term_ids = $this->_queryableParams($paramStr)) return;
        
        $include = $exclude = $slugs = [];
        foreach (array_keys($term_ids) as $k) {
            // ID
            if (is_numeric($term_ids[$k])) {
                $include[] = $term_ids[$k];
                continue;
            }
            
            // Current post
            if ($term_ids[$k] === '_current_') {
                if (isset($GLOBALS['drts_entity'])) {
                    if ($GLOBALS['drts_entity']->isTaxonomyTerm()) {
                        if ($GLOBALS['drts_entity']->getBundleType() === $fieldName) {
                            $include[] = $GLOBALS['drts_entity']->getId();
                        }
                    } else {
                        if ($terms = $GLOBALS['drts_entity']->getFieldValue($fieldName)) {
                            foreach ($terms as $term) {
                                $include[] = $term->getId();
                            }
                        }
                    }
                }
                continue;
            }
            
            // Slug
            $slugs[] = $term_ids[$k];
        }
        if (!empty($slugs)
            && ($taxonomy_bundle = $this->_application->Entity_Bundle($fieldName, $bundle->component, $bundle->group))
        ) {
            foreach ($this->_application->Entity_Types_impl($taxonomy_bundle->entitytype_name)->entityTypeEntitiesBySlugs($taxonomy_bundle->name, $slugs) as $term) {
                $include[] = $term->getId();
            }
        }

        if (!empty($include)) {
            $query->taxonomyTermIdIn($fieldName, $include, !$this->_application->Entity_BundleTypeInfo($fieldName, 'is_hierarchical'));
        }
    }
    
    public function fieldHumanReadableText(Field\IField $field, Entity\Type\IEntity $entity, $separator = null, $key = null)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return '';
        
        $ret = [];
        foreach ($values as $term) {
            $ret[] = $term->getTitle();
        }
        
        return implode(isset($separator) ? $separator : PHP_EOL, $ret);
    }
    
    public function fieldConditionableInfo(Field\IField $field)
    {
        if (!$this->_isTaxonomyConditionable($field)) return;
        
        return [
            '' => [
                'compare' => ['value', '!value', 'one', 'empty', 'filled'],
                'tip' => __('Enter taxonomy term IDs and/or slugs separated with commas.', 'directories'),
                'example' => '1,5,arts,17',
            ],
        ];
    }
    
    public function fieldConditionableRule(Field\IField $field, $compare, $value = null, $_name = '')
    {
        switch ($compare) {
            case 'value':
            case '!value':
            case 'one':
                $value = trim($value);
                if (strpos($value, ',')) {
                    if (!$value = explode(',', $value)) return;
                    
                    $value = array_map('trim', $value);
                }
                return ['type' => $compare, 'value' => $value];
            case 'empty':
                return ['type' => 'filled', 'value' => false];
            case 'filled':
                return ['type' => 'empty', 'value' => false];
            default:
                return;
        }
    }

    protected function _isTaxonomyConditionable(Field\IField $field)
    {
        return (($taxonomy_bundle = $field->getTaxonomyBundle())
            && !empty($taxonomy_bundle->info['is_hierarchical'])
            && false !== $this->_application->Entity_BundleTypeInfo($taxonomy_bundle, 'taxonomy_assignable')
        ) ? $taxonomy_bundle : false;
    }
}