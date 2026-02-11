<?php
/**
 * Register custom taxonomies for the card post type.
 *
 * @package HK_Card_Compare
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKCC_Card_Taxonomy {

	/**
	 * Hook into WordPress.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register card_bank and card_network taxonomies.
	 * Both are hierarchical (category-style picker) for single selection.
	 */
	public static function register() {
		// Card Bank taxonomy (發卡機構).
		register_taxonomy( 'card_bank', 'card', array(
			'labels'            => array(
				'name'          => '發卡機構',
				'singular_name' => '發卡機構',
				'search_items'  => '搜尋發卡機構',
				'all_items'     => '所有發卡機構',
				'edit_item'     => '編輯發卡機構',
				'update_item'   => '更新發卡機構',
				'add_new_item'  => '新增發卡機構',
				'new_item_name' => '發卡機構名稱',
				'menu_name'     => '發卡機構',
			),
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'card-bank' ),
		) );

		// Card Network taxonomy (結算機構).
		register_taxonomy( 'card_network', 'card', array(
			'labels'            => array(
				'name'          => '結算機構',
				'singular_name' => '結算機構',
				'search_items'  => '搜尋結算機構',
				'all_items'     => '所有結算機構',
				'edit_item'     => '編輯結算機構',
				'update_item'   => '更新結算機構',
				'add_new_item'  => '新增結算機構',
				'new_item_name' => '結算機構名稱',
				'menu_name'     => '結算機構',
			),
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'card-network' ),
		) );
	}
}
