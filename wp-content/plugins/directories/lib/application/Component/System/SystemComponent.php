<?php
namespace SabaiApps\Directories\Component\System;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class SystemComponent extends AbstractComponent implements IAdminRouter
{
    const VERSION = '1.2.19', PACKAGE = 'directories'; 
    
    protected $_system = true;

    public static function description()
    {
        return 'Provides API and UI for managing components.';
    }
    
    public function hasVarDir()
    {
        return array('logs', 'tmp');
    }
    
    public function getTmpDir()
    {
        return $this->getVarDir('tmp');
    }
    
    public function systemAdminRoutes()
    {
        return array(
            '/_drts/system/tool' => array(
                'controller' => 'RunTool',
                'type' => Application::ROUTE_CALLBACK,
                'method' => 'post',
            ),
            '/_drts/system/tool_with_progress' => array(
                'controller' => 'RunToolWithProgress',
            ),
            '/_drts/system/log' => array(
                'controller' => 'ViewLog',
                'type' => Application::ROUTE_CALLBACK,
            ),
            '/_drts/system/progress' => array(
                'controller' => 'Progress',
                'type' => Application::ROUTE_CALLBACK,
            ),
            '/_drts/system/download' => array(
                'controller' => 'Download',
                'type' => Application::ROUTE_CALLBACK,
            ),
        );
    }

    public function systemOnAccessAdminRoute(Context $context, $path, $accessType, array &$route){}

    public function systemAdminRouteTitle(Context $context, $path, $titleType, array $route){}

    public function onSystemIMainRouterInstalled(AbstractComponent $component)
    {
        $this->_onSystemIRouterInstalled($component, false);
    }

    public function onSystemIAdminRouterInstalled(AbstractComponent $component)
    {
        $this->_onSystemIRouterInstalled($component, true);
    }

    private function _onSystemIRouterInstalled(AbstractComponent $component, $admin = false)
    {
        if ($admin) {
            $this->_createRoutes($component, $component->systemAdminRoutes(), true);
        } else {
            $this->_createRoutes($component, $component->systemMainRoutes(false));
            foreach ($this->_application->getPlatform()->getLanguages() as $lang) {
                $this->_createRoutes($component, $component->systemMainRoutes($lang), false, $lang);
            }
        }
    }
    
    private function _createRoutes(AbstractComponent $component, array $routes, $admin = false, $lang = '')
    {
        if (empty($routes)) return;

        $model = $this->getModel();
        $root_paths = [];

        // Insert route data
        foreach ($routes as $route_path => $route_data) {
            $route_path = strtolower(rtrim($route_path, '/'));
            if ($lang !== '' && strpos($route_path, '/_drts') === 0) continue;
            
            $route = $model->create('Route');
            $route->markNew();
            $route->admin = $admin;
            $route->controller = (string)@$route_data['controller'];
            $route->forward = (string)@$route_data['forward'];
            $route->component = $component->getName();
            $route->controller_component = isset($route_data['controller_component']) ? $route_data['controller_component'] : $component->getName();
            $route->type = isset($route_data['type']) ? $route_data['type'] : Application::ROUTE_NORMAL;
            $route->path = $route_path;
            $route->format = (array)@$route_data['format'];
            $route->method = (string)@$route_data['method'];
            $route->access_callback = !empty($route_data['access_callback']) ? 1 : 0;
            $route->title_callback = !empty($route_data['title_callback']) ? 1 : 0;
            $route->callback_path = isset($route_data['callback_path']) ? $route_data['callback_path'] : $route_path;
            $route->callback_component = isset($route_data['callback_component']) ? $route_data['callback_component'] : $component->getName();
            $route->weight = isset($route_data['weight']) ? ($route_data['weight'] > 99 ? 99 : $route_data['weight']) : 9;
            $route->depth = substr_count($route_path, '/');
            $route->language = $lang;
            $route->data = (array)@$route_data['data'];
            if (!isset($route_data['priority'])) {
                // Set lower priority if it is a child route of another plugin
                if (0 !== strpos(str_replace('_', '', $route_path), '/' . strtolower($component->getName()))) {
                    $route->priority = 3; // default is 5
                }
            } else {
                $route->priority = intval($route_data['priority']);
            }

            if ($root_path = substr($route_path, 0, strpos($route_path, '/', 1))) {
                $root_paths[$root_path] = $root_path;
            }
        }

        $model->commit();

        // Clear cached route data
        if (!empty($root_paths)) {
            $lang = $this->_application->getPlatform()->getCurrentLanguage();
            foreach ($root_paths as $root_path) {
                $this->_application->getPlatform()->deleteCache('system_' . ($admin ? 'route_admin' : 'route') . str_replace('/', '_', $root_path) . '_' . $lang);
            }
        }
    }

    public function onSystemIMainRouterUninstalled(AbstractComponent $component)
    {
        $this->_onSystemIRouterUninstalled($component, false);
    }

    public function onSystemIAdminRouterUninstalled(AbstractComponent $component)
    {
        $this->_onSystemIRouterUninstalled($component, true);
    }

    private function _onSystemIRouterUninstalled(AbstractComponent $component, $admin = false)
    {
        $model = $this->getModel();
        $criteria = $model->createCriteria('Route')->admin_is($admin)->component_is($component->getName());
        $model->getGateway('Route')->deleteByCriteria($criteria);
    }

    public function reloadRoutes(AbstractComponent $component, $admin = false)
    {
        $this->_onSystemIRouterUninstalled($component, $admin);
        $this->_onSystemIRouterInstalled($component, $admin);
        return $this;
    }
    
    public function reloadAllRoutes($mainOnly = false)
    {
        $routes = $this->getModel('Route');
        if ($mainOnly) {
            $routes->admin_is(0);
            $interfaces = ['System\IMainRouter' => 0];
        } else {
            $interfaces = ['System\IMainRouter' => 0, 'System\IAdminRouter' => 1];
        }
        $routes->delete();
        foreach ($interfaces as $interface => $is_admin) {
            foreach ($this->_application->InstalledComponentsByInterface($interface) as $component_name) {
                if (!$this->_application->isComponentLoaded($component_name)) continue;
            
                $this->_onSystemIRouterInstalled($this->_application->getComponent($component_name), $is_admin);
            }
        }
        return $this;
    }

    public function onCoreResponseSendViewLayout(Context $context, &$content, &$vars)
    {
        $this->_application->getPlatform()->loadDefaultAssets()
            ->addJs('DRTS.init($("' . $context->getContainer() . '"));', true, -99);
    }

    public function onCoreResponseSendComplete(Context $context)
    {
        // Save response messages to session if flashing is enabled
        if ($context->isFlashEnabled() && ($flash = $context->getFlash())) {
            $this->_application->getPlatform()->setSessionVar('system_flash', $flash, $this->_application->getUser()->id);
        }
    }

    public function getMainRoutes($rootPath = '/', $lang = null)
    {
        if (!isset($lang)) {
            if (!$lang = $this->_application->getPlatform()->getCurrentLanguage()) {
                $lang = '';
            }
        }
        return $this->_getRoutes($rootPath, false, $lang);
    }

    public function getAdminRoutes($rootPath = '/')
    {
        return $this->_getRoutes($rootPath, true);
    }

    private function _getRoutes($rootPath, $admin = false, $lang = '')
    {
        $root_path = rtrim($rootPath, '/');
        
        if ($lang !== '' && strpos($root_path, '/_drts') === 0) $lang = '';

        // Check if already cached
        $cache_id = 'system_' . ($admin ? 'route_admin' : 'route') . str_replace('/', '_', $root_path) . '_' . $lang;
        if ($cache = $this->_application->getPlatform()->getCache($cache_id)) {
            return $cache;
        }

        $ret = [];
        $routes = $this->getModel('Route')
            ->admin_is($admin)
            ->path_startsWith($root_path)
            ->language_is($lang)
            // fetch routes with lower priority first so that the ones with higher priority will overwrite them
            ->fetch(0, 0, 'priority', 'ASC');
        if ($routes->count()) {
            $root_path_dir = dirname($root_path);
            foreach ($routes as $route) {
                if (!$this->_application->isComponentLoaded($route->component)) continue;
                
                // Initialize route data
                // Any child route data already defined?
                $child_routes = !empty($ret[$route->path]['routes']) ? $ret[$route->path]['routes'] : [];
                $ret[$route->path] = $route->toArray();
                $ret[$route->path]['routes'] = $child_routes;

                $current_path = $route->path;
                while ($root_path_dir !== $parent_path = dirname($current_path)) {
                    $current_base = substr($current_path, strlen($parent_path) + 1); // remove the parent path part

                    if (!isset($ret[$current_path]['path'])) {
                        // Check whether format is defined if dynamic route
                        $format = [];
                        if (0 === strpos($current_base, ':') && isset($ret[$route->path]['format'][$current_base])) {
                            $format = $ret[$route->path]['format'][$current_base];
                            unset($ret[$route->path]['format'][$current_base]);
                        }
                        $ret[$current_path]['path'] = $current_path;
                        $ret[$current_path]['component'] = $route->component;
                        $ret[$current_path]['type'] = Application::ROUTE_NORMAL;
                        $ret[$current_path]['format'] = !empty($format) ? array($current_base => $format) : [];
                    }
                    if (!isset($ret[$parent_path]['component'])) $ret[$parent_path]['component'] = $route->component;
                    $ret[$parent_path]['routes'][$current_base] = $current_path;

                    $current_path = $parent_path;
                }
            }
        }

        // Allow components to modify routes
        $ret = $this->_application->Filter('system_routes', $ret, array($rootPath, $admin, $lang));
        // Cache routes
        $this->_application->getPlatform()->setCache($ret, $cache_id);

        return $ret;
    }

    public function onSystemComponentInstalled(Model\Component $componentEntity)
    {
        $component = $this->_application->getComponent($componentEntity->name);
        
        $this->_invokeComponentEvents($component, 'installed', 'install_success');
    }

    public function onSystemComponentUninstalled(Model\Component $componentEntity)
    {
        $component = $this->_application->getComponent($componentEntity->name);

        $this->_invokeComponentEvents($component, 'uninstalled', 'uninstall_success');
    }

    public function onSystemComponentUpgraded(Model\Component $componentEntity, $previousVersion)
    {
        $component = $this->_application->getComponent($componentEntity->name);
        
        $this->_invokeComponentEvents($component, 'upgraded', 'upgrade_success', array($previousVersion));
    }
    
    private function _invokeComponentEvents(AbstractComponent $component, $event, $event2, array $args = [])
    {
        $event_component_name = strtolower($component->getName());
        $args = array_merge(array($component), $args);
        $this->_application->Action($event_component_name . '_' . $event, $args);

        // Invoke first set of events for each interface implemented by the component
        if ($interfaces = class_implements($component, false)) { // get interfaces implemented by the plugin
            // Remove component namespace part
            $prefix = 'SabaiApps\\Directories\\Component\\';
            foreach (array_keys($interfaces) as $k) {
                if (strpos($interfaces[$k], $prefix) === 0) {
                    $_interface = trim(substr($interfaces[$k], strlen($prefix)), '\\');
                    $interfaces[$k] = $_interface;
                } else {
                    unset($interfaces[$k]);
                }
            }
            $interfaces = array_flip($interfaces);
        } else {
            $interfaces = [];
        }
        if (is_callable(array($component, 'interfaces'))
            && ($_interfaces = call_user_func(array($component, 'interfaces'))) // check for extra interfaces implemented
        ) {
            $interfaces += array_flip($_interfaces);
        }
        if (!empty($interfaces)) {
            // Dispatch event for each interface
            foreach (array_keys($interfaces) as $interface) {
                $action = str_replace('\\', '_', strtolower($interface)) . '_' . $event;
                $this->_application->Action($action, $args);
            }
        }
        
        $this->_application->Action($action = $event_component_name . $event2, $args);
        
        // Invoke second set of events for each interface implemented by the component
        if (!empty($interfaces)) {
            // Dispatch event for each interface
            foreach (array_keys($interfaces) as $interface) {
                $action = str_replace('\\', '_', strtolower($interface)) . '_' . $event2;
                $this->_application->Action($action, $args);
            }
        }
    }
    
    public function onSystemIWidgetsInstalled(AbstractComponent $component)
    {
        $this->_application->getPlatform()->deleteCache('system_widgets');
    }

    public function onSystemIWidgetsUninstalled(AbstractComponent $component)
    {
        $this->_application->getPlatform()->deleteCache('system_widgets');
    }

    public function onSystemIWidgetsUpgraded(AbstractComponent $component)
    {
        $this->_application->getPlatform()->deleteCache('system_widgets');
    }
    
    public function onCoreComponentsLoaded()
    {
        if (!$logger = $this->_application->getLogger()) return;
        
        // Add a log handler to write error logs
        $logger->pushHandler(new StreamHandler($this->getVarDir('logs') . '/error.log', Logger::ERROR));
        if ($this->_application->getPlatform()->isDebugEnabled()) {
            // Add a log handler to write logs with debug level or up
            $logger->pushHandler(new StreamHandler($this->getVarDir('logs') . '/debug.log', Logger::DEBUG));
        }
        
        // Show messages saved in session during the previous request as flash messages
        if ($flash = $this->_application->getPlatform()->getSessionVar('system_flash', $this->_application->getUser()->id)) {
            $this->_application->getPlatform()->addFlash($flash)
                ->deleteSessionVar('system_flash', $this->_application->getUser()->id);
        }
    }
    
    public function onCorePlatformWordPressAdminInit()
    {
        if (!$this->_application->getUser()->isAdministrator()) return;
        
        if (false === $updates = $this->_application->getPlatform()->getCache('system_component_updates')) {
            $updates = [];
            $installed_components = $this->_application->InstalledComponents();
            $local_components = $this->_application->LocalComponents();
            foreach ($installed_components as $component_name => $installed_component) {
                if (isset($local_components[$component_name])
                    && version_compare($installed_component['version'], $local_components[$component_name]['version'], '<')
                ) {
                    $updates[] = $component_name;
                }
            }
            $this->_application->getPlatform()->setCache($updates, 'system_component_updates');
        }
        if (!empty($updates)
            && $this->_application->getPlatform()->isAdmin()
        ) {
            $this->_application->getPlatform()->addFlash(array(
                array(
                    'msg' => sprintf(
                        __('There are %1$d upgradable component(s). Please go to System -> Tools and reload all components.', 'directories'),
                        count($updates)
                    ),
                    'level' => 'danger',
                ),
            ));
        }
    }
    
    public function onSystemAdminSystemToolsFilter(&$tools)
    {
        $tools += array(
            'system_reload' => array(
                'label' => __('Reload components', 'directories'),
                'description' => __('This tool will reload all componentns to ensure they are in sync with stored data.', 'directories'),
                'weight' => 1,
                'with_progress' => true,
                'start' => false,
            ),
            'system_clear_cache' => array(
                'label' => __('Clear cache', 'directories'),
                'description' => __('This tool will clear settings and data currently cached.', 'directories'),
                'weight' => 5,
                'with_progress' => true,
                'form' => [
                    'caches' => [
                        '#type' => 'checkboxes',
                        '#options' => [
                            'settings' => __('Clear settings cache', 'directories'),
                            'content' => __('Clear content cache', 'directories'),
                        ],
                        '#default_value' => ['settings'],
                        '#weight' => -1,
                    ],
                ],
            ),
            'system_run_cron' => array(
                'label' => __('Run cron', 'directories'),
                'description' => __('Use this tool to manually run cron.', 'directories'),
                'weight' => 15,
                'with_progress' => true,
            ),
            'system_clear_logs' => array(
                'label' => __('Clear log files', 'directories'),
                'description' => sprintf(
                    __('This tool will clear all log files saved under %s.', 'directories'),
                    $this->getVarDir('logs')
                ),
                'weight' => 20,
            ),
            'system_alter_collation' => [
                'label' => __('Change table collation', 'directories'),
                'description' => sprintf(
                    __('Use this tool to change the collation of database tables created by %s.', 'directories'),
                    Directories
                ),
                'weight' => 90,
                'with_progress' => true,
                'form' => [
                    'collation' => [
                        '#type' => 'select',
                        '#options' => [
                            'utf8_general_ci' => 'utf8_general_ci',
                            'utf8_unicode_ci' => 'utf8_unicode_ci',
                            'utf8mb4_general_ci' => 'utf8mb4_general_ci',
                            'utf8mb4_unicode_ci' => 'utf8mb4_unicode_ci',
                        ],
                        '#default_value' => $this->_application->getPlatform()->getOption('system_table_collation'),
                    ],
                ],
            ],
        );
    }
        
    public function onSystemAdminRunTool($tool, $progress, array $values = null)
    {
        switch ($tool) {
            case 'system_clear_cache':
                if (!empty($values['caches'])) {
                    if (in_array('settings', $values['caches'])
                        && in_array('content', $values['caches'])
                    ) {
                        // clear all cache
                        $this->_application->getPlatform()->clearCache();
                        $this->_application->Action('system_clear_cache', [null]);
                        $progress->set('Cache cleared.');
                    } else {
                        foreach ($values['caches'] as $group) {
                            $this->_application->getPlatform()->clearCache($group);
                            $this->_application->Action('system_clear_cache', [$group]);
                            $progress->set('Cache (' . $group . ') cleared.');
                        }
                    }
                }
                break;
            case 'system_reload':
                $components_upgraded = $this->_application->System_Component_upgradeAll(null, true, $progress);
                foreach (array_keys($this->_application->LocalComponents()) as $component_name) {
                    if (!in_array($component_name, $components_upgraded)) {
                        try {
                            $this->_application->System_Component_install($component_name, 0, $progress);
                        } catch (Exception\IException $e) {
                            $this->_application->logError(sprintf(
                                'Failed installing component %s. Error: %s',
                                $component_name,
                                $e->getMessage()
                            ));
                        }
                    }
                }
                $this->reloadAllRoutes();
                break;
            case 'system_run_cron':
                $this->_application->System_Cron($progress, true);
                break;
            case 'system_clear_logs':
                $failed = [];
                foreach (glob($this->getVarDir('logs') . '/*.log') as $log_file) {
                    if (!@unlink($log_file)) {
                        $failed[] = $log_file;
                    }
                }
                if (!empty($failed)) {
                    throw new Exception\RuntimeException(
                        sprintf(__('Failed deleting log file: %s', 'directories'), implode(',', $failed))
                    );
                }
                $this->_application->logDebug('Logs cleared using system tool.');
                break;

            case 'system_alter_collation':
                $this->_application->System_Tools_changeCollation($values['collation']);
                $this->_application->getPlatform()->setOption('system_table_collation', $values['collation']);
                $progress->set('Tables updated.');
                break;
        }
    }
    
    public function onSystemAdminSystemLogsFilter(&$logs)
    {
        $logs['error'] = array(
            'label' => __('Error log', 'directories'),
            'file' => $this->getVarDir('logs') . '/error.log',
            'weight' => 1,
        );
        $logs['debug'] = array(
            'label' => __('Debug log', 'directories'),
            'file' => $this->getVarDir('logs') . '/debug.log',
            'weight' => 5,
        );
    }
    
    public function onSystemCron($progress, $lastRun)
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->getTmpDir(), \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        if (count($files)) {
            foreach ($files as $fileinfo) {
                $func = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                $func($fileinfo->getRealPath());
            }
            $progress->set(__('Cleared tmp directory.', 'directories'));
        }
    }
}
