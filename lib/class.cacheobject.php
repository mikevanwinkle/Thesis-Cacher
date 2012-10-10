<?php
define("CACHE_DIR", ABSPATH.'wp-content/cache');
define("CACHE_DRIVER", 'DEFAULT'); //possible values are "APC" and "DEFAULT"
class WPCacheObject {
	public $cache_key;
	public $cache_log_file = 'log.txt';
	public $cache_dir = CACHE_DIR;
	public $cache_file;
	public $cache_file_name;
	public $cache_time = 26000;
	public $result;
	public $caching;
	public $flag;
	public $doing = 0;
	public $logged = true;
	public $purge_schema = array(
		'wp_update_nav_menu_item' => 'menu',
		'wp_update_nav_menu'			=> 'menu',
		'save_post'								=> 'posts',
		'publish_post'						=> 'posts',
		'clean_object_term_cache' => 'taxonomy',
		'clean_term_cache'				=> 'terms',
		'create_term'							=> 'terms',
		'wp_update_term_parent'		=> 'terms',
		'edit_term'								=> 'terms',
		'delete_term'							=> 'terms',
		'comment_post'						=> 'posts',
		'edit_post'								=> 'posts',
		'delete_post'							=> 'posts',
		'ngg_gallery_sort'				=> 'ngg_gallery'
	);
	public $purged = array();
	
	function __construct() {

		//create cache dir if it doesn't exist
		if(!is_dir(CACHE_DIR)) {
			mkdir(CACHE_DIR,0777,true);
		}
		
		//create .htaccess to block web access to cache (access should ONLY be from the file system)
		if(!file_exists(CACHE_DIR.'/.htaccess')) {
			$htaccess = "<LIMIT GET POST>\norder deny,allow\ndeny from all\nallow from none\n</LIMIT>";
			file_put_contents(CACHE_DIR.'/.htaccess',$htaccess);		
		}
		
		//setup default directory
		if(!is_dir($this->cache_dir.'/default/')) {
			mkdir($this->cache_dir.'/default/',0777,true);
		}
		
		self::enforce_purge();
			
	}
	
	function start($key,$flag = '',$time = '') {
		if($time != '') 
			$this->cache_time = $time; 
		
		if($flag != '')
			$this->flag = $flag;
		else
			$this->flag = 'default';
		
		if(isset($this->flag) AND !is_dir($this->cache_dir.'/'.$flag)) {
			mkdir($this->cache_dir.'/'.$flag,0755,true);
		}
		
		if(isset($this->flag)) {
			$this->cache_file_name = $this->cache_dir.'/'.$this->flag.'/'.$this->cache_file.'.txt';
		} else {
			$this->cache_file_name = $this->cache_dir.'/default/'.$this->cache_file.'.txt';
		}
		
		if($this->cache_exists() ) {
			return $this->get();
		} else {
			$this->doing = 1;
			ob_start();
			return false;
		}
			
	}
	
	function set_object($key, $data = '', $flag = '', $time = '', $logged = false) {

		if($time != '') 
			$this->cache_time = $time; 
		
		if($flag != '')
			$this->flag = $flag;
		else
			$this->flag = 'default';
		
		if(isset($this->flag) AND !is_dir($this->cache_dir.'/'.$flag)) {
			mkdir($this->cache_dir.'/'.$flag,0755,true);
		}
		
		if($this->logged !== false) {
			$this->cache_file = $this->cache_log[$key];
		} else {
			$this->cache_file = str_replace('/','_',($key));
		}
		
		if(isset($this->flag)) {
			$this->cache_file_name = $this->cache_dir.'/'.$this->flag.'/'.$this->cache_file.'.txt';
		} else {
			$this->cache_file_name = $this->cache_dir.'/default/'.$this->cache_file.'.txt';
		}

		$this->set(maybe_serialize($data));
		
	}
	
	function get_object($key, $flag, $logged = false) {

		$this->prep_cache($key,$flag,$logged);		
	
		if($this->cache_exists() ) {
			return $this->get();
		} else {
			return false;
		}
			
	}
	
	function prep_cache($key,$flag,$logged) {
		global $blog_id;

		if($blog_id) 
			$key = $blog_id.$key; 
		
		$this->logged = $logged;
	
		if($flag != '')
			$this->flag = $flag;
		else
			$this->flag = 'default';

		
		if($this->logged !== false) {
			$this->cache_file = $this->cache_log[$key];
		} else {
			$this->cache_file = str_replace('/','_',$key);
		}

		if(isset($this->flag)) {
			$this->cache_file_name = $this->cache_dir.'/'.$this->flag.'/'.$this->cache_file.'.txt';
		} else {
			$this->cache_file_name = $this->cache_dir.'/default/'.$this->cache_file.'.txt';
		}
		
	}
	
	function end() {
		if($this->doing == 1) {
			$output = ob_get_contents(); 
			ob_end_clean();
			$this->set($output);
			return $output;
		} else {
			return;
		}
	}
	
	function cache_exists($key = null,$flag=null) {
		
		$this->prep_cache($key,$flag,false);	
		
		switch (CACHE_DRIVER) {
			case 'DEFAULT':
				if( file_exists($this->cache_file_name) AND ( time() - filemtime($this->cache_file_name) < $this->cache_time))
				{
					$return = 1;
				} else {
					return false;
				}
			break;
			
			case 'APC':
				$return = apc_exists($this->flag.'_'.$this->cache_file);
			break;
		}
		
		return $return;
	}
	
	function set($key='',$data,$flag='default',$time = false) {
		$this->prep_cache($key,$flag,false);
		switch (CACHE_DRIVER) {
			case 'DEFAULT':
				//setup default directory
				if(!is_dir($this->cache_dir.'/'.$flag.'/')) {
					mkdir($this->cache_dir.'/'.$flag.'/',0777,true);
				}
				return file_put_contents($this->cache_file_name,serialize($data)); 
			break;
			
			case 'APC':
				return apc_add($this->flag.'_'.$this->cache_file,$data,$this->cache_time);
			break;
		}

	}
	
	function get($key,$flag='default') {
		$this->prep_cache($key,$flag,false);
		
		switch (CACHE_DRIVER) {
			case 'DEFAULT':
				return maybe_unserialize(file_get_contents($this->cache_file_name));
			break;
			
			case 'APC':
				return apc_fetch($this->flag.'_'.$this->cache_file);
			break;
		}

	}
	
	function delete($key,$flag) {
		
		$this->prep_cache($key,$flag, false);
		switch (CACHE_DRIVER) {
			case 'APC':
				apc_delete($this->flag.'_'.$this->cache_file);
			break;
			case 'DEFAULT':
				unlink($this->cache_file_name);
			break;
		}	
		
	}
	
	/**
	 * Clears the cache
	 *
	 * @since 0.1
	 * @param string $flag Flag to clear
	 */
	
	public static function clear($flag = '',$key = '') {
		global $wp_current_filter,$purge;
		$current = current_filter(); 
		
		if($flag == 'all') {
			$flags = self::get_flags(); 
			if(count($flags) > 0 ) {
				foreach($flags as $flag) {
					self::clear_flag($flag);
				}
			}
			return true;
		} 
			
		if($key AND $key != '' AND !$this->flag) {
				$this->delete($key,$flag);
		}
		
		if(!empty($current) AND !array_key_exists($purge->purge_schema[$current],$purge->purged)) {
		 	$flag = $purge->purge_schema[$current]?$purge->purge_schema[$current]:'';
		 	if(is_array($flag)) {
		 		foreach($flag as $f) {
		 			self::clear_flag($f);
		 			$purge->purged[$f] = 1;
		 		}
		 	} else {
		 		self::clear_flag($flag);
		 		$purge->purged[$flag] = 1;
		 	}
		} elseif(array_key_exists($purge->purge_schema[$current],$purge->purged)) {
			return;
		}
	
	}
	
	public static function clear_flag($flag) {
		if(CACHE_DRIVER == 'DEFAULT')
		{
			if($flag != '') {
				$flag = trim($flag,'/').'/';
			} 
			
			$dir = CACHE_DIR.'/'.$flag;
			if(!is_writable($dir)) {
				return new WP_Error('error','Could not delete '.$flag.'. Make sure your cache directory is writable');
			} 
			if(is_dir($dir)) {
				$mydir = opendir($dir);
				while(false !== ($file = readdir($mydir))) {
						if($file != "." && $file != ".." AND $file != ".htaccess") {
								chmod($dir.$file, 0777);
								if(!is_writable($dir.$file)) {
									return new WP_Error('error','Could not delete '.$flag.'. Make sure your cache directory is writable');
								} else {
									if(is_file($dir.$file)) {
									 	unlink($dir.$file) or die('cannot do it');
									}
								}
						}
				}
				closedir($mydir);
			}
		} else {
			$toDelete = new APCIterator('user', '/^'.$flag.'_(.*)/', APC_ITER_VALUE);
			apc_delete($toDelete);
		}
	}
	
	public static function get_flags() {
			//using the opendir function
		$path = CACHE_DIR;
		$dir_handle = @opendir($path) or die("Unable to open $path");
		
		$flags = array();
		while (false !== ($file = readdir($dir_handle))) 
		{
			if($file!="." && $file!="..")
			{
					if (is_dir($path."/".$file))
					{
							$flags[] = $file;							
					}
			}
		}

		//closing the directory
		closedir($dir_handle);
		return $flags;
	}	
	
	function enforce_purge() {
		global $purge;
		if(!defined("CACHE_AUTO_PURGE")) { define("CACHE_AUTO_PURGE",true); }
			if(CACHE_AUTO_PURGE == TRUE ) {
				$purge = $this;
				foreach($purge->purge_schema as $hook => $flag) {
					$this->flag = $flag;
					add_action($hook,array($purge,'clear'));
					$this->flag = '';
				} 
			}
	}
	
	public static function part($group,$part='',$flag='',$key='') {
		$cache = new WPCacheObject(); 
		global $wp_query,$post;
		$key = $group.':'.$part.':'.$post->ID.':'.$_SERVER['REQUEST_URI'];
		if(!$output = $cache->get_object($key,$flag) ) {
			ob_start();
			get_template_part($group,$part); 
			$output = ob_get_contents();
			ob_end_clean();
			$cache->set_object($key,$output,$flag);
		}
		return $output;
	}

	
}