<?php
/**
 * Register the 'card' custom post type.
 *
 * @package HK_Card_Compare
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKCC_Card_Post_Type {

	/**
	 * Hook into WordPress.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_image_size( 'card-thumb', 600, 380, true );
	}

	/**
	 * Register the card post type.
	 */
	public static function register() {
		$labels = array(
			'name'                  => '信用卡',
			'singular_name'         => '信用卡',
			'add_new'               => '新增信用卡',
			'add_new_item'          => '新增信用卡',
			'edit_item'             => '編輯信用卡',
			'new_item'              => '新增信用卡',
			'view_item'             => '檢視信用卡',
			'view_items'            => '檢視信用卡',
			'search_items'          => '搜尋信用卡',
			'not_found'             => '找不到信用卡',
			'not_found_in_trash'    => '回收站中找不到信用卡',
			'all_items'             => 'All Cards',
			'archives'              => '信用卡檔案',
			'menu_name'             => 'Cards',
		);

		$args = array(
			'labels'              => $labels,
			'public'              => true,
			'has_archive'         => true,
			'rewrite'             => array( 'slug' => 'cards' ),
			'supports'            => array( 'title', 'editor', 'thumbnail', 'revisions', 'custom-fields' ),
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-money-alt',
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'can_export'          => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
		);

		register_post_type( 'card', $args );
	}
}
