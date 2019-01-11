<?php
/*
Plugin Name: azurecurve Conditional Page Links
Plugin URI: http://development.azurecurve.co.uk/plugins/conditional-page-links

Description: Allows you to create a page with links to other pages, before those other pages have been created; anchor tags are added only if the page exists.
Version: 1.0.0

Author: Ian Grieve
Author URI: http://development.azurecurve.co.uk

Text Domain: azc-cpl
Domain Path: /languages

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

The full copy of the GNU General Public License is available here: http://www.gnu.org/licenses/gpl.txt

*/

function azc_cpl_load_plugin_textdomain(){
	$loaded = load_plugin_textdomain('azc_cpl', false, dirname(plugin_basename(__FILE__)).'/languages/');
	//if ($loaded){ echo 'true'; }else{ echo 'false'; }
}
add_action('plugins_loaded', 'azc_cpl_load_plugin_textdomain');

function azc_cpl_load_css(){
	wp_enqueue_style( 'azc_cpl', plugins_url( 'style.css', __FILE__ ) );
}
add_action('admin_enqueue_scripts', 'azc_cpl_load_css');

function azc_cpl_set_default_options($networkwide) {
	
	$option_name = 'azc_cpl';
	$new_options = array(
						'display_edit_link' => 1,
						'display_add_link' => 1,
					);
	
	// set defaults for multi-site
	if (function_exists('is_multisite') && is_multisite()) {
		// check if it is a network activation - if so, run the activation function for each blog id
		if ($networkwide) {
			global $wpdb;

			$blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
			$original_blog_id = get_current_blog_id();

			foreach ($blog_ids as $blog_id) {
				switch_to_blog($blog_id);

				if (get_option($option_name) === false) {
					add_option($option_name, $new_options);
				}
			}

			switch_to_blog($original_blog_id);
		}else{
			if (get_option($option_name) === false) {
				add_option($option_name, $new_options);
			}
		}
		if (get_site_option($option_name) === false) {
			add_site_option($option_name, $new_options);
		}
	}
	//set defaults for single site
	else{
		if (get_option($option_name) === false) {
			add_option($option_name, $new_options);
		}
	}
}
register_activation_hook(__FILE__, 'azc_cpl_set_default_options');

function azc_cpl_plugin_action_links($links, $file) {
    static $this_plugin;

    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {
        $settings_link = '<a href="'.get_bloginfo('wpurl').'/wp-admin/admin.php?page=azurecurve-cpl">'.__('Settings', 'azc_cpl').' </a>';
        array_unshift($links, $settings_link);
    }

    return $links;
}
add_filter('plugin_action_links', 'azc_cpl_plugin_action_links', 10, 2);

function azc_cpl_site_settings_menu() {
	add_options_page('azurecurve Conditional Page Links',
	'azurecurve Conditional Page Links', 'manage_options',
	'azurecurve-cpl', 'azc_cpl_site_settings');
}
add_action('admin_menu', 'azc_cpl_site_settings_menu');

function azc_cpl_site_settings() {
	if (!current_user_can('manage_options')) {
		$error = new WP_Error('not_found', __('You do not have sufficient permissions to access this page.' , 'azc_cpl'), array('response' => '200'));
		if(is_wp_error($error)){
			wp_die($error, '', $error->get_error_data());
		}
    }
	
	// Retrieve plugin site options from database
	$options = get_option('azc_cpl');
	?>
	<div id="azc-cpl-general" class="wrap">
		<fieldset>
			<h2>azurecurve Conditional Page Links <?php _e('Site Settings', 'azc_cpl'); ?></h2>
			<?php if(isset($_GET['options-updated'])) { ?>
				<div id="message" class="updated">
					<p><strong><?php _e('Site settings have been saved.') ?></strong></p>
				</div>
			<?php } ?>
			
			<form method="post" action="admin-post.php">
				<input type="hidden" name="action" value="save_azc_cpl_site_settings" />
				<input name="page_options" type="hidden" value="display_add_link,display_edit_link" />
				
				<!-- Adding security through hidden referrer field -->
				<?php wp_nonce_field('azc_cpl_nonce', 'azc_cpl_nonce'); ?>
				<table class="form-table">
				
					<tr><th scope="row"><?php _e('Display Add Link?', 'azc_cpl'); ?></th><td>
						<fieldset><legend class="screen-reader-text"><span><?php _e('Display Add/Edit Link?', 'azc_cpl'); ?></span></legend>
						<label for="display_add_link"><input name="display_add_link" type="checkbox" id="display_add_link" value="1" <?php checked('1', $options['display_add_link']); ?> /></label>
						</fieldset>
					</td></tr>
				
					<tr><th scope="row"><?php _e('Display Edit Link?', 'azc_cpl'); ?></th><td>
						<fieldset><legend class="screen-reader-text"><span><?php _e('Display Add/Edit Link?', 'azc_cpl'); ?></span></legend>
						<label for="display_edit_link"><input name="display_edit_link" type="checkbox" id="display_edit_link" value="1" <?php checked('1', $options['display_edit_link']); ?> /></label>
						</fieldset>
					</td></tr>
				</table>
				<input type="submit" value="Submit" class="button-primary" />
			</form>
		</fieldset>
	</div>
<?php }

function azc_cpl_admin_init() {
	add_action('admin_post_save_azc_cpl_site_settings', 'azc_cpl_save_site_settings');
}
add_action('admin_init', 'azc_cpl_admin_init');

function azc_cpl_save_site_settings() {
	// Check that user has proper security level
	if (!current_user_can('manage_options')) {
		$error = new WP_Error('not_found', __('You do not have sufficient permissions to perform this action.' , 'azc_cpl'), array('response' => '200'));
		if(is_wp_error($error)){
			wp_die($error, '', $error->get_error_data());
		}
    }
	
	// Check that nonce field created in configuration form is present
	if (! empty($_POST) && check_admin_referer('azc_cpl_nonce', 'azc_cpl_nonce')) {
		// Retrieve original plugin options array
		$options = get_site_option('azc_cpl');
		
		$option_name = 'display_add_link';
		if (isset($_POST[$option_name])) {
			$options[$option_name] = 1;
		}else{
			$options[$option_name] = 0;
		}
		
		$option_name = 'display_edit_link';
		if (isset($_POST[$option_name])) {
			$options[$option_name] = 1;
		}else{
			$options[$option_name] = 0;
		}
		
		// Store updated options array to database
		update_option('azc_cpl', $options);
		
		// Redirect the page to the configuration form that was processed
		wp_redirect(add_query_arg('page', 'azurecurve-cpl&options-updated', admin_url('options-general.php')));
		exit;
	}
}

function azc_cpl_shortcode($atts, $content = null) {
	extract(shortcode_atts(array(
		'slug' => '',
	), $atts));
	
	$slug = sanitize_text_field($slug);
	
	global $wpdb;
	
	$options = get_option('azc_cpl');
	
	$page_url = trailingslashit(get_bloginfo('url'));
	//"http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
	
	/*if (strlen($slug) > 0){
		$page = get_page_by_path($slug);
		$page_id = $page->ID;
	}*/

	$sql = $wpdb->prepare("SELECT ID,post_title, post_name, post_status FROM ".$wpdb->prefix."posts WHERE post_status in ('publish') AND post_type = 'page' AND post_name='%s' limit 0,1", sanitize_title($slug));
	
	$return = '';
	$the_page = $wpdb->get_row( $sql );
	if ($the_page){
		$return .= "<a href='".get_permalink($the_page->ID)."' class='azc_cpl'>".$the_page->post_title."</a>";
		if (current_user_can('edit_posts') and $options['display_edit_link'] == 1){
			if ($the_page->post_status == 'publish'){
				$return .= '&nbsp;<a href="'.$page_url.'wp-admin/post.php?post='.$page_id.'&action=edit" class="azc_cpl_admin">['.__('Edit','azc_cpl').']</a>';
			}
		}
	}else{
		$return .= $slug."</a>";
		if (current_user_can('edit_posts') and $options['display_add_link'] == 1){
			$return .= '&nbsp;<a href="'.$page_url.'wp-admin/post-new.php?post_type=page" class="azc_cpl_admin">['.__('Add','azc_cpl').']</a>';
		}
	}
	return $return;
}
add_shortcode( 'cpl', 'azc_cpl_shortcode' );
add_shortcode( 'Cpl', 'azc_cpl_shortcode' );
add_shortcode( 'CPL', 'azc_cpl_shortcode' );

?>