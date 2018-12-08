<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

class TranslationsHelper
{
    public function help(Application $application, IEntity $entity, $loadEntityFields = true)
    {
        $ret = [];
        if ($entity_ids = $this->ids($application, $entity)) {
            if ($entities = $application->Entity_Entities($entity->getType(), $entity_ids, $loadEntityFields)) {
                foreach ($entity_ids as $lang => $entity_id) {
                    if (isset($entities[$entity_id])) {
                        $ret[$lang] = $entities[$entity_id];
                    }
                }
            }
        }
        
        return $ret;
    }
    
    public function ids(Application $application, IEntity $entity)
    {
        if (!$application->getPlatform()->isTranslatable($entity->getType(), $entity->getBundleName())
            || (!$languages = $application->getPlatform()->getLanguages())
            || count($languages) <= 1
        ) return []; 
        
        $entity_ids = [];
        foreach ($languages as $lang) {
            if (($entity_id = $application->getPlatform()->getTranslatedId($entity->getType(), $entity->getBundleName(), $entity->getId(), $lang))
                && $entity_id != $entity->getId()
            ) {
                $entity_ids[$lang] = $entity_id;
            }
        }
        
        return $entity_ids;
    }
}