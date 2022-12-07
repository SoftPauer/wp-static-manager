<?php

if ( ! class_exists( 'Utils' ) ) :
class Utils
{

    public static $re = '/(src=\\"\/|src="h|src:url\("\/|https:\/\/|http:\/\/|\/\/|src="\/|href="\/|http:\\\\\/\\\\\/)([0-9a-zA-\\\\Z_\-\/:.@]+\.(css|js|png|jpg|jpeg|tff|svg|woff2|eot|woff|ttf|gif|com\/css\?)(\?v=\d+)?(family=[0-9a-zA-\\\\Z_\-\/:.]+)?)/';
    /**
     * Use output buffering to convert a function that echoes
     * to a return string instead
     */
    public static function echo_to_string($function, $args = array())
    {
        ob_start();
        call_user_func($function, ...$args);
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    public static function custom_logs($message)
    {
        if (is_array($message)) {
            $message = json_encode($message);
        }
        $file = fopen(STATIC_MANAGER_PLUGIN_DIR . "/custom_logs.log", "a");
        fwrite($file, "\n" . date('Y-m-d h:i:s') . " :: " . $message);
        fclose($file);
    }

    public static function get_content($page, $queryKey = "page_id")
    {
        $get_post = new \WP_Query(array($queryKey => $page["id"]));
        if ($get_post->have_posts()) {
            $get_post->the_post();
            get_template_part('template-parts/content/content-page');
        }
    }

    public static function dirToArray($dir = '')
    {
        $result = array();

        $cdir = scandir(STATIC_MANAGER_MOBILE_HTML . $dir);
        foreach ($cdir as $key => $value) {
            if (!in_array($value, array(".", ".."))) {
                if (is_dir(STATIC_MANAGER_MOBILE_HTML . $dir . DIRECTORY_SEPARATOR . $value)) {
                    $result = array_merge($result,  Utils::dirToArray($dir . DIRECTORY_SEPARATOR . $value));
                } else {
                    $result[] = array(
                        "filepath" => $dir . DIRECTORY_SEPARATOR . $value,
                        "hash" => hash_file('md5', STATIC_MANAGER_MOBILE_HTML . $dir . DIRECTORY_SEPARATOR . $value),
                    );
                }
            }
        }

        return $result;
    }

    public static function get_new_zip()
    {

        // Enter the name to creating zipped directory
        $filename = STATIC_MANAGER_PLUGIN_DIR . "wordpress.zip";
        Utils::custom_logs("zip: " . $filename);
        Utils::Zip(STATIC_MANAGER_MOBILE_HTML, $filename);

        // if (file_exists($filename)) {
        //     header('Content-Type: application/zip');
        //     header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        //     header('Content-Length: ' . filesize($filename));

        //     ob_clean(); // why do I need this??
        //     ob_end_flush();
        //     flush();
        //     readfile($filename);
        //     die();
        // }
        // readfile($zipcreated);
    }


    public static function Zip($source, $destination)
    {
        if (!extension_loaded('zip') || !file_exists($source)) {
            Utils::custom_logs("zip extension not loaded");
            return false;
        }
        $zip = new \ZipArchive();
        if (!$zip->open($destination, \ZIPARCHIVE::CREATE)) {
            return false;
        }
        $source = str_replace('\\', '/', realpath($source));
        if (is_dir($source) === true) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source), \RecursiveIteratorIterator::SELF_FIRST);
            foreach ($files as $file) {
                $file = str_replace('\\', '/', $file);
                // Ignore "." and ".." folders
                if (in_array(substr($file, strrpos($file, '/') + 1), array('.', '..')))
                    continue;
                $file = realpath($file);
                if (is_dir($file) === true) {
                    $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                } else if (is_file($file) === true) {
                    $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
                }
            }
        } else if (is_file($source) === true) {
            $zip->addFromString(basename($source), file_get_contents($source));
        }

        return $zip->close();
    }


    /**
     * https://github.com/spatie/crawler may use later
     */
    public static function create_indexHtml($prefix = "")
    {
        //remove html queries 
        $queries = explode(",", get_option('SM_XPATHS_REMOVE', ""));

        Utils::delete_folder(STATIC_MANAGER_MOBILE_HTML_TEMP);

        try {
            $indexHtml = STATIC_MANAGER_MOBILE_HTML_TEMP . "/index.html";
            Utils::custom_logs("site url " . get_site_url());
            $internalUrl =  get_site_url() === "http://localhost:8080" ? "http://127.0.0.1:80" : get_site_url();
            Utils::custom_logs("html " .  $internalUrl );
            
            $indexHtmlContent  = file_get_contents($internalUrl, false);
            Utils::custom_logs("html " .  $indexHtmlContent);
            $doc = new \DOMDocument();
            $doc->loadHTML($indexHtmlContent);
            $xpath = new \DOMXPath($doc);
            foreach ($queries as $query) {
                foreach ($xpath->query($query) as $e) {
                    // Delete this node
                    $e->parentNode->removeChild($e);
                }
            }
            //add mobile.css
            $mobile_css_element = $doc->createElement('style', file_get_contents(STATIC_MANAGER_PLUGIN_DIR . "/mobile.css"));
            $elm_type_attr = $doc->createAttribute('type');
            $elm_type_attr->value = 'text/css';
            $mobile_css_element->appendChild($elm_type_attr);
            $head = $doc->getElementsByTagName('head');
            $head[0]->appendChild($mobile_css_element);

            // add image-preloader.js
            $image_preloader_element = $doc->createElement('script');
            $elm_type_attr = $doc->createAttribute('type');
            $elm_type_attr->value = 'text/javascript';
            $elm_src_attr = $doc->createAttribute('src');
            $elm_src_attr->value = '/wp-content/plugins/wp-static-manager/image-preloader.js';
            $image_preloader_element->appendChild($elm_type_attr);
            $image_preloader_element->appendChild($elm_src_attr);
            $head = $doc->getElementsByTagName('head');
            $head[0]->appendChild($image_preloader_element);

            // add site-url.js
            $site_url_element = $doc->createElement('script');
            $elm_type_attr = $doc->createAttribute('type');
            $elm_type_attr->value = 'text/javascript';
            $elm_src_attr = $doc->createAttribute('src');
            $elm_src_attr->value = '/wp-content/plugins/wp-static-manager/site-url.js';
            $site_url_element->appendChild($elm_type_attr);
            $site_url_element->appendChild($elm_src_attr);
            $head = $doc->getElementsByTagName('head');
            $head[0]->appendChild($site_url_element);

            // add imagesloaded.min.js
            $imagesloaded_element = $doc->createElement('script', "imagesLoaded=" . file_get_contents(ABSPATH . "/wp-includes/js/imagesloaded.min.js"));
            $elm_type_attr = $doc->createAttribute('type');
            $elm_type_attr->value = 'text/javascript';

            // add mobile_hide.js

            $mobile_hide_element = $doc->createElement('script');
            $elm_type_attr = $doc->createAttribute('type');
            $elm_type_attr->value = 'text/javascript';
            $elm_src_attr = $doc->createAttribute('src');
            $elm_src_attr->value = '/wp-content/plugins/wp-static-manager/mobile_hide.js';
            $mobile_hide_element->appendChild($elm_type_attr);
            $mobile_hide_element->appendChild($elm_src_attr);
            $loader = $doc->getElementById('loader');

            // $loader->appendChild($mobile_hide_element);


            $imagesloaded_element->appendChild($elm_type_attr);
            $head = $doc->getElementsByTagName('head');
            $head[0]->appendChild($imagesloaded_element);

            $indexHtmlContent = $doc->saveHTML($doc->documentElement);
            $urls = Utils::find_all_urls_in_string($indexHtmlContent);
            // $urls = Utils::find_strings_in_array_starting($urls, "http://localhost:8080");
            error_log("urls: " . json_encode($urls));
            $resUrls = Utils::filter_resource_urls($urls);

            foreach ($resUrls as $key => $value) {
                $path = Utils::get_path_from_url($value);
                if ($path[0] !== "/") {
                    $path = "/" . $path;
                }
                Utils::download_file($value, $path);
            }

            // lets inject preload urls into image preloader script 
            // Utils::find_and_replace_content_in_file(STATIC_MANAGER_MOBILE_HTML . "/wp-content/plugins/static-manager" . "/image-preloader.js", "var preload_urls = [];", "var preload_urls = " . json_encode($preload_urls) . ";");
            $indexHtmlContent = Utils::replace_urls_with_abs_path($indexHtmlContent, $prefix);
            Utils::createFile($indexHtml, $indexHtmlContent);
            // Utils::copy_file(STATIC_MANAGER_PLUGIN_DIR . "./sw.js", STATIC_MANAGER_MOBILE_HTML_TEMP); // no need server worker for now
            Utils::download_resources_from_pages($prefix);

            Utils::custom_copy('/var/www/html/wp-content/plugins/wp-reactpress/css/', STATIC_MANAGER_MOBILE_HTML_TEMP . '/wp-content/reactpress/css');
            Utils::custom_copy('/var/www/html/wp-content/plugins/wp-reactpress/dashboard/', STATIC_MANAGER_MOBILE_HTML_TEMP . '/wp-content/reactpress/dashboard');
            Utils::custom_copy('/var/www/html/wp-content/plugins/wp-reactpress/images/', STATIC_MANAGER_MOBILE_HTML_TEMP . '/wp-content/reactpress/images');
            Utils::custom_copy('/var/www/html/wp-content/plugins/wp-reactpress/Itinerary/', STATIC_MANAGER_MOBILE_HTML_TEMP . '/wp-content/reactpress/Itinerary');
            Utils::custom_copy('/var/www/html/wp-content/plugins/wp-reactpress/js/', STATIC_MANAGER_MOBILE_HTML_TEMP . '/wp-content/reactpress/js');
            Utils::custom_copy('/var/www/html/wp-content/plugins/wp-reactpress/profile/', STATIC_MANAGER_MOBILE_HTML_TEMP . '/wp-content/reactpress/profile');
            Utils::custom_copy('/var/www/html/wp-content/plugins/wp-reactpress/profile-v2/build/', STATIC_MANAGER_MOBILE_HTML_TEMP . '/wp-content/reactpress/profile-v2/build');


            //Find all index.html files and add theme class to html tag
            Utils::add_theme_class_to_html_files();


        } catch (\Throwable $th) {
            Utils::custom_logs("error: " . $th->getMessage());
            Utils::custom_logs("error line:" . $th->getLine());

            return "error";
        }
    }

    public static function rename_dir($old_name, $new_name)
    {
        if (file_exists($old_name)) {
            rename($old_name, $new_name);
        }
    }

    public static function  add_theme_class_to_html_files()
    {
        $reactpress_pages = [STATIC_MANAGER_MOBILE_HTML_TEMP . '/wp-content/reactpress/profile/index.html'];
        $site_name = str_replace(" ","_",get_bloginfo('name'));
        foreach ($reactpress_pages as $file) {
            $content = file_get_contents($file);
            $content = str_replace("<html", '<html class="'. $site_name. '"', $content);
            file_put_contents($file, $content);
        }
    }

    public static function delete_folder($dir)
    {
        if (!file_exists($dir)) {
            Utils::custom_logs("dir doesnt exist");
            return true;
        }

        if (!is_dir($dir)) {
            Utils::custom_logs(" is not dir ");
            Utils::custom_logs("directory: " . $dir);
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            Utils::custom_logs("dir item" . $item);
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!Utils::delete_folder($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }

    public static function find_and_replace_content_in_file($file, $find, $replace)
    {
        $content = file_get_contents($file);
        $content = str_replace($find, $replace, $content);
        file_put_contents($file, $content);
    }

    public static function custom_copy($src, $dst){
        Utils::custom_logs("this is source file: " . $src);
        // open the source directory
    $dir = opendir($src); 
   
    // Make the destination directory if not exist
    @mkdir($dst,0777,true); 
   
    // Loop through the files in source directory
    foreach (scandir($src) as $file) { 
   
        if (( $file != '.' ) && ( $file != '..' )) { 
            if ( is_dir($src . '/' . $file) ) 
            { 
   
                // Recursively calling custom copy function
                // for sub directory 
                Utils::custom_copy($src . '/' . $file, $dst . '/' . $file); 
   
            } 
            else { 
                copy($src . '/' . $file, $dst . '/' . $file); 
            } 
        } 
    } 
   
    closedir($dir);
    }

    public static function copy_file($source, $destination, $noWebPExtension = false)
    {
        if (!file_exists($source)) {
            return false;
        }

        if (!is_dir($destination)) {
            return false;
        }

        $destination = $destination . DIRECTORY_SEPARATOR . basename($source);
        if ($noWebPExtension) {
            $destination = str_replace(".webp", "", $destination);
        }
        return copy($source, $destination);
    }

    public static function download_resources_from_pages($prefix)
    {
        $pages = staticManager()->rest->get_pages();
        $preload_urls = [];
        foreach ($pages->data as $key => $value) {
            Utils::custom_logs("page: " . $value->id);
            $content = $value["content"];
            $urls = Utils::find_all_urls_in_string($content);

            Utils::custom_logs("urls: " . json_encode($urls));
            $resUrls = Utils::filter_resource_urls($urls);
            Utils::custom_logs("resource urls: " . json_encode($resUrls));

            $preurls = Utils::get_preload_urls($resUrls);
            foreach ($preurls as $key => $value) {
                $preurls[$key] = Utils::replace_url_with_abs_path($value, $prefix);
            }
            $preload_urls =  array_merge($preload_urls, $preurls);
            $preload_urls = array_unique($preload_urls);
            foreach ($resUrls as $key => $value) {
                $path = Utils::get_path_from_url($value);
                if ($path[0] !== "/") {
                    $path = "/" . $path;
                }
                Utils::download_file($value, $path);
            }
        }

        $posts = staticManager()->rest->get_posts();
        foreach ($posts->data as $key => $value) {
            $content = $value["content"];
            $urls = Utils::find_all_urls_in_string($content);

            Utils::custom_logs("urls: " . json_encode($urls));
            $resUrls = Utils::filter_resource_urls($urls);
            Utils::custom_logs("resource urls: " . json_encode($resUrls));

            $preurls = Utils::get_preload_urls($resUrls);
            foreach ($preurls as $key => $value) {
                $preurls[$key] = Utils::replace_url_with_abs_path($value, $prefix);
            }
            $preload_urls =  array_merge($preload_urls, $preurls);
            $preload_urls = array_unique($preload_urls);
            foreach ($resUrls as $key => $value) {
                $path = Utils::get_path_from_url($value);
                if ($path[0] !== "/") {
                    $path = "/" . $path;
                }
                Utils::download_file($value, $path);
            }
        }
        // lets inject preload urls into image preloader script
        Utils::find_and_replace_content_in_file(STATIC_MANAGER_MOBILE_HTML_TEMP . "/wp-content/plugins/static-manager" . "/image-preloader.js", "var preload_urls = [];", "var preload_urls = " . json_encode($preload_urls) . ";");
    }



    // write new file with new content
    public static function createFile($file, $content)
    {
        $myfile = fopen($file, "w") or die("Unable to open file!");
        fwrite($myfile, $content);
        fclose($myfile);
    }
    public static function find_strings_in_array_starting($array, $string)
    {
        $result = array();
        foreach ($array as $key => $value) {
            if (strpos($value, $string) === 0) {
                $result[] = $value;
            }
        }
        return $result;
    }
    public static function find_all_urls_in_string($string)
    {

        preg_match_all(Utils::$re, $string, $matches);
        Utils::custom_logs("matches: " . json_encode($matches[0]));
        return $matches[0];
    }
    public static function filter_resource_urls($urls)
    {
        $result = array();

        foreach ($urls as $key => $value) {
            $value = Utils::sanitize_url_for_download($value);
            if (
                strpos($value, ".css") !== false ||
                strpos($value, ".js") !== false ||
                strpos($value, ".png") !== false ||
                strpos($value, ".jpg") !== false ||
                strpos($value, ".jpeg") !== false ||
                strpos($value, ".gif") !== false ||
                strpos($value, ".svg") !== false ||
                strpos($value, ".woff") !== false ||
                strpos($value, ".woff2") !== false ||
                strpos($value, ".ttf") !== false ||
                strpos($value, ".eot") !== false ||
                strpos($value, ".ico") !== false ||
                strpos($value, ".mp4") !== false ||
                strpos($value, ".mp3") !== false ||
                strpos($value, "fonts.googleapis.com") !== false // google fonts generate content, not static resource file
            ) {
                array_push($result, $value);
            }
        }
        return $result;
    }
    public static function get_preload_urls($urls)
    {
        $result = array();

        foreach ($urls as $key => $value) {
            if (
                strpos($value, ".png") !== false ||
                strpos($value, ".jpg") !== false ||
                strpos($value, ".jpeg") !== false ||
                strpos($value, ".svg") !== false
            ) {
                array_push($result, $value);
            }
        }
        return $result;
    }

    /**
     * makes path webp if file exists
     */
    public static function webp_path($path)
    {
        if (
            strpos($path, ".png") !== false ||
            strpos($path, ".jpg") !== false ||
            strpos($path, ".jpeg") !== false
        ) {
            $pathFromWPContent = str_replace("/wp-content", "", $path);
            if (
                file_exists("/var/www/html/wp-content/uploads-webpc" . $pathFromWPContent . ".webp")
            ) {
                return "/wp-content/uploads-webpc" . $pathFromWPContent . ".webp";
            }
        }
        return false;
    }

    public static function download_file($url, $path)
    {
        $mobilePath = STATIC_MANAGER_MOBILE_HTML_TEMP . $path;
        Utils::custom_logs("downloading: " . $url . " to " . $mobilePath);
        $dir = Utils::get_directory_from_path($mobilePath);
        Utils::check_if_directory_exists($dir);
        $context = stream_context_create(
            array(
                'http' => array(
                    'follow_location' => true
                )
            )
        );

        $webP = Utils::webp_path($path);
        if ($webP) {
            Utils::custom_logs("file is webp" . $webP);
            Utils::copy_file("/var/www/html" . $webP, $dir, true);
            return;
        }

        if (
            file_exists("/var/www/html" . $path)
        ) {
            Utils::custom_logs("local file exists" . $path);
            $file = file_get_contents("/var/www/html" . $path, false);
        } else {
            $url = str_replace("http://localhost:8080", "http://localhost:80", $url); //TODO: remove this  (docker runs on 80 inside but we map to 8080 external )
            $file = file_get_contents($url, false, $context);
        }

        if ($file === false) {
            Utils::custom_logs("error downloading file: " . $url);
            return false;
        }
        //jqueryEvent is skipped as we need it to run jquery ready once
        if (strpos($url, "jqueryEvent.js") === false) {
            $file = str_replace(["jQuery(document).ready(function($)"], 'window.jQuery( document).on( PRIVATE_JQUERY_EVENT, function(_,$)', $file);

            $file = str_replace(["$(document).ready(", "window.jQuery(document).ready(", "jQuery(document).ready("], 'window.jQuery(document).on( PRIVATE_JQUERY_EVENT ,', $file);
            $file = str_replace(["t(document).ready("], 'window.jQuery(document).on( "_jquery_ready" ,', $file);
        }
        if (file_put_contents($mobilePath, $file)) {
            Utils::custom_logs("File downloaded successfully");
        } else {
            Utils::custom_logs("File downloading failed.");
        }
        // fclose($file);
    }
    public static function check_if_directory_exists($dir)
    {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
    }
    public static function get_directory_from_path($path)
    {
        $path = str_replace("\\", "/", $path);
        $path = explode("/", $path);
        array_pop($path);
        $path = implode("/", $path);
        return $path;
    }
    public static function get_path_from_url($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        Utils::custom_logs("path: " . $path);
        return $path;
    }
    public static function replace_urls_with_abs_path($content, $prefix = '')
    {
        $content = preg_replace_callback(Utils::$re, function ($matches) use ($prefix) {
            return Utils::replace_url_with_abs_path($matches[0], $prefix);
        }, $content);

        return $content;
    }
    public static function replace_url_with_abs_path($url, $prefix = '')
    {
        $urlDownload = Utils::sanitize_url_for_download($url);
        $path = Utils::get_path_from_url($urlDownload);
        Utils::custom_logs("path: " . $path);
        Utils::custom_logs("url: " . $url);
        if (strpos($path, "//") === 0) { // for some reason some urls have // in the path??
            $path = substr($path, 1);
        }
        if ($prefix !== '') {
            $path = $prefix . $path;
        }
        if (strpos($url, "src=\"//") === 0) {
            return "src=\"" . $path;
        }
        if (strpos($url, "src=\"http") === 0) {
            return "src=\"" . $path;
        }
        if (strpos($url, "src=\"") === 0) {
            return "src=\"" . $path;
        }
        if (strpos($url, "href=\"") === 0) {
            return "href=\"" . $path;
        }
        if (strpos($url, "src:url(\"") === 0) {
            return "src:url(\"" . $path;
        }
        if (strpos($url, "//") === 0) {
            return $path;
        }
        return  $path;
    }

    public static function sanitize_url_for_download($url)
    {

        if (strpos($url, "//localhost") === 0) {
            return  "http:" . $url;
        }
        if (strpos($url, "//") === 0) {
            return "https:" . $url;
        }
        if (strpos($url, "src=\"//") === 0) {
            return str_replace("src=\"", "https:", $url);
        }
        if (strpos($url, "src=\"http") === 0) {
            return str_replace("src=\"", "", $url);
        }
        if (strpos($url, "src=\"") === 0) {
            return str_replace("src=\"", get_site_url(), $url);
        }
        if (strpos($url, "href=\"") === 0) {
            return str_replace("href=\"", get_site_url(), $url);
        }
        if (strpos($url, "src:url(\"") === 0) {
            return str_replace("src:url(\"", get_site_url(), $url);
        }
        return $url;
    }
    public static function remove_lazy_loading_from_html($content)
    {
        $content = str_replace("loading=\"lazy\"", "", $content);
        return $content;
    }
    public static function find_and_replace($content, $find, $replace)
    {
        $content = str_replace($find, $replace, $content);
        return $content;
    }
    public static function remove_elements_from_html_with_css_selector($content, $selector)
    {
        $content = preg_replace_callback(Utils::$re, function ($matches) use ($selector) {
            if (strpos($matches[0], $selector) !== false) {
                return "";
            }
            return $matches[0];
        }, $content);
        return $content;
    }
}

endif;