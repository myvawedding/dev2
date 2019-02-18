<?php
/**
 * Plugin Name: Directories - Frontend
 * Plugin URI: https://directoriespro.com/
 * Description: Frontend submit and dashboard add-on for Directories.
 * Author: SabaiApps
 * Author URI: https://codecanyon.net/user/onokazu/portfolio?ref=onokazu
 * Text Domain: directories-frontend
 * Domain Path: /languages
 * Version: 1.2.24
 */

add_filter('drts_core_component_paths', function ($paths) {
    $paths['directories-frontend'] = [__DIR__ . '/lib/components', '1.2.24'];
    return $paths;
});

if (is_admin()) {
    register_activation_hook(__FILE__, function () {
        if (!function_exists('drts')) die('The directories plugin needs to be activated first before activating this plugin!');
    });
}
