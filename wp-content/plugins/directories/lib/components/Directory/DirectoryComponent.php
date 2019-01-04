<?php
namespace SabaiApps\Directories\Component\Directory;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Context;

class DirectoryComponent extends AbstractComponent implements
    ITypes,
    System\ISlugs,
    Entity\IBundleTypes,
    System\IAdminRouter,
    System\IWidgets
{
    const VERSION = '1.2.17', PACKAGE = 'directories';
    
    public static function description()
    {
        return 'Adds features to build a general directory of listings with category and tag taxonomies.';
    }
    
    public function onCoreComponentsLoaded()
    {
        $this->_application->setHelper('Directory_Directory', function (Application $application, $directoryName) {
            return $application->getModel('Directory', 'Directory')->fetchById($directoryName);
        });
    }
    
    public function systemAdminRoutes()
    {
        return [
            '/directories' => [
                'controller' => 'Directories',
                'title_callback' => true,
                'callback_path' => 'directories',
                'type' => Application::ROUTE_TAB,
                'weight' => 9,
            ],
            '/directories/:directory_name' => [
                'controller' => 'EditDirectory',
                'title_callback' => true,
                'access_callback' => true,
                'callback_path' => 'edit_directory',
                'type' => Application::ROUTE_TAB,
            ],
            '/directories/:directory_name/content_types' => [
                'controller' => 'ContentTypes',
                'title_callback' => true,
                'callback_path' => 'content_types',
                'type' => Application::ROUTE_TAB,
            ],
            '/directories/:directory_name/delete' => [
                'controller' => 'DeleteDirectory',
                'access_callback' => true,
                'title_callback' => true,
                'callback_path' => 'delete_directory',
            ],
            '/directories/add' => [
                'controller' => 'AddDirectory',
                'access_callback' => true,
                'title_callback' => true,
                'callback_path' => 'add_directory',
                'type' => Application::ROUTE_MENU,
            ],
            '/directories/settings' => [
                'controller' => 'Settings',
                'access_callback' => true,
                'title_callback' => true,
                'callback_path' => 'settings',
                'type' => Application::ROUTE_TAB,
                'weight' => 98,
            ],
            '/directories/system' => [
                'controller' => 'System',
                'access_callback' => true,
                'title_callback' => true,
                'callback_path' => 'system',
                'type' => Application::ROUTE_TAB,
                'weight' => 99,
            ],
        ];
    }

    public function systemOnAccessAdminRoute(Context $context, $path, $accessType, array &$route)
    {
        switch ($path) {
            case 'edit_directory':
                if ($accessType === Application::ROUTE_ACCESS_LINK) {
                    if ((!$directory_name = $context->getRequest()->asStr('directory_name'))
                        || (!$directory = $this->getModel('Directory')->fetchById($directory_name))
                    ) return false;
                    
                    if (!$this->_application->HasPermission('directory_admin_directory_' . $directory_name)) {
                        $context->setForbiddenError();
                        return false;
                    }
                    
                    $context->clearMenus();
                    // Add directory admin menus
                    $menus = [
                        'visit' => [
                            'title' => __('Visit Directory', 'directories'),
                            'url' => $this->_application->MainUrl('/' . $this->getSlug($directory_name)),
                        ],
                    ];
                    foreach ($this->_application->Filter('directory_admin_directory_menus', $menus, [$directory]) as $menu) {
                        $context->addMenu(
                            [
                                'title' => $menu['title'],
                                'url' => $menu['url'],
                                'data' => isset($menu['data']) ? $menu['data'] : null,
                            ],
                            !isset($menu['page']) || $menu['page']
                        );
                    }
                    
                    $context->directory = $directory;
                }
                return true;
            case 'add_directory':
                if ($accessType === Application::ROUTE_ACCESS_LINK) {
                    if (!$this->_application->getUser()->isAdministrator()) return false;
                    
                    $route['data'] = [
                        'link_options' => [
                            'container' => 'modal',
                            'cache' => 'drts-directory-add-directory',
                            'icon' => 'fas fa-plus',
                        ],
                        'link_attr' => [
                            'class' => DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-success',
                        ],
                    ];
                }
                return true;
            default:
                if ($accessType === Application::ROUTE_ACCESS_LINK) {
                    return $this->_application->getUser()->isAdministrator();
                }
                return true;
        }
    }

    public function systemAdminRouteTitle(Context $context, $path, $titleType, array $route)
    {
        switch ($path) {
            case 'directories':
                return $titleType === Application::ROUTE_TITLE_TAB ? __('All Directories', 'directories') : __('Directories', 'directories');
            case 'edit_directory':
                return $titleType === Application::ROUTE_TITLE_TAB ? __('Settings', 'directories') : $context->directory->getLabel();
            case 'delete_directory':
                return __('Delete Directory', 'directories');
            case 'add_directory':
                return __('Add Directory', 'directories');
            case 'content_types':
                return __('Content Types', 'directories');
            case 'settings':
                return __('Settings', 'directories');
            case 'system':
                return __('System', 'directories');
        }
    }
    
    public function systemSlugs()
    {
        $slugs = [];
        $component_slug = strtolower($this->_name);
        foreach ($this->getModel('Directory')->fetch() as $directory) {
            if (!$directory_type = $this->_application->Directory_Types_impl($directory->type, true)) continue;
            
            $slugs[$directory->name] = [
                'title' => $directory->getLabel(),
                'admin_title' => $directory->getLabel(),
                'slug' => $component_slug . '-' . $directory->name,
                'bundle_group' => $directory->name,
                'wp_shortcode' => ['drts-directory-view', ['directory' => $directory->name]],
                'weight' => 100,
            ];
        }
        return $slugs;
    }
    
    public function entityGetBundleTypeNames()
    {
        $ret = ['directory_category', 'directory_tag'];
        foreach (array_keys($this->_application->Directory_Types()) as $directory_type_name) {
            if (!$directory_type = $this->_application->Directory_Types_impl($directory_type_name, true)) continue;

            foreach ($directory_type->directoryInfo('content_types') as $content_type) {
                $ret[] = $directory_type_name . '__' . $content_type;
            }
        }    
        return $ret;
    }
    
    public function entityGetBundleType($name)
    {
        switch ($name) {
            case 'directory_category':
                return new EntityBundleType\CategoryEntityBundleType($this->_application, $name);
            case 'directory_tag':
                return new EntityBundleType\TagEntityBundleType($this->_application, $name); 
            default:
                return new EntityBundleType\DirectoryEntityBundleType($this->_application, $name);
        }
    }
    
    public function onWordPressPostTypeFilter(&$postType, $bundle)
    {
        if ($bundle->component === 'Directory'
            && ($directory = $this->getModel('Directory')->fetchById($bundle->group))
        ) {
            $postType['labels']['name'] = $directory->getLabel() . ' - ' . $bundle->getLabel();
            $postType['labels']['singular_name'] = $directory->getLabel() . ' - ' . $bundle->getLabel('singular');
            if (empty($bundle->info['parent'])) {
                $postType['labels']['menu_name'] = $directory->getLabel();
                if ($icon = $directory->getIcon()) {
                    foreach (explode(' ', $icon) as $dashicon) {
                        if (strpos($dashicon, 'dashicons-') === 0) {
                            break;
                        }
                    }
                    $postType['menu_icon'] = $dashicon;
                }
            }
        }
    }
    
    public function onWordPressTaxonomyFilter(&$taxonomy, $bundle)
    {
        if ($bundle->component === 'Directory'
            && ($directory = $this->getModel('Directory')->fetchById($bundle->group))
        ) {
            $taxonomy['labels']['name'] = $directory->getLabel() . ' - ' . $bundle->getLabel();
            $taxonomy['labels']['singular_name'] = $directory->getLabel() . ' - ' . $bundle->getLabel('singular');
        }
    }
    
    public function directoryGetTypeNames()
    {
        return ['directory'];
    }
    
    public function directoryGetType($name)
    {
        switch ($name) {
            case 'directory':
                return new Type\DirectoryType($this->_application, $name);
        }
    }
    
    public function systemGetWidgetNames()
    {
        $ret = [];
        foreach ($this->_application->Entity_Bundles(null, 'Directory') as $bundle) {
            if (empty($bundle->info['public'])
                || !empty($bundle->info['is_taxonomy'])
                || !empty($bundle->info['parent'])
            ) continue;
               
            $ret[] = 'directory_posts_' . $bundle->type;
            $ret[] = 'directory_related_posts_' . $bundle->type;
            if (!empty($bundle->info['taxonomies'])) {
                foreach (array_keys($bundle->info['taxonomies']) as $taxonomy_bundle_type) {
                    if ($this->_application->Entity_BundleTypeInfo($taxonomy_bundle_type, 'is_hierarchical')) {
                        $ret[] = 'directory_terms_' . $taxonomy_bundle_type . '___' . $bundle->type;
                    }
                }
            }
        }
        
        return $ret;
    }
    
    public function systemGetWidget($name)
    {
        if (strpos($name, 'directory_terms_') === 0) {
            return new SystemWidget\TermsSystemWidget($this->_application, $name, substr($name, 16));
        }
        if (strpos($name, 'directory_posts_') === 0) {
            return new SystemWidget\PostsSystemWidget($this->_application, $name, substr($name, 16));
        }
        if (strpos($name, 'directory_related_posts_') === 0) {
            return new SystemWidget\RelatedPostsSystemWidget($this->_application, $name, substr($name, 24));
        }
    }
    
    public function onEntityBundleTypesFilter(&$bundleTypes)
    {
        foreach (array_keys($bundleTypes) as $bundle_type) {
            if ($bundleTypes[$bundle_type] !== $this->_name
                || !strpos($bundle_type, '__')
            ) continue;
            
            $parts = explode('__', $bundle_type);
            if (!$this->_application->Directory_Types_Impl($parts[0], true, false)) {
                // Remove the entity bundle type since the directory type associated is disabled or removed
                unset($bundleTypes[$bundle_type]);
            }
        }
    }
    
    public function onWordPressAdminEndpointsFilter(&$endpoints)
    {
        $endpoints['/directories'] = [
            'label' => $label = __('Directories', 'directories'),
            'label_menu' => $label,
            'icon' => 'data:image/svg+xml;base64,' . base64_encode('<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="30" height="30" viewBox="0 0 30 30" xml:space="preserve">
    <g transform="translate(-450 -620)">
        <g xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" id="directories">
            <path fill="black" d="M479,624h-10c-0.55,0-1.36-0.27-1.8-0.6l-2.4-1.801c-0.439-0.33-1.25-0.6-1.8-0.6h-5c-0.55,0-1,0.45-1,1v2h-2    c-0.55,0-1,0.45-1,1v2h-2c-0.55,0-1,0.45-1,1v19c0,0.55,0.45,1,1,1h21c0.55,0,1-0.45,1-1v-2h2c0.55,0,1-0.45,1-1v-2h2    c0.55,0,1-0.45,1-1v-16C480,624.45,479.55,624,479,624z M452,628h5c0.33,0,0.936,0.201,1.199,0.4l2.401,1.8    c0.606,0.456,1.639,0.8,2.399,0.8h10v2h-21V628z M473,647h-21v-13h21V647z M476,644h-2v-13c0-0.55-0.45-1-1-1h-10    c-0.55,0-1.36-0.27-1.8-0.6l-2.4-1.801c-0.439-0.33-1.25-0.6-1.8-0.6h-2v-2h5c0.33,0,0.936,0.201,1.199,0.4l2.401,1.8    c0.606,0.456,1.639,0.8,2.399,0.8h10V644z M479,641h-2v-13c0-0.55-0.45-1-1-1h-10c-0.55,0-1.36-0.27-1.8-0.6l-2.4-1.801    c-0.439-0.33-1.25-0.6-1.8-0.6h-2v-2h5c0.33,0,0.936,0.201,1.199,0.4l2.401,1.8c0.606,0.456,1.639,0.8,2.399,0.8h10V641z"/>
        </g>
    </g>
</svg>'),
            'order' => 99.12425,
            'capability' => 'read',
            'children' => [
                '/directories' => [
                    'label' => $label,
                    'label_menu' => __('All Directories', 'directories'),
                ],
            ],
            'permission' => 'directory_admin',
        ];
    }
    
    public function onWordPressPermissionsFilter(&$permissions, $componentName, $group)
    {
        if ($componentName !== 'Directory') return;
        
        $permissions['directory_admin'] = [
            'title' => _x('Admin', 'permission tab label', 'directories'),
            'perms' => [
                'directory_admin_directory' => [
                    'title' =>_x('Admin Directory', 'permission name', 'directories'),
                    'weight' => 1,
                ],
            ],
            'suffix' => '_' . $group,
        ];
    }
    
    public function onDirectoryContentTypeSettingsFormFilter(&$form, $directoryType, $contentType, $info, $settings, $parents, $submitValues)
    {
        if (!empty($info['is_taxonomy'])
            || !empty($info['parent'])
        ) return;
        
        if (!empty($info['directory_category_enable'])) {
            $form['directory_category_enable'] = array(
                '#type' => 'checkbox',
                '#title' => __('Enable categories', 'directories'),
                '#default_value' => !empty($settings['directory_category_enable']) || is_null($settings),
                '#horizontal' => true,
            );
        }
        if (!empty($info['directory_tag_enable'])) {
            $form['directory_tag_enable'] = array(
                '#type' => 'checkbox',
                '#title' => __('Enable tags', 'directories'),
                '#default_value' => !empty($settings['directory_tag_enable']) || is_null($settings),
                '#horizontal' => true,
            );
        }
    }
    
    public function onDirectoryContentTypeInfoFilter(&$info, $contentType, $settings = null)
    {
        foreach (array('directory_category', 'directory_tag') as $key) {
            if (!isset($info[$key . '_enable'])) continue;
            
            if (!empty($info['is_taxonomy'])
                || !empty($info['parent'])
            ) {
                unset($info[$key . '_enable']);
                continue;
            }
        
            if (isset($settings[$key . '_enable']) && !$settings[$key . '_enable']) {
                $info[$key . '_enable'] = false;
            }
        }
    }
        
    public function onEntityBundlesInfoFilter(&$bundles, $componentName, $group)
    {
        foreach (array('directory_category', 'directory_tag') as $entity_bundle_type) {
            foreach (array_keys($bundles) as $bundle_type) {
                $info =& $bundles[$bundle_type];
            
                if (empty($info[$entity_bundle_type . '_enable'])
                    || !empty($info['is_taxonomy'])
                    || !empty($info['parent'])
                ) continue;
            
                // Associate bundle
                if (!isset($info['taxonomies'][$entity_bundle_type]) // may already be set if updating or importing
                    || !is_array($info['taxonomies'][$entity_bundle_type]) // not an array if updating
                ) {
                    $info['taxonomies'][$entity_bundle_type] = [];
                }
                if (!empty($info[$entity_bundle_type . '_field'])) {
                    $info['taxonomies'][$entity_bundle_type] += $info[$entity_bundle_type . '_field'];
                }
            
                // Add bundle
                if (!isset($bundles[$entity_bundle_type])) { // may already set if updating
                    $bundles[$entity_bundle_type] = [];
                }
                $bundles[$entity_bundle_type] += $this->entityGetBundleType($entity_bundle_type)->entityBundleTypeInfo();
            
                continue 2; // there should be only one bundle enabled
            }
        
            // No bundle enabled found, so make sure the bundle is not assigned
            unset($bundles[$entity_bundle_type]);
        }
    }
    
    public function onEntityBundleInfoKeysFilter(&$keys)
    {
        $keys[] = 'directory_category_enable';
        $keys[] = 'directory_tag_enable';
    }
    
    public function onWordPressShortcodesFilter(&$shortcodes)
    {
        foreach ($this->_application->Filter('directory_shortcodes', []) as $name => $path) {
            $data = ['component' => 'Directory'];
            if (is_array($path)) {
                $data += $path;
            } else {
                $data['path'] = $path;
            }
            $shortcodes['drts-directory-' . $name] = $data + ['cache' => true];
        }
    }
    
    public function onWordPressDoShortcodeFilter(&$ret, $shortcode, $component)
    {
        if (strpos($shortcode, 'drts-directory-') !== 0) return;

        if (!isset($ret['atts']['directory'])) {
            if (!$directory = $this->getModel('Directory')->fetchOne()) {
                throw new Exception\RuntimeException('Shortcode [' . $shortcode . ']: No directory found.');
            }
            $ret['atts']['directory'] = $directory->name;
        }
        
        if (!isset($ret['atts']['type'])) {
            // No bundle type specified, so fetch the primary bundle of the directory
            foreach ($this->_application->Entity_Bundles(null, 'Directory', $ret['atts']['directory']) as $_bundle) {
                if (!empty($_bundle->info['is_primary'])) {
                    $bundle = $_bundle;
                    break;
                }
            }
            if (!isset($bundle)) {
                throw new Exception\RuntimeException('Shortcode [' . $shortcode . ']: invalid bundle.');
            }
        } else {        
            if (!$bundle = $this->_application->Entity_Bundle($ret['atts']['type'], 'Directory', $ret['atts']['directory'])) {
                throw new Exception\RuntimeException('Shortcode [' . $shortcode . ']: invalid bundle.');
            }
        }

        $ret['path'] = $this->_application->Entity_BundlePath($bundle) . $ret['path'];

        // Since $ret is passes as reference, we can't return it here
        $ret = $this->_application->Filter(
            'directory_do_shortcode',
            $ret,
            [substr($shortcode, strlen('drts-directory-')), $bundle]
        );

        return $ret;
    }
    
    public function onDirectoryAdminSettingsFormFilter(&$form)
    {
        $form['#tabs'][$this->_name] = array(
            '#title' => _x('Uninstall', 'admin settings tab', 'directories'),
            '#weight' => 100,
        );
        $form[$this->_name] = [
            '#tree' => true,
            '#tab' => $this->_name,
            '#component' => $this->_name,
            '#title' => __('Uninstall Settings', 'directories'),
            'uninstall_remove_data' => [
                '#type' => 'checkbox',
                '#title' => __('Remove data', 'directories'),
                '#description' => __('Check this option to completely remove all directory data when the Directories plugin is deleted.', 'directories'),
                '#default_value' => !empty($this->_config['uninstall_remove_data']),
                '#horizontal' => true,
            ],
        ];
    }
    
    public function onCoreUninstallRemoveDataFilter(&$bool)
    {
        $bool = !empty($this->_config['uninstall_remove_data']);
    }
}
