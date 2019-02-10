<?php
/**
 * Plugin Name: Directories - Reviews
 * Plugin URI: https://directoriespro.com/
 * Description: Reviews add-on for Directories.
 * Author: SabaiApps
 * Author URI: https://codecanyon.net/user/onokazu/portfolio?ref=onokazu
 * Text Domain: directories-reviews
 * Domain Path: /languages
 * Version: 1.2.23
 */

add_filter('drts_core_component_paths', function ($paths) {
    $paths['directories-reviews'] = [__DIR__ . '/lib/components', '1.2.23'];
    return $paths;
});

if (is_admin()) {
    register_activation_hook(__FILE__, function () {
        if (!function_exists('drts')) die('The directories plugin needs to be activated first before activating this plugin!');
    });
}
