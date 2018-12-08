<?php
namespace SabaiApps\Directories\Platform\WordPress;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Field;

class Util
{
    public static function activate(Platform $platform, array $components = [])
    {    
        if (intval(ini_get('max_execution_time')) < 600){
            @ini_set('max_execution_time', '600');
        }
        if (intval(ini_get('memory_limit')) < 128){
            @ini_set('memory_limit', '128M');
        }
    
        try {
            $app = $platform->getApplication(true, true, true);
            // If no exception, the plugin is already installed so do nothing
            return;
        } catch (Exception\NotInstalledException $e) {
            $app = $platform->getApplication(false); // get application without loading components
        }
    
        $log = new \ArrayObject();
    
        try {
            $log[] = 'Clearing old cache data if any...';
            $platform->clearCache();
            $log[] = 'done...';
        } catch (\Exception $e) {
            $log[] = $e->getMessage() . '...';
        }
    
        $log[] = 'Installing sabai...';
    
        // Install the System component
        try {
            $system = $app->fetchComponent('System')->install();
            if (!$system_entity = $system->getModel('Component')->name_is('System')->fetchOne()) {
                die('Failed fetching the System component entity.');
            }
            $system_entity->config = $system->getDefaultConfig();
            $system_entity->events = $app->ComponentEvents($system);
            $system_entity->data = [];
            $system_entity->commit();
        } catch (\Exception $e) {
            die(sprintf('Failed installing the System component. Error: %s', $e->getMessage()));
        }
    
        $app->reloadComponents();

        $log[] = 'System component installed...';
    
        // Install core components first
        $components = array(
            'System' => [],
            'Form' => [],
            'WordPress' => [],
        ) + $components;
        $result = self::_installComponents($app, $components, $log, array('System' => $system_entity));
    
        $log[] = 'done.';
    
        $platform->clearCache();

        $install_log = implode('', (array)$log);
        $platform->setOption('install_log', $install_log, false);
    
        if (!$result) {
            die(sprintf('Failed installing sabai. Log: %s', $install_log));
        }
    }

    public static function activatePlugin(Platform $platform, $plugin, $componentsPath = null)
    {    
        if (intval(ini_get('max_execution_time')) < 600){
            @ini_set('max_execution_time', '600');
        }
        if (intval(ini_get('memory_limit')) < 128){
            @ini_set('memory_limit', '128M');
        }
    
        $log = new \ArrayObject();
    
        try {
            $log[] = 'Clearing old cache data if any';
            $platform->clearCache();
            $log[] = 'done';
        } catch (\Exception $e) {
            $log[] = $e->getMessage();
        }
    
        $log[] = 'Installing ' . $plugin;
    
        try {
            $app = $platform->getApplication(true, true, true);
        } catch (Exception\NotInstalledException $e) {
            die($e->getMessage());
        }
    
        // Find components files
        if (!isset($componentsPath)) $componentsPath = $platform->getPackagePath() . '/' . $plugin . '/lib/components';
        if (!$files = glob(rtrim($componentsPath, '/') . '/*', GLOB_ONLYDIR)) {
            die('No valid files found under ' . $componentsPath);
        }
        $components = [];
        foreach ($files as $file) {
            $component_name = basename($file);
            // Skip components without a valid name
            if (!preg_match(Application::COMPONENT_NAME_REGEX, $component_name)) continue;
            
            // Skip if no valid component file
            if (!file_exists($component_file = $componentsPath . '/' . $component_name . '/' . $component_name . 'Component.php')) {
                $log[] = 'Component file not found, skipping ' . $component_name;
                    continue;
                }
        
            $components[$component_name] = [];
        }
    
        // Install ad-ons that are still not installed
        $installed_components = $app->InstalledComponents(true);
        $result = true;
        if ($components_to_install = array_diff_key($components, $installed_components)) {    
            $result = self::_installComponents($app, $components_to_install, $log);
        }
    
        $log[] = 'done.';
    
        $platform->clearCache();

        $install_log = implode('...', (array)$log);
    
        if (!$result) {
            die (sprintf('Failed installing %s. Log: %s', $plugin, $install_log));
        }
    }

    protected static function _installComponents(Application $app, array $components, $log, array $componentsInstalled = [])
    {
        $failed = false;
        foreach ($components as $component => $component_settings) {
            if (isset($componentsInstalled[$component])) continue;
        
            $component_settings = array_merge(array('priority' => 1), $component_settings);
            try {
                $entity = $app->System_Component_install($component, $component_settings['priority']);
            } catch (Exception\ComponentNotInstallableException $e) {
                $log[] = sprintf('skipping component %s since it may not be installed. Error: %s...', $component, $e->getMessage());
                continue;
            } catch (Exception\ComponentNotFoundException $e) {
                $log[] = sprintf('skipping component %s since its main file was not found. Error: %s...', $component, $e->getMessage());
                continue;
            } catch (Exception\ComponentNotInstalledException $e) {
                $log[] = sprintf('skipping component %s since its file and folders were not found. Error: %s...', $component, $e->getMessage());
                continue;
            } catch (\Exception $e) {
                $failed = true;
                $log[] = sprintf('failed installing component %s. Error: %s...', $component, $e->getMessage());
                break;
            }

            $componentsInstalled[$component] = $entity;

            $log[] = sprintf('%s component installed...', $component);
        }

        $app->reloadComponents();

        if (!$failed) {
            foreach ($componentsInstalled as $component => $component_entity) {
                $app->Action('system_component_installed', array($component_entity));
            }
            // Reload components data
            $app->reloadComponents();
        } else {
            if (!empty($componentsInstalled)) {
                // Uninstall all components
                $log[] = 'Uninstalling installed components...';
                foreach (array_keys($componentsInstalled) as $component) {
                    try {
                        $app->getComponent($component)->uninstall(true);
                    } catch (\Exception $e) {
                        $log[] = sprintf('failed uninstalling the %s component! You must manually uninstall the component. Error: %s...', $component, $e->getMessage());
                        continue;
                    }
                    $log[] = sprintf('%s component uninstalled...', $component);
                }
            }
        }

        return !$failed;
    } 

    public static function createPage(Platform $platform, $slug, $title, $lang = false)
    {
        if ($page = get_page_by_path($slug)) {
            wp_publish_post($page->ID);
            return $page->ID;
        }
        if (strpos($slug, '/')) { // not a root page
            if (!$parent_page = get_page_by_path(substr($slug, 0, strrpos($slug, '/')))) {
                // parent page must exist
                return;
            }
            $slug = basename($slug);
            $parent = $parent_page->ID;
        } else {
            $parent = 0;
        }
        
        // Create a new page for this slug
        $page = array(
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_content' => '',
            'post_date' => current_time('mysql'),
            'post_date_gmt' => current_time('mysql', 1),
            'post_name' => $slug,
            'post_status' => 'publish',
            'post_title' => $title,
            'post_type' => 'page',
            'post_parent' => $parent,
        );
        return wp_insert_post($page);
    }

    public static function updateDatabase(Platform $platform, $schema, $previousSchema = null)
    {
        global $wpdb;
        if (isset($schema)) {
            if (is_string($schema)) {
                $schema = include $schema;
            }
            $sql = self::_updateDatabaseSchema($wpdb, $schema);
            if ($sql['delta']) {
                require_once ABSPATH . 'wp-admin/includes/upgrade.php';
                dbDelta($sql['delta']);
            }
            foreach ($sql['inserts'] as $table_name => $inserts) {
                foreach ($inserts as $insert) {
                    $wpdb->insert($table_name, $insert);
                }
            }
        } elseif (isset($previousSchema)) {
            if (is_string($previousSchema)) {
                $previousSchema = include $previousSchema;
            }
            $sql = self::_updateDatabaseSchema($wpdb, $previousSchema);
            if (!empty($sql['tables'])) {
                $wpdb->query('DROP TABLE IF EXISTS ' . implode(',', $sql['tables']) . ';');
            }
        }
    }
    
    protected static function _updateDatabaseSchema($wpdb, $schema)
    {
        $ret = array('delta' => null, 'tables' => [], 'inserts' => []);
        if (empty($schema['tables'])) return $ret;
    
        $sql = [];
        $table_prefix = $wpdb->prefix . 'drts_';
        foreach ($schema['tables'] as $table => $table_info) {
            $table_name = $table_prefix . $table;
            if (strlen($table_name) > 64) {
                throw new Exception\RuntimeException('Table name is too long: ' . $table_name);
            }
            $columns = [];
            foreach ($table_info['fields'] as $column => $column_info) {
                switch ($column_info['type']) {
                    case Application::COLUMN_BOOLEAN:
                        $columns[] = sprintf(
                            '%s tinyint(1) DEFAULT \'%d\'%s',
                            $column,
                            !empty($column_info['default']) ? 1 : 0,
                            false === @$column_info['notnull'] ? '' : ' NOT NULL'
                        );
                        break;
                    case Application::COLUMN_DECIMAL:
                        $scale = !isset($column_info['scale']) ? 2 : $column_info['scale'];
                        $columns[] = sprintf(
                            '%s decimal(%d,%d)%s DEFAULT \'%s\'%s',
                            $column,
                            empty($column_info['length']) ? 10 : $column_info['length'],
                            $scale,
                            !empty($column_info['unsigned']) ? ' unsigned' : '',
                            isset($column_info['default']) ? $column_info['default'] : '0.' . str_repeat('0', $scale),
                            false === @$column_info['notnull'] ? '' : ' NOT NULL'
                        );
                        break;
                    case Application::COLUMN_INTEGER:
                        $length = empty($column_info['length']) ? 10 : $column_info['length'];
                        $type = $length > 10 ? 'bigint' : 'int';
                        $columns[] = sprintf(
                            '%s %s(%d)%s%s%s%s',
                            $column,
                            $type,
                            $length,
                            !empty($column_info['unsigned']) ? ' unsigned' : '',
                            empty($column_info['autoincrement']) && isset($column_info['default']) ? " DEFAULT '" . intval($column_info['default']) . "'" : '',
                            false === @$column_info['notnull'] ? '' : ' NOT NULL',
                            empty($column_info['autoincrement']) ? '' : ' AUTO_INCREMENT'
                        );
                        break;
                    case Application::COLUMN_TEXT:
                        $columns[] = sprintf(
                            '%s text%s',
                            $column,
                            false === @$column_info['notnull'] ? '' : ' NOT NULL'
                        );
                        break;
                    case Application::COLUMN_VARCHAR:
                        $columns[] = sprintf(
                            '%s varchar(%d) DEFAULT \'%s\'%s',
                            $column,
                            empty($column_info['length']) ? 255 : $column_info['length'],
                            (string)@$column_info['default'],
                            false === @$column_info['notnull'] ? '' : ' NOT NULL'
                        );
                        break;
                }
            }
            foreach ($table_info['indexes'] as $index => $index_info) {
                $index_fields = [];
                foreach ($index_info['fields'] as $field => $field_info) {
                    $index_fields[] = isset($field_info['length']) ? $field . '(' . $field_info['length'] . ')' : $field;
                }
                if (!empty($index_info['primary'])) {
                    $columns[] = sprintf('PRIMARY KEY (%s)', implode(',', $index_fields));
                } elseif (!empty($index_info['unique'])) {
                    $columns[] = sprintf('UNIQUE KEY `%s` (%s)', $index, implode(',', $index_fields));
                } else {
                    $columns[] = sprintf('KEY `%s` (%s)', $index, implode(',', $index_fields));
                }
            }
            if (!empty($table_info['initialization'])) {
                foreach ($table_info['initialization'] as $init_type => $init_data) {
                    switch ($init_type) {
                        case 'insert';
                            $ret['inserts'][$table_name] = $init_data;
                            break;
                    }
                }
            }

            $charset_collate = '';
            if (!empty($wpdb->charset)) {
                $charset_collate .= ' DEFAULT CHARACTER SET ' . $wpdb->charset;
            }
            if (!empty($wpdb->collate)) {
                $charset_collate .= ' COLLATE ' . $wpdb->collate;
            }
            $sql[$table_name] = sprintf('CREATE TABLE %s (
  %s
)%s;',
                $table_name,
                implode(",\n", $columns),
                $charset_collate
            );
        }
        if (!empty($sql)) {
            $ret['delta'] = implode("\n", $sql);
            $ret['tables'] = array_keys($sql);
        }
        return $ret;
    }
}