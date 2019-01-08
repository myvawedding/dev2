<?php
namespace SabaiApps\Directories\Component\Claiming;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Display;

class ClaimingComponent extends AbstractComponent implements
    Entity\IBundleTypes,
    Field\ITypes,
    Field\IWidgets,
    Display\ILabels,
    Display\IButtons
{
    const VERSION = '1.2.19', PACKAGE = 'directories-pro';
    
    public static function interfaces()
    {
        return array('WordPressContent\INotifications');
    }
    
    public static function description()
    {
        return 'Allows listing owners to claim their listings and get verified.';
    }

    public function fieldGetTypeNames()
    {
        return array('claiming_status');
    }
    
    public function fieldGetType($name)
    {
        return new FieldType\StatusFieldType($this->_application, $name);
    }
    
    public function fieldGetWidgetNames()
    {
        return array('claiming_status');
    }
    
    public function fieldGetWidget($name)
    {
        return new FieldWidget\StatusFieldWidget($this->_application, $name);
    } 
    
    public function displayGetLabelNames(Entity\Model\Bundle $bundle)
    {
        $ret = [];
        if ($bundle->type === 'claiming_claim') {
            $ret[] = 'claiming_status';
        }
        return $ret;
    }
    
    public function displayGetLabel($name)
    {
        return new DisplayLabel\StatusDisplayLabel($this->_application, $name);
    }
    
    public function displayGetButtonNames(Entity\Model\Bundle $bundle)
    {
        return empty($bundle->info['claiming_enable']) ? [] : ['claiming_claim'];
    }
    
    public function displayGetButton($name)
    {
        return new DisplayButton\ClaimEntityDisplayButton($this->_application, $name);
    }
    
    public function entityGetBundleTypeNames()
    {        
        return array('claiming_claim');
    }
    
    public function entityGetBundleType($name)
    {
        return new EntityBundleType\ClaimEntityBundleType($this->_application, $name);
    }
    
    public function onDirectoryContentTypeSettingsFormFilter(&$form, $directoryType, $contentType, $info, $settings, $parents, $submitValues)
    {
        if (!isset($info['claiming_enable'])
            || !empty($info['parent'])
            || !empty($info['is_taxonomy'])
        ) return;
        
        $form['claiming_enable'] = array(
            '#type' => 'checkbox',
            '#title' => __('Enable claims', 'directories-pro'),
            '#default_value' => !empty($settings['claiming_enable']) || is_null($settings),
            '#horizontal' => true,
        );
    }
    
    public function onDirectoryContentTypeInfoFilter(&$info, $contentType, $settings = null)
    {        
        if (!isset($info['claiming_enable'])) return;
        
        if (!empty($info['is_taxonomy'])
            || !empty($info['parent'])
        ) {
            unset($info['claiming_enable']);
        }
        
        if (isset($settings['claiming_enable']) && !$settings['claiming_enable']) {
            $info['claiming_enable'] = false;
        }
    }
    
    public function onEntityBundlesInfoFilter(&$bundles, $componentName, $group)
    {
        foreach (array_keys($bundles) as $bundle_type) {
            $info =& $bundles[$bundle_type];
            
            if (empty($info['claiming_enable'])
                || !empty($info['is_taxonomy'])
                || !empty($info['parent'])
            ) continue;

            // Add claim bundle
            if (!isset($bundles['claiming_claim'])) { // may already set if updating or importing
                $bundles['claiming_claim'] = [];
            }
            $bundles['claiming_claim']['parent'] = $bundle_type; // must be bundle type for Entity component to create parent field
            $bundles['claiming_claim'] += $this->entityGetBundleType('claiming_claim')->entityBundleTypeInfo();
            $bundles['claiming_claim']['properties']['parent']['label'] = $info['label_singular'];
            
            return; // there should be only one bundle with claiming enabled in a group
        }
        
        // No bundle with claiming enabled found, so make sure the claiming_claim bundle is not assigned
        unset($bundles['claiming_claim']);
    }
    
    public function onEntityBundleInfoKeysFilter(&$keys)
    {
        $keys[] = 'claiming_enable';
    }
    
    public function onEntityCreateEntitySuccess($bundle, $entity, $values, $extraArgs)
    {
        if ($bundle->type !== 'claiming_claim'
            || (!$parent_entity = $this->_application->Entity_ParentEntity($entity, false))
        ) return;
        
        if (in_array($entity->getSingleFieldValue('claiming_status'), array('approved', 'rejected'))) {
            $this->_onClaimApprovedOrRejected($entity, $parent_entity);
        } else {
            $this->_application->Action('claiming_claim_pending', array($entity, $parent_entity));
        }
    }
    
    public function onEntityUpdateEntitySuccess($bundle, $entity, $oldEntity, $values, $extraArgs)
    {
        if ($bundle->type !== 'claiming_claim'
            || empty($values['claiming_status'])
            || in_array($oldEntity->getSingleFieldValue('claiming_status'), array('approved', 'rejected')) // make sure has not previously been approved/rejected
            || !in_array($entity->getSingleFieldValue('claiming_status'), array('approved', 'rejected')) // make sure has been approved/rejected with this update
            || (!$parent_entity = $this->_application->Entity_ParentEntity($entity, false))
        ) return;
        
        $this->_onClaimApprovedOrRejected($entity, $parent_entity);
    }
    
    protected function _onClaimApprovedOrRejected(Entity\Type\IEntity $claim, Entity\Type\IEntity $claimedEntity)
    {
        if ($claim->getSingleFieldValue('claiming_status') === 'approved') {
            // Update author of the parent entity to that of the claim
            $this->_application->Entity_Save($claimedEntity, array('author' => $claim->getAuthorId()));
            
            // Claim translated posts
            foreach ($this->_application->Entity_Translations($claimedEntity, false) as $entity) {
                if ($entity->getAuthorId() !== $claim->getAuthorId()) {
                    $this->_application->Entity_Save($entity, array('author' => $claim->getAuthorId()));
                }
            }                
            $this->_application->Action('claiming_claim_approved', array($claim, $claimedEntity));
        } else {                
            $this->_application->Action('claiming_claim_rejected', array($claim, $claimedEntity));
        }
    }
    
    public function onEntityIsClaimingClaimRoutableFilter(&$result, $bundle, $action, Entity\Type\IEntity $entity = null)
    {
        if ($result === false) return;
        
        if ($action === 'add') {
            // Do not allow claiming if target entity already has an author or is not claimable
            if ($entity->getAuthorId()) {
                $result = false;
            }
        } elseif ($action === 'edit') {
            // Do not allow editing of already approved/rejected claims
            if (in_array($entity->getSingleFieldValue('claiming_status'), ['approved', 'rejected'])) {
                $result = false;
            }
        }
    }
    
    public function wpGetNotificationNames()
    {
        return array(
            'claiming_pending',
            'claiming_approved',
            'claiming_rejected',
        );
    }
    
    public function wpGetNotification($name)
    {
        return new WordPressNotification\ClaimWordPressNotification($this->_application, $name);
    }
    
    public function onClaimingClaimPending($claim, Entity\Type\IEntity $claimedEntity)
    {
        if ($this->_application->isComponentLoaded('WordPress')) {
            $this->_application->WordPressContent_Notifications_send('claiming_pending', $claim, $claimedEntity);
        }
    }
    
    public function onClaimingClaimApproved($claim, Entity\Type\IEntity $claimedEntity)
    {
        if ($this->_application->isComponentLoaded('WordPress')) {
            $this->_application->WordPressContent_Notifications_send('claiming_approved', $claim, $claimedEntity);
        }
    }
    
    public function onClaimingClaimRejected($claim, Entity\Type\IEntity $claimedEntity)
    {
        if ($this->_application->isComponentLoaded('WordPress')) {
            $this->_application->WordPressContent_Notifications_send('claiming_rejected', $claim, $claimedEntity);
        }
    }

    public function onFrontendsubmitGuestAllowedFilter(&$allowed, $bundleType, $action)
    {
        if ($bundleType === 'claiming_claim' && $action === 'add') $allowed = false;
    }
}