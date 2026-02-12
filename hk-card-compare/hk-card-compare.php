<?php
/**
 * Plugin Name: HK Card Compare
 * Plugin URI:  https://example.com/hk-card-compare
 * Description: Credit card comparison plugin for Hong Kong travel & personal finance blogs. Supports Traditional Chinese, points conversion, miles/cash toggle, and affiliate click tracking.
 * Version:     1.0.2
 * Author:      HK Card Compare
 * Author URI:  https://example.com
 * Text Domain: hk-card-compare
 * Domain Path: /languages
 * Requires at least: 6.9.1
 * Requires PHP: 8.0
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HKCC_VERSION', '1.0.3' );
define( 'HKCC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HKCC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'HKCC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Create custom database tables on activation.
 */
function hkcc_activate() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();

	$sql_points_systems = "CREATE TABLE {$wpdb->prefix}card_points_systems (
		id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		system_name VARCHAR(100) NOT NULL,
		system_name_en VARCHAR(100),
		status ENUM('active','inactive') DEFAULT 'active',
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		UNIQUE KEY system_name (system_name)
	) {$charset_collate};";

	$sql_points_conversion = "CREATE TABLE {$wpdb->prefix}card_points_conversion (
		id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		system_id BIGINT UNSIGNED NOT NULL,
		reward_type VARCHAR(50) NOT NULL,
		points_required INT UNSIGNED NOT NULL,
		reward_value DECIMAL(10,2) NOT NULL,
		reward_currency VARCHAR(10) DEFAULT 'HKD',
		effective_date DATE,
		expiry_date DATE,
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		FOREIGN KEY (system_id) REFERENCES {$wpdb->prefix}card_points_systems(id) ON DELETE CASCADE,
		KEY system_reward (system_id, reward_type)
	) {$charset_collate};";

	$sql_card_clicks = "CREATE TABLE {$wpdb->prefix}card_clicks (
		id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		card_id BIGINT UNSIGNED NOT NULL,
		source_url VARCHAR(500),
		clicked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
		KEY card_id (card_id),
		KEY clicked_at (clicked_at),
		KEY source_card (source_url(191), card_id)
	) {$charset_collate};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql_points_systems );
	dbDelta( $sql_points_conversion );
	dbDelta( $sql_card_clicks );

	update_option( 'hkcc_db_version', HKCC_VERSION );
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'hkcc_activate' );

/**
 * Clean up on deactivation.
 */
function hkcc_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'hkcc_deactivate' );

/*
 * Load plugin classes.
 */
require_once HKCC_PLUGIN_DIR . 'includes/class-card-post-type.php';
require_once HKCC_PLUGIN_DIR . 'includes/class-card-taxonomy.php';
require_once HKCC_PLUGIN_DIR . 'includes/class-card-meta.php';
require_once HKCC_PLUGIN_DIR . 'includes/class-points-system.php';
require_once HKCC_PLUGIN_DIR . 'includes/class-click-tracker.php';
require_once HKCC_PLUGIN_DIR . 'includes/class-schema-output.php';

if ( is_admin() ) {
	require_once HKCC_PLUGIN_DIR . 'admin/class-card-admin.php';
	require_once HKCC_PLUGIN_DIR . 'admin/class-points-admin.php';
	require_once HKCC_PLUGIN_DIR . 'admin/class-analytics-admin.php';
}

require_once HKCC_PLUGIN_DIR . 'public/class-card-shortcodes.php';
require_once HKCC_PLUGIN_DIR . 'public/class-card-display.php';

/**
 * Initialize plugin components.
 */
function hkcc_init() {
	HKCC_Card_Post_Type::init();
	HKCC_Card_Taxonomy::init();
	HKCC_Card_Meta::init();
	HKCC_Points_System::init();
	HKCC_Click_Tracker::init();
	HKCC_Schema_Output::init();

	if ( is_admin() ) {
		HKCC_Card_Admin::init();
		HKCC_Points_Admin::init();
		HKCC_Analytics_Admin::init();
	}

	HKCC_Card_Shortcodes::init();
	HKCC_Card_Display::init();
}
add_action( 'plugins_loaded', 'hkcc_init' );

/**
 * Flush rewrite rules once after plugin update (fixes 404 on card URLs).
 */
function hkcc_maybe_flush_rules() {
	if ( get_option( 'hkcc_flush_rules_version' ) !== HKCC_VERSION ) {
		flush_rewrite_rules();
		update_option( 'hkcc_flush_rules_version', HKCC_VERSION );
	}
}
add_action( 'init', 'hkcc_maybe_flush_rules', 99 );

/**
 * Load single-card template from plugin when viewing a card post.
 */
function hkcc_template_include( $template ) {
	if ( is_singular( 'card' ) ) {
		$plugin_template = HKCC_PLUGIN_DIR . 'templates/single-card.php';
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}
	}
	return $template;
}
add_filter( 'template_include', 'hkcc_template_include' );

/**
 * Enable PublishPress Revisions support for the card post type.
 */
add_filter( 'publishpress_revisions_post_types', function ( $post_types ) {
	$post_types[] = 'card';
	return $post_types;
} );
