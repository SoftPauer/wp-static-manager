<?php

namespace StaticManager;

final class StaticManager
{
  /**
   * @var StaticManager
   */
  private static $instance = null;
  /**
   * @var RestApi
   */
  private $restAPI;

  /**
   * @var Database
   */
  private $database;

  private function __construct()
  {

    require_once STATIC_MANAGER_PLUGIN_DIR . 'includes/load.php';
    add_action('init', array($this, 'init'), 0);
   
    add_action('admin_menu', '\StaticManager\StaticManager::static_setup_menu');
    wp_enqueue_script("jquery_private_event",  "/wp-content/plugins/static-manager/jqueryEvent.js", array('jquery'), "1.0.0", false);
    wp_enqueue_script("mobile_navigation",  "/wp-content/plugins/static-manager/navigation.js", array('jquery'), "1.0.0", false);
    wp_enqueue_script("service_worker",  "/wp-content/plugins/static-manager/serviceWorkerReg.js", array('jquery'), "0.0.1", false);
    register_activation_hook(STATIC_MANAGER_PLUGIN_DIR . "includes/database.php", 'static_install');
  }

  /**
   * Init StaticManager when WordPress Initialises.
   */
  public function init()
  {
    $this->restAPI = new RestAPI();
    $this->database = new Database();
  }

  /**
   * @return StaticManager
   */
  public static function getInstance()
  {
    if (self::$instance == null) {
      self::$instance = new StaticManager();
    }
    return self::$instance;
  }

  public function database()
  {
    return $this->database;
  }
  public function rest()
  {
    return $this->restAPI;
  }

  public static function static_setup_menu()
  {
    add_menu_page('Static Manager', 'Static Manager', 'manage_options', 'static-manager', '\StaticManager\StaticManager::static_init');
  }

  public static function static_init()
  {

    $paths = get_option('SM_XPATHS_REMOVE', "");
    echo '<div style="text-align:center;">
      
    <form method="post">
        <input type="submit" name="Update the app"
                value="Update the app"/>
    </form>
    <form method="post">
        <input type="submit" name="Get zip"
                value="Zip Contents" target="_blank"/>
    </form>
    <a href="/wp-content/plugins/static-manager/wordpress.zip" download> Download zip</a>
    <form method="post" style="display: inline-grid;" >
    <input type="submit" name="Create index html mobile"
                value="Generate Mobile Code"/>
    <label >XPath queries to remove from all html </label>
    <textarea name="xpaths" style="width:400px;height:100px;">'
      . $paths;
    echo '
    </textarea>
    </form>
    <form method="post">
    <input type="submit" name="Create index html test"
                value="Generate Mobile Code - browser test"/>
    </form>
    
    </div>
    ';


    if (isset($_POST['Update_the_app'])) {
      Utils::custom_logs("deleting: " . STATIC_MANAGER_MOBILE_HTML);
      Utils::delete_folder(STATIC_MANAGER_MOBILE_HTML);
      Utils::rename_dir(STATIC_MANAGER_MOBILE_HTML_TEMP, STATIC_MANAGER_MOBILE_HTML);
      StaticManager::getInstance()->database()->update_file_hash();
    }
    if (isset($_POST['Get_zip'])) {
      Utils::get_new_zip();
    }
    if (isset($_POST['Create_index_html_mobile'])) {
      update_option('SM_XPATHS_REMOVE', stripslashes($_POST['xpaths']));
      Utils::create_indexHtml();
    }
    if (isset($_POST['Create_index_html_test'])) {
      Utils::create_indexHtml("/wp-content/plugins/static-manager/mobile-html");
    }
  }
}
