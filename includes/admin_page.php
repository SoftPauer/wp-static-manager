<?php

if (!class_exists('StaticManager_Admin_Page')) :

    class StaticManager_Admin_Page
    {

        /**
         * Bootstrap for the Anthologize singleton
         *
         * @since 0.7
         * @return obj Anthologize instance
         */
        public static function init()
        {
            static $instance;
            if (empty($instance)) {
                $instance = new StaticManager_Admin_Page();
            }
            return $instance;
        }

        /**
         *	Creates the Dashboard Panel 
         */
        function __construct()
        {
        }

        function display()
        {

            
?>
            <div style="text-align:center;">

                <form method="post">
                    <input type="submit" name="Update the app" value="Update the app" />
                </form>
                <form method="post">
                    <input type="submit" name="Get zip" value="Zip Contents" target="_blank" />
                </form>
                <a href="/wp-content/plugins/wp-static-manager/wordpress.zip" download> Download zip</a>
                <form method="post" style="display: inline-grid;">
                    <input type="submit" name="Create index html mobile" value="Generate Mobile Code" />
                    <label>XPath queries to remove from all html </label>
                    <textarea name="xpaths" style="width:400px;height:100px;">
      <?php
            get_option('SM_XPATHS_REMOVE', "")
        ?>
      </textarea>
                </form>
                <form method="post">
                    <input type="submit" name="Create index html test" value="Generate Mobile Code - browser test" />
                </form>

            </div>
<?php

            if (isset($_POST['Update_the_app'])) {
                Utils::custom_logs("deleting: " . STATIC_MANAGER_MOBILE_HTML);
                Utils::delete_folder(STATIC_MANAGER_MOBILE_HTML);
                Utils::rename_dir(STATIC_MANAGER_MOBILE_HTML_TEMP, STATIC_MANAGER_MOBILE_HTML);
                staticManager()->db->update_file_hash();
            }
            if (isset($_POST['Get_zip'])) {
                Utils::get_new_zip();
            }
            if (isset($_POST['Create_index_html_mobile'])) {
                update_option('SM_XPATHS_REMOVE', stripslashes($_POST['xpaths']));
                error_log("create html");
                Utils::create_indexHtml();
            }
            if (isset($_POST['Create_index_html_test'])) {
                Utils::create_indexHtml("/wp-content/plugins/wp-static-manager/mobile-html");
            }
        }
    }
endif;
