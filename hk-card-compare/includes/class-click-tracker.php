<?php
/**
 * Affiliate click tracking via AJAX.
 *
 * @package HK_Card_Compare
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKCC_Click_Tracker {

	/**
	 * Hook into WordPress.
	 */
	public static function init() {
		add_action( 'wp_ajax_hkcc_track_click', array( __CLASS__, 'handle' ) );
		add_action( 'wp_ajax_nopriv_hkcc_track_click', array( __CLASS__, 'handle' ) );
	}

	/**
	 * Record a click.
	 */
	public static function handle() {
		check_ajax_referer( 'hkcc_public_nonce', 'nonce' );

		global $wpdb;

		$card_id    = isset( $_POST['card_id'] ) ? absint( $_POST['card_id'] ) : 0;
		$source_url = isset( $_POST['source_url'] ) ? esc_url_raw( $_POST['source_url'] ) : '';

		if ( ! $card_id ) {
			wp_send_json_error( 'Missing card_id' );
		}

		$wpdb->insert(
			$wpdb->prefix . 'card_clicks',
			array(
				'card_id'    => $card_id,
				'source_url' => $source_url,
				'clicked_at' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s' )
		);

		wp_send_json_success();
	}

	/* ------------------------------------------------------------------
	 * Query helpers used by the analytics page.
	 * ---------------------------------------------------------------- */

	/**
	 * Top cards by click count.
	 *
	 * @param int    $days  Look-back window in days.
	 * @param string $source_url Optional filter by source URL.
	 * @param int    $limit Number of rows.
	 * @return array
	 */
	public static function top_cards( $days = 30, $source_url = '', $limit = 10 ) {
		global $wpdb;

		$where = $wpdb->prepare(
			"WHERE c.clicked_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
			$days
		);
		if ( $source_url ) {
			$where .= $wpdb->prepare( " AND c.source_url = %s", $source_url );
		}

		$total = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}card_clicks c {$where}"
		);

		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT c.card_id, p.post_title, COUNT(*) AS click_count
			 FROM {$wpdb->prefix}card_clicks c
			 JOIN {$wpdb->posts} p ON c.card_id = p.ID
			 {$where}
			 GROUP BY c.card_id
			 ORDER BY click_count DESC
			 LIMIT %d",
			$limit
		) );

		// Attach click rate.
		foreach ( $rows as &$row ) {
			$row->click_rate = $total > 0 ? round( $row->click_count / $total * 100, 1 ) : 0;
		}

		return $rows;
	}

	/**
	 * Top source pages.
	 *
	 * @param int $days  Look-back window.
	 * @param int $limit Number of rows.
	 * @return array
	 */
	public static function top_sources( $days = 30, $limit = 10 ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT source_url, COUNT(*) AS total_clicks
			 FROM {$wpdb->prefix}card_clicks
			 WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
			 GROUP BY source_url
			 ORDER BY total_clicks DESC
			 LIMIT %d",
			$days,
			$limit
		) );
	}

	/**
	 * Recent clicks.
	 *
	 * @param int $limit Number of rows.
	 * @return array
	 */
	public static function recent( $limit = 100 ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT c.clicked_at, c.source_url, p.post_title
			 FROM {$wpdb->prefix}card_clicks c
			 JOIN {$wpdb->posts} p ON c.card_id = p.ID
			 ORDER BY c.clicked_at DESC
			 LIMIT %d",
			$limit
		) );
	}
}
