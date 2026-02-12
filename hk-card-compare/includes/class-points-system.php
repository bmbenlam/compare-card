<?php
/**
 * Points system database operations and auto-calculation logic.
 *
 * @package HK_Card_Compare
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKCC_Points_System {

	public static function init() {}

	/* ------------------------------------------------------------------
	 * CRUD helpers for points systems table.
	 * ---------------------------------------------------------------- */

	public static function get_all() {
		global $wpdb;
		return $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}card_points_systems ORDER BY system_name ASC"
		);
	}

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}card_points_systems WHERE id = %d",
				$id
			)
		);
	}

	public static function create( $name, $name_en = '' ) {
		global $wpdb;
		$result = $wpdb->insert(
			$wpdb->prefix . 'card_points_systems',
			array(
				'system_name'    => sanitize_text_field( $name ),
				'system_name_en' => sanitize_text_field( $name_en ),
			),
			array( '%s', '%s' )
		);
		return $result ? $wpdb->insert_id : false;
	}

	public static function update( $id, $name, $name_en, $status = 'active' ) {
		global $wpdb;
		return (bool) $wpdb->update(
			$wpdb->prefix . 'card_points_systems',
			array(
				'system_name'    => sanitize_text_field( $name ),
				'system_name_en' => sanitize_text_field( $name_en ),
				'status'         => in_array( $status, array( 'active', 'inactive' ), true ) ? $status : 'active',
			),
			array( 'id' => intval( $id ) ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	public static function delete( $id ) {
		global $wpdb;
		return (bool) $wpdb->delete(
			$wpdb->prefix . 'card_points_systems',
			array( 'id' => intval( $id ) ),
			array( '%d' )
		);
	}

	/* ------------------------------------------------------------------
	 * Conversion rate helpers.
	 * ---------------------------------------------------------------- */

	public static function get_conversions( $system_id ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}card_points_conversion WHERE system_id = %d ORDER BY reward_type ASC",
				$system_id
			)
		);
	}

	public static function save_conversions( $system_id, $conversions ) {
		global $wpdb;
		$system_id = intval( $system_id );

		$wpdb->delete(
			$wpdb->prefix . 'card_points_conversion',
			array( 'system_id' => $system_id ),
			array( '%d' )
		);

		foreach ( $conversions as $row ) {
			$wpdb->insert(
				$wpdb->prefix . 'card_points_conversion',
				array(
					'system_id'       => $system_id,
					'reward_type'     => sanitize_text_field( $row['reward_type'] ),
					'points_required' => absint( $row['points_required'] ),
					'reward_value'    => floatval( $row['reward_value'] ),
					'reward_currency' => sanitize_text_field( $row['reward_currency'] ?? 'HKD' ),
					'effective_date'  => ! empty( $row['effective_date'] ) ? sanitize_text_field( $row['effective_date'] ) : null,
					'expiry_date'     => ! empty( $row['expiry_date'] ) ? sanitize_text_field( $row['expiry_date'] ) : null,
				),
				array( '%d', '%s', '%d', '%f', '%s', '%s', '%s' )
			);
		}
	}

	/* ------------------------------------------------------------------
	 * Transaction types (reward categories) — now configurable.
	 * ---------------------------------------------------------------- */

	private static function get_default_transaction_types() {
		return array(
			'local_retail',
			'overseas_retail',
			'online_hkd',
			'online_fx',
			'local_dining',
			'designated_supermarket',
			'public_transport',
			'designated_merchant',
			'contactless_mobile',
			'online_bill_payment',
			'payme_reload',
			'alipay_reload',
			'wechat_reload',
			'octopus_reload',
		);
	}

	/**
	 * Get all transaction types including user-defined custom ones.
	 *
	 * @return array Flat array of slugs.
	 */
	public static function get_transaction_types() {
		$defaults = self::get_default_transaction_types();
		$custom   = get_option( 'hkcc_custom_txn_types', array() );

		if ( ! empty( $custom ) && is_array( $custom ) ) {
			foreach ( $custom as $type ) {
				if ( ! empty( $type['slug'] ) ) {
					$defaults[] = $type['slug'];
				}
			}
		}

		return $defaults;
	}

	/**
	 * Get slug => Chinese label map for all transaction types.
	 *
	 * @return array
	 */
	public static function get_transaction_labels() {
		$labels = array(
			'local_retail'           => '本地零售簽賬',
			'overseas_retail'        => '海外零售簽賬',
			'online_hkd'             => '網上港幣簽賬',
			'online_fx'              => '網上外幣簽賬',
			'local_dining'           => '本地餐飲簽賬',
			'designated_supermarket' => '指定超市簽賬',
			'public_transport'       => '公共交通簽賬',
			'designated_merchant'    => '指定商戶簽賬',
			'contactless_mobile'     => '手機感應式支付',
			'online_bill_payment'    => '網上繳費',
			'payme_reload'           => 'PayMe 增值',
			'alipay_reload'          => 'AlipayHK 增值',
			'wechat_reload'          => 'WeChat Pay 增值',
			'octopus_reload'         => '八達通增值',
		);

		$custom = get_option( 'hkcc_custom_txn_types', array() );
		if ( ! empty( $custom ) && is_array( $custom ) ) {
			foreach ( $custom as $type ) {
				if ( ! empty( $type['slug'] ) && ! empty( $type['label'] ) ) {
					$labels[ $type['slug'] ] = $type['label'];
				}
			}
		}

		return $labels;
	}

	/* ------------------------------------------------------------------
	 * Auto-calculation: points → cash / miles.
	 * ---------------------------------------------------------------- */

	public static function extract_earning_rate( $text ) {
		if ( preg_match( '/=\s*([\d.]+)/', $text, $m ) ) {
			return floatval( $m[1] );
		}
		if ( preg_match( '/^([\d.]+)$/', trim( $text ), $m ) ) {
			return floatval( $m[1] );
		}
		return 0.0;
	}

	/**
	 * Run auto-calculation for a card after save.
	 *
	 * @param int $post_id Card post ID.
	 */
	public static function auto_calculate_rebates( $post_id ) {
		$system_id = (int) get_post_meta( $post_id, 'points_system_id', true );
		if ( $system_id <= 0 ) {
			return;
		}

		$conversions = self::get_conversions( $system_id );
		if ( empty( $conversions ) ) {
			return;
		}

		$value_per_point = array();
		foreach ( $conversions as $conv ) {
			if ( $conv->points_required > 0 ) {
				$value_per_point[ $conv->reward_type ] = $conv->reward_value / $conv->points_required;
			}
		}

		$cash_vpp  = $value_per_point['cash'] ?? 0;
		$miles_vpp = $value_per_point['asia_miles'] ?? 0;

		foreach ( self::get_transaction_types() as $txn ) {
			$points_text = get_post_meta( $post_id, "{$txn}_points", true );

			if ( $points_text === '' || $points_text === false ) {
				continue;
			}

			$earning_rate = self::extract_earning_rate( $points_text );
			if ( $earning_rate <= 0 ) {
				update_post_meta( $post_id, "{$txn}_cash_sortable", 0 );
				update_post_meta( $post_id, "{$txn}_cash_display", '不適用' );
				update_post_meta( $post_id, "{$txn}_miles_display", '不適用' );
				continue;
			}

			if ( $cash_vpp > 0 ) {
				$cash_pct = round( $earning_rate * $cash_vpp * 100, 2 );
				update_post_meta( $post_id, "{$txn}_cash_sortable", $cash_pct );
				update_post_meta( $post_id, "{$txn}_cash_display", $cash_pct . '% 現金回贈' );
			}

			if ( $miles_vpp > 0 ) {
				$miles_per_dollar = $earning_rate * $miles_vpp;
				if ( $miles_per_dollar > 0 ) {
					$hkd_per_mile = round( 1 / $miles_per_dollar, 1 );
					update_post_meta( $post_id, "{$txn}_miles_display", 'HK$' . $hkd_per_mile . '/里' );
					update_post_meta( $post_id, "{$txn}_miles_sortable", $hkd_per_mile );
				}
			}

			foreach ( $value_per_point as $rtype => $vpp ) {
				if ( in_array( $rtype, array( 'cash', 'asia_miles' ), true ) ) {
					continue;
				}
				$pts_per_dollar = $earning_rate * $vpp;
				if ( $pts_per_dollar > 0 ) {
					$hkd_per_point = round( 1 / $pts_per_dollar, 1 );
					update_post_meta( $post_id, "{$txn}_{$rtype}_display", 'HK$' . $hkd_per_point . '/分' );
				}
			}
		}
	}
}
