<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Field\IField;

class LoadFieldsHelper
{    
    public function help(Application $application, $entityType, array $entities = null, $force = false, $cache = true, array $fields = null)
    {
        if ($entityType instanceof \SabaiApps\Directories\Component\Entity\Type\IEntity) {
            if ($entityType->isFieldsLoaded() && !$force) {
                return;
            }
            $entities = array($entityType->getId() => $entityType);
            $entityType = $entityType->getType();
        }
        if (!$force) {
            $entities_loaded = $application->Entity_FieldCache($entityType, $entities);
            $entities_to_load = array_diff_key($entities, array_flip($entities_loaded));
        } else {
            $entities_to_load = $entities;
        }
        if (!empty($entities_to_load)) {
            $this->_loadEntityFields($application, $entityType, $entities_to_load, $cache, $fields);
            if ($cache && !isset($fields)) {
                try {
                    $application->Entity_FieldCache_save($entityType, $entities_to_load);
                } catch (\Exception $e) {
                    $application->logError($e);
                }
            }
        }
    }

    protected function _loadEntityFields(Application $application, $entityType, array $entities, $cache, array $fields = null)
    {
        $entities_by_bundle = $field_values_by_bundle = $field_types_by_bundle = $fields_by_bundle = [];
        foreach ($entities as $entity_id => $entity) {     
            $entities_by_bundle[$entity->getBundleName()][$entity_id] = $entity;
        }
        $bundles = $application->Entity_Bundles(array_keys($entities_by_bundle));
        foreach (array_keys($bundles) as $bundle_name) {
            foreach ($application->Entity_Field($bundle_name) as $field) {
                $field_name = $field->getFieldName();
                if (isset($fields)
                    && !in_array($field_name, $fields)
                ) continue;
                
                $fields_by_bundle[$bundle_name][$field_name] = $field;
                $field_types_by_bundle[$bundle_name][$field_name] = $field->getFieldType();
            }
            $field_values_by_bundle[$bundle_name] = $application->Entity_Storage()
                ->fetchValues($entityType, array_keys($entities_by_bundle[$bundle_name]), array_keys($fields_by_bundle[$bundle_name]));
        }
  
        // Load field values
        foreach (array_keys($bundles) as $bundle_name) {
            foreach ($entities_by_bundle[$bundle_name] as $entity_id => $entity) {
                $entity_field_values = [];
                foreach ($application->Entity_Field($bundle_name) as $field) {
                    if ($field->isPropertyField()) continue; // do not call fieldTypeOnLoad() on property fields

                    if (!$ifield_type = $application->Field_Type($field->getFieldType(), true)) continue;

                    // Check whether or not the value for this field is cacheable
                    if ($cache && false === $ifield_type->fieldTypeInfo('cacheable')) continue;

                    $field_name = $field->getFieldName();
                    if (!isset($field_values_by_bundle[$bundle_name][$entity_id][$field_name])) {
                        $values = $ifield_type->fieldTypeInfo('load_empty') ? [] : null;
                    } else {
                        $values = $field_values_by_bundle[$bundle_name][$entity_id][$field_name];
                    }
                    $entity_field_values[$field_name] = $values;
                }

                foreach (array_keys($entity_field_values) as $field_name) {
                    $field = $application->Entity_Field($bundle_name, $field_name);
                    if (!$this->_checkFieldConditions($application, $field, $entity_field_values)) {
                        $entity_field_values[$field_name] = false; // always hide
                    } else {
                        if (null !== $entity_field_values[$field_name]) {
                            // Let the field type component for each field to work on values on load
                            $application->Field_Type($field->getFieldType())->fieldTypeOnLoad($field, $entity_field_values[$field_name], $entity);
                        }
                    }
                }
                // Init entity and let other components take action
                $entity->initFields($entity_field_values, $field_types_by_bundle[$bundle_name], !isset($fields));
                
                // Let other components modify entity
                $application->Action('entity_field_values_loaded', array($entity, $bundles[$bundle_name], $fields_by_bundle[$bundle_name], $cache));
            }
        }
    }

    protected function _checkFieldConditions(Application $application, IField $field, $values)
    {
        $conditions = $field->getFieldConditions();
        if ((isset($conditions['add']) && !$conditions['add'])
            || empty($conditions['rules'])
        ) return true;

        foreach ($conditions['rules'] as $rule) {
            if (strpos($rule['field'], ',')) {
                if (!$_rule = explode(',', $rule['field'])) continue;

                $field_name = $_rule[0];
                $_name = $_rule[1];
            } else {
                $field_name = $rule['field'];
                $_name = '';
            }

            if ((!$_field = $application->Entity_Field($field->bundle_name, $field_name))
                || (!$field_type = $application->Field_Type($_field->getFieldType(), true))
                || !$field_type instanceof \SabaiApps\Directories\Component\Field\Type\IConditionable
                || !$field_type->fieldConditionableInfo($_field)
                || (!$_rule = $field_type->fieldConditionableRule($_field, $rule['compare'], $rule['value'], $_name))
                || (!$_rule = $application->Filter('entity_field_condition_rule', $_rule, [$_field, $rule['compare'], $rule['value'], $_name, 'php']))
            ) continue;

            $field_value = isset($values[$field_name]) && is_array($values[$field_name]) ? $values[$field_name] : null;
            if ($field_type->fieldConditionableMatch($_field, $_rule, $field_value)) {
                // Matched
                if ($conditions['action']['match'] === 'any') {
                    return $conditions['action']['name'] === 'hide' ? false : true;
                }
            } else {
                // Not matched
                if ($conditions['action']['match'] === 'all') {
                    return $conditions['action']['name'] === 'hide' ? true : false;
                }
            }
        }

        if ($conditions['action']['match'] === 'any') {
            // None matched
            return $conditions['action']['name'] === 'hide' ? true : false;
        } else {
            // All matched
            return $conditions['action']['name'] === 'hide' ? false : true;
        }
    }
}