<?php
/*
Plugin Name: Thesis Cache(r)
Version: 1.2.2
Description: A plugin to help make your thesis installation leverage caching
Author: Mike Van Winkle
Author URI: http://www.mikevanwinkle.com
Plugin URI: http://www.mikevanwinkle.com/wordpress/plugins/wp30-events/
License: GPL
*/

include_once(dirname(__FILE__).'/lib/class.thesiscache.php'); 
define("TC_PLUGIN_DIR", rtrim(dirname(__FILE__)));
/*
 * Activation 
 */

register_activation_hook(__FILE__,'thesis_cacher_activate');
function thesis_cacher_activate() {
	global $wp_version;
	if(version_compare($wp_version, "3.0", "<")) { 
		exit("Dude, upgrade your stinkin Wordpress Installation."); 
	}

}

register_deactivation_hook(__FILE__,'thesis_cacher_deactivation');
function thesis_cacher_deactivation() {

		$obcache_file = WP_CONTENT_DIR.'/object-cache.php';
		if(file_exists($obcache_file)) {
			
			$object_cache = file_get_contents($obcache_file);

			//if this is the object-cache installed by thesis cache(r) remove it.
			if( preg_match("/THESISCACHER/",$object_cache) )
				unlink($obcache_file);
		
		}
}


/*
 * Initialize
 */

$GLOBALS['thesis_cacher'] = new ThesisCacher();
/*
 * Thesis Cache Debugger
*/
if(  $thesis_cacher->settings['user']['debug'] == 'on' AND !is_admin() ) {
	define('SAVEQUERIES',true);
	add_action('init','thesis_cache_debug_start');
	function thesis_cache_debug_start() {
		global $thesisstart;
		if(empty($thesisstart)) {
			$thesisstart = array('mem'=> memory_get_usage(true),'time'=>microtime());
		}
	}
	
	add_action('shutdown','thesis_cache_debug');
	function thesis_cache_debug() {		
		global $thesis_cacher,$wpdb,$thesisstart;
		echo '<!-- Total Queries: '.count($wpdb->queries).'-->';
		if(!empty($wpdb->queries)) {
			foreach($wpdb->queries as $query) {
				echo '<!-- '.$query[0].' -->';
			}
		}
		if($thesis_cacher->requests) {
			foreach($thesis_cacher->requests as $request => $cached) {
				if($cached == 1) {
					echo "<!--Cached: $request -->";
				}
			}
		}

		if(!empty($thesisstart)) {
		echo '<!-- THESIS HAS USED:'.round(abs($thesisstart['mem'] - memory_get_usage())/1048576,2).'| time: '.number_format(microtime() - $thesisstart['time'],3).'s -->';
		unset($thesisstart);
		}
	}
}

/*
 * Admin bar API
 */
add_action("admin_bar_menu", "customize_menu");
function customize_menu(){
    global $wp_admin_bar,$thesis_cache;
    if(!empty($thesis_cache) AND current_user_can('administrator')) {
		$wp_admin_bar->add_menu( array(
			'id'	=> 'wp-thesis_cacher',
			'title'	=> 'Thesis Cache',
			'href'	=> false,
			'parent'=> false,
			'meta'	=> false
			)
		);
		
		$wp_admin_bar->add_menu( array( 
			'id' 	=> 'wp-thesis-cacher-all',
			'title'	=> 'clear <strong>All</strong>',
			'href'		=> add_query_arg(array('cache-clear'=>'doit','_wpnonce'=>wp_create_nonce()),admin_url()),
			'parent'	=> 'wp-thesis_cacher',
			'meta'		=> false
		));
		
		if(CACHE_DRIVER == 'DEFAULT') 
		{
		$flags = $thesis_cache::get_flags();
			asort($flags);
			if(!empty($flags)) {
				foreach($flags as $group) {
					$wp_admin_bar->add_menu(
						array(
							'id'	=>	"wp-thesis-cacher-$group",
							'title'	=> "clear <strong>$group</strong>",
							'href'	=> add_query_arg(array('flag'=>$group,'_wpnonce'=>wp_create_nonce()),admin_url()),
							'parent'=>'wp-thesis_cacher',
							'meta'=> false
						)
					);
				}
			}
		}
	   
	 }
}
