<?php
/**
 * Manage card post meta fields: definitions, sanitisation, and save logic.
 *
 * @package HK_Card_Compare
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKCC_Card_Meta {

	/**
	 * All meta field definitions grouped by tab.
	 */
	public static function get_fields() {
		return array(
			'basic'    => array(
				'card_name'      => array( 'label' => 'Card Name (前端顯示名稱)', 'type' => 'text', 'max' => 200 ),
				'tagline'        => array( 'label' => 'Tagline', 'type' => 'text', 'max' => 200 ),
				'affiliate_link' => array( 'label' => 'Affiliate Link', 'type' => 'url' ),
			),
			'fees'     => array(
				'annual_fee_display'           => array( 'label' => '年費 (Display)', 'type' => 'text' ),
				'annual_fee_sortable'          => array( 'label' => '年費 (Sortable)', 'type' => 'int' ),
				'annual_fee_waiver'            => array( 'label' => '年費豁免', 'type' => 'text' ),
				'fx_fee_display'               => array( 'label' => '外幣兌換手續費 (Display)', 'type' => 'text' ),
				'fx_fee_sortable'              => array( 'label' => '外幣兌換手續費 (Sortable)', 'type' => 'float' ),
				'cross_border_fee_display'     => array( 'label' => '跨境結算手續費 (Display)', 'type' => 'text' ),
				'cross_border_fee_sortable'    => array( 'label' => '跨境結算手續費 (Sortable)', 'type' => 'float' ),
				'late_fee_display'             => array( 'label' => '逾期還款費 (Display)', 'type' => 'text' ),
				'late_fee_sortable'            => array( 'label' => '逾期還款費 (Sortable)', 'type' => 'int' ),
				'interest_free_period_display' => array( 'label' => '免息還款期 (Display)', 'type' => 'text' ),
				'interest_free_period_sortable'=> array( 'label' => '免息還款期 (Sortable)', 'type' => 'int' ),
			),
			'rewards'  => array(
				'points_system_id'             => array( 'label' => '積分系統', 'type' => 'int' ),
				'local_retail_points'          => array( 'label' => '本地零售簽賬 (積分)', 'type' => 'text' ),
				'overseas_retail_points'       => array( 'label' => '海外零售簽賬 (積分)', 'type' => 'text' ),
				'online_hkd_points'            => array( 'label' => '網上港幣簽賬 (積分)', 'type' => 'text' ),
				'online_fx_points'             => array( 'label' => '網上外幣簽賬 (積分)', 'type' => 'text' ),
				'local_dining_points'          => array( 'label' => '本地餐飲簽賬 (積分)', 'type' => 'text' ),
				'online_bill_payment_points'   => array( 'label' => '網上繳費 (積分)', 'type' => 'text' ),
				'payme_reload_points'          => array( 'label' => 'PayMe 增值 (積分)', 'type' => 'text' ),
				'alipay_reload_points'         => array( 'label' => 'AlipayHK 增值 (積分)', 'type' => 'text' ),
				'wechat_reload_points'         => array( 'label' => 'WeChat Pay 增值 (積分)', 'type' => 'text' ),
				'octopus_reload_points'        => array( 'label' => '八達通增值 (積分)', 'type' => 'text' ),
				'local_retail_cash_display'    => array( 'label' => '本地零售簽賬 現金回贈 (Display)', 'type' => 'text' ),
				'local_retail_cash_sortable'   => array( 'label' => '本地零售簽賬 現金回贈 (Sortable)', 'type' => 'float' ),
				'overseas_retail_cash_display'  => array( 'label' => '海外零售簽賬 現金回贈 (Display)', 'type' => 'text' ),
				'overseas_retail_cash_sortable' => array( 'label' => '海外零售簽賬 現金回贈 (Sortable)', 'type' => 'float' ),
				'online_hkd_cash_display'      => array( 'label' => '網上港幣簽賬 現金回贈 (Display)', 'type' => 'text' ),
				'online_hkd_cash_sortable'     => array( 'label' => '網上港幣簽賬 現金回贈 (Sortable)', 'type' => 'float' ),
				'online_fx_cash_display'       => array( 'label' => '網上外幣簽賬 現金回贈 (Display)', 'type' => 'text' ),
				'online_fx_cash_sortable'      => array( 'label' => '網上外幣簽賬 現金回贈 (Sortable)', 'type' => 'float' ),
				'local_dining_cash_display'    => array( 'label' => '本地餐飲簽賬 現金回贈 (Display)', 'type' => 'text' ),
				'local_dining_cash_sortable'   => array( 'label' => '本地餐飲簽賬 現金回贈 (Sortable)', 'type' => 'float' ),
				'online_bill_payment_cash_display'  => array( 'label' => '網上繳費 現金回贈 (Display)', 'type' => 'text' ),
				'online_bill_payment_cash_sortable' => array( 'label' => '網上繳費 現金回贈 (Sortable)', 'type' => 'float' ),
				'payme_reload_cash_display'    => array( 'label' => 'PayMe 增值 現金回贈 (Display)', 'type' => 'text' ),
				'payme_reload_cash_sortable'   => array( 'label' => 'PayMe 增值 現金回贈 (Sortable)', 'type' => 'float' ),
				'alipay_reload_cash_display'   => array( 'label' => 'AlipayHK 增值 現金回贈 (Display)', 'type' => 'text' ),
				'alipay_reload_cash_sortable'  => array( 'label' => 'AlipayHK 增值 現金回贈 (Sortable)', 'type' => 'float' ),
				'wechat_reload_cash_display'   => array( 'label' => 'WeChat Pay 增值 現金回贈 (Display)', 'type' => 'text' ),
				'wechat_reload_cash_sortable'  => array( 'label' => 'WeChat Pay 增值 現金回贈 (Sortable)', 'type' => 'float' ),
				'octopus_reload_cash_display'  => array( 'label' => '八達通增值 現金回贈 (Display)', 'type' => 'text' ),
				'octopus_reload_cash_sortable' => array( 'label' => '八達通增值 現金回贈 (Sortable)', 'type' => 'float' ),
				'redemption_types'             => array( 'label' => '兌換方式', 'type' => 'array' ),
				'statement_credit_requirement' => array( 'label' => 'Statement Credit 要求', 'type' => 'text' ),
				'points_system_name'           => array( 'label' => '積分系統名稱', 'type' => 'text' ),
				'points_redemption_fee_display' => array( 'label' => '積分兌換費用 (Display)', 'type' => 'text' ),
				'points_redemption_fee_sortable'=> array( 'label' => '積分兌換費用 (Sortable)', 'type' => 'int' ),
				'transferable_airlines'        => array( 'label' => '可轉換航空里程', 'type' => 'array' ),
				'transferable_hotels'          => array( 'label' => '可轉換酒店積分', 'type' => 'array' ),
			),
			'welcome'  => array(
				'welcome_offer_short'             => array( 'label' => '迎新優惠簡述 (Preview)', 'type' => 'text', 'max' => 120 ),
				'welcome_cooling_period_display'  => array( 'label' => '迎新冷河期 (Display)', 'type' => 'text' ),
				'welcome_cooling_period_sortable' => array( 'label' => '迎新冷河期 (Sortable)', 'type' => 'int' ),
				'welcome_offer_description'       => array( 'label' => '迎新優惠詳細描述', 'type' => 'html' ),
				'welcome_offer_expiry'            => array( 'label' => '迎新優惠到期日', 'type' => 'date' ),
			),
			'benefits' => array(
				'lounge_access_display'  => array( 'label' => '免費貴賓室 (Display)', 'type' => 'text' ),
				'lounge_access_sortable' => array( 'label' => '免費貴賓室 (Sortable)', 'type' => 'int' ),
				'travel_insurance'       => array( 'label' => '旅遊保險', 'type' => 'text' ),
			),
			'featured' => array(
				'featured_param_1' => array( 'label' => 'Featured Parameter 1', 'type' => 'select' ),
				'featured_param_2' => array( 'label' => 'Featured Parameter 2', 'type' => 'select' ),
				'featured_param_3' => array( 'label' => 'Featured Parameter 3', 'type' => 'select' ),
				'featured_param_4' => array( 'label' => 'Featured Parameter 4', 'type' => 'select' ),
			),
			'eligibility' => array(
				'min_age_display'    => array( 'label' => '最低年齡 (Display)', 'type' => 'text' ),
				'min_age_sortable'   => array( 'label' => '最低年齡 (Sortable)', 'type' => 'int' ),
				'min_income_display' => array( 'label' => '最低收入 (Display)', 'type' => 'text' ),
				'min_income_sortable'=> array( 'label' => '最低收入 (Sortable)', 'type' => 'int' ),
			),
		);
	}

	public static function get_all_keys() {
		$keys = array();
		foreach ( self::get_fields() as $fields ) {
			$keys = array_merge( $keys, array_keys( $fields ) );
		}
		return $keys;
	}

	/**
	 * Fields that can be chosen as "featured parameter" values.
	 * Returns clean zh-HK labels for display-appropriate fields only.
	 * Values auto-switch between miles and cash view.
	 */
	public static function get_featurable_fields() {
		$options = array( '' => '— Select —' );

		// Fee fields.
		$options['annual_fee_display']       = '年費';
		$options['fx_fee_display']           = '外幣兌換手續費';
		$options['cross_border_fee_display'] = '跨境結算手續費';

		// Reward fields (dynamic from transaction types).
		$txn_labels = HKCC_Points_System::get_transaction_labels();
		foreach ( HKCC_Points_System::get_transaction_types() as $txn ) {
			$label = $txn_labels[ $txn ] ?? $txn;
			$options[ "{$txn}_cash_display" ] = "{$label}回贈";
		}

		// Benefits.
		$options['lounge_access_display'] = '免費貴賓室';
		$options['travel_insurance']      = '旅遊保險';

		// Welcome.
		$options['welcome_offer_short']            = '迎新優惠';
		$options['welcome_cooling_period_display']  = '迎新冷河期';

		return $options;
	}

	public static function init() {
		add_action( 'save_post_card', array( __CLASS__, 'save' ), 10, 2 );
	}

	/**
	 * Sanitise and save all meta fields when a card is saved.
	 */
	public static function save( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['hkcc_meta_nonce'] ) || ! wp_verify_nonce( $_POST['hkcc_meta_nonce'], 'hkcc_save_meta' ) ) {
			return;
		}

		$all_fields = self::get_fields();

		foreach ( $all_fields as $group_fields ) {
			foreach ( $group_fields as $key => $def ) {
				if ( ! isset( $_POST[ 'hkcc_' . $key ] ) ) {
					continue;
				}

				$raw = $_POST[ 'hkcc_' . $key ];

				switch ( $def['type'] ) {
					case 'int':
						$value = intval( $raw );
						break;
					case 'float':
						$value = floatval( $raw );
						break;
					case 'url':
						$value = esc_url_raw( $raw );
						break;
					case 'html':
						$value = wp_kses_post( $raw );
						break;
					case 'date':
						$value = sanitize_text_field( $raw );
						break;
					case 'array':
						$value = is_array( $raw ) ? array_map( 'sanitize_text_field', $raw ) : array();
						break;
					default:
						$value = sanitize_text_field( $raw );
						break;
				}

				update_post_meta( $post_id, $key, $value );
			}
		}

		// Save custom transaction type fields.
		$custom_types = get_option( 'hkcc_custom_txn_types', array() );
		if ( ! empty( $custom_types ) ) {
			foreach ( $custom_types as $type ) {
				$slug = $type['slug'];
				if ( isset( $_POST[ "hkcc_{$slug}_points" ] ) ) {
					update_post_meta( $post_id, "{$slug}_points", sanitize_text_field( $_POST[ "hkcc_{$slug}_points" ] ) );
				}
				if ( isset( $_POST[ "hkcc_{$slug}_cash_display" ] ) ) {
					update_post_meta( $post_id, "{$slug}_cash_display", sanitize_text_field( $_POST[ "hkcc_{$slug}_cash_display" ] ) );
				}
				if ( isset( $_POST[ "hkcc_{$slug}_cash_sortable" ] ) ) {
					update_post_meta( $post_id, "{$slug}_cash_sortable", floatval( $_POST[ "hkcc_{$slug}_cash_sortable" ] ) );
				}
			}
		}

		HKCC_Points_System::auto_calculate_rebates( $post_id );
	}
}
