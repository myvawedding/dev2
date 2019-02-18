<?php
/**
 * Plugin Name: Directories
 * Plugin URI: https://directoriespro.com/
 * Description: Directories plugin for WordPress.
 * Author: SabaiApps
 * Author URI: https://codecanyon.net/user/onokazu/portfolio?ref=onokazu
 * Text Domain: directories
 * Domain Path: /languages
 * Version: 1.2.24
 */

if (!class_exists('\SabaiApps\Directories\Platform\WordPress\Loader', false)) {
    require __DIR__ . '/lib/application/Platform/WordPress/Loader.php';
}
\SabaiApps\Directories\Platform\WordPress\Loader::register(__DIR__, '1.2.24');

add_filter('drts_core_component_paths', function ($paths) {
    $paths['directories'] = [__DIR__ . '/lib/components', '1.2.24'];
    return $paths;
});

if (!function_exists('drts')) {
    function drts() {
        return \SabaiApps\Directories\Platform\WordPress\Loader::getPlatform()->getApplication();
    }
}

if (is_admin()) {
    add_action('plugin_row_meta', function($meta, $file, $data, $status) {
        if ($file === 'directories/directories.php') {
            $meta['documentation'] = '<a href="https://directoriespro.com/documentation/" target="_blank">Documentation</a>';
            $meta['changelog'] = '<a href="https://directoriespro.com/category/releases/" target="_blank">Change log</a>';
        }
        return $meta;
    }, 10, 4);
}
