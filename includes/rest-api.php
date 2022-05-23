<?php

namespace StaticManager;

class RestAPI
{
    protected $_namespace = 'staticManager/v1';

    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_rest_route']);
    }
    public function register_rest_route()
    {
        register_rest_route($this->_namespace, '/data', array(
            array(
                'methods'   => 'GET',
                'callback' => [$this, 'custom_menu_endpoint'],
            )
        ));

        register_rest_route($this->_namespace, '/updateneeded/(?P<version>\d+)', array(
            'methods' => 'GET',
            'callback' =>  [$this, 'is_update_needed'],
        ));

        register_rest_route($this->_namespace, '/filemap', array(
            'methods' => 'GET',
            'callback' => [$this, 'get_file_map'],
        ));
    }

    function custom_menu_endpoint($request)
    {
        $prefix = $request->get_query_params()["prefix"];
        $pages = $this->get_pages(true, $prefix);
        $posts = $this->get_posts(true, $prefix);

        ///filter out not needed html 
        $queries =explode(",",get_option('SM_XPATHS_REMOVE', ""));
        for ($i = 0; $i < count($pages->data); $i++) {
            $doc = new \DOMDocument('1.0', 'utf-8');
            $content = mb_convert_encoding($pages->data[$i]["content"], 'HTML-ENTITIES', 'UTF-8');
            $doc->loadHTML($content );
            $xpath = new \DOMXPath($doc);
            foreach ($queries as $query) {
                foreach ($xpath->query($query) as $e) {
                    // Delete this node
                    $e->parentNode->removeChild($e);
                }
            }
            $pages->data[$i]["content"] =$doc->saveHTML($doc->documentElement);
            
        }
     
        // $ret_obj['menu'] = $rest_menu;
        $ret_obj['pages'] = $pages;
        $ret_obj['posts'] = $posts;


        return apply_filters('rest_menus_format_menus', $ret_obj);
    }

    /**
     * Checks if update is needed based on "static_file_version" option 
     * @param int $version "static_file_version" option from the client .
     * @return bool 
     */
    function is_update_needed($data)
    {
        $v = get_option('static_file_version', 0);

        if ($v == $data['version']) {
            return false;
        }
        return true;
    }

    /**
     * Returns file map {filepath, hash} 
     * @return  array 
     */
    function get_file_map()
    {
        return StaticManager::getInstance()->database()->get_file_map();
    }

    public function get_pages($absolutePaths = false, $prefix = '')
    {
         Utils::custom_logs("get_pages");
        $rest_controller = new \WP_REST_Posts_Controller('page');
        $query = new \WP_REST_Request();
        $query->set_query_params(
            array(
                'per_page'  => 100,
            )
        );
        $pages = $rest_controller->get_items($query);
        for ($i = 0; $i < count($pages->data); $i++) {
            
            $pages->data[$i]["content"] = Utils::echo_to_string('\StaticManager\Utils::get_content', [$pages->data[$i]]);
            if ($absolutePaths) {
                $pages->data[$i]["content"] = Utils::replace_urls_with_abs_path($pages->data[$i]["content"], $prefix);
            }
            $pages->data[$i]["content"] = Utils::remove_lazy_loading_from_html($pages->data[$i]["content"]);
        }
         Utils::custom_logs("get_pages done");
        return $pages;
    }
    public function get_posts($absolutePaths = false, $prefix = '')
    {
        $rest_controller = new \WP_REST_Posts_Controller('post');
        $query = new \WP_REST_Request();
        $query->set_query_params(
            array(
                'per_page'  => 100,
            )
        );
        $posts = $rest_controller->get_items($query);
        for ($i = 0; $i < count($posts->data); $i++) {
            $posts->data[$i]["content"] = Utils::echo_to_string('\StaticManager\Utils::get_content', [$posts->data[$i],"p"]);
            if ($absolutePaths) { 
                $posts->data[$i]["content"] = Utils::replace_urls_with_abs_path($posts->data[$i]["content"], $prefix);
            }
            $posts->data[$i]["content"] = Utils::remove_lazy_loading_from_html($posts->data[$i]["content"]);
        }
        return $posts;
    }
}
