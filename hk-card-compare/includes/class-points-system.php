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

	/**
	 * Hook into WordPress.
	 */
	public static function init() {
		// No front-end hooks needed; admin pages call methods directly.
	}

	/* ------------------------------------------------------------------
	 * CRUD helpers for points systems table.
	 * ---------------------------------------------------------------- */

	/**
	 * Get all active points systems.
	 *
	 * @return array
	 */
	public static function get_all() {
		global $wpdb;
		return $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}card_points_systems ORDER BY system_name ASC"
		);
	}

	/**
	 * Get a single points system by ID.
	 *
	 * @param int $id System ID.
	 * @return object|null
	 */
	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}card_points_systems WHERE id = %d",
				$id
			)
		);
	}

	/**
	 * Insert a new points system.
	 *
	 * @param string $name    Chinese name.
	 * @param string $name_en English name.
	 * @return int|false Inserted ID or false.
	 */
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

	/**
	 * Update an existing points system.
	 *
	 * @param int    $id      System ID.
	 * @param string $name    Chinese name.
	 * @param string $name_en English name.
	 * @param string $status  active or inactive.
	 * @return bool
	 */
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

	/**
	 * Delete a points system and its conversions.
	 *
	 * @param int $id System ID.
	 * @return bool
	 */
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

	/**
	 * Get all conversions for a given system.
	 *
	 * @param int $system_id System ID.
	 * @return array
	 */
	public static function get_conversions( $system_id ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}card_points_conversion WHERE system_id = %d ORDER BY reward_type ASC",
				$system_id
			)
		);
	}

	/**
	 * Replace all conversion rows for a system (delete + insert).
	 *
	 * @param int   $system_id   System ID.
	 * @param array $conversions Array of arrays with keys: reward_type, points_required, reward_value, reward_currency, effective_date, expiry_date.
	 */
	public static function save_conversions( $system_id, $conversions ) {
		global $wpdb;
		$system_id = intval( $system_id );

		// Remove old rows.
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
	 * Auto-calculation: points → cash / miles.
	 * ---------------------------------------------------------------- */

	/**
	 * Transaction type slugs used in meta keys.
	 *
	 * @return array
	 */
	public static function get_transaction_types() {
		return array(
			'local_retail',
			'overseas_retail',
			'online_hkd',
			'online_fx',
			'local_dining',
			'online_bill_payment',
			'payme_reload',
			'alipay_reload',
			'wechat_reload',
			'octopus_reload',
		);
	}

	/**
	 * Extract the numeric earning rate from a points string like "HK$1 = 3 MR 積分".
	 *
	 * @param string $text Points earning description.
	 * @return float Earning rate (points per HK$1). Returns 0 if not parseable.
	 */
	public static function extract_earning_rate( $text ) {
		// Try pattern "HK$1 = X" or just a bare number.
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
	 * For each transaction type that has a points string, compute the equivalent
	 * cash rebate percentage and miles-per-dollar, then store as meta.
	 *
	 * @param int $post_id Card post ID.
	 */
	public static function auto_calculate_rebates( $post_id ) {
		$system_id = (int) get_post_meta( $post_id, 'points_system_id', true );
		if ( $system_id <= 0 ) {
			return; // Direct cash card — nothing to calculate.
		}

		$conversions = self::get_conversions( $system_id );
		if ( empty( $conversions ) ) {
			return;
		}

		// Build lookup: reward_type => value per point.
		$value_per_point = array();
		foreach ( $conversions as $conv ) {
			if ( $conv->points_required > 0 ) {
				$value_per_point[ $conv->reward_type ] = $conv->reward_value / $conv->points_required;
			}
		}

		$cash_vpp  = $value_per_point['cash'] ?? 0;   // HKD per point.
		$miles_vpp = $value_per_point['asia_miles'] ?? 0; // Miles per point.

		foreach ( self::get_transaction_types() as $txn ) {
			$points_text = get_post_meta( $post_id, "{$txn}_points", true );
			if ( empty( $points_text ) ) {
				continue;
			}

			$earning_rate = self::extract_earning_rate( $points_text );
			if ( $earning_rate <= 0 ) {
				continue;
			}

			// Cash rebate percentage.
			if ( $cash_vpp > 0 ) {
				$cash_pct = round( $earning_rate * $cash_vpp * 100, 2 );
				update_post_meta( $post_id, "{$txn}_cash_sortable", $cash_pct );
				update_post_meta( $post_id, "{$txn}_cash_display", $cash_pct . '% 現金回贈' );
			}

			// Miles per dollar display.
			if ( $miles_vpp > 0 ) {
				$miles_per_dollar = $earning_rate * $miles_vpp;
				if ( $miles_per_dollar > 0 ) {
					$hkd_per_mile = round( 1 / $miles_per_dollar, 1 );
					update_post_meta( $post_id, "{$txn}_miles_display", 'HK$' . $hkd_per_mile . '/里' );
				}
			}

			// Additional reward types (Marriott, Hilton, etc.)
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
