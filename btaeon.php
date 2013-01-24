<?php
/**
 * Plugin Name: Bulletaeon Bulletin System
 * Plugin URI: http://nyllep.wordpress.com/
 * Description: A bulletin system for HSNU
 * Version: 0.1
 * Author: Yu-Te Lin (aka Pellaeon)
 * Author URI: http://nyllep.wordpress.com/
 * License: GPL2
 */

// Stop direct call
if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF']))
	die('You are not allowed to call this page directly.');

global $wpdb;

include_once('bt_upload.php');
include_once('shortcodes.php');
include_once('categories.php');
include_once('bt_config.php');
include_once('actions.php');
include_once('renderer.php');
include_once('validator.php');
include_once('dboperator.php');
include_once('ajax.php');

// Define the tables used in Bulletaeon
// $table_prefix is deprecated in version 2.1, use $wpdb->prefix instead.
define('WP_BTAEON_TABLE', $wpdb->prefix . 'btaeon_msgs');
define('WP_BTAEON_CONFIG_TABLE', $wpdb->prefix . 'btaeon_config');
define('WP_BTAEON_CATEGORIES_TABLE', $wpdb->prefix . 'btaeon_categories');
define('WP_USERS_TABLE', $wpdb->prefix . 'users');
define('BT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Function to add the bulletin menu
function btaeon_menu()
{
	global $wpdb;
	$renderer = new Renderer();

	// Set the capability needed to manage messages
	$allowed_group = 'edit_posts';

	// Handles options saving and redirecting
	bt_admin_redirect();

	// Maybe I'll add internationalization in the future
	if ( function_exists('add_menu_page') )
	{
		// Add a top-level menu
		add_menu_page('管理公告', '公告系統', $allowed_group, 'bulletaeon', array($renderer, 'manage_main'));
	}
	if ( function_exists('add_submenu_page') )
	{
		$admpage[0] = add_submenu_page('bulletaeon', '管理公告', '管理公告', $allowed_group, 'bulletaeon', array($renderer, 'manage_main'));
		// Only admin can change options and manage categories
		$admpage[1] = add_submenu_page('bulletaeon', '管理公告分類', '分類', 'manage_options', 'btcat', 'bt_manage_categories');
		$admpage[2] = add_submenu_page('bulletaeon', '公告系統設定', '設定', 'manage_options', 'btconf', 'bt_config');
	}
}

Class Bulletaeon {
	// Function to check if Bulletaeon is installed and install it if not.
	public function install()
	{
		global $wpdb;

		// Create a folder for uploading
		$dir = WP_CONTENT_DIR . '/bt_uploads/';
		if ( !file_exists($dir) ) mkdir($dir);

		// Set database charset and collation
		if ( ! empty($wpdb->charset) )
			$charset_collate = "CHARACTER SET $wpdb->charset";
		else
			$charset_collate = "CHARACTER SET utf8";
		if ( ! empty($wpdb->collate) )
			$charset_collate .= " COLLATE $wpdb->collate";
		else
			$charset_collate .= " COLLATE utf8_general_ci";

		// Predefine some default values
		$wp_btaeon_exists = false;
		$wp_btaeon_categories_exists = false;

		$tables = $wpdb->get_results("SHOW TABLES");

		foreach ( $tables as $table )
		{
			foreach ( $table as $value )
			{
				if ( $value == WP_BTAEON_TABLE )
					$wp_btaeon_exists = true;
				if ( $value == WP_BTAEON_CONFIG_TABLE )
					$wp_btaeon_config_exists = true;
				if ( $value == WP_BTAEON_CATEGORIES_TABLE )
					$wp_btaeon_categories_exists = true;
			}
		}

		// Perform operations according to the findings
		if ( $wp_btaeon_exists == false )
		{
			$sql = "CREATE TABLE " . WP_BTAEON_TABLE . " (
				msg_id INT NOT NULL AUTO_INCREMENT,
				msg_owner VARCHAR(20) NOT NULL,
				msg_category INT(4) NOT NULL DEFAULT 1,
				msg_time DATETIME NOT NULL,
				msg_title VARCHAR(255) NOT NULL,
				msg_content LONGTEXT NOT NULL,
				msg_link TEXT DEFAULT '',
				msg_file TEXT DEFAULT '',
				sticky TINYINT(1) NULL DEFAULT 0,
				PRIMARY KEY (msg_id)
			) " . $charset_collate;
			$wpdb->get_results($sql);
		}

		if ( $wp_btaeon_categories_exists == false )
		{
			$sql = "CREATE TABLE " . WP_BTAEON_CATEGORIES_TABLE . " (
				category_id TINYINT NOT NULL AUTO_INCREMENT,
				category_name VARCHAR(10) NOT NULL,
				category_link TEXT,
				PRIMARY KEY (category_id)
			) " . $charset_collate;
			$wpdb->get_results($sql);

			// Create a default category
			// XXX: Change default category_name?
			$sql = "INSERT INTO " . WP_BTAEON_CATEGORIES_TABLE . "
				SET category_id = 1, category_name = '未分類'";
			$wpdb->get_results($sql);
		}
	}

}

// Check existence upon activation
register_activation_hook( __FILE__, array('Bulletaeon', 'install') );

// Create a master category for Calendar and its sub-pages
add_action('admin_menu', 'btaeon_menu');

/* Load sytles and javascripts
 * Ref: http://codex.wordpress.org/Function_Reference/wp_enqueue_script
 */
function load_bt_admin_scripts() {
	wp_register_style('bt-manage', BT_PLUGIN_URL . '/style/manage.css');
	wp_enqueue_style('bt-manage');
}
add_action( 'admin_enqueue_scripts', 'load_bt_admin_scripts' );

function load_bt_shortcode_scripts() {
	wp_register_style('bt-shortcode', BT_PLUGIN_URL . '/style/shortcodes.css');
	wp_enqueue_style('bt-shortcode');
}
add_action( 'wp_enqueue_scripts', 'load_bt_shortcode_scripts' );

/* AJAX script for handling sticky messages
 */
function stickymsg_js() {
	wp_register_script('stickymsg_js', BT_PLUGIN_URL . '/js/stickymsg.js');
	wp_enqueue_script('stickymsg_js');
}
add_action('admin_enqueue_scripts', 'stickymsg_js');
?>
