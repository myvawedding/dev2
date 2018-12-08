<?php
namespace SabaiApps\Directories\Component\Voting\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

class RecalculateVotesHelper
{
    public function help(Application $application, Entity\Type\IEntity $entity, $fieldName)
    {
        // Make sure all the fields are loaded
        $application->Entity_LoadFields($entity, null, false, false);
        
        // Calculate results
        $results = $application->getModel(null, 'Voting')->getGateway('Vote')->getResults($entity->getBundleName(), $entity->getId(), $fieldName);

        // Field values for the entity
        if (!empty($results)) {
            $values = [];
            foreach (array_keys($results) as $name) {
                $values[] = array('name' => $name) + $results[$name];
            }
        } else {
            $values = false;
        }

        // Update field and return its value
        return $application->Entity_Save($entity, array($fieldName => $values))->getSingleFieldValue($fieldName);
    }
}