<?php

if ( !class_exists( 'StaticManager_DB' ) ) :
class StaticManager_DB
{
    protected $static_db_version, $sm_table_name;

    public function  __construct()
    {
        global  $wpdb;
        $this->static_db_version = '1.0';
        $this->sm_table_name = $wpdb->prefix . 'static_files';
    }

    public function static_install()
    {

        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();
        Utils::custom_logs("static_install");
        $sql = "CREATE TABLE $this->sm_table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		filename text NOT NULL,
		hash text NOT NULL,
		PRIMARY KEY  (id)) $charset_collate;"; 

        dbDelta($sql);
        add_option('static_manager_db_version', $this->static_db_version);
        add_option('static_file_version', 0); // with every update this number is increased if client and server have the same version no updates needed 
    }
    /**
     * Returns file map {filepath, hash} 
     * @return  array 
     */
    public function get_file_map()
    {
        global $wpdb;
        $v = get_option('static_file_version', 0);
        $map = $wpdb->get_results("SELECT filename, hash FROM $this->sm_table_name");
        return  ["map" => $map, "version" => $v];
    }
    public function update_file_hash()
    {
        global $wpdb;
        $files_array = Utils::dirToArray();
        Utils::custom_logs("file array: " . json_encode($files_array));
        $wpdb->get_results("DELETE FROM $this->sm_table_name");

        $sql = "INSERT INTO $this->sm_table_name 
        (time, filename, hash) VALUES";
        foreach ($files_array as &$value) {
            $sql = $sql . "(now(),'$value[filepath]','$value[hash]'),";
        }
        $sql = rtrim($sql, ", ");
        Utils::custom_logs("sql: " . $sql);
        $wpdb->get_results($sql);
        update_option('static_file_version', get_option('static_file_version', 0) + 1); // updating static files version 

    }
}

endif;
