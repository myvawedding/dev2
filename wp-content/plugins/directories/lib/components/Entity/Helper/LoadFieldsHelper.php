<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;

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
                    if (null !== $values) {
                        // Let the field type component for each field to work on values on load
                        $ifield_type->fieldTypeOnLoad($field, $values, $entity); 
                    }
                    $entity_field_values[$field_name] = $values;
                }
                // Init entity and let other components take action
                $entity->initFields($entity_field_values, $field_types_by_bundle[$bundle_name], !isset($fields));
                
                // Let other components modify entity
                $application->Action('entity_field_values_loaded', array($entity, $bundles[$bundle_name], $fields_by_bundle[$bundle_name], $cache));
            }
        }
    }
}