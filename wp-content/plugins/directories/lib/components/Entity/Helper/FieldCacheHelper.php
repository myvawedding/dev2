<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;

class FieldCacheHelper
{   
    public function help(Application $application, $entityType, array $entities)
    {
        $platform = $application->getPlatform();
        $loaded = [];
        foreach (array_keys($entities) as $entity_id) {
            if ($cache = $platform->getOption('_entity_field_' . $entityType . '_' . $entity_id)) {
                $entities[$entity_id]->initFields($cache[0], $cache[1]);
                $loaded[] = $entity_id;
            }
        }
        return $loaded;
    }
    
    public function save(Application $application, $entityType, array $entities)
    {
        $platform = $application->getPlatform();
        foreach ($entities as $entity_id => $entity) {
            $platform->setOption(
                '_entity_field_' . $entityType . '_' . $entity_id,
                array($entity->getFieldValues(), $entity->getFieldTypes()),
                false // do not autoload
             );
        }
    }

    public function remove(Application $application, $entityType, array $entityIds)
    {
        $platform = $application->getPlatform();
        foreach ($entityIds as $entity_id) {
            $platform->deleteOption('_entity_field_' . $entityType . '_' . $entity_id);
        }
    }

    public function clean(Application $application, $entityType = null)
    {
        $application->getPlatform()->clearOptions('_entity_field_' . $entityType . '_');
    }
}