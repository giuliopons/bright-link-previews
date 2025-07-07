<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/*
Plugin Name: Bright Link Previews
Plugin URI: http://www.barattalo.it/
Description: Show previews of links (clear, neat, simple), analyze links and track user behaviour on the links of your site
Version: 1.85
Author: Giulio Pons
*/

global $blpwp_option_defaults;

$blpwp_option_defaults = array(
	'selector'=>'',
	'selectorchk'=>'',
	'commentlinks'=>'on',
	'contentlinks'=>'on',
	'imagelinks'=>'on',
	'extint'=>'',
	'style'=>'rounded',
	'cache'=>'on',
	'counter'=>'',
	'blank'=>'on',
	'color'=>'#ffff00',
	'fgcolor'=>'#000000',
	'color0'=>'#ffffff',
	'fgcolor0'=>'#000000',
	'size'=>'10'
);


add_action('init', 'blpwp_plugin_init');


/**
 * On activation, add all functions to the scheduled action hook.
 * 
 * @return void
 */
function blpwp_activation() {
	// nothing
	blpwp_plugin_init();
}
register_activation_hook(__FILE__, 'blpwp_activation');


/**
 * function for initialization
 * 
 * @return void
 */ 
function blpwp_plugin_init() {
	global $blpwp_option_defaults;

	if ( ! get_option( 'blpwp_plugin_options' ) ) {
		add_option( 'blpwp_plugin_options', $blpwp_option_defaults );
	}

} 


$blpwp_labels = array(
	"loading-preview" => "<div class='blpwp_wrap'>" . __("Loading preview...","blpwp") ."</div>",
);


register_deactivation_hook( __FILE__, 'blpwp_deactivation' );
/**
 * On deactivation, remove all functions from the scheduled action hook.
 */
function blpwp_deactivation() {
	
}

function blpwp_get_plugin_version() {
	// this plugin version
	preg_match_all('/Version:(.*)$/m', file_get_contents( __FILE__ ), $matches );
	return $matches[1][0];	
}


function blpwp_scripts(){
	global $post, $blpwp_labels, $blpwp_option_defaults;

	if(!is_admin()) {

		$options = wp_parse_args(get_option('blpwp_plugin_options'), $blpwp_option_defaults	);

		$params = array(
			"options" => $options,
			"url" => admin_url( 'admin-ajax.php' ),
			"internals" => get_site_url(),
			"labels" => $blpwp_labels,
			"idpost" => get_queried_object_id()
		);

		// this plugin version
		$ver = blpwp_get_plugin_version();

		wp_register_script('blpwp_script_js',plugin_dir_url( __FILE__ ).'script.js',array('jquery'),$ver,true);
		wp_enqueue_script('blpwp_script_js');

		wp_register_style( 'blpwp_css', plugin_dir_url( __FILE__ ).'style.css', [],  $ver);
		wp_enqueue_style( 'blpwp_css' );

		wp_localize_script( 'blpwp_script_js', 'blpwp_params', $params );

	}

}
add_action('wp_enqueue_scripts','blpwp_scripts');


// add url to settings in list of plugins
add_filter( 'plugin_action_links_bright-link-previews/index.php', 'blpwp_settings_link' );
function blpwp_settings_link($links ) {
	
	$url = esc_url( add_query_arg(
		'page',
		'blpwp-plugin',
		get_admin_url() . 'options-general.php'
	) );
	
	$settings_link = "<a href='$url'>" . __( 'Settings' ) . '</a>';
	
	array_push(
		$links,
		$settings_link
	);
	return $links;
}




/**
 * On activation create table
 * 
 * @return void 
 */
function blpwp_activate() {
	global $wpdb;

	// CREATE MAIN TABLE
	$wpdb->query("
	CREATE TABLE if not exists `".$wpdb->prefix."blpwp_links` (
		`id_link` int(10) UNSIGNED NOT NULL,
		`id_post` int(10) UNSIGNED NOT NULL,
		`de_url` varchar(1024) NOT NULL,
		`hover` int(11) NOT NULL,
		`click` int(11) NOT NULL,
		`error` tinyint(3) NOT NULL,
		`fl_internal` tinyint(1) NOT NULL DEFAULT '0',
		`de_domain` varchar(150) NOT NULL DEFAULT '',
		`de_rel` varchar(50) NOT NULL DEFAULT ''		
	  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Data for blpwp plugin';") or die($wpdb->error);

	//
	// ADD PRIMARY KEY
	 $q = $wpdb->get_var(
		"SELECT count(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_NAME = '".$wpdb->prefix."blpwp_links' and INDEX_NAME='PRIMARY' AND TABLE_SCHEMA='".DB_NAME."'"
	 );
	 if($q == 0) {
	 	$wpdb->query("ALTER TABLE `".$wpdb->prefix."blpwp_links`	ADD PRIMARY KEY (`id_link`);") or die($wpdb->error);
	 }

	 // ADD AUTOINCREMENT
	$wpdb->query("ALTER TABLE `".$wpdb->prefix."blpwp_links`	 MODIFY `id_link` int(10) UNSIGNED NOT NULL auto_increment;") or die($wpdb->error);

	// NEW TABLE STATS
    $wpdb->query("
	CREATE TABLE if not exists `".$wpdb->prefix."blpwp_stats` ( `cd_link` INT NOT NULL , `thedate` DATE NOT NULL , `action` ENUM('click','hover') NOT NULL , `count` INT NOT NULL ) ENGINE = MyISAM;") or die($wpdb->error);

	// ADD UNIQUE
	$q = $wpdb->get_var(
		"SELECT count(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_NAME = '".$wpdb->prefix."blpwp_stats' and INDEX_NAME='cd_link' AND TABLE_SCHEMA='".DB_NAME."'"
	 );
	 if($q == 0) {
		$wpdb->query("ALTER TABLE `".$wpdb->prefix."blpwp_stats` ADD UNIQUE(`cd_link`, `thedate`, `action`);") or die($wpdb->error);
	 }


	

}

// activate plugin create table
register_activation_hook(__FILE__, 'blpwp_activate');



/**
 * Load admin scripts
 * 
 * @param string $hook
 * 
 * @return void
 */
function blpwp_admin_script($hook) {

	if($hook!="settings_page_blpwp-plugin") return;
	// admin scripts should be loaded only where they are useful:

	// this plugin version
	$ver = blpwp_get_plugin_version();
	
	wp_register_script('blpwp_script_js',plugin_dir_url( __FILE__ ).'admin.js',array('jquery'),$ver,true);
	wp_enqueue_style( 'blpwp_css', plugin_dir_url( __FILE__ ).'style.css', [], $ver );
	wp_enqueue_style( 'blpwp_admin_css', plugin_dir_url( __FILE__ ).'admin.css', [], $ver );
	wp_enqueue_script('blpwp_script_js');

    // tabulator external css and js libraries
    wp_register_script('blpwp_script_tabulator_js',plugin_dir_url( __FILE__ )."assets/tabulator/tabulator.min.js");
    wp_enqueue_style( 'blpwp_script_tabulator_css',plugin_dir_url( __FILE__ )."assets/tabulator/tabulator.min.css");
    wp_enqueue_style( 'blpwp_script_tabulator_css_theme',plugin_dir_url( __FILE__ )."assets/tabulator/tabulator_midnight.min.css");    
    wp_enqueue_script('blpwp_script_tabulator_js');

	wp_register_script('blpwp_script_chart_js',plugin_dir_url( __FILE__ )."assets/charts/chart.js");
	wp_enqueue_script('blpwp_script_chart_js');
		
		
}
add_action( 'admin_enqueue_scripts', 'blpwp_admin_script' );
 	

/**
 * Parse $content and add classname to links to handle js behaviour
 * 
 * @param string $html
 * @param string $classname
 * 
 * @return string
 */
function blpwp_addClassToLinks($html, $classname) {
    // Define the regular expression pattern to match <a> tags
    $pattern = '/<a(.*?)>/i';
    
    // Callback function to add or modify class attribute
    $callback = function ($matches) use ($classname) {
        $tag = $matches[0];
        $attributes = $matches[1];
        
        // Check if the <a> tag already has a class attribute
        if (preg_match('/class\s*=\s*["\'](.*?)["\']/i', $attributes, $classMatches)) {
            // If class attribute already exists, concatenate the new class
            $existingClasses = $classMatches[1];
            $classes = explode(' ', $existingClasses);
            
            // Check if the class already exists
            if (!in_array($classname, $classes)) {
                $classes[] = $classname;
            }
            $classAttribute = 'class="' . implode(' ', $classes) . '"';
            
            // Replace the existing class attribute with the modified one
            $attributes = preg_replace('/class\s*=\s*["\'](.*?)["\']/i', $classAttribute, $attributes);
        } else {
            // If no class attribute exists, create it with the new class
            $classAttribute = 'class="' . $classname . '"';
            $attributes .= ' ' . $classAttribute;
        }
        
        // Concatenate the modified tag
        return '<a' . $attributes . '>';
    };
    
    // Use preg_replace_callback to replace <a> tags with modified ones
    $html = preg_replace_callback($pattern, $callback, $html);
    
    return $html;
}




/**
 *  add classname on content links
 * 
 * @param string $content
 * 
 * @return string
 * */ 
function blpwp_filter_the_content_links( $content ) {
	if($content) {
		if(!is_admin()) {
			$options = get_option( 'blpwp_plugin_options' );
			$val = isset($options['contentlinks']) ? $options['contentlinks'] : "";
			if ($val) 
				return blpwp_addClassToLinks($content, "blpwp");
		}
		return $content;
	} else {
		return null;
	}
}
add_filter( 'the_content', 'blpwp_filter_the_content_links', 10 );



/**
 *  add classname on comments links
 * 
 * @param string $content
 * 
 * @return string
 * */ 
function blpwp_filter_the_comments_links( $comment_text ) {
	if(!is_admin()) {
		$options = get_option( 'blpwp_plugin_options' );
		$val = isset($options['commentlinks']) ? $options['commentlinks'] : "";
		if ($val) 
			return blpwp_addClassToLinks($comment_text, "blpwp_comment");
	}
	return $comment_text;
}
add_filter( 'comment_text', 'blpwp_filter_the_comments_links' );





include("assets/minibots.class.php");

include("ajax.php");

include("paging.php");

include("settings-page.php");
