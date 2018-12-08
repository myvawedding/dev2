<?php
namespace SabaiApps\Directories\Component\Voting\Helper;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Application;

/**
 * Makes sure voting data is loaded only once before display is rendered
 */
class LoadEntitiesHelper
{
    private $_loaded = [];
    
    public function help(Application $application, Entity\Model\Bundle $bundle, array $entities)
    {
        $entity_ids = array_keys($entities);
        $key = md5($bundle->name . implode(',', $entity_ids));

        if (isset($this->_loaded[$key])) return;
        
        $this->_loaded[$key] = true;
        
        if ($application->getUser()->isAnonymous()) return;
            
        $votes = $application->getModel(null, 'Voting')->getGateway('Vote')->getVotes(
            $bundle->name,
            $entity_ids,
            $application->getUser()->id,
            array('voting_updown', 'voting_rating', 'voting_bookmark')
        );
        foreach ($votes as $field_name => $_votes) {
            foreach ($_votes as $entity_id => $value) {
                $entities[$entity_id]->data[$field_name . '_voted'] = $value;
            }
        }
    }
}