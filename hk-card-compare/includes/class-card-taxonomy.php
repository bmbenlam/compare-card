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
	 */
	public static function register() {
		// Card Bank taxonomy.
		register_taxonomy( 'card_bank', 'card', array(
			'labels'            => array(
				'name'          => '發卡銀行',
				'singular_name' => '發卡銀行',
				'search_items'  => '搜尋發卡銀行',
				'all_items'     => '所有發卡銀行',
				'edit_item'     => '編輯發卡銀行',
				'update_item'   => '更新發卡銀行',
				'add_new_item'  => '新增發卡銀行',
				'new_item_name' => '發卡銀行名稱',
				'menu_name'     => '發卡銀行',
			),
			'hierarchical'      => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'card-bank' ),
		) );

		// Card Network taxonomy.
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
			'hierarchical'      => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'card-network' ),
		) );
	}
}
