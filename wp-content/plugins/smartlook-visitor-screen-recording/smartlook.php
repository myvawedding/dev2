<?php
/**
 * @package   SmartLook
 * @author    Tomáš Blatný <blatny@kurzor.net>
 * @license   GPL-2.0+
 * @link      http://www.kurzor.net
 * @copyright 2016 Kurzor
 *
 * Plugin Name:       SmartLook Visitor Screen Recording
 * Plugin URI:        https://www.smartlook.com
 * Description:       Smartlook is a simple tool which records the screens of real users on your website. You can see what visitors clicked with their mouse, what they filled into a form field, where they spend most of their time, and how they browse through each page.
 * Version:           2.1
 * Author:            Smartsupp
 * Author URI:        https://www.smartlook.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

use Smartlook\Plugin;


if (!defined('WPINC')) {
	die;
}

require_once __DIR__ . '/src/autoload.php';

$plugin = new Plugin;
$plugin->register(__FILE__);
