<div id="thesis_options" class="wrap<?php if (get_bloginfo('text_direction') == 'rtl') { echo ' rtl'; } ?>">
<?php	thesis_version_indicator(); ?>
<?php	thesis_options_title(__('Caching Options', 'thesis')); ?>
<?php	thesis_options_nav(); ?>

<form class="thesis" action="<?php echo admin_url('admin.php?page=thesis_caching&action=thesis_caching_settings'); ?>" method="post">
	<div class="options_column">
			
	<?php if(defined('CACHE_DIR')): ?>
		<div class="options_module" id="layout-constructor">
			<h3><?php _e('Cache Settings', 'thesis'); ?></h3>
			<div class="module_subsection">
				<h4 class="module_switch"><a href="" title="<?php _e('Show/hide additional information', 'thesis'); ?>"><span class="pos">+</span><span class="neg">&#8211;</span></a><?php _e('Cache Directory', 'thesis'); ?></h4>
				<div class="more_info">
						<p>Directory where your cache should be stored. The cache will be blocked from web access. 
												Default: <code><?php echo CACHE_DIR; ?></code>
						</p>
						<p class="form_input">
						<input type="text" class="text_input" name="caching[cache_dir]" value="<?php echo $settings['cache_dir']?$settings['cache_dir']:CACHE_DIR; ?>" cols="10"/>
						</p>
				</div>
			</div>
			
			<div class="module_subsection">
				<h4 class="module_switch"><a href="" title="<?php _e('Show/hide additional information', 'thesis'); ?>"><span class="pos">+</span><span class="neg">&#8211;</span></a><?php _e('Cache Driver', 'thesis'); ?></h4>
				<div class="more_info"> 
						<p>By default Thesis cache stores data as files in a web-safe directory. Currently the plugin also supports APC for the object cache. APC must be configured on your server for this to work. </p>
						<p>Current: <code><?php echo CACHE_DRIVER; ?></code></p>
				</div>
			</div>
		</div>
	<?php endif; ?>
			


		<div class="options_module" id="layout-constructor">
			<h3><?php _e('Site Caching Options', 'thesis'); ?></h3>
			<div class="module_subsection">
				<h4 class="module_switch"><a href="" title="<?php _e('Show/hide additional information', 'thesis'); ?>"><span class="pos">+</span><span class="neg">&#8211;</span></a><?php _e('Homepage', 'thesis'); ?></h4>
				<div class="more_info">
					<p><?php _e('Do you want to cache your homepage:', 'thesis'); ?></p>
					<p class="form_input add_margin" id="cache_home">
						<select id="caching[cache_home]" name="caching[cache_home]" size="1" cols="20" style="width:8em; margin-right:10px;" >
							<option value="yes"<?php if ($settings['cache_home'] == 'yes') echo ' selected="selected"'; ?>><?php _e('Yes', 'thesis'); ?></option>
							<option value="no"<?php if ($settings['cache_home'] == 'no') echo ' selected="selected"'; ?>><?php _e('No', 'thesis'); ?></option>
						</select>
						<input type="text" class="short" name="caching[cache_home_ttl]" value="<?php echo $settings['cache_home_ttl']; ?>" cols="10"/> mins</p>
							<p><?php _e('Omit sidebar from homepage caching:', 'thesis'); ?></p>
							<p class="form_input add_margin" id="cache_home">
							<select id="caching[cache_skip_home_sidebar]" name="caching[cache_skip_home_sidebar]" size="1" cols="20" style="width:8em; margin-right:10px;" >
								<option value="yes"<?php if ($settings['cache_skip_home_sidebar'] == 'yes') echo ' selected="selected"'; ?>><?php _e('Yes', 'thesis'); ?></option>
								<option value="no"<?php if ($settings['cache_skip_home_sidebar'] == 'no') echo ' selected="selected"'; ?>><?php _e('No', 'thesis'); ?></option>
							</select>
							</p>
				</div>

			</div>				
			
			<div class="module_subsection">
				<h4 class="module_switch"><a href="" title="<?php _e('Show/hide additional information', 'thesis'); ?>"><span class="pos">+</span><span class="neg">&#8211;</span></a><?php _e('Archives', 'thesis'); ?></h4>
				<div class="more_info">
					<p><?php _e('Cache archives?:', 'thesis'); ?></p>
					<p class="form_input add_margin" id="cache_archives">
						<select id="caching[cache_archives]" name="caching[cache_archives]" size="1" cols="20" style="width:8em; margin-right:10px;" >
							<option value="yes"<?php if ($settings['cache_archives'] == 'yes') echo ' selected="selected"'; ?>><?php _e('Yes', 'thesis'); ?></option>
							<option value="no"<?php if ($settings['cache_archives'] == 'no') echo ' selected="selected"'; ?>><?php _e('No', 'thesis'); ?></option>
						</select>
						<input type="text" class="short" name="caching[cache_archives_ttl]" value="<?php echo $settings['cache_archives_ttl']; ?>" cols="10"/> mins
					</p>
				</div>
			</div>
			
		</div><!-- end module -->
	</div><!-- end column-->
	
	<div class="options_column">
			<div class="options_module button_module">
				<?php wp_nonce_field('tc-save-settings'); ?>
					<input type="submit" class="save_button" id="options_submit" name="submit" value="Big Ass Save Button">
			</div>			
			
			
		<div class="options_module" id="layout-constructor">
			<div class="module_subsection">
				<h4 class="module_switch"><a href="" title="<?php _e('Show/hide additional information', 'thesis'); ?>"><span class="pos">+</span><span class="neg">&#8211;</span></a><?php _e('Debug Mode', 'thesis'); ?></h4>
				<div class="more_info">
					<p><?php _e('When turned "on" debug mode adds debugging data at the bottom of your html source code.', 'thesis'); ?></p>
					<p class="form_input add_margin" id="cache_archives">
						<select id="caching[debug]" name="caching[debug]" size="1" cols="20" style="width:8em; margin-right:10px;" >
							<option value="on"<?php if ($settings['debug'] == 'on') echo ' selected="selected"'; ?>><?php _e('On', 'thesis'); ?></option>
							<option value="off"<?php if ($settings['debug'] == 'off') echo ' selected="selected"'; ?>><?php _e('Off', 'thesis'); ?></option>
						</select>
					</p>
				</div>
			</div>
		</div>
		
	</div	>
	
</form>
</div>