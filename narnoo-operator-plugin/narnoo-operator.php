<?php
/*
Plugin Name: Narnoo Operator
Plugin URI: http://narnoo.com/
Description: Allows Wordpress users to manage and include their Narnoo media into their Wordpress site. You will need a Narnoo API key pair to include your Narnoo media. You can find this by logging into your account at Narnoo.com and going to Account -> View APPS.
Version: 2.0.0
Author: Narnoo Wordpress developer
Author URI: http://www.narnoo.com/
License: GPL2 or later
*/

/*  Copyright 2012  Narnoo.com  (email : info@narnoo.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
// plugin definitions
define( 'NARNOO_OPERATOR_PLUGIN_NAME', 'Narnoo Operator' );
define( 'NARNOO_OPERATOR_CURRENT_VERSION', '2.0.0' );
define( 'NARNOO_OPERATOR_I18N_DOMAIN', 'narnoo-operator' );

define( 'NARNOO_OPERATOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NARNOO_OPERATOR_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'NARNOO_OPERATOR_SETTINGS_PAGE', 'options-general.php?page=narnoo-operator-api-settings' );

// include files
if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
require_once( NARNOO_OPERATOR_PLUGIN_PATH . 'class-narnoo-operator-helper.php' );
require_once( NARNOO_OPERATOR_PLUGIN_PATH . 'class-narnoo-operator-followers-table.php' );
require_once( NARNOO_OPERATOR_PLUGIN_PATH . 'class-narnoo-operator-images-table.php' );
require_once( NARNOO_OPERATOR_PLUGIN_PATH . 'class-narnoo-operator-brochures-table.php' );
require_once( NARNOO_OPERATOR_PLUGIN_PATH . 'class-narnoo-operator-videos-table.php' );
require_once( NARNOO_OPERATOR_PLUGIN_PATH . 'class-narnoo-operator-albums-table.php' );
require_once( NARNOO_OPERATOR_PLUGIN_PATH . 'class-narnoo-operator-products-table.php' );
require_once( NARNOO_OPERATOR_PLUGIN_PATH . 'class-narnoo-operator-library-images-table.php' );

//PHP VERSION 2.0
require_once( NARNOO_OPERATOR_PLUGIN_PATH . 'libs/narnoo/http/WebClient.php' );
require_once( NARNOO_OPERATOR_PLUGIN_PATH . 'libs/narnoo/operator.php' );

//Cache Php Fastcache
require_once( NARNOO_OPERATOR_PLUGIN_PATH . 'libs/cache/phpfastcache.php' );

// begin!
new Narnoo_Operator();

class Narnoo_Operator {

	/**
	 * Plugin's main entry point.
	 **/
	function __construct() {
		register_uninstall_hook( __FILE__, array( 'NarnooOperator', 'uninstall' ) );
		add_action( 'init', array( &$this, 'create_custom_post_type' ) );


		if ( is_admin() ) {
			add_action( 'plugins_loaded', array( &$this, 'load_language_file' ) );
			add_filter( 'plugin_action_links', array( &$this, 'plugin_action_links' ), 10, 2 );

			add_action( 'admin_notices', array( &$this, 'display_reminders' ) );
			add_action( 'admin_menu', array( &$this, 'create_menus' ) );
			add_action( 'admin_init', array( &$this, 'admin_init' ) );

			add_filter( 'media_upload_tabs', array( &$this, 'add_narnoo_library_menu_tab' ) );
			add_action( 'media_upload_narnoo_library', array( &$this, 'media_narnoo_library_menu_handle') );

			add_action( 'wp_ajax_narnoo_operator_api_request', array( 'Narnoo_Operator_Helper', 'ajax_api_request' ) );

			//Meta Boxes
			add_action('add_meta_boxes', array( &$this, 'add_noo_album_meta_box'));
			add_action( 'save_post', array( &$this, 'save_noo_album_meta_box'));
			add_action('add_meta_boxes', array( &$this, 'add_noo_video_meta_box'));
			add_action( 'save_post', array( &$this, 'save_noo_video_meta_box'));
			add_action('add_meta_boxes', array( &$this, 'add_noo_print_meta_box'));
			add_action( 'save_post', array( &$this, 'save_noo_print_meta_box'));
			

		} else {
			/*add_shortcode( 'narnoo_operator_brochure', array( &$this, 'narnoo_operator_brochure_shortcode' ) );
			add_shortcode( 'narnoo_operator_video', array( &$this, 'narnoo_operator_video_shortcode' ) );
			add_shortcode( 'narnoo_operator_tiles_gallery', array( &$this, 'narnoo_operator_tiles_gallery_shortcode' ) );
			add_shortcode( 'narnoo_operator_single_link_gallery', array( &$this, 'narnoo_operator_single_link_gallery_shortcode' ) );
			add_shortcode( 'narnoo_operator_slider_gallery', array( &$this, 'narnoo_operator_slider_gallery_shortcode' ) );
			add_shortcode( 'narnoo_operator_grid_gallery', array( &$this, 'narnoo_operator_grid_gallery_shortcode' ) );*/

			add_action( 'wp_enqueue_scripts', array( &$this, 'load_scripts' ) );
			add_action( 'init', array( &$this, 'check_request' ) );

			add_filter( 'widget_text', 'do_shortcode' );
		}

		//add_action( 'wp_ajax_narnoo_operator_lib_request', array( &$this, 'narnoo_operator_ajax_lib_request' ) );
		//add_action( 'wp_ajax_nopriv_narnoo_operator_lib_request', array( &$this, 'narnoo_operator_ajax_lib_request' ) );
	}

	/**
	 * Register custom post types for Narnoo Products.
	 **/
	function create_custom_post_type() {

		register_post_type(
				'narnoo_product',
				array(
					'labels'      => [
                               'name'          => __('Products'),
                               'singular_name' => __('Product'),
                           ],
					'hierarchical' => true,
					'rewrite' => array( 'slug' => 'product' ),
					'description' => "Custom post type for imported products from Narnoo",
					'public' => true,
					'exclude_from_search' => true,
					'has_archive' => true,
					'publicly_queryable' => true,
					'show_ui' => true,
					'show_in_menu' => 'product_import_page',
					'show_in_admin_bar' => true,
					'supports' => array( 'title', 'excerpt', 'thumbnail', 'editor', 'author', 'revisions', 'custom-fields', 'page-attributes' ),
				)
			);

		flush_rewrite_rules();

	}

	/**
	 * Add Narnoo Library tab to Wordpress media upload menu.
	 **/
	function add_narnoo_library_menu_tab( $tabs ) {
		$newTab = array( 'narnoo_library' => __( 'Narnoo Library', NARNOO_OPERATOR_I18N_DOMAIN ) );
		return array_merge($tabs, $newTab);
	}

	/**
	 * Handle display of Narnoo library in Wordpress media upload menu.
	 **/
	function media_narnoo_library_menu_handle() {
		return wp_iframe( array( &$this, 'media_narnoo_library_menu_display' ) );
	}

	function media_narnoo_library_menu_display() {
		media_upload_header();
		$narnoo_operator_library_images_table = new Narnoo_Operator_Library_Images_Table();
		?>
			<form id="narnoo-images-form" class="media-upload-form" method="post" action="">
				<?php
				$narnoo_operator_library_images_table->prepare_items();
				$narnoo_operator_library_images_table->display();
				?>
			</form>
		<?php
	}

	/**
	 * Clean up upon plugin uninstall.
	 **/
	static function uninstall() {
		unregister_setting( 'narnoo_operator_settings', 'narnoo_operator_settings', array( &$this, 'settings_sanitize' ) );
	}

	/**
	 * Add settings link for this plugin to Wordpress 'Installed plugins' page.
	 **/
	function plugin_action_links( $links, $file ) {
		if ( $file == plugin_basename( dirname(__FILE__) . '/narnoo-operator.php' ) ) {
			$links[] = '<a href="' . NARNOO_OPERATOR_SETTINGS_PAGE . '">' . __('Settings') . '</a>';
		}

		return $links;
	}

	/**
	 * Load language file upon plugin init (for future extension, if any)
	 **/
	function load_language_file() {
		load_plugin_textdomain( NARNOO_OPERATOR_I18N_DOMAIN, false, NARNOO_OPERATOR_PLUGIN_PATH . 'languages/' );
	}

	/**
	 * Display reminder to key in API keys in admin backend.
	 **/
	function display_reminders() {
		$options = get_option( 'narnoo_operator_settings' );

		if ( empty( $options['access_key'] ) || empty( $options['secret_key'] ) || empty( $options['token'] ) ) {
			Narnoo_Operator_Helper::show_notification(
				sprintf(
					__( '<strong>Reminder:</strong> Please key in your Narnoo API settings in the <strong><a href="%s">Settings->Narnoo API</a></strong> page.', NARNOO_OPERATOR_I18N_DOMAIN ),
					NARNOO_OPERATOR_SETTINGS_PAGE
				)
			);
		}
	}

	/**
	 * Add admin menus and submenus to backend.
	 **/
	function create_menus() {
		// add Narnoo API to settings menu
		add_options_page(
			__( 'Narnoo API Settings', NARNOO_OPERATOR_I18N_DOMAIN ),
			__( 'Narnoo API', NARNOO_OPERATOR_I18N_DOMAIN ),
			'manage_options',
			'narnoo-operator-api-settings',
			array( &$this, 'api_settings_page' )
		);

		// add main Narnoo Media menu
		add_menu_page(
			__( 'Narnoo Media', NARNOO_OPERATOR_I18N_DOMAIN ),
			__( 'Narnoo', NARNOO_OPERATOR_I18N_DOMAIN ),
			'manage_options',
			'narnoo-operator-followers',
			array( &$this, 'narnoo_page' ),
			NARNOO_OPERATOR_PLUGIN_URL . 'images/icon-16.png',
			11
		);

		// add main Narnoo Imports menu
		add_menu_page(
			__( 'Product Imports', NARNOO_OPERATOR_I18N_DOMAIN ),
			__( 'Product Imports', NARNOO_OPERATOR_I18N_DOMAIN ),
			'manage_options', 
			'product_import_page', 
			array( &$this, 'product_import_page' ),   
			NARNOO_OPERATOR_PLUGIN_URL . 'images/icon-16.png', 
			12
		);

		// add submenus to Narnoo Media menu
		/*$page = add_submenu_page(
			'narnoo-operator-followers',
			__( 'Narnoo Media - Followers', NARNOO_OPERATOR_I18N_DOMAIN ),
			__( 'Followers', NARNOO_OPERATOR_I18N_DOMAIN ),
			'manage_options',
			'narnoo-operator-distributors',
			array( &$this, 'followers_page' )
		);
		add_action( "load-$page", array( 'Narnoo_Operator_Followers_Table', 'add_screen_options' ) );*/

		$page = add_submenu_page(
			'narnoo-operator-followers',
			__( 'Narnoo Media - Albums', NARNOO_OPERATOR_I18N_DOMAIN ),
			__( 'Albums', NARNOO_OPERATOR_I18N_DOMAIN ),
			'manage_options',
			'narnoo-operator-albums',
			array( &$this, 'albums_page' )
		);
		add_action( "load-$page", array( 'Narnoo_Operator_Albums_Table', 'add_screen_options' ) );

		$page = add_submenu_page(
			'narnoo-operator-followers',
			__( 'Narnoo Media - Images', NARNOO_OPERATOR_I18N_DOMAIN ),
			__( 'Images', NARNOO_OPERATOR_I18N_DOMAIN ),
			'manage_options',
			'narnoo-operator-images',
			array( &$this, 'images_page' )
		);
		add_action( "load-$page", array( 'Narnoo_Operator_Images_Table', 'add_screen_options' ) );

		$page = add_submenu_page(
			'narnoo-operator-followers',
			__( 'Narnoo Media - Print', NARNOO_OPERATOR_I18N_DOMAIN ),
			__( 'Print', NARNOO_OPERATOR_I18N_DOMAIN ),
			'manage_options',
			'narnoo-operator-brochures',
			array( &$this, 'brochures_page' )
		);
		add_action( "load-$page", array( 'Narnoo_Operator_Brochures_Table', 'add_screen_options' ) );

		$page = add_submenu_page(
			'narnoo-operator-followers',
			__( 'Narnoo Media - Videos', NARNOO_OPERATOR_I18N_DOMAIN ),
			__( 'Videos', NARNOO_OPERATOR_I18N_DOMAIN ),
			'manage_options',
			'narnoo-operator-videos',
			array( &$this, 'videos_page' )
		);
		add_action( "load-$page", array( 'Narnoo_Operator_Videos_Table', 'add_screen_options' ) );

		$page = add_submenu_page(
			'narnoo-operator-followers',
			__( 'Narnoo Media - Products', NARNOO_OPERATOR_I18N_DOMAIN ),
			__( 'Products', NARNOO_OPERATOR_I18N_DOMAIN ),
			'manage_options',
			'narnoo-operator-products',
			array( &$this, 'products_page' )
		);
		add_action( "load-$page", array( 'Narnoo_Operator_Products_Table', 'add_screen_options' ) );
		
	}

	/**
	 * Upon admin init, register plugin settings and Narnoo shortcodes button, and define input fields for API settings.
	 **/
	function admin_init() {
		register_setting( 'narnoo_operator_settings', 'narnoo_operator_settings', array( &$this, 'settings_sanitize' ) );

		add_settings_section(
			'api_settings_section',
			__( 'API Settings', NARNOO_OPERATOR_I18N_DOMAIN ),
			array( &$this, 'settings_api_section' ),
			'narnoo_operator_api_settings'
		);

		add_settings_field(
			'access_key',
			__( 'Acesss key', NARNOO_OPERATOR_I18N_DOMAIN ),
			array( &$this, 'settings_access_key' ),
			'narnoo_operator_api_settings',
			'api_settings_section'
		);

		add_settings_field(
			'secret_key',
			__( 'Secret key', NARNOO_OPERATOR_I18N_DOMAIN ),
			array( &$this, 'settings_secret_key' ),
			'narnoo_operator_api_settings',
			'api_settings_section'
		);

		add_settings_field(
			'token',
			__( 'Token key', NARNOO_OPERATOR_I18N_DOMAIN ),
			array( &$this, 'settings_token' ),
			'narnoo_operator_api_settings',
			'api_settings_section'
		);

		// register Narnoo shortcode button and MCE plugin
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
			return;
		}

		if ( get_user_option('rich_editing') == 'true' ) {
			//add_filter( 'mce_external_plugins', array( &$this, 'add_shortcode_plugin' ) );
			//add_filter( 'mce_buttons', array( &$this, 'register_shortcode_button' ) );
		}
	}

	function settings_api_section() {
		echo '<p>' . __( 'You can edit your Narnoo API settings below.', NARNOO_OPERATOR_I18N_DOMAIN ) . '</p>';
	}

	function settings_access_key() {
		$options = get_option( 'narnoo_operator_settings' );
		echo "<input id='access_key' name='narnoo_operator_settings[access_key]' size='40' type='text' value='" . esc_attr($options['access_key']). "' />";
	}

	function settings_secret_key() {
		$options = get_option( 'narnoo_operator_settings' );
		echo "<input id='secret_key' name='narnoo_operator_settings[secret_key]' size='40' type='text' value='" . esc_attr($options['secret_key']). "' />";
	}

	function settings_token() {
		$options = get_option( 'narnoo_operator_settings' );
		echo "<input id='token' name='narnoo_operator_settings[token]' size='40' type='text' value='" . esc_attr($options['token']). "' />";
	}

	/**
	 * Sanitize input settings.
	 **/
	function settings_sanitize( $input ) {
		$new_input['access_key'] 	= trim( $input['access_key'] );
		$new_input['secret_key'] 	= trim( $input['secret_key'] );
		$new_input['token'] 		= trim( $input['token'] );
		return $new_input;
	}

	/**
	 * Display API settings page.
	 **/
	function api_settings_page() {
		?>
		<div class="wrap">
			<div class="icon32"><img src="<?php echo NARNOO_OPERATOR_PLUGIN_URL; ?>/images/icon-32.png" /><br /></div>
			<h2><?php _e( 'Narnoo API Settings', NARNOO_OPERATOR_I18N_DOMAIN ) ?></h2>
			<form action="options.php" method="post">
				<?php settings_fields( 'narnoo_operator_settings' ); ?>
				<?php do_settings_sections( 'narnoo_operator_api_settings' ); ?>

				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
				</p>
			</form>
			<?php

			$cache	 		= Narnoo_Operator_Helper::init_noo_cache();
			$request 		= Narnoo_Operator_Helper::init_api();

			$operator = null;
			if ( ! is_null( $request ) ) {

				$operator = $cache->get('operator_details');

				if( empty( $operator ) ){

					try {
						

						$operator = $request->accountDetails();

						if(!empty( $operator->success ) ){
								$cache->set('operator_details', $operator, 43200);
						}


					} catch ( Exception $ex ) {
						$operator = null;
						Narnoo_Operator_Helper::show_api_error( $ex );
					}

				}


			}

			if ( ! is_null( $operator ) ) {
				?>
				<h3><?php _e( 'Operator Details', NARNOO_OPERATOR_I18N_DOMAIN ) ?></h3>
				<table class="form-table">
					<tr><th><?php _e( 'ID', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->operator_id; ?></td></tr>
					<tr><th><?php _e( 'UserName', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->operator_username; ?></td></tr>
					<tr><th><?php _e( 'Email', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->email; ?></td></tr>
					<tr><th><?php _e( 'Business Name', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->operator_businessname; ?></td></tr>
					<tr><th><?php _e( 'Contact Name', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->operator_contactname; ?></td></tr>
					<tr><th><?php _e( 'Location', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->location; ?></td></tr>
					<!--<tr><th><?php _e( 'Country', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->country_name; ?></td></tr>
					<tr><th><?php _e( 'Post Code', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->postcode; ?></td></tr>
					<tr><th><?php _e( 'Suburb', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->suburb; ?></td></tr>
					<tr><th><?php _e( 'State', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->state; ?></td></tr> -->
					<tr><th><?php _e( 'Phone', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->phone; ?></td></tr>
					<tr><th><?php _e( 'URL', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->operator_url; ?></td></tr>
					<tr><th><?php _e( 'Category', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->category; ?></td></tr>
					<tr><th><?php _e( 'Sub Category', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->sub_category; ?></td></tr>
					<tr><th><?php _e( 'Keywords', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->keywords; ?></td></tr>
					<tr><th><?php _e( 'Total Images', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->image_count; ?></td></tr>
					<tr><th><?php _e( 'Total Print', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->brochure_count; ?></td></tr>
					<tr><th><?php _e( 'Total Videos', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->video_count; ?></td></tr>
					<!--<tr><th><?php _e( 'Total Products', NARNOO_OPERATOR_I18N_DOMAIN ); ?></th><td><?php echo $operator->description_count; ?></td></tr> --> 
				</table>
				<?php
			}
			?>
		</div>
		<?php
	}

	/**
	 * Display Narnoo Followers page.
	 **/
	function followers_page() {
		global $narnoo_operator_followers_table;
		?>
		<div class="wrap">
			<div class="icon32"><img src="<?php echo NARNOO_OPERATOR_PLUGIN_URL; ?>/images/icon-32.png" /><br /></div>
			<h2><?php _e( 'Narnoo Media - Followers', NARNOO_OPERATOR_I18N_DOMAIN ) ?></h2>
			<form id="narnoo-followers-form" method="post" action="?<?php echo esc_attr( build_query( array( 'page' => isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '', 'paged' => $narnoo_operator_followers_table->get_pagenum() ) ) ); ?>">
				<?php
				$narnoo_operator_followers_table->prepare_items();
				$narnoo_operator_followers_table->display();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Display Narnoo Albums page.
	 **/
	function albums_page() {
		global $narnoo_operator_albums_table;
		?>
		<div class="wrap">
			<div class="icon32"><img src="<?php echo NARNOO_OPERATOR_PLUGIN_URL; ?>/images/icon-32.png" /><br /></div>
			<h2><?php _e( 'Narnoo Media - Albums', NARNOO_OPERATOR_I18N_DOMAIN ) ?>
				<a href="?<?php echo build_query( array( 'page' => isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '', 'paged' => $narnoo_operator_albums_table->get_pagenum(), 'action' => 'create' ) ); ?>" class="add-new-h2"><?php echo esc_html_x( 'Create New', NARNOO_OPERATOR_I18N_DOMAIN ); ?></a></h2>
			<form id="narnoo-albums-form" method="post" action="?<?php echo esc_attr( build_query( array( 'page' => isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '', 'paged' => $narnoo_operator_albums_table->get_pagenum(), 'album_page' => $narnoo_operator_albums_table->current_album_page, 'album' => $narnoo_operator_albums_table->current_album_id, 'album_name' => urlencode( $narnoo_operator_albums_table->current_album_name ) ) ) ); ?>">
			<?php
			if ( $narnoo_operator_albums_table->prepare_items() ) {
				?><h3>Currently viewing album: <?php echo $narnoo_operator_albums_table->current_album_name; ?></h3><?php
				_e( 'Select album:', NARNOO_OPERATOR_I18N_DOMAIN );
				echo $narnoo_operator_albums_table->select_album_html_script;
				submit_button( __( 'Go', NARNOO_OPERATOR_I18N_DOMAIN ), 'button-secondary action', false, false, array( 'id' => "album_select_button" ) );

				$narnoo_operator_albums_table->views();
				$narnoo_operator_albums_table->display();
			}
			?>
			</form>
		</div>
		<?php
	}


	/**
	 * Display Narnoo Images page.
	 **/
	function images_page() {
		global $narnoo_operator_images_table;
		?>
		<div class="wrap">
			<div class="icon32"><img src="<?php echo NARNOO_OPERATOR_PLUGIN_URL; ?>/images/icon-32.png" /><br /></div>
			<h2><?php _e( 'Narnoo Media - Images', NARNOO_OPERATOR_I18N_DOMAIN ) ?></h2>
			<form id="narnoo-images-form" method="post" action="?<?php echo esc_attr( build_query( array( 'page' => isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '', 'paged' => $narnoo_operator_images_table->get_pagenum() ) ) ); ?>">
				<?php
				if ( $narnoo_operator_images_table->prepare_items() ) {
					$narnoo_operator_images_table->display();
				}
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Display Narnoo Brochures page.
	 **/
	function brochures_page() {
		global $narnoo_operator_brochures_table;
		?>
		<div class="wrap">
			<div class="icon32"><img src="<?php echo NARNOO_OPERATOR_PLUGIN_URL; ?>/images/icon-32.png" /><br /></div>
			<h2><?php _e( 'Narnoo Media - Print Material', NARNOO_OPERATOR_I18N_DOMAIN ) ?></h2>
			<form id="narnoo-brochures-form" method="post" action="?<?php echo esc_attr( build_query( array( 'page' => isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '', 'paged' => $narnoo_operator_brochures_table->get_pagenum() ) ) ); ?>">
				<?php
				if ( $narnoo_operator_brochures_table->prepare_items() ) {
					$narnoo_operator_brochures_table->display();
				}
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Display Narnoo Videos page.
	 **/
	function videos_page() {
		global $narnoo_operator_videos_table;
		?>
		<div class="wrap">
			<div class="icon32"><img src="<?php echo NARNOO_OPERATOR_PLUGIN_URL; ?>/images/icon-32.png" /><br /></div>
			<h2><?php _e( 'Narnoo Media - Videos', NARNOO_OPERATOR_I18N_DOMAIN ) ?></h2>
			<form id="narnoo-videos-form" method="post" action="?<?php echo esc_attr( build_query( array( 'page' => isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '', 'paged' => $narnoo_operator_videos_table->get_pagenum() ) ) ); ?>">
				<?php
				if ( $narnoo_operator_videos_table->prepare_items() ) {
					$narnoo_operator_videos_table->display();
				}
				?>
			</form>
		</div>
		<?php
	}

	/**
	 *
	 * Date Created: 14-09-16
	 * Display Narnoo products page.
	 **/
	function products_page() {
		global $narnoo_operator_products_table;
		?>
		<div class="wrap">
			<div class="icon32"><img src="<?php echo NARNOO_OPERATOR_PLUGIN_URL; ?>/images/icon-32.png" /><br /></div>
			<h2><?php _e( 'Narnoo Media - Products', NARNOO_OPERATOR_I18N_DOMAIN ) ?></h2>
			<form id="narnoo-products-form" method="post" action="?<?php echo esc_attr( build_query( array( 'page' => isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '', 'paged' => $narnoo_operator_products_table->get_pagenum() ) ) ); ?>">
				<?php
				if ( $narnoo_operator_products_table->prepare_items() ) {
					$narnoo_operator_products_table->display();
				}
				?>
			</form>
		</div>
		<?php
	}

	/*
	*
	*	title: Narnoo page to display help information
	*	date created: 15-09-16
	*/
	function narnoo_page(){
		ob_start();
		require( NARNOO_OPERATOR_PLUGIN_PATH . 'libs/html/help_info_tpl.php' );
		echo ob_get_clean();
	}

	/*
	*
	*	title: Narnoo add narnoo album to a page
	*	date created: 15-09-16
	*/
	function add_noo_album_meta_box()
	{
	   
	            add_meta_box(
	                'noo-album-box-class',      		// Unique ID
				    'Select Narnoo Album', 		 		    // Title
				    array( &$this,'box_display_album_information'),    // Callback function
				    'page',         					// Admin page (or post type)
				    'side',         					// Context
				    'low'         					// Priority
	             );
	        
	}

	/*
	*
	*	title: Display the album select box
	*	date created: 15-09-16
	*/
	function box_display_album_information( $post )
	{
	
	global $post;
    //$values = get_post_custom( $post->ID );
    $selected = get_post_meta($post->ID,'noo_album_select_id',true);
    //$selected = isset( $values['noo_album_select_id'] ) ? esc_attr( $values['noo_album_select_id'] ) : '';

	// We'll use this nonce field later on when saving.
    wp_nonce_field( 'album_meta_box_nonce', 'box_display_album_information_nonce' );
	   
		$current_page 		      = 1;
		$cache	 				  = Narnoo_Operator_Helper::init_noo_cache();
		$request 				  = Narnoo_Operator_Helper::init_api();

		//Get Narnoo Ablums.....
		if ( ! is_null( $request ) ) {
			
			$list = $cache->get('albums_'.$current_page);

			if( empty($list) ){

					try {

						$list = $request->getAlbums( $current_page );
						if ( ! is_array( $list->operator_albums ) ) {
							throw new Exception( sprintf( __( "Error retrieving albums. Unexpected format in response page #%d.", NARNOO_OPERATOR_I18N_DOMAIN ), $current_page ) );
						}

						if(!empty( $list->success ) ){
								$cache->set('albums_'.$current_page, $list, 43200);
						}

					} catch ( Exception $ex ) {
						Narnoo_Operator_Helper::show_api_error( $ex );
					} 		

			}

			//Check the total pages and run through each so we can build a bigger list of albums	
		
		}


    ?> <p>
        <label for="my_meta_box_select">Narnoo Album:</label>
        <select name="noo_album_select" id="noo_album_select">
        	<option value="">None</option>
            <?php foreach ($list->operator_albums as $album) { ?>
            		<option value="<?php echo $album->album_id; ?>" <?php selected( $selected, $album->album_id ); ?>><?php echo ucwords( $album->album_name ); ?></option>
            <?php } ?>
        </select>
        <p><small><em>Select an album and this will be displayed the page.</em></small></p>
    </p>
  	<?php

	}

	function save_noo_album_meta_box( $post_id ){

		// Bail if we're doing an auto save
	    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	     
	    // if our nonce isn't there, or we can't verify it, bail
	    if( !isset( $_POST['box_display_album_information_nonce'] ) || !wp_verify_nonce( $_POST['box_display_album_information_nonce'], 'album_meta_box_nonce' ) ) return;
	     
	    // if our current user can't edit this post, bail
	    if( !current_user_can( 'edit_post' ) ) return;

	    if( isset( $_POST['noo_album_select'] ) ){
        	update_post_meta( $post_id, 'noo_album_select_id', esc_attr( $_POST['noo_album_select'] ) );
    	}

	}

	/*
	*
	*	title: Narnoo add narnoo album to a page
	*	date created: 15-09-16
	*/
	function add_noo_video_meta_box()
	{
	   
	            add_meta_box(
	                'noo-video-box-class',      		// Unique ID
				    'Enter Narnoo Video ID', 		 		    // Title
				    array( &$this,'box_display_video_information'),    // Callback function
				    'page',         					// Admin page (or post type)
				    'side',         					// Context
				    'low'         					// Priority
	             );
	        
	}

	/*
	*
	*	title: Display the album select box
	*	date created: 15-09-16
	*/
	function box_display_video_information( $post )
	{
	
	global $post;
    //$values = get_post_custom( $post->ID );
    $selected = get_post_meta($post->ID,'noo_video_id',true);
    //$selected = isset( $values['noo_album_select_id'] ) ? esc_attr( $values['noo_album_select_id'] ) : '';

	// We'll use this nonce field later on when saving.
    wp_nonce_field( 'video_meta_box_nonce', 'box_display_video_information_nonce' );
	   


    ?> <p>
        <label for="video_box_text">Narnoo Video:</label>
        <input type="text" name="noo_video_box_text" id="noo_video_box_text" value="<?php echo $selected; ?>" />
    </p>
        <p><small><em>Enter a video ID to display a video on the page.</em></small></p>
    </p>
  	<?php

	}

	function save_noo_video_meta_box( $post_id ){

		// Bail if we're doing an auto save
	    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	     
	    // if our nonce isn't there, or we can't verify it, bail
	    if( !isset( $_POST['box_display_video_information_nonce'] ) || !wp_verify_nonce( $_POST['box_display_video_information_nonce'], 'video_meta_box_nonce' ) ) return;
	     
	    // if our current user can't edit this post, bail
	    if( !current_user_can( 'edit_post' ) ) return;

	    if( isset( $_POST['noo_video_box_text'] ) ){
        	update_post_meta( $post_id, 'noo_video_id', wp_kses( $_POST['noo_video_box_text'] ) );
    	}

	}



	/*
	*
	*	title: Narnoo add narnoo album to a page
	*	date created: 15-09-16
	*/
	function add_noo_print_meta_box()
	{
	   
	            add_meta_box(
	                'noo-print-box-class',      		// Unique ID
				    'Enter Narnoo Print ID', 		 		    // Title
				    array( &$this,'box_display_print_information'),    // Callback function
				    'page',         					// Admin page (or post type)
				    'side',         					// Context
				    'low'         					// Priority
	             );
	        
	}

	/*
	*
	*	title: Display the print select box
	*	date created: 15-09-16
	*/
	function box_display_print_information( $post )
	{
	
	global $post;
    //$values = get_post_custom( $post->ID );
    $selected = get_post_meta($post->ID,'noo_print_id',true);
    //$selected = isset( $values['noo_album_select_id'] ) ? esc_attr( $values['noo_album_select_id'] ) : '';

	// We'll use this nonce field later on when saving.
    wp_nonce_field( 'print_meta_box_nonce', 'box_display_print_information_nonce' );
	   


    ?> <p>
        <label for="print_box_text">Narnoo Print Item:</label>
        <input type="text" name="noo_print_box_text" id="noo_print_box_text" value="<?php echo $selected; ?>" />
    </p>
        <p><small><em>Enter a print ID to display a PDF on the page.</em></small></p>
    </p>
  	<?php

	}

	function save_noo_print_meta_box( $post_id ){

		// Bail if we're doing an auto save
	    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	     
	    // if our nonce isn't there, or we can't verify it, bail
	    if( !isset( $_POST['box_display_print_information_nonce'] ) || !wp_verify_nonce( $_POST['box_display_print_information_nonce'], 'print_meta_box_nonce' ) ) return;
	     
	    // if our current user can't edit this post, bail
	    if( !current_user_can( 'edit_post' ) ) return;

	    if( isset( $_POST['noo_print_box_text'] ) ){
        	update_post_meta( $post_id, 'noo_print_id', wp_kses( $_POST['noo_print_box_text'] ) );
    	}

	}


 //global $post;

	   // if(!empty($post))
	    //{

	      //  if( is_page_template('page-home.php') )
	       // {
	//}
	   // }


}
