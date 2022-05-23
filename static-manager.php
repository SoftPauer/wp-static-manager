<?php

/**
 * Plugin Name: Static Manager
 * Plugin URI: 
 * Description: Provides a way to update mobile app
 * Version: 1.0.1
 * Author: Andrius Murauskas
 * Author URI: 
 * GitHub Plugin URI: https://github.com/SoftPauer/wp-static-manager
 **/

// use sm\Utils;
// use sm;

if (!class_exists('StaticManager\StaticManager')) {


	if (!defined('STATIC_MANAGER_PLUGIN_DIR')) {
		define('STATIC_MANAGER_PLUGIN_DIR', plugin_dir_path(__FILE__)); // The path with trailing slash
	}
	if (!defined('STATIC_MANAGER_MOBILE_HTML')) {
		define('STATIC_MANAGER_MOBILE_HTML', plugin_dir_path(__FILE__) . "/mobile-html"); // The path with trailing slash
	}
	if (!defined('STATIC_MANAGER_MOBILE_HTML_TEMP')) {
		define('STATIC_MANAGER_MOBILE_HTML_TEMP', STATIC_MANAGER_MOBILE_HTML . "-TEMP"); // The path with trailing slash
	}


	include_once STATIC_MANAGER_PLUGIN_DIR . 'includes/static-manager.php';

	function staticManagerInit()
	{

		return \StaticManager\StaticManager::getInstance();
	}

	staticManagerInit();
}
