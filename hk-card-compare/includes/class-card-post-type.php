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

		// Custom admin columns for the "All Cards" list table.
		add_filter( 'manage_card_posts_columns', array( __CLASS__, 'add_columns' ) );
		add_action( 'manage_card_posts_custom_column', array( __CLASS__, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-card_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'sort_by_expiry' ) );
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

	/**
	 * Add custom columns to the card list table.
	 */
	public static function add_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			// Insert after title.
			if ( 'title' === $key ) {
				$new['welcome_expiry'] = '迎新到期日';
			}
		}
		return $new;
	}

	/**
	 * Render custom column content.
	 */
	public static function render_column( $column, $post_id ) {
		if ( 'welcome_expiry' !== $column ) {
			return;
		}
		$expiry = get_post_meta( $post_id, 'welcome_offer_expiry', true );
		if ( ! $expiry ) {
			echo '<span style="color:#999;">—</span>';
			return;
		}
		$ts    = strtotime( $expiry );
		$now   = time();
		$days  = (int) round( ( $ts - $now ) / 86400 );
		$color = '#333';
		if ( $days < 0 ) {
			$color = '#999';
			$label = '已過期';
		} elseif ( $days <= 7 ) {
			$color = '#b20000';
			$label = $days . ' 日後到期';
		} elseif ( $days <= 30 ) {
			$color = '#b38850';
			$label = $days . ' 日後到期';
		} else {
			$label = '';
		}
		echo '<span style="color:' . esc_attr( $color ) . ';">' . esc_html( $expiry ) . '</span>';
		if ( $label ) {
			echo '<br><small style="color:' . esc_attr( $color ) . ';">' . esc_html( $label ) . '</small>';
		}
	}

	/**
	 * Make the expiry column sortable.
	 */
	public static function sortable_columns( $columns ) {
		$columns['welcome_expiry'] = 'welcome_expiry';
		return $columns;
	}

	/**
	 * Handle sorting by welcome_offer_expiry.
	 */
	public static function sort_by_expiry( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( 'welcome_expiry' !== $query->get( 'orderby' ) ) {
			return;
		}
		$query->set( 'meta_key', 'welcome_offer_expiry' );
		$query->set( 'orderby', 'meta_value' );
	}
}
