<?php
class ThesisCacher {
	public $doing_cache = 0;
	public $requests = array();
	public $hits = array();
	public $purge_schema = array();
	public $notice = false;
	public $settings = array(
		'cache_loops'	=> array('posts'),
	);
	public $marker;
	
	/**
	 * Constructor: fetch's settings and runs setup
	 *
	 * @package ThesisCacher
	 * @params none
	 *
	 **/
	function __construct() {
		add_action('init',array($this,'init'));
		$this->settings = get_option('thesis_cache_settings',array());
		$this->settings['defaults'] = array(
		'cache_default'	=> 'yes',
		'cache_home'=>'yes',
		'cache_home_ttl'=>'10',
		'cache_skip_home_sidebar'=>'no',
		'cache_dir' => CACHE_DIR,
		'cache_driver' => CACHE_DRIVER,
		'cache_archives'	=> 'yes',
		'cache_archives_ttl'	=> '60',
		'debug'	=> 'off'
	);	
		$this->metabox();
	}
	
	/**
	 * Primary setup function
	 *
	 * @package ThesisCacher
	 * @params none
	 * @todo break this into several methods instead of one big one
	 *
	 **/
	function init() {
	
		$this->setup_caching();
		
		//check perms on wp-content
		if(!get_transient('tc_cache_notice')) {
			$this->check_wp_content_dir();
		}
		
		//print_r(fileperms($path));		
		add_action('template_redirect',array($this,'framework'));	
		add_action('save_post',array($this,'clear_post'));
		add_action('comment_post',array($this,'clear_post'));
		add_action('admin_menu',array($this,'settings_menu'));
		
		//cache clearing
		$this->maybe_clear();
	}
	
	/**
	 * Handles clearing the cache when various requests are sent. 
	 *
	 * @package ThesisCacher
	 * @params none
	 *
	 **/
	function maybe_clear() {
		global $thesis_cache; 
		if(!empty($thesis_cache)) {
			if(@$_REQUEST['cache-clear'] == 'doit' AND wp_verify_nonce($_REQUEST['_wpnonce'])) { 
				$thesis_cache->clear('all'); 
				set_transient('tc_cache_notice',array('class'=>'updated','message'=>__('Cache was successfully cleared','thesis-cacher')));
				add_action('admin_notices',array($this,'notices'));
			} elseif(isset($_REQUEST['flag']) AND wp_verify_nonce($_REQUEST['_wpnonce'])) {
				$response = $thesis_cache::clear_flag($_REQUEST['flag']);
				if(is_wp_error($response)) {
					set_transient('tc_cache_notice',array('class'=>'error','message'=>$response->get_error_message()));
					add_action('admin_notices',array($this,'notices'));
				} else {
					set_transient('tc_cache_notice',array('class'=>'updated','message'=>__('Cache for "'.$_REQUEST['flag'].'" was successfully cleared','thesis-cacher')));
					add_action('admin_notices',array($this,'notices'));
				}
			} 
		}
	}
	
	/**
	 * After installing the object cache, we want to make sure the wp-content directory is changed back to 0755
	 *
	 * @package ThesisCacher
	 * @params none
	 *
	 **/	
	function check_wp_content_dir() {
		global $_wp_using_ext_object_cache;
			$wp_content_dir = WP_CONTENT_DIR;
			if(is_writable($wp_content_dir)) {
				set_transient('tc_cache_notice',array('class'=>'updated','message'=>'It looks like your wp-content folder is still writable. We recommend you set permissions to 0755. '));
				add_action('admin_notices',array($this,'notices'));
			}
	}	
	
	/**
	 * Display notices
	 *
	 * @package ThesisCacher
	 * @params none
	 *
	 **/
	function notices() {
			if(empty($this->notice)) { $this->notice = get_transient('tc_cache_notice'); }
			extract($this->notice);
			include(dirname(__FILE__).'/views/admin-notice.php');
			delete_transient('tc_cache_notice');
	}
	
	function setup_caching() {
	
		//check for object cache
		global $_wp_using_ext_object_cache;
		
		//if we're not using an object cache already then use the included library
			if( !$_wp_using_ext_object_cache ) {
				//path to object-cache
				$path = ABSPATH."/wp-content/object-cache.php";
		
				try {
					$cache_file = file_get_contents(dirname(__FILE__).'/object-cache.php');
					$success = file_put_contents($path,$cache_file);
					if(!$success) {
						$error = __('<strong>Thesis Cacher</strong> could not copy the default object caching library to your wp-content directory. Please make sure to set permissions on <strong>'.ABSPATH.'/wp-content</strong> to 0777.','thesis-cacher');
					} 
				} catch (Exception $e) { }
				
				if(isset($error)) {
					set_transient('tc_cache_notice',array('class'=>'error','message'=>$error));
					add_action('admin_notices',array($this,'notices'));
				} else {
					set_transient('tc_cache_notice',array('class'=>'success updated','message'=>'Successfully installed object caching library.' ));
					add_action('admin_notices',array($this,'notices'));
				}
			}
	
	}
	
	/**
	 * Singleton, returns instance of class
	 *
	 * @package ThesisCacher
	 * @params none
	 *
	 **/
	public static function instance() {
		global $thesis_cacher;
		if(empty($thesis_cache)) {
			$thesis_cacher = new ThesisCacher();
		}
		return $thesis_cacher;
	}
	
	/**
	 * We need this function because some actions need to happen AFTER thesis has bootstrapped.
	 *
	 * @package ThesisCacher
	 * @params none
	 *
	 **/
	public function after_theme_setup() {
		//echo 'test';
	}
	
	/**
	 * Set markers for use later.
	 *
	 * @package ThesisCacher
	 * @params none
	 *
	 **/
	public function set_markers() {
		$hooks = array('thesis_hook_after_header','thesis_hook_before_header','thesis_hook_before_content_box','thesis_hook_after_content_box','thesis_hook_before_teasers_box','thesis_hook_after_teasers_box','thesis_hook_before_headline','thesis_hook_after_headline','thesis_hook_after_comments','thesis_hook_before_sidebars','thesis_hook_after_sidebars','thesis_hook_before_footer','thesis_hook_after_footer');
		foreach($hooks as $hook) {
			$this->marker = $hook;
			add_action($hook,array($this, 'print_marker'));
		}
		
	}
	
	public function print_marker() {
		echo "<!--#Marker:".current_filter()."#-->";
	}
	
	
	
	/**
	 * Register the metaboxes
	 *
	 * @package ThesisCacher
	 * @params none
	 *
	 **/
	function metabox() {
		include_once(dirname(__FILE__).'/metaboxes/init.php');
		$pts = get_post_types(array('public'=>true),'names');
		DMB_register_box(array(
			'id' 		=> 'thesis_cacher_options',
		    'title' => 'Cache Options',
		    'pages' => $pts, // post type
				'context' 	=> 'side',
				'priority' 	=> 'high',
				'show_names' => true, // Show field names on the left
		    'fields' => array(
						array(
						    'name' => 'Cached?',
						    'desc' => '<small>Default is yes</small>',
						    'id' =>'thesis_cache_is_cached',
						    'type' => 'select',
						    'options'=> array(
						    	array('name' => 'Yes', 'value' => 'yes'),
									array('name' => 'No', 'value' => 'no'),
						    )
						),
						array(
						    'name' => 'Exclude sidebar',
						    'desc' => '<small>Selecting yes will slow down your site.</small>',
						    'id' =>'thesis_cache_skip_sidebar',
						    'type' => 'select',
						    'std'	=> 'no',
						    'options'=> array(
						    	array('name' => 'Yes', 'value' => 'yes'),
									array('name' => 'No', 'value' => 'no'),
						    )
						 ),
						array(
							'name' => 'Minutes',
							'desc'	=> '<br/><small>Number of minutes to maintain cache</small>',
							'id'		=> 'thesis_cache_ttl',
							'type'	=> 'text_small',
							'std'=> 10
						)
				)
		));
		DMB_Meta_Box_Builder::init_boxes();
	}

	
	public function framework() {
		if(function_exists('thesis_html_framework')) {
			$this->set_markers();
			global $wp_query,$post;
			$settings = array_merge($this->settings['defaults'],$this->settings['user']);
			$is_cached = get_post_meta($post->ID,'thesis_cache_is_cached',true);
			$skip_sidebar = get_post_meta($post->ID,'thesis_cache_skip_sidebars');
			$ttl = intval(get_post_meta($post->ID,'thesis_cache_ttl',true)) * 3600;
			
			if(is_home() OR is_front_page()) {
				$ttl = $settings['cache_home_ttl'];
				$is_cached = $settings['cache_home'];
				$skip_sidebar = $settings['cache_skip_home_sidebar'];
			} 
			
			if($is_cached !== 'no' ) {
				if(is_singular()) {
					$key = 'thesis:'.md5($post->ID);
					$flag = 'thesis';
				} else {
					$key = 'thesis:'.md5($wp_query->query_vars_hash);
					$flag = 'posts';
				} 
	
				//setup content 
				$content = $this->cache_framework($key,$flag,$ttl,$skip_sidebar);
				echo $content;
				exit();
			}
		}
	}
	
	public function cache_framework($key,$flag,$ttl,$skip_sidebar) {
			$content = wp_cache_get($key,$flag);
			if(!$content) {
				ob_start();
				thesis_html_framework(); 
				$content = ob_get_contents();
				ob_end_clean();
				$content = preg_replace('#!\s|\n|\r|\t#','',$content); 
				wp_cache_set($key,$content,$flag,$ttl);
				$cached = 0;
			} else {
				$cached = 1;
			}
				
			if($skip_sidebar == 'yes') {
				$content = preg_replace("/<!--#Marker:thesis_hook_before_sidebars#?-->.*<!--#Marker:thesis_hook_after_sidebars#?-->/",$this->cached_sidebars(),$content);
			}
			
			$this->requests[$key] = $cached;
			
		return $content;
	}
	
	public function cached_sidebars($post) {
		ob_start();
		thesis_sidebars();
		$sidebars = ob_get_contents();
		ob_end_clean();
		return $sidebars;
	}
	
	public function clear_post($post_id) {
		$current = current_filter();
		
		switch($current) {
			case 'save_post':
				if(isset($_POST['thesis_cache_is_cached'])) {
						wp_cache_delete('thesis:'.md5($post_id),'thesis');
						wp_cache_delete($post_id,'posts');
						wp_cache_delete($post_id,'postmeta');
				}
			break; 
			case 'comment_post':
				$comment = get_comment($post_id);
				wp_cache_delete('thesis:'.md5($comment->comment_post_ID), 'thesis');
			break;
			
		}

	}
	
	public function settings_menu() {
		$this->settings_save();
		add_submenu_page('thesis-options','Caching', 'Caching', 'manage_options', 'thesis_caching', array($this,'settings_Page'));
	}
	
	public function settings_page() {
			if(!empty($this->settings['user'])) {
				$settings = array_merge($this->settings['defaults'],$this->settings['user']);
			} else {
				$settings = $this->settings['defaults'];
			}
			include(dirname(__FILE__).'/views/settings.php');
	}
	
	public function settings_save() {
		if(isset($_REQUEST['action']) AND $_REQUEST['action'] == 'thesis_caching_settings' AND wp_verify_nonce($_POST['_wpnonce'],'tc-save-settings')) {
			$this->settings['user'] = $_POST['caching'];
			update_option('thesis_cache_settings',$this->settings);
			set_transient('tc_cache_notice',array('class'=>'updated','message'=>'Settings saved!'));
			wp_redirect(admin_url('admin.php?page=thesis_caching'));
		}
	}
	
}