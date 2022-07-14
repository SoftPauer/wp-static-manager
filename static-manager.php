<?php

/**
 * Plugin Name: Static Manager
 * Plugin URI: 
 * Description: Provides a way to update mobile app
 * Version: 1.1.0
 * Author: Andrius Murauskas
 * Author URI: 
 * GitHub Plugin URI: https://github.com/SoftPauer/wp-static-manager
 **/

// use sm\Utils;
// use sm;
session_start();

if ( ! defined( 'STATIC_MANAGER_VERSION' ) )
	define( 'STATIC_MANAGER_VERSION', '1.1.0' );

if ( ! class_exists( 'StaticManager' ) ) :

class StaticManager {

	/**
   * @var StaticManager_DB
   */
	public $db;
		/**
   * @var StaticManager_RestAPI
   */
  public $rest;

	public static function init() {
		static $instance;
		if ( empty( $instance ) ) {
			$instance = new StaticManager();
		}
		return $instance;
	}

	public function __construct() {


		register_activation_hook( __FILE__, array( $this, 'activation' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivation' ) );
		add_action('rest_api_init', array($this,'rest_init'));

		wp_enqueue_script("jquery_private_event",  "/wp-content/plugins/wp-static-manager/jqueryEvent.js", array('jquery'), "1.0.0", false);
		wp_enqueue_script("mobile_navigation",  "/wp-content/plugins/wp-static-manager/navigation.js", array('jquery'), "1.0.0", false);
	
	
		$this->plugin_dir   = plugin_dir_path( __FILE__ );
		$this->plugin_url   = plugin_dir_url( __FILE__ );
		$this->includes_dir = trailingslashit( $this->plugin_dir . 'includes' );
		
	

		$this->setup_constants();
		$this->includes();
		$this->setup_hooks();

	}

	public function setup_constants() {
		if (!defined('STATIC_MANAGER_PLUGIN_DIR')) {
			define('STATIC_MANAGER_PLUGIN_DIR', plugin_dir_path(__FILE__)); 
		}
		if (!defined('STATIC_MANAGER_MOBILE_HTML')) {
			define('STATIC_MANAGER_MOBILE_HTML', plugin_dir_path(__FILE__) . "/mobile-html"); 
		}
		if (!defined('STATIC_MANAGER_MOBILE_HTML_TEMP')) {
			define('STATIC_MANAGER_MOBILE_HTML_TEMP', STATIC_MANAGER_MOBILE_HTML . "-TEMP"); 
		}
	
	}
	function activation() {
		require_once( dirname( __FILE__ ) . '/includes/database.php' );
		$db = new StaticManager_DB();
		$db->static_install();

		error_log("activation");

	}
	function rest_init(){
		$this->rest->register_rest_route();
	}

	function deactivation() {}

	public function setup_hooks() {
		add_action( 'init',  array( $this, 'staticManager_init' ) );
	
		add_action('admin_menu',   array( $this, 'build_admin_menu'));
	}
	public static function staticManager_init() {
		error_log("staticManager_init");
		do_action( 'staticManager_init' );
	}

	public function build_admin_menu(){
		if ( is_admin() ) {
			require( $this->includes_dir . 'admin_page.php' );
			$this->admin = new StaticManager_Admin_Page();
			add_menu_page('Static Manager', 'Static Manager', 'manage_options', 'static-manager', array($this->admin,'display'));
		}
	}




	public function includes() {
		require( $this->includes_dir . 'database.php' );
		$this->db = new StaticManager_DB();
		require( $this->includes_dir . 'utils.php' );
		require( $this->includes_dir . 'rest-api.php' );
		$this->rest = new StaticManager_RestAPI();
	}
}

endif;

/**
 * A wrapper function that allows access to the StaticManager singleton
 *
 * We also use this function to bootstrap the plugin.
 *
 * @since 0.7
 */
function staticManager() {
	return StaticManager::init();
}


$_GLOBALS['staticManager'] = staticManager();
