<?php
/*
Plugin Name: Metabox Class for WP Developers
Version: 0.0.1
Description: This plugin creates a series of simple functions developers can use to create metaboxes
Author: Mike Van Winkle
Author URI: http://www.mikevanwinkle.com
Plugin URI: http://www.mikevanwinkle.com/wordpress/plugins/developer-metaboxes/
License: GPL
*/
define("DMB_VERSION","1.0");
define("DMB_ROOT",dirname(__FILE__));

add_action('admin_init',array('DMB_Meta_Box_Builder','init_boxes'));
/**
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * **********************************************************************
 */

/************************************************************************
		You should not edit the code below or things might explode!
*************************************************************************/
wp_enqueue_script('jquery-ui-core');
define('DMB_META_BOX_URL', trailingslashit( str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, dirname(__FILE__) ) ) );
/**
 *
 * This function registers all the metaboxes and returns a global variable used by the class.
 *
 */
if(!function_exists('DMB_register_box')) {
	function DMB_register_box($boxes) {
		global $DMB_boxes;
		$DMB_boxes[] = $boxes;
		return $DMB_boxes;
	}
}
	
/**
 *
 * This function initiates the meta boxes.
 *
 */

	class DMB_Meta_Box_Builder {	
		public static function init_boxes() {
			global $DMB_boxes;
			if(!empty($DMB_boxes)) {
				foreach ( $DMB_boxes as $meta_box ) {
					if(!isset($meta_box['child_class'])) {
						new DMB_Meta_Box($meta_box);
					} else {
						new $meta_box['child_class']($meta_box);
					}
					new DMB_Custom_Column($meta_box);
				}
			}		
		}
				
	} // end class

	
/**
 * Validate value of meta fields
 * Define ALL validation methods inside this class and use the names of these 
 * methods in the definition of meta boxes (key 'validate_func' of each field)
 */
	
	class DMB_Meta_Box_Validate {
		
		function check_text( $text ) {
			if ($text != 'hello') {
				return false;
			}
			return true;
		}
	} // end class 

/**
 * 
 * Class for sanitizing fields. 
 * 
 */
	
	class DMB_Meta_Box_Sanitize {
		
		function uppercase( $text ) {
			return ucwords( $text );
		}
		
		function lowercase( $text ) {
			return strtolower($text);
		}
		
		function sanitize_link( $text ) {
			$text = str_replace("http://",'', $text);
			return $text;
		}
		
	} // end class



	/**
	 * Create meta boxes
	 */

	class DMB_Meta_Box {
		protected $_meta_box;
	
		function __construct( $meta_box ) {
			if ( !is_admin() ) return;
	
			$this->_meta_box = $meta_box;
			$upload = false;
			
			foreach ( $meta_box['fields'] as $field ) {
				if ( $field['type'] == 'file' || $field['type'] == 'file_list' ) {
					$upload = true;
					break;
				}
			}
			
			$current_page = substr(strrchr($_SERVER['PHP_SELF'], '/'), 1, -4);
			
			if ( $upload && ( $current_page == 'page' || $current_page == 'page-new' || $current_page == 'post' || $current_page == 'post-new' ) ) {
				add_action('admin_head', array(&$this, 'add_post_enctype'));
			}
	
			add_action( 'admin_menu', array(&$this, 'add') );
			add_action( 'save_post', array(&$this, 'save') );
			
		}
	
		function add_post_enctype() {
			echo '
			<script type="text/javascript">
			jQuery(document).ready(function(){
				jQuery("#post").attr("enctype", "multipart/form-data");
				jQuery("#post").attr("encoding", "multipart/form-data");
			});
			</script>';
		}
	
		/// Add metaboxes
		function add() {
			$this->_meta_box['context'] = empty($this->_meta_box['context']) ? 'normal' : $this->_meta_box['context'];
			$this->_meta_box['priority'] = empty($this->_meta_box['priority']) ? 'high' : $this->_meta_box['priority'];
			foreach ($this->_meta_box['pages'] as $page) {
				add_meta_box($this->_meta_box['id'], $this->_meta_box['title'], array(&$this, 'handle'), $page, $this->_meta_box['context'], $this->_meta_box['priority']);
			}
		}
	
		function handle() {
			if(isset($this->_meta_box['callback'])) {
				call_user_func_array($this->_meta_box['callback'],array($this->_meta_box));
			} else {
				$this->show();
			}
		}
	
		// Show fields
		function show() {
			global $post;

			// Use nonce for verification
			echo '<input type="hidden" name="wp_meta_box_nonce" value="', wp_create_nonce(basename(__FILE__)), '" />';
			echo '<table class="form-table DMB_metabox">';
	
			foreach ( $this->_meta_box['fields'] as $field ) {
				
				// Set up blank values for empty ones
				if ( !isset($field['desc']) ) $field['desc'] = '';
				if ( !isset($field['std']) ) $field['std'] = '';
				$meta = get_post_meta( $post->ID, $field['id'], 'multicheck' != $field['type'] /* If multicheck this can be multiple values */ );
	
				echo '<tr class="'.$field['type'].'">';
		
				if ( $field['type'] == "title" ) {
					echo '<td colspan="2">';
				} else {
					if( $this->_meta_box['show_names'] == true ) {
						echo '<th style="width:18%"><label for="', $field['id'], '">', $field['name'], '</label></th>';
					}			
					echo '<td>';
				}		
				
				if ( isset($field['callback'])) {
					call_user_func_array( $field['callback'], array($field,$post));
				} else {
					$this->field_input($field,$meta,$post);
				}
				echo '</td>','</tr>';
			}
			echo '</table>';
		}
		
		
		//field input handler
		function field_input($field,$meta,$post) {
			switch ( $field['type'] ) {
					case 'text':
						echo '<input type="text" name="', $field['id'], '" id="', $field['id'], '" value="', $meta ? $meta : $field['std'], '" style="width:97%" />',
							'<p class="DMB_metabox_description">', $field['desc'], '</p>';
						break;
					case 'text_small':
						echo '<input class="DMB_text_small" type="text" name="', $field['id'], '" id="', $field['id'], '" value="', $meta ? $meta : $field['std'], '" /><span class="DMB_metabox_description">', $field['desc'], '</span>';
						break;
					case 'text_medium':
						echo '<input class="DMB_text_medium" type="text" name="', $field['id'], '" id="', $field['id'], '" value="', $meta ? $meta : $field['std'], '" /><span class="DMB_metabox_description">', $field['desc'], '</span>';
						break;
					case 'text_date':
						echo '<input class="DMB_text_small DMB_datepicker" type="text" name="', $field['id'], '" id="', $field['id'], '" value="', $meta ? $meta : $field['std'], '" /><span class="DMB_metabox_description">', $field['desc'], '</span>';
						break;
					case 'text_money':
						echo '$ <input class="DMB_text_money" type="text" name="', $field['id'], '" id="', $field['id'], '" value="', $meta ? $meta : $field['std'], '" /><span class="DMB_metabox_description">', $field['desc'], '</span>';
						break;
					case 'textarea':
						echo '<textarea name="', $field['id'], '" id="', $field['id'], '" cols="60" rows="10" style="width:97%">', $meta ? $meta : $field['std'], '</textarea>',
							'<p class="DMB_metabox_description">', $field['desc'], '</p>';
						break;
					case 'textarea_small':
						echo '<textarea name="', $field['id'], '" id="', $field['id'], '" cols="60" rows="4" style="width:97%">', $meta ? $meta : $field['std'], '</textarea>',
							'<p class="DMB_metabox_description">', $field['desc'], '</p>';
						break;
					case 'select':
						$meta = $meta?$meta:$field['std'];
						echo '<select name="', $field['id'], '" id="', $field['id'], '">';

						foreach ($field['options'] as $option) {
							echo '<option value="', $option['value'], '"', $meta == $option['value'] ? ' selected="selected"' : '', '>', $option['name'], '</option>';
						}
						echo '</select>';
						echo '<p class="DMB_metabox_description">', $field['desc'], '</p>';
						break;
					case 'radio_inline':
						echo '<div class="DMB_radio_inline">';
						foreach ($field['options'] as $option) {
							echo '<div class="DMB_radio_inline_option"><input type="radio" name="', $field['id'], '" value="', $option['value'], '"', $meta == $option['value'] ? ' checked="checked"' : '', ' />', $option['name'], '</div>';
						}
						echo '</div>';
						echo '<p class="DMB_metabox_description">', $field['desc'], '</p>';
						break;
					case 'radio':
						foreach ($field['options'] as $option) {
							echo '<p><input type="radio" name="', $field['id'], '" value="', $option['value'], '"', $meta == $option['value'] ? ' checked="checked"' : '', ' />', $option['name'].'</p>';
						}
						echo '<p class="DMB_metabox_description">', $field['desc'], '</p>';
						break;
					case 'checkbox':
						echo '<input type="checkbox" name="', $field['id'], '" id="', $field['id'], '"', $meta ? ' checked="checked"' : '', ' />';
						echo '<span class="DMB_metabox_description">', $field['desc'], '</span>';
						break;
					case 'multicheck':
						echo '<ul>';
						foreach ( $field['options'] as $value => $name ) {
							// Append `[]` to the name to get multiple values
							// Use in_array() to check whether the current option should be checked
							echo '<li><input type="checkbox" name="', $field['id'], '[]" id="', $field['id'], '" value="', $value, '"', in_array( $value, $meta ) ? ' checked="checked"' : '', ' /><label>', $name, '</label></li>';
						}
						echo '</ul>';
						echo '<span class="DMB_metabox_description">', $field['desc'], '</span>';					
						break;		
					case 'title':
						echo '<h5 class="DMB_metabox_title">', $field['name'], '</h5>';
						echo '<p class="DMB_metabox_description">', $field['desc'], '</p>';
						break;
					case 'wysiwyg':
						echo '<div id="poststuff" class="meta_mce">';
						echo '<div class="customEditor"><textarea name="', $field['id'], '" id="', $field['id'], '" cols="60" rows="7" style="width:97%">', $meta ? $meta : '', '</textarea></div>';
	                    echo '</div>';
				        echo '<p class="DMB_metabox_description">', $field['desc'], '</p>';
					break;
	/*
					case 'wysiwyg':
						echo '<textarea name="', $field['id'], '" id="', $field['id'], '" class="theEditor" cols="60" rows="4" style="width:97%">', $meta ? $meta : $field['std'], '</textarea>';
						echo '<p class="DMB_metabox_description">', $field['desc'], '</p>';	
						break;
	*/
					case 'file_list':
						if($field['mode'] == 'all' || !isset($field['mode'])) {
							echo '<input id="upload_file" type="text" size="36" name="', $field['id'], '" value="" />';
							echo '<input class="upload_button button" type="button" value="Upload File" />';
							echo '<p class="DMB_metabox_description">', $field['desc'], '</p>';
								$args = array(
										'post_type' => 'attachment',
										'numberposts' => null,
										'post_status' => null,
										'post_parent' => $post->ID
									);
									$attachments = get_posts($args);
									if ($attachments) {
										echo '<ul class="attach_list">';
										foreach ($attachments as $attachment) {
											echo '<li>'.wp_get_attachment_link($attachment->ID, 'thumbnail', 0, 0, 'Download');
											echo '<span>';
											echo apply_filters('the_title', '&nbsp;'.$attachment->post_title);
											echo '</span>';
											echo ' / <span><a href="" id="remove-attach" rel="'.$attachment->ID.'">Remove</a></li>';
										}
										echo '</ul>';
									}
						} elseif($field['mode'] == 'only') { 
								echo '<input id="upload_file" type="text" size="36" name="', $field['id'], '" value="" />';
								echo '<input class="upload_button button" type="button" value="Upload File" />';
								echo '<p class="DMB_metabox_description">', $field['desc'], '</p>';
							$files = get_post_meta($post->ID,$field['id'],false);
							if(!empty($files)) {
										echo '<ul class="attach_list">';
									if(is_array($files)) {
											foreach ($files as $file) {
												echo '<li>'.wp_get_attachment_link($file, 'thumbnail', 0, 0, 'Download');
												echo '<span>';
												echo apply_filters('the_title', '&nbsp;'.get_the_title($file));
												echo '</span></li>';	
											}
									} else {
												echo '<li>'.wp_get_attachment_link($files, 'thumbnail', 0, 0, 'Download');
												echo '<span>';
												echo apply_filters('the_title', '&nbsp;'.get_the_title($file));
												echo '</span></li>';	
									}
								}
							}
							echo '<div id="', $field['id'], '_status" class="DMB_upload_status">';	
							echo '</div>';
							break;
					case 'file':
						echo '<input id="upload_file" type="text" size="45" class="', $field['id'], '" name="', $field['id'], '" value="', $meta, '" />';
						echo '<input class="upload_button button" type="button" value="Upload File" />';
						echo '<p class="DMB_metabox_description">', $field['desc'], '</p>';
						echo '<div id="', $field['id'], '_status" class="DMB_upload_status">';	
							if ( $meta != '' ) { 
								$check_image = preg_match( '/(^.*\.jpg|jpeg|png|gif|ico*)/i', $meta );
								if ( $check_image ) {
									echo '<div class="img_status">';
									echo '<a href="#" class="remove_file_button" rel="', $field['id'], '">Remove Image</a><br>';
									echo '<img src="', $meta, '" alt="" />';
									echo '</div>';
								} else {
									$parts = explode( "/", $meta );
									for( $i = 0; $i < sizeof( $parts ); ++$i ) {
										$title = $parts[$i];
									} 
									echo 'File: <strong>', $title, '</strong>&nbsp;&nbsp;&nbsp; (<a href="', $meta, '" target="_blank" rel="external">Download</a> / <a href="# class="remove_file_button" rel="', $field['id'], '">Remove</a>)';
								}	
							}
						echo '</div>'; 
					break;
					case 'taxonomy-single':
					$vals = wp_get_object_terms($post->ID,$field['taxonomy'],array('fields'=>'ids'));
						wp_dropdown_categories(array(
						'name' => $field['id'], 
						'id'=> $field['taxonomy'], 
						'hide_empty'=> 0,
						'show_count'=>0,
						'selected' =>($vals)?$vals[0]:'',
						'taxonomy' => $field['taxonomy'])
						); 
					echo '<p class="DMB_metabox_description">', $field['desc'], '</p>';
					break;
					
					case 'taxonomy-text':
					$vals = wp_get_object_terms($post->ID,$field['taxonomy'],array('fields'=>'all'));
					echo '<input class="DMB_text_small" type="text" name="', $field['id'], '" id="', $field['id'], '" value="'.@$vals[0]->name.'" /><span class="DMB_metabox_description">', $field['desc'], '</span>';
					break;
					
					} // switch
		}
		
		function pre_save($post_id) {

			
		}
		
		// Save data from metabox
		function save( $post_id)  {
			global $post;
			if ( ! isset( $_POST['wp_meta_box_nonce'] ) || !wp_verify_nonce($_POST['wp_meta_box_nonce'], basename(__FILE__))) {
				return $post_id;
			}
	
			// check autosave
			if ( defined('DOING_AUTOSAVE' ) && DOING_AUTOSAVE) {
				return $post_id;
			}
	
			// check permissions
			if ( 'page' == $_POST['post_type'] ) {
				if ( !current_user_can( 'edit_page', $post_id ) ) {
					return $post_id;
				}
			} elseif ( !current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}
			
			if(in_array($post->post_type,$this->_meta_box['pages'])) {

				foreach ( $this->_meta_box['fields'] as $field ) {
					$name = $field['id'];
					$old = get_post_meta( $post_id, $name, 'multicheck' != $field['type'] /* If multicheck this can be multiple values */ );
					$new = isset( $_POST[$field['id']] ) ? $_POST[$field['id']] : null;
		
					if ( $field['type'] == 'wysiwyg' ) {
						$new = wpautop($new);
					}
		
					if ( ($field['type'] == 'textarea') || ($field['type'] == 'textarea_small') ) {
						$new = htmlspecialchars($new);
					}
					
					if( isset($field['sanitize_func']) ) {
						$new = call_user_func(array('DMB_Meta_Box_Sanitize',$field['sanitize_func']), $new );
					}
					
					// validate meta value
					if ( isset($field['validate_func']) ) {
						$ok = call_user_func(array('DMB_Meta_Box_Validate', $field['validate_func']), $new);
						
						if ( $ok === false ) { // pass away when meta value is invalid
							continue;
						}
					} elseif( ($field['type']) == 'file' ) {
						delete_post_meta($post_id,$name);
						if($new != null) {				
							add_post_meta($post_id,$name,$new);
						}
					} elseif ( 'multicheck' == $field['type'] ) {
						// Do the saving in two steps: first get everything we don't have yet
						// Then get everything we should not have anymore
						if ( empty( $new ) ) {
							$new = array();
						}
						$aNewToAdd = array_diff( $new, $old );
						$aOldToDelete = array_diff( $old, $new );
						foreach ( $aNewToAdd as $newToAdd ) {
							add_post_meta( $post_id, $name, $newToAdd, false );
						}
						foreach ( $aOldToDelete as $oldToDelete ) {
							delete_post_meta( $post_id, $name, $oldToDelete );
						}
					} elseif ($new && $new != $old) {
						update_post_meta($post_id, $name, $new);
					} elseif ('' == $new && $old && $field['type'] != 'file') {
						delete_post_meta($post_id, $name, $old);
					}
				}
			}
		}
		
} // end class


class DMB_PT_Meta_Box extends DMB_Meta_Box {
	
	function __construct($meta_box) {
		parent::__construct($meta_box); 		
	
	}
	
	// Show fields
	function show() {
		global $post;
		$post_keys = array('post_content','post_title','menu_order','ID','post_name','post_status','post_type');
		$related_posts = new WP_Query(array('post_type'=>$this->_meta_box['post_type_to_save'],'showposts'=>-1,'post_parent'=>$post->ID));
		// Use nonce for verification
		echo '<input type="hidden" name="wp_meta_box_nonce" value="', wp_create_nonce(basename(__FILE__)), '" />';
		echo '<table class="form-table DMB_metabox">';
		$i = 0;
		if(count($related_posts->posts) > 0) {
			foreach($related_posts->posts as $rel) {
				?>
				<script>
				jQuery(document).ready(function() {
					jQuery('a#<?php echo $rel->ID; ?>').click(function(event) {
						event.preventDefault();
						jQuery('tbody.<?php echo $rel->ID; ?>').slideToggle('slow'); 
					});
				});
				</script>
				<?php
				echo '<thead class="'.$rel->ID.' "><tr>
				<td class="rel-title">'.$rel->post_title.'</td>
				<td><a id="'.$rel->ID.'" href="#" class="button">Edit</a> <a href="#" class="button">Delete</a></td></tr><tr>';
				echo '<tbody class="'.$rel->ID.'" style="display:none">';
				$delete_nonce = wp_create_nonce();
				echo '<input name="'.$this->_meta_box['post_type_to_save'].'['.$i.'][ID]" value="'.$rel->ID.'" type="hidden" />';
				foreach ( $this->_meta_box['fields'] as $field ) {
					// Set up blank values for empty ones
					if ( !isset($field['desc']) ) $field['desc'] = '';
					if ( !isset($field['std']) ) $field['std'] = '';

					if(in_array($field['id'],$post_keys)) {
						$meta = $rel->$field['id'];
					} elseif(isset($field['taxonomy']) AND is_tax($field['taxonomy'])) {
						$meta = wp_get_object_terms($rel->ID,$field['taxonomy']);
					} else {
						$meta = get_post_meta($rel->ID, $field['id'], 'multicheck' != $field['type'] );
					}
		
					$field['id'] = $this->_meta_box['post_type_to_save'].'['.$i.']['.$field['id'].']';
					
					echo '<tr class="'.$field['type'].'">';
			
					if ( $field['type'] == "title" ) {
						echo '<td colspan="2">';
					} else {
						if( $this->_meta_box['show_names'] == true ) {
							echo '<th style="width:18%"><label for="', $field['id'], '">', $field['name'], '</label></th>';
						}			
						echo '<td>';
					}		
					
					if ( isset($field['callback'])) {
						call_user_func_array( $field['callback'], array($field,$post));
					} else {
						$this->field_input($field,$meta,$rel);
					}
					echo '</td>','</tr>';
				} //endforeach
				$i++;
				echo '<tr><td colspan="2"><hr class="mb_line_break"/></td></tr></tbody>';
			} //endforeach
		}
		//empty form
		foreach ( $this->_meta_box['fields'] as $field ) {
				// Set up blank values for empty ones
				if ( !isset($field['desc']) ) $field['desc'] = '';
				if ( !isset($field['std']) ) $field['std'] = '';
				
				$field['id'] = $this->_meta_box['post_type_to_save'].'['.$i.']['.$field['id'].']';
	
				echo '<tr class="'.$field['type'].'">';
		
				if ( $field['type'] == "title" ) {
					echo '<td colspan="2">';
				} else {
					if( $this->_meta_box['show_names'] == true ) {
						echo '<th style="width:18%"><label for="', $field['id'], '">', $field['name'], '</label></th>';
					}			
					echo '<td>';
				}		
				
				if ( isset($field['callback'])) {
					call_user_func_array( $field['callback'], array($field,$post));
				} else {
					$this->field_input($field,$meta = '',$post);
				}
				echo '</td>','</tr>';
			} //endforeach		
		
		echo '</table>';
	}
	
	function save($post_id) {
		if ( ! isset( $_POST['wp_meta_box_nonce'] ) || !wp_verify_nonce($_POST['wp_meta_box_nonce'], basename(__FILE__))) {
			return $post_id;
		}

		// check autosave
		if ( defined('DOING_AUTOSAVE' ) && DOING_AUTOSAVE) {
			return $post_id;
		}
					
		$post = get_post($post_id);
		$post_keys = array('post_content','post_title','menu_order','ID','post_name','post_status','post_type');
		
		if($post->post_type != $this->_meta_box['post_type_to_save'] and in_array($post->post_type,$this->_meta_box['pages'])) {
			foreach($_POST[$this->_meta_box['post_type_to_save']] as $related_posts) {
				//setup values
				$new = array();

				foreach($related_posts as $k => $v) {
					if(in_array($k,$post_keys)) {
						$new['post'][$k] = $v; 
					} elseif(is_taxonomy($k)) {
						if(is_array($v)) {
							$new['tax'][$k] = array(implode(',',$v));
						} elseif(in_array($k, $this->_meta_box['stringed_terms'])) {
							$new['tax'][$k] = (string) $v;
						} else {
							$new['tax'][$k] = intval($v);
						}
					} else {
						$new['meta'][$k] = $v;
					}
				}
				
				update_option('testing_meta',$new['tax']);
				
				if(!empty($new)) {
					$new['post']['post_type'] = $this->_meta_box['post_type_to_save'];
					$new['post']['post_parent'] = $post_id;
					if(isset($new['post']['ID'])) {
						$new_post_id = wp_update_post($new['post']);
					} else {
						$new_post_id = wp_insert_post($new['post']);
					}
					
					
					foreach($new['meta'] as $mk => $mv) {
						update_post_meta($new_post_id,$mk,$mv);
					}
					
					if($new['tax'] && $new_post_id) {
						foreach($new['tax'] as $taxonomy => $terms) {
							
						
							if(is_array($terms)) {
								$term_ids = array_map('intval', $terms);
						    $term_ids = array_unique( $terms );
						    $terms = $term_ids;
							} 
							
							$error = wp_set_object_terms($new_post_id,$terms,$taxonomy,false);
						
						}				
					}
				}
			}	
		}
		return $post_id;
	} 	
}// end class
	
	
/**
 * Adding scripts and styles
 */

	function DMB_scripts( $hook ) {
	  	if ( $hook == 'post.php' OR $hook == 'post-new.php' OR $hook == 'page-new.php' OR $hook == 'page.php' ) {
			wp_register_script( 'cmb-scripts', DMB_META_BOX_URL.'jquery.cmbScripts.js', array('jquery','media-upload','thickbox'));
			wp_localize_script( 'cmb-scripts', 'DMB_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-ui-core' ); // Make sure and use elements form the 1.7.3 UI - not 1.8.9
			wp_enqueue_script( 'media-upload' );
			wp_enqueue_script( 'thickbox' );
			wp_enqueue_script( 'cmb-scripts' );
			wp_enqueue_style( 'thickbox' );
			wp_enqueue_style( 'jquery-custom-ui' );
			add_action( 'admin_head', 'DMB_styles_inline' );
	  	}
	}
	add_action( 'admin_enqueue_scripts', 'DMB_scripts',10,1 );




	function DMB_editor_admin_init() {
	 // wp_enqueue_script('word-count');
	 // wp_enqueue_script('post');
	 // wp_enqueue_script('editor');
	}
	
	function DMB_editor_admin_head() {
	  wp_tiny_mce();
	}
	
	
	add_action('admin_enqueue_scripts', 'DMB_editor_admin_init',100);
	add_action('admin_head', 'DMB_editor_admin_head');
	
	function DMB_editor_footer_scripts() { ?>
			<script type="text/javascript">/* <![CDATA[ */
			jQuery(function($) {
				var i=1;
				$('.customEditor textarea').each(function(e) {
					var id = $(this).attr('id');
	 				if (!id) {
						id = 'customEditor-' + i++;
						$(this).attr('id',id);
					}
	 				tinyMCE.execCommand('mceAddControl', false, id);
	 			});
			});
		/* ]]> */</script>
		<?php }
	add_action('admin_print_footer_scripts','DMB_editor_footer_scripts',99);
	
	
	function DMB_styles_inline() { 
		echo '<link rel="stylesheet" type="text/css" href="' . DMB_META_BOX_URL.'style.css" />';
		// For some reason this script doesn't like to register
		?>	
		<style type="text/css">
			table.DMB_metabox td, table.DMB_metabox th { border-bottom: 1px solid #f5f5f5; /* Optional borders between fields */ } 
			table.DMB_metabox th { text-align: right; font-weight:bold; vertical-align:top;}
			table.DMB_metabox th label { display:block; }
			p.DMB_metabox_description { color: #AAA; font-style: italic; margin: 2px 0 !important;}
			span.DMB_metabox_description { color: #AAA; font-style: italic;}
			input.DMB_text_small { width: 100px; margin-right: 15px;}
			input.DMB_text_money { width: 90px; margin-right: 15px;}
			input.DMB_text_medium { width: 230px; margin-right: 15px;}
			table.DMB_metabox input, table.DMB_metabox textarea { font-size:11px; padding: 5px;}
			table.DMB_metabox li { font-size:11px; float:left; width:25%; margin:0 10px;}
			table.DMB_metabox ul { padding-top:5px; }
			table.DMB_metabox select { font-size:11px; padding: 5px 10px;}
			table.DMB_metabox input:focus, table.DMB_metabox textarea:focus { background: #fffff8;}
			.DMB_radio_inline { padding: 4px 0 0 0;}
			.DMB_radio_inline_option {display: inline; padding-right: 18px;}
			table.DMB_metabox input[type="radio"] { margin-right:3px;}
			table.DMB_metabox input[type="checkbox"] { margin-right:6px;}
			table.DMB_metabox .mceLayout {border:1px solid #DFDFDF !important;}
			table.DMB_metabox .meta_mce {width:97%;}
			table.DMB_metabox .meta_mce textarea {width:100%;}
			table.DMB_metabox .DMB_upload_status {  margin: 10px 0 0 0;}
			table.DMB_metabox .DMB_upload_status .img_status {  position: relative; }
			table.DMB_metabox .DMB_upload_status .img_status img { border:1px solid #DFDFDF; background: #FAFAFA; max-width:350px; padding: 5px; -moz-border-radius: 2px; border-radius: 2px;}
			table.DMB_metabox .DMB_upload_status .img_status .remove_file_button { 
				text-indent: -9999px; 
				background: url(<?php bloginfo('stylesheet_directory'); ?>/lib/metabox/images/ico-delete.png); 
				width: 16px; 
				height: 16px; 
				}
			table.DMB_metabox thead tr {
				border-bottom: 1px solid #ccc;
			}
		</style>
		<?php
	}

	add_action('wp_ajax_cmb-remove-file','DMB_ajax_remove_file');
	function DMB_ajax_remove_file() {
		global $wpdb;
		$id = $_POST['pid'];
		$query = $wpdb->prepare("UPDATE $wpdb->posts SET post_parent = 0 WHERE ID = %d"	, $id);
		$wpdb->query($query);
		echo 'Removed from post';
		exit;
	}

	
require_once('custom-columns.php');