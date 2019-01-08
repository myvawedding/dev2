<?php
namespace SabaiApps\Directories\Component\FrontendSubmit;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\CSV;
use SabaiApps\Framework\User\AbstractIdentity;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Application;

class FrontendSubmitComponent extends AbstractComponent implements
    System\IMainRouter,
    Field\ITypes,
    Field\IWidgets,
    System\ISlugs,
    Display\IButtons,
    CSV\IExporters,
    CSV\IImporters
{
    const VERSION = '1.2.19', PACKAGE = 'directories-frontend';
    
    public static function description()
    {
        return 'Enables registered and non-registered users to submit content from the frontend.';
    }
    
    public function systemMainRoutes($lang = null)
    {
        $routes = array(
            '/' . $this->getSlug('login', $lang) => array(
                'controller' => 'LoginOrRegister',
                'access_callback' => true,
                'callback_path' => 'login',
            ),
        );
        foreach ($this->_application->Entity_Bundles() as $bundle) {
            if (!$this->_application->isComponentLoaded($bundle->component)
                || !empty($bundle->info['is_taxonomy'])
            ) continue;

            if (empty($bundle->info['parent'])) {
                $routes['/' . $this->_application->FrontendSubmit_AddEntitySlug($bundle, $lang)] = array(
                    'controller' => 'AddEntity',
                    'access_callback' => true,
                    'title_callback' => true,
                    'callback_path' => 'add_entity',
                    'data' => array(
                        'bundle_type' => $bundle->type,
                    ),
                );
            } else {
                $routes['/' . $this->_application->FrontendSubmit_AddEntitySlug($bundle, $lang)] = array(
                    'controller' => 'AddChildEntity',
                    'access_callback' => true,
                    'title_callback' => true,
                    'callback_path' => 'add_child_entity',
                    'data' => array(
                        'bundle_type' => $bundle->type,
                        'component' => $bundle->component,
                        'group' => $bundle->group,
                    ),
                );
            }
        }
        
        return $routes;
    }
    
    protected function _getBundle(array $route)
    {
        return $this->_application->Entity_Bundle($route['data']['bundle_type'], $route['data']['component'], $route['data']['group']);
    }

    public function systemOnAccessMainRoute(Context $context, $path, $accessType, array &$route)
    {
        switch ($path) {
            case 'add_child_entity':
                if (!isset($context->child_bundle)) {
                    if (!$bundle = $this->_getBundle($route)) return false;

                    $context->child_bundle = $bundle;
                }
            case 'add_entity':
                if ($accessType === Application::ROUTE_ACCESS_LINK) {
                    $context->bundle_type = $route['data']['bundle_type'];
                }
                return true;
            case 'login':
                if ($accessType === Application::ROUTE_ACCESS_LINK
                    || $this->_application->getUser()->isAnonymous()
                ) return true;

                // Redirect to frontend dashboard if enabled
                if ($this->_application->isComponentLoaded('Dashboard')
                    && ($dashboard_slug = $this->_application->getComponent('Dashboard')->getSlug('dashboard'))
                ) {
                    $context->setRedirect('/' . $dashboard_slug);
                }
                return false;
        }
    }

    public function systemMainRouteTitle(Context $context, $path, $titleType, array $route)
    {
        switch ($path) {
            case 'add_child_entity_non_public':
            case 'add_child_entity':
                return $context->child_bundle->getLabel('add');
            case 'add_entity':
                return $this->_application->FrontendSubmit_AddEntitySlug_title($context->bundle_type);
        }
    }
    
    public function fieldGetTypeNames()
    {
        return ['frontendsubmit_guest'];
    }
    
    public function fieldGetType($name)
    {
        switch ($name) {
            case 'frontendsubmit_guest':
                return new FieldType\GuestFieldType($this->_application, $name);
        }
    }
    
    public function fieldGetWidgetNames()
    {
        return ['frontendsubmit_guest'];
    }
    
    public function fieldGetWidget($name)
    {
        switch ($name) {
            case 'frontendsubmit_guest':
                return new FieldWidget\GuestFieldWidget($this->_application, $name);
        }
    }
    
    public function systemSlugs()
    {
        return array(
            'login' => array(
                'admin_title' => __('Login/Registration', 'directories-frontend'),
                'title' => __('Login or Register', 'directories-frontend'),
                'wp_shortcode' => 'drts-frontend-login',
            ),
        );
    }
        
    public function onCoreLoginUrlFilter(&$url, $redirect)
    {
        if ($this->hasSlug('login')) {
            $url = (string)$this->_application->Url('/' . $this->getSlug('login'), array('redirect_to' => $redirect));
        }
    }
    
    public function onSystemSlugsFilter(&$slugs)
    {
        foreach ($this->_application->Entity_Bundles() as $bundle) {
            if (!$this->_application->isComponentLoaded($bundle->component)
                || !empty($bundle->info['is_taxonomy'])
                || !empty($bundle->info['parent'])
                || !empty($bundle->info['internal'])
            ) continue;
            
            $add_slug = $this->_application->FrontendSubmit_AddEntitySlug_name($bundle->type);
            if (isset($slugs[$this->_name][$add_slug])) continue;
            
            $info = $this->_application->Entity_BundleTypeInfo($bundle);
            $slugs[$this->_name][$add_slug] = array(
                'slug' => $add_slug,
                'admin_title' => $info['label_add'],
                'title' => $info['label_add'],
                'component' => $this->_name,
                'wp_shortcode' => 'drts-' . $add_slug . '-form',
             );
        }
    }
    
    public function onEntityIsAuthorFilter(&$false, Entity\Type\IEntity $entity)
    {
        if (!$entity->getAuthorId() // must be a guest post
            && ($guid = $entity->getSingleFieldValue('frontendsubmit_guest', 'guid'))
            && ($cookie_guids = $this->_application->FrontendSubmit_GuestAuthorCookie_guids())
            && in_array($guid, $cookie_guids)
        ) {
            $false = true;
        }
    }
    
    public function onEntityAuthorFilter(AbstractIdentity $author, Entity\Type\IEntity $entity)
    {
        if ($author->isAnonymous()
            && $author->name === null
            && ($guest = $entity->getSingleFieldValue('frontendsubmit_guest'))
        ) {
            foreach (['name', 'email', 'url'] as $key) {
                if (strlen($guest[$key])) $author->$key = $guest[$key];
            }
        }
    }
    
    protected function _onEntityCreateBundlesSuccess(array $bundles, $update = false)
    {
        foreach ($bundles as $bundle) {
            if (empty($bundle->info['is_taxonomy'])
                && $this->_application->Entity_BundleTypeInfo($bundle, 'frontendsubmit_guest')
            ) {
                $this->_application->getComponent('Entity')->createEntityField(
                    $bundle,
                    'frontendsubmit_guest',
                    array(
                        'type' => 'frontendsubmit_guest',
                        'max_num_items' => 1,
                    )
                );
            }
        }
    }
    
    public function onEntityCreateBundlesSuccess(array $bundles)
    {
        $this->_onEntityCreateBundlesSuccess($bundles);
    }
    
    public function onEntityUpdateBundlesSuccess(array $bundles)
    {
        $this->_onEntityCreateBundlesSuccess($bundles, true);
    }
    
    public function onEntityPermissionsFilter(&$permissions, Entity\Model\Bundle $bundle)
    {
        // Enable some guest permissions
        if (empty($bundle->info['is_taxonomy'])) {
            if (!empty($bundle->info['public'])) {
                $permissions['entity_create']['guest_allowed'] = true;
                $permissions['entity_publish']['guest_allowed'] = true;
                //$permissions['entity_edit']['guest_allowed'] = true;
                //$permissions['entity_edit_published']['guest_allowed'] = true;
                //$permissions['entity_delete']['guest_allowed'] = true;
                //$permissions['entity_delete_published']['guest_allowed'] = true;
            }
        } else {
            $permissions['entity_assign']['guest_allowed'] = true;
        }
    }
    
    public function displayGetButtonNames(Entity\Model\Bundle $bundle)
    {
        $ret = [];
        if (empty($bundle->info['is_taxonomy'])) {
            if (empty($bundle->info['parent'])) {
                foreach ($this->_application->Entity_BundleTypes_children($bundle->type) as $bundle_type) {
                    if (!$this->_application->Entity_Bundle($bundle_type, $bundle->component, $bundle->group)) continue;
                    
                    $ret[] = 'frontendsubmit_add_' . $bundle_type;
                }
            }
        }
        return $ret;
    }
    
    public function displayGetButton($name)
    {
        return new DisplayButton\AddEntityDisplayButton($this->_application, $name);
    }
    
    public function onDirectoryAdminSettingsFormFilter(&$form)
    {
        $form['#tabs'][$this->_name] = array(
            '#title' => __('Frontend Submit', 'directories-frontend'),
            '#weight' => 16,
        );
        $form[$this->_name] = array(
            '#tree' => true,
            '#component' => $this->_name,
            '#tab' => $this->_name,
        ) + $this->_application->FrontendSubmit_SettingsForm($this->_config, array($this->_name));
    }

    public function onDirectoryContentTypeSettingsFormFilter(&$form, $directoryType, $contentType, $info, $settings, $parents, $submitValues)
    {
        if (!isset($info['frontendsubmit_enable'])
            || !$info['frontendsubmit_enable']
            || !empty($info['parent'])
            || !empty($info['is_taxonomy'])
        ) return;

        $form['frontendsubmit_enable'] = array(
            '#type' => 'checkbox',
            '#title' => __('Enable frontend submit', 'directories-frontend'),
            '#default_value' => !empty($settings['frontendsubmit_enable'])
                || !isset($settings['frontendsubmit_enable']) // for compat with < 1.1.2
                || is_null($settings),
            '#horizontal' => true,
        );
    }

    public function onDirectoryContentTypeInfoFilter(&$info, $contentType, $settings = null)
    {
        if (!isset($info['frontendsubmit_enable'])) return;

        if (!$info['frontendsubmit_enable']
            || !empty($info['is_taxonomy'])
        ) {
            unset($info['frontendsubmit_enable']);
        }

        if (!empty($info['parent'])) {
            $info['frontendsubmit_enable'] = true;
        } else {
            if (isset($settings['frontendsubmit_enable']) && !$settings['frontendsubmit_enable']) {
                $info['frontendsubmit_enable'] = false;
            }
        }
    }

    public function onEntityBundleInfoKeysFilter(&$keys)
    {
        $keys[] = 'frontendsubmit_enable';
    }
    
    public function csvGetImporterNames()
    {
        return ['frontendsubmit_guest'];
    }
    
    public function csvGetImporter($name)
    {
        return new CSVImporter\GuestCSVImporter($this->_application, $name);
    }
    
    public function csvGetExporterNames()
    {
        return ['frontendsubmit_guest'];
    }
    
    public function csvGetExporter($name)
    {
        return new CSVExporter\GuestCSVExporter($this->_application, $name);
    }
}