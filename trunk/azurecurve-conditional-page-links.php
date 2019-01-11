<?php
/*
Plugin Name: azurecurve Conditional Links
Plugin URI: http://development.azurecurve.co.uk/plugins/conditional-links

Description: Allows you to create a page or post with links to other pages or posts, before those other pages or posts have been created; anchor tags are added only if the page or post exists.
Version: 2.0.2

Author: azurecurve
Author URI: http://development.azurecurve.co.uk

Text Domain: azc-cl
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

function azc_cl_load_plugin_textdomain(){
	$loaded = load_plugin_textdomain('azc_cl', false, dirname(plugin_basename(__FILE__)).'/languages/');
	//if ($loaded){ echo 'true'; }else{ echo 'false'; }
}
add_action('plugins_loaded', 'azc_cl_load_plugin_textdomain');

function azc_cl_load_css(){
	wp_enqueue_style( 'azc_cl', plugins_url( 'style.css', __FILE__ ) );
}
add_action('admin_enqueue_scripts', 'azc_cl_load_css');

function azc_cl_set_default_options($networkwide) {
	
	$option_name = 'azc_cl';
	$new_options = array(
						'display_edit_link' => 1,
						'display_add_link' => 1,
						'blog_display_edit_link' => 1,
						'blog_display_add_link' => 1,
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
register_activation_hook(__FILE__, 'azc_cl_set_default_options');

function azc_cl_plugin_action_links($links, $file) {
    static $this_plugin;

    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {
        $settings_link = '<a href="'.get_bloginfo('wpurl').'/wp-admin/admin.php?page=azurecurve-cpl">'.__('Settings', 'azc_cl').' </a>';
        array_unshift($links, $settings_link);
    }

    return $links;
}
add_filter('plugin_action_links', 'azc_cl_plugin_action_links', 10, 2);

/*
function azc_cpl_site_settings_menu() {
	add_options_page('azurecurve Conditional Page Links',
	'azurecurve Conditional Page Links', 'manage_options',
	'azurecurve-cpl', 'azc_cpl_site_settings');
}
add_action('admin_menu', 'azc_cpl_site_settings_menu');
*/

function azc_cl_site_settings() {
	if (!current_user_can('manage_options')) {
		$error = new WP_Error('not_found', __('You do not have sufficient permissions to access this page.' , 'azc_cl'), array('response' => '200'));
		if(is_wp_error($error)){
			wp_die($error, '', $error->get_error_data());
		}
    }
	
	// Retrieve plugin site options from database
	$options = get_option('azc_cpl');
	?>
	<div id="azc-cl-general" class="wrap">
		<fieldset>
			<h2>azurecurve Conditional Page Links <?php _e('Site Settings', 'azc_cl'); ?></h2>
			<?php if(isset($_GET['options-updated'])) { ?>
				<div id="message" class="updated">
					<p><strong><?php _e('Site settings have been saved.') ?></strong></p>
				</div>
			<?php } ?>
			
			<form method="post" action="admin-post.php">
				<input type="hidden" name="action" value="save_azc_cl_site_settings" />
				<input name="page_options" type="hidden" value="display_add_link,display_edit_link" />
				
				<!-- Adding security through hidden referrer field -->
				<?php wp_nonce_field('azc_cl_nonce', 'azc_cl_nonce'); ?>
				
					<table class="form-table">
					<tr>
						<th scope="row" colspan="2">
							<label for="explanation">
								<?php _e('Conditional links to pages are added using the <strong>cpl</strong> shortcode and conditional links to posts are added using the <strong>cbl</strong> shortcodes. Valid formats for both cpl and cbl shortcodes are:', 'azc_gpi'); ?>
								<ul><li>[cpl slug="page slug"]</li>
								<li>[cpl slug="page slug"]text to display[/cpl]</li>
								<li>[cpl title="page title"]text to display[/cpl]</li></ul>
							</label>
						</th>
					</tr>
					<tr><th scope="row"><h2>Page Links</h2></th><td> </td></tr>
					<tr><th scope="row"><?php _e('Display Add Link?', 'azc_cl'); ?></th><td>
						<fieldset><legend class="screen-reader-text"><span><?php _e('Display Add/Edit Link?', 'azc_cl'); ?></span></legend>
						<label for="display_add_link"><input name="display_add_link" type="checkbox" id="display_add_link" value="1" <?php checked('1', $options['display_add_link']); ?> /></label>
						</fieldset>
					</td></tr>
				
					<tr><th scope="row"><?php _e('Display Edit Link?', 'azc_cl'); ?></th><td>
						<fieldset><legend class="screen-reader-text"><span><?php _e('Display Add/Edit Link?', 'azc_cl'); ?></span></legend>
						<label for="display_edit_link"><input name="display_edit_link" type="checkbox" id="display_edit_link" value="1" <?php checked('1', $options['display_edit_link']); ?> /></label>
						</fieldset>
					</td></tr>
					<tr><th scope="row"><h2>Blog Links</h2></th><td> </td></tr>
					<tr><th scope="row"><?php _e('Display Add Link?', 'azc_cl'); ?></th><td>
						<fieldset><legend class="screen-reader-text"><span><?php _e('Display Add/Edit Link?', 'azc_cl'); ?></span></legend>
						<label for="blog_display_add_link"><input name="blog_display_add_link" type="checkbox" id="blog_display_add_link" value="1" <?php checked('1', $options['blog_display_add_link']); ?> /></label>
						</fieldset>
					</td></tr>
				
					<tr><th scope="row"><?php _e('Display Edit Link?', 'azc_cl'); ?></th><td>
						<fieldset><legend class="screen-reader-text"><span><?php _e('Display Add/Edit Link?', 'azc_cl'); ?></span></legend>
						<label for="blog_display_edit_link"><input name="blog_display_edit_link" type="checkbox" id="blog_display_edit_link" value="1" <?php checked('1', $options['blog_display_edit_link']); ?> /></label>
						</fieldset>
					</td></tr>
				</table>
				<input type="submit" value="Submit" class="button-primary" />
			</form>
		</fieldset>
	</div>
<?php }

function azc_cl_admin_init() {
	add_action('admin_post_save_azc_cl_site_settings', 'azc_cl_save_site_settings');
}
add_action('admin_init', 'azc_cl_admin_init');

function azc_cl_save_site_settings() {
	// Check that user has proper security level
	if (!current_user_can('manage_options')) {
		$error = new WP_Error('not_found', __('You do not have sufficient permissions to perform this action.' , 'azc_cl'), array('response' => '200'));
		if(is_wp_error($error)){
			wp_die($error, '', $error->get_error_data());
		}
    }
	
	// Check that nonce field created in configuration form is present
	if (! empty($_POST) && check_admin_referer('azc_cl_nonce', 'azc_cl_nonce')) {
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
		
		$option_name = 'blog_display_add_link';
		if (isset($_POST[$option_name])) {
			$options[$option_name] = 1;
		}else{
			$options[$option_name] = 0;
		}
		
		$option_name = 'blog_display_edit_link';
		if (isset($_POST[$option_name])) {
			$options[$option_name] = 1;
		}else{
			$options[$option_name] = 0;
		}
		
		// Store updated options array to database
		update_option('azc_cpl', $options);
		
		// Redirect the page to the configuration form that was processed
		wp_redirect(add_query_arg('page', 'azc-cl&options-updated', admin_url('admin.php')));
		exit;
	}
}

function azc_cpl_shortcode($atts, $content = null) {
	extract(shortcode_atts(array(
		'slug' => '',
	), $atts));
	
	$slug = sanitize_text_field($slug);
	if (strlen($slug)==0){
		$slug=sanitize_text_field($content);
	}
	//echo $slug;
	
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
		$return .= "<a href='".get_permalink($the_page->ID)."' class='azc_cl'>".$the_page->post_title."</a>";
		if (current_user_can('edit_posts') and $options['display_edit_link'] == 1){
			if ($the_page->post_status == 'publish'){
				$return .= '&nbsp;<a href="'.$page_url.'wp-admin/post.php?post='.$the_page->ID.'&action=edit" class="azc_cl_admin">['.__('Edit','azc_cl').']</a>';
			}
		}
	}else{
		$return .= $slug."</a>";
		if (current_user_can('edit_posts') and $options['display_add_link'] == 1){
			$return .= '&nbsp;<a href="'.$page_url.'wp-admin/post-new.php?post_type=page" class="azc_cl_admin">['.__('Add','azc_cl').']</a>';
		}
	}
	return $return;
}
add_shortcode( 'cpl', 'azc_cpl_shortcode' );
add_shortcode( 'Cpl', 'azc_cpl_shortcode' );
add_shortcode( 'CPL', 'azc_cpl_shortcode' );

function azc_cbl_shortcode($atts, $content = null) {
	extract(shortcode_atts(array(
		'slug' => '',
		'title' => '',
	), $atts));
	
	$slug = sanitize_text_field($slug);
	if (strlen($slug)==0){
		$slug=sanitize_text_field($content);
	}
	$title = sanitize_text_field($title);
	//echo $title;
	
	global $wpdb;
	
	$options = get_option('azc_cpl');
	
	$page_url = trailingslashit(get_bloginfo('url'));
	//"http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
	
	/*if (strlen($slug) > 0){
		$page = get_page_by_path($slug);
		$page_id = $page->ID;
	}*/

	$sql = $wpdb->prepare("SELECT ID,post_title, post_name, post_status FROM ".$wpdb->prefix."posts WHERE post_status in ('publish') AND post_type = 'post' AND post_name='%s' limit 0,1", sanitize_title($slug));
	
	$return = '';
	$the_page = $wpdb->get_row( $sql );
	if ($the_page){
		if (strlen($title) == 0){
			$title = $the_page->post_title;
		}
		$return .= "<a href='".get_permalink($the_page->ID)."' class='azc_cl'>".$title."</a>";
		if (current_user_can('edit_posts') and $options['display_edit_link'] == 1){
			if ($the_page->post_status == 'publish'){
				$return .= '&nbsp;<a href="'.$page_url.'wp-admin/post.php?post='.$the_page->ID.'&action=edit" class="azc_cl_admin">['.__('Edit','azc_cl').']</a>';
			}
		}
	}else{
		$return .= $slug."</a>";
		if (current_user_can('edit_posts') and $options['display_add_link'] == 1){
			$return .= '&nbsp;<a href="'.$page_url.'wp-admin/post-new.php?post_type=page" class="azc_cl_admin">['.__('Add','azc_cl').']</a>';
		}
	}
	return $return;
}
add_shortcode( 'cbl', 'azc_cbl_shortcode' );
add_shortcode( 'Cbl', 'azc_cbl_shortcode' );
add_shortcode( 'CBL', 'azc_cbl_shortcode' );


// azurecurve menu
if (!function_exists('azc_create_plugin_menu')){
	function azc_create_plugin_menu() {
		global $admin_page_hooks;
		
		if ( empty ( $admin_page_hooks['azc-menu-test'] ) ){
			add_menu_page( "azurecurve Plugins"
							,"azurecurve"
							,'manage_options'
							,"azc-plugin-menus"
							,"azc_plugin_menus"
							,plugins_url( '/images/Favicon-16x16.png', __FILE__ ) );
			add_submenu_page( "azc-plugin-menus"
								,"Plugins"
								,"Plugins"
								,'manage_options'
								,"azc-plugin-menus"
								,"azc_plugin_menus" );
		}
	}
	add_action("admin_menu", "azc_create_plugin_menu");
}

function azc_create_cl_plugin_menu() {
	global $admin_page_hooks;
    
	add_submenu_page( "azc-plugin-menus"
						,"Conditional Links"
						,"Conditional Links"
						,'manage_options'
						,"azc-cl"
						,"azc_cl_site_settings" );
}
add_action("admin_menu", "azc_create_cl_plugin_menu");

if (!function_exists('azc_plugin_index_load_css')){
	function azc_plugin_index_load_css(){
		wp_enqueue_style( 'azurecurve_plugin_index', plugins_url( 'pluginstyle.css', __FILE__ ) );
	}
	add_action('admin_head', 'azc_plugin_index_load_css');
}

if (!function_exists('azc_plugin_menus')){
	function azc_plugin_menus() {
		echo "<h3>azurecurve Plugins";
		
		echo "<div style='display: block;'><h4>Active</h4>";
		echo "<span class='azc_plugin_index'>";
		if ( is_plugin_active( 'azurecurve-bbcode/azurecurve-bbcode.php' ) ) {
			echo "<a href='admin.php?page=azc-bbcode' class='azc_plugin_index'>BBCode</a>";
		}
		if ( is_plugin_active( 'azurecurve-comment-validator/azurecurve-comment-validator.php' ) ) {
			echo "<a href='admin.php?page=azc-cv' class='azc_plugin_index'>Comment Validator</a>";
		}
		if ( is_plugin_active( 'azurecurve-conditional-links/azurecurve-conditional-links.php' ) ) {
			echo "<a href='admin.php?page=azc-cl' class='azc_plugin_index'>Conditional Links</a>";
		}
		if ( is_plugin_active( 'azurecurve-display-after-post-content/azurecurve-display-after-post-content.php' ) ) {
			echo "<a href='admin.php?page=azc-dapc' class='azc_plugin_index'>Display After Post Content</a>";
		}
		if ( is_plugin_active( 'azurecurve-filtered-categories/azurecurve-filtered-categories.php' ) ) {
			echo "<a href='admin.php?page=azc-fc' class='azc_plugin_index'>Filtered Categories</a>";
		}
		if ( is_plugin_active( 'azurecurve-flags/azurecurve-flags.php' ) ) {
			echo "<a href='admin.php?page=azc-f' class='azc_plugin_index'>Flags</a>";
		}
		if ( is_plugin_active( 'azurecurve-floating-featured-image/azurecurve-floating-featured-image.php' ) ) {
			echo "<a href='admin.php?page=azc-ffi' class='azc_plugin_index'>Floating Featured Image</a>";
		}
		if ( is_plugin_active( 'azurecurve-get-plugin-info/azurecurve-get-plugin-info.php' ) ) {
			echo "<a href='admin.php?page=azc-gpi' class='azc_plugin_index'>Get Plugin Info</a>";
		}
		if ( is_plugin_active( 'azurecurve-icons/azurecurve-icons.php' ) ) {
			echo "<a href='admin.php?page=azc-f' class='azc_plugin_index'>Icons</a>";
		}
		if ( is_plugin_active( 'azurecurve-insult-generator/azurecurve-insult-generator.php' ) ) {
			echo "<a href='admin.php?page=azc-ig' class='azc_plugin_index'>Insult Generator</a>";
		}
		if ( is_plugin_active( 'azurecurve-mobile-detection/azurecurve-mobile-detection.php' ) ) {
			echo "<a href='admin.php?page=azc-md' class='azc_plugin_index'>Mobile Detection</a>";
		}
		if ( is_plugin_active( 'azurecurve-multisite-favicon/azurecurve-multisite-favicon.php' ) ) {
			echo "<a href='admin.php?page=azc-msf' class='azc_plugin_index'>Multisite Favicon</a>";
		}
		if ( is_plugin_active( 'azurecurve-page-index/azurecurve-page-index.php' ) ) {
			echo "<a href='admin.php?page=azc-pi' class='azc_plugin_index'>Page Index</a>";
		}
		if ( is_plugin_active( 'azurecurve-posts-archive/azurecurve-posts-archive.php' ) ) {
			echo "<a href='admin.php?page=azc-pa' class='azc_plugin_index'>Posts Archive</a>";
		}
		if ( is_plugin_active( 'azurecurve-rss-feed/azurecurve-rss-feed.php' ) ) {
			echo "<a href='admin.php?page=azc-rssf' class='azc_plugin_index'>RSS Feed</a>";
		}
		if ( is_plugin_active( 'azurecurve-rss-suffix/azurecurve-rss-suffix.php' ) ) {
			echo "<a href='admin.php?page=azc-rsss' class='azc_plugin_index'>RSS Suffix</a>";
		}
		if ( is_plugin_active( 'azurecurve-series-index/azurecurve-series-index.php' ) ) {
			echo "<a href='admin.php?page=azc-si' class='azc_plugin_index'>Series Index</a>";
		}
		if ( is_plugin_active( 'azurecurve-shortcodes-in-comments/azurecurve-shortcodes-in-comments.php' ) ) {
			echo "<a href='admin.php?page=azc-sic' class='azc_plugin_index'>Shortcodes in Comments</a>";
		}
		if ( is_plugin_active( 'azurecurve-shortcodes-in-widgets/azurecurve-shortcodes-in-widgets.php' ) ) {
			echo "<a href='admin.php?page=azc-siw' class='azc_plugin_index'>Shortcodes in Widgets</a>";
		}
		if ( is_plugin_active( 'azurecurve-tag-cloud/azurecurve-tag-cloud.php' ) ) {
			echo "<a href='admin.php?page=azc-tc' class='azc_plugin_index'>Tag Cloud</a>";
		}
		if ( is_plugin_active( 'azurecurve-taxonomy-index/azurecurve-taxonomy-index.php' ) ) {
			echo "<a href='admin.php?page=azc-ti' class='azc_plugin_index'>Taxonomy Index</a>";
		}
		if ( is_plugin_active( 'azurecurve-theme-switcher/azurecurve-theme-switcher.php' ) ) {
			echo "<a href='admin.php?page=azc-ts' class='azc_plugin_index'>Theme Switcher</a>";
		}
		if ( is_plugin_active( 'azurecurve-timelines/azurecurve-timelines.php' ) ) {
			echo "<a href='admin.php?page=azc-t' class='azc_plugin_index'>Timelines</a>";
		}
		if ( is_plugin_active( 'azurecurve-toggle-showhide/azurecurve-toggle-showhide.php' ) ) {
			echo "<a href='admin.php?page=azc-tsh' class='azc_plugin_index'>Toggle Show/Hide</a>";
		}
		echo "</span></div>";
		echo "<p style='clear: both' />";
		
		echo "<div style='display: block;'><h4>Other Available Plugins</h4>";
		echo "<span class='azc_plugin_index'>";
		if ( !is_plugin_active( 'azurecurve-bbcode/azurecurve-bbcode.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-bbcode/' class='azc_plugin_index'>BBCode</a>";
		}
		if ( !is_plugin_active( 'azurecurve-comment-validator/azurecurve-comment-validator.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-comment-validator/' class='azc_plugin_index'>Comment Validator</a>";
		}
		if ( !is_plugin_active( 'azurecurve-conditional-links/azurecurve-conditional-links.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-conditional-links/' class='azc_plugin_index'>Conditional Links</a>";
		}
		if ( !is_plugin_active( 'azurecurve-display-after-post-content/azurecurve-display-after-post-content.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-display-after-post-content/' class='azc_plugin_index'>Display After Post Content</a>";
		}
		if ( !is_plugin_active( 'azurecurve-filtered-categories/azurecurve-filtered-categories.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-filtered-categories/' class='azc_plugin_index'>Filtered Categories</a>";
		}
		if ( !is_plugin_active( 'azurecurve-flags/azurecurve-flags.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-flags/' class='azc_plugin_index'>Flags</a>";
		}
		if ( !is_plugin_active( 'azurecurve-floating-featured-image/azurecurve-floating-featured-image.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-floating-featured-image/' class='azc_plugin_index'>Floating Featured Image</a>";
		}
		if ( !is_plugin_active( 'azurecurve-get-plugin-info/azurecurve-get-plugin-info.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-get-plugin-info/' class='azc_plugin_index'>Get Plugin Info</a>";
		}
		if ( !is_plugin_active( 'azurecurve-icons/azurecurve-icons.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-icons/' class='azc_plugin_index'>Icons</a>";
		}
		if ( !is_plugin_active( 'azurecurve-insult-generator/azurecurve-insult-generator.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-insult-generator/' class='azc_plugin_index'>Insult Generator</a>";
		}
		if ( !is_plugin_active( 'azurecurve-mobile-detection/azurecurve-mobile-detection.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-mobile-detection/' class='azc_plugin_index'>Mobile Detection</a>";
		}
		if ( !is_plugin_active( 'azurecurve-multisite-favicon/azurecurve-multisite-favicon.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-multisite-favicon/' class='azc_plugin_index'>Multisite Favicon</a>";
		}
		if ( !is_plugin_active( 'azurecurve-page-index/azurecurve-page-index.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-page-index/' class='azc_plugin_index'>Page Index</a>";
		}
		if ( !is_plugin_active( 'azurecurve-posts-archive/azurecurve-posts-archive.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-posts-archive/' class='azc_plugin_index'>Posts Archive</a>";
		}
		if ( !is_plugin_active( 'azurecurve-rss-feed/azurecurve-rss-feed.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-rss-feed/' class='azc_plugin_index'>RSS Feed</a>";
		}
		if ( !is_plugin_active( 'azurecurve-rss-suffix/azurecurve-rss-suffix.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-rss-suffix/' class='azc_plugin_index'>RSS Suffix</a>";
		}
		if ( !is_plugin_active( 'azurecurve-series-index/azurecurve-series-index.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-series-index/' class='azc_plugin_index'>Series Index</a>";
		}
		if ( !is_plugin_active( 'azurecurve-shortcodes-in-comments/azurecurve-shortcodes-in-comments.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-shortcodes-in-comments/' class='azc_plugin_index'>Shortcodes in Comments</a>";
		}
		if ( !is_plugin_active( 'azurecurve-shortcodes-in-widgets/azurecurve-shortcodes-in-widgets.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-shortcodes-in-widgets/' class='azc_plugin_index'>Shortcodes in Widgets</a>";
		}
		if ( !is_plugin_active( 'azurecurve-tag-cloud/azurecurve-tag-cloud.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-tag-cloud/' class='azc_plugin_index'>Tag Cloud</a>";
		}
		if ( !is_plugin_active( 'azurecurve-taxonomy-index/azurecurve-taxonomy-index.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-taxonomy-index/' class='azc_plugin_index'>Taxonomy Index</a>";
		}
		if ( !is_plugin_active( 'azurecurve-theme-switcher/azurecurve-theme-switcher.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-theme-switcher/' class='azc_plugin_index'>Theme Switcher</a>";
		}
		if ( !is_plugin_active( 'azurecurve-timelines/azurecurve-timelines.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-timelines/' class='azc_plugin_index'>Timelines</a>";
		}
		if ( !is_plugin_active( 'azurecurve-toggle-showhide/azurecurve-toggle-showhide.php' ) ) {
			echo "<a href='https://wordpress.org/plugins/azurecurve-toggle-showhide/' class='azc_plugin_index'>Toggle Show/Hide</a>";
		}
		echo "</span></div>";
	}
}

?>