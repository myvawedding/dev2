<?php
/**
 * Plugin Name: Directories Pro
 * Plugin URI: https://directoriespro.com/
 * Description: Directories (Pro version) plugin for WordPress.
 * Author: SabaiApps
 * Author URI: https://codecanyon.net/user/onokazu/portfolio?ref=onokazu
 * Text Domain: directories-pro
 * Domain Path: /languages
 * Version: 1.2.23
 */

add_filter('drts_core_component_paths', function ($paths) {
    $paths['directories-pro'] = [__DIR__ . '/lib/components', '1.2.23'];
    return $paths;
});

if (is_admin()) {
    register_activation_hook(__FILE__, function () {
        if (!function_exists('drts')) die('The directories plugin needs to be activated first before activating this plugin!');
    });
}
