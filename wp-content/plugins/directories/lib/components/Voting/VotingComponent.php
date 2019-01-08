<?php
namespace SabaiApps\Directories\Component\Voting;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity;

class VotingComponent extends AbstractComponent implements
    System\IMainRouter,
    Field\ITypes,
    Field\IRenderers,
    Field\IFilters,
    Display\IButtons,
    Display\IStatistics,
    ITypes
{
    const VERSION = '1.2.19', PACKAGE = 'directories';

    protected static $_types = [];
    
    public static function interfaces()
    {
        return array('Dashboard\IPanels');
    }
    
    public static function description()
    {
        return 'Adds voting/rating/bookmarking features to content.';
    }
    
    public function onCoreComponentsLoaded()
    {
        $this->_application->setHelper('Voting_CanVote', array(__CLASS__, 'canVoteHelper'));
    }
    
    public static function canVoteHelper(Application $application, Entity\Type\IEntity $entity, $type, $isDownVote = false)
    {
        $info = $application->Voting_Types_impl($type)->votingTypeInfo();
        
        // Permission required?
        if (empty($info['require_permission'])) {
            // Allow if not anonymous
            return !$application->getUser()->isAnonymous();
        }
        
        // Allow guest?
        if (empty($info['allow_anonymous']) && $application->getUser()->isAnonymous()) return false;
        
        // Has vote permission?
        if (!$application->HasPermission('voting_' . $type . '_' . $entity->getBundleName())) return false;
        
        // Additional check for down voting
        if ($isDownVote) {
            return empty($info['require_down_permission'])
                || $application->HasPermission('voting_' . $type . '_down_' . $entity->getBundleName());
        }
        
        // Additional check for voting own item
        if ($application->Entity_IsAuthor($entity)) {
            return empty($info['require_own_permission'])
                || $application->HasPermission('voting_' . $type . '_own_' . $entity->getBundleName());
        }
            
        return true;
    }
    
    public function systemMainRoutes($lang = null)
    {
        $routes = [];
        foreach ($this->_application->getModel('FieldConfig', 'Entity')->type_is('voting_vote')->fetch()->with('Fields', 'Bundle') as $field_config) {                
            foreach ($field_config->Fields as $field) {
                if (!$field->Bundle
                    || !$this->_application->isComponentLoaded($field->Bundle->component)
                ) continue;

                $permalink_path = $this->_application->Entity_BundlePath($field->Bundle, true, $lang);
                $base_path = empty($field->Bundle->info['parent']) ? $permalink_path . '/:slug' : $permalink_path . '/:entity_id';
                if (!isset($routes[$base_path . '/vote'])) {
                    $routes[$base_path . '/vote'] = [];
                }
                $type = substr($field->getFieldName(), strlen('voting_'));
                $routes[$base_path . '/vote/' . $type] = array(
                    'controller' => 'VoteEntity',
                    'type' => Application::ROUTE_CALLBACK,
                    'data' => array(
                        'type' => $type,
                    ),
                    'callback_path' => 'vote_entity',
                    'access_callback' => true,
                );
            }
        }
        if ($this->_application->isComponentLoaded('Dashboard')) {
            // For dashboard
            $routes['/' . $this->_application->getComponent('Dashboard')->getSlug('dashboard') . '/:panel_name/votes'] = [
                'controller' => 'DashboardVotes',
                'callback_path' => 'dashboard_votes',
                'access_callback' => true,
            ];
        }

        return $routes;
    }

    public function systemOnAccessMainRoute(Context $context, $path, $accessType, array &$route)
    {
        switch ($path) {
            case 'vote_entity':
                if ($accessType === Application::ROUTE_ACCESS_LINK) {
                    $type = $route['data']['type'];
                    if (!$this->_application->Voting_CanVote($context->entity, $type)) {
                        if ($this->_application->getUser()->isAnonymous()) {
                            $context->setUnauthorizedError($this->_application->Entity_PermalinkUrl($context->entity));
                        } else {
                            $context->setError(__('You do not have the permission to perform this action.', 'directories'));
                        }
                        return false;
                    }
                    $context->voting_type = $type;
                }
                return true;
                
            case 'dashboard_votes':
                if ($accessType === Application::ROUTE_ACCESS_LINK) {
                    if ($context->dashboard_panel !== 'voting_votes') return false;
                    
                    try {
                        $this->_application->Voting_Types_impl($context->dashboard_panel_link);
                    } catch (Exception\IException $e) {
                        $this->_application->logError($e);
                        return false;
                    }
                }
                return true;
        }
    }

    public function systemMainRouteTitle(Context $context, $path, $titleType, array $route){}

    public function fieldGetTypeNames()
    {
        return array('voting_vote');
    }

    public function fieldGetType($name)
    {
        return new FieldType\FieldType($this->_application, $name);
    }
    
    public function fieldGetRendererNames()
    {
        return ['voting_rating'];
    }
    
    public function fieldGetRenderer($name)
    {
        switch ($name) {
            case 'voting_rating':
                return new FieldRenderer\RatingFieldRenderer($this->_application, $name);
        }
    }
    
    public function fieldGetFilterNames()
    {
        return array('voting_rating');
    }

    public function fieldGetFilter($name)
    {
        switch ($name) {
            case 'voting_rating':
                return new FieldFilter\RatingFieldFilter($this->_application, $name);
        }
    }

    public function onEntityPermissionsFilter(&$permissions, $bundle)
    {
        foreach (array_keys($this->_application->Voting_Types()) as $type) {
            if (!$type_info = $this->_isVotingPermissionRequired($bundle, $type)) continue;
            
            $guest_allowed = !empty($type_info['allow_anonymous']);
            $permissions['voting_' . $type] = array(
                'title' => sprintf($type_info['permission_label'], $bundle->getLabel()),
                'guest_allowed' => $guest_allowed,
                'default' => true,
                'weight' => 100,
            );
            if (!empty($type_info['own_permission_label'])) {
                $permissions['voting_' . $type . '_own'] = array(
                    'title' => sprintf($type_info['own_permission_label'], $bundle->getLabel()),
                    'guest_allowed' => $guest_allowed,
                    'weight' => 101,
                );
            }
            if (!empty($type_info['require_down_permission'])) {
                $permissions['voting_' . $type . '_down'] = array(
                    'title' => sprintf($type_info['down_permission_label'], $bundle->getLabel()),
                    'guest_allowed' => $guest_allowed,
                    'default' => true,
                    'weight' => 102,
                );
            }
        }
    }
    
    protected function _isVotingPermissionRequired($bundle, $type)
    {
        return (($voting = $this->_application->Entity_BundleTypeInfo($bundle, 'voting_enable'))
            && in_array($type, $voting)
            && ($type_impl = $this->_application->Voting_Types_impl($type))
            && ($type_info = $type_impl->votingTypeInfo())
            && !empty($type_info['require_permission'])
        ) ? $type_info : false; 
    }
    
    public function onEntityCreateBundlesSuccess($bundles)
    {
        $reload = false;
        foreach ($bundles as $bundle) {
            if ($voting = $this->_application->Entity_BundleTypeInfo($bundle, 'voting_enable')) {
                foreach ($voting as $type) {
                    if (!$type_impl = $this->_application->Voting_Types_impl($type, true)) continue;

                    if (!$label = $type_impl->votingTypeInfo('label_field')) {
                        $label = $type_impl->votingTypeInfo('label');
                    }
                    $this->_application->getComponent('Entity')->createEntityField(
                        $bundle,
                        'voting_' . $type,
                        array(
                            'type' => 'voting_vote',
                            'label' => $label,
                            'weight' => 99,
                            'max_num_items' => 1, // Only 1 entry per entity should be created
                        ),
                        true
                    );
                    $reload = true;
                }
            }
        }
        if ($reload) {
            // Reload system routing tables to reflect changes
            $this->_application->getComponent('System')->reloadRoutes($this);
        }
    }
    
    public function onEntityUpdateBundlesSuccess($bundles)
    {
        if (empty($bundles)) return;
        
        $this->onEntityCreateBundlesSuccess($bundles);
    }
    
    public function onEntityDeleteBundlesCommitted(array $bundles, $deleteContent)
    {
        if (empty($bundles) || empty($deleteContent)) return;
            
        $criteria = $this->getModel()->createCriteria('Vote')->bundleName_in(array_keys($bundles));
        $this->getModel()->getGateway('Vote')->deleteByCriteria($criteria);
    }
    
    public function displayGetButtonNames(Entity\Model\Bundle $bundle)
    {
        $ret = [];
        if ($voting = $this->_application->Entity_BundleTypeInfo($bundle, 'voting_enable')) {
            foreach ($voting as $type) {
                if ((!$type_impl = $this->_application->Voting_Types_impl($type, true))
                    || (!$names = $type_impl->votingTypeInfo('entity_button'))
                ) continue;
                
                if (is_array($names)) {
                    foreach ($names as $name) $ret[] = $name;
                } else {
                    $ret[] = 'voting_' . $type;
                }
            }
        }
        return $ret;
    }
    
    public function displayGetButton($name)
    {
        return new DisplayButton\DisplayButton($this->_application, $name);
    }
        
    public function displayGetStatisticNames(Entity\Model\Bundle $bundle)
    {
        $ret = [];
        if ($voting = $this->_application->Entity_BundleTypeInfo($bundle, 'voting_enable')) {
            foreach ($voting as $type) {
                if ((!$type_impl = $this->_application->Voting_Types_impl($type, true))
                    || (!$names = $type_impl->votingTypeInfo('entity_statistic'))
                ) continue;
                
                if (is_array($names)) {
                    foreach ($names as $name) $ret[] = $name;
                } else {
                    $ret[] = 'voting_' . $type;
                }
            }
        }
        return $ret;
    }
    
    public function displayGetStatistic($name)
    {
        return new DisplayStatistic\CountDisplayStatistic($this->_application, $name);
    }
    
    public function votingGetTypeNames()
    {
        return array('updown', 'bookmark', 'rating');
    }
    
    public function votingGetType($name)
    {
        switch ($name) {
            case 'updown':
                return new Type\UpdownType($this->_application, $name);
            case 'bookmark':
                return new Type\BookmarkType($this->_application, $name);
            case 'rating':
                return new Type\RatingType($this->_application, $name);
        }
    }
    
    public function dashboardGetPanelNames()
    {
        return array('voting_votes');
    }
    
    public function dashboardGetPanel($name)
    {
        return new DashboardPanel\VotesDashboardPanel($this->_application, $name);
    }
}