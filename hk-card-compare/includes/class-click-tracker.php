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
	 * Record a click (affiliate or blog).
	 */
	public static function handle() {
		check_ajax_referer( 'hkcc_public_nonce', 'nonce' );

		global $wpdb;

		$card_id       = isset( $_POST['card_id'] ) ? absint( $_POST['card_id'] ) : 0;
		$source_url    = isset( $_POST['source_url'] ) ? esc_url_raw( $_POST['source_url'] ) : '';
		$click_type    = isset( $_POST['click_type'] ) ? sanitize_text_field( $_POST['click_type'] ) : 'affiliate';
		$click_context = isset( $_POST['click_context'] ) ? sanitize_text_field( $_POST['click_context'] ) : '';

		// Whitelist click_type and click_context values.
		if ( ! in_array( $click_type, array( 'affiliate', 'blog' ), true ) ) {
			$click_type = 'affiliate';
		}
		if ( ! in_array( $click_context, array( 'preview', 'expanded', 'spotlight', 'image', '' ), true ) ) {
			$click_context = '';
		}

		if ( ! $card_id ) {
			wp_send_json_error( 'Missing card_id' );
		}

		$wpdb->insert(
			$wpdb->prefix . 'card_clicks',
			array(
				'card_id'       => $card_id,
				'source_url'    => $source_url,
				'click_type'    => $click_type,
				'click_context' => $click_context,
				'clicked_at'    => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s' )
		);

		wp_send_json_success();
	}

	/* ------------------------------------------------------------------
	 * Impression tracking (server-side, aggregated daily).
	 * ---------------------------------------------------------------- */

	/**
	 * Record an impression for a card on the current page.
	 *
	 * Uses INSERT ... ON DUPLICATE KEY UPDATE for efficient daily aggregation.
	 *
	 * @param int $card_id Card post ID.
	 */
	public static function record_impression( $card_id ) {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}
		// Skip bots/crawlers.
		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && preg_match( '/bot|crawl|spider|slurp|facebookexternalhit|Mediapartners/i', $_SERVER['HTTP_USER_AGENT'] ) ) {
			return;
		}

		global $wpdb;
		$source_url = home_url( $_SERVER['REQUEST_URI'] ?? '' );
		$view_date  = current_time( 'Y-m-d' );

		$wpdb->query( $wpdb->prepare(
			"INSERT INTO {$wpdb->prefix}card_impressions (card_id, source_url, view_date, impression_count)
			 VALUES (%d, %s, %s, 1)
			 ON DUPLICATE KEY UPDATE impression_count = impression_count + 1",
			absint( $card_id ),
			esc_url_raw( $source_url ),
			$view_date
		) );
	}

	/* ------------------------------------------------------------------
	 * Query helpers used by the analytics page.
	 * ---------------------------------------------------------------- */

	/**
	 * Top cards by click count, with impression and CTR data.
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
		$aff_where = $where . " AND c.click_type = 'affiliate'";
		if ( $source_url ) {
			$where     .= $wpdb->prepare( " AND c.source_url = %s", $source_url );
			$aff_where .= $wpdb->prepare( " AND c.source_url = %s", $source_url );
		}

		$total = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}card_clicks c {$aff_where}"
		);

		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT c.card_id, p.post_title,
			        SUM(CASE WHEN c.click_type = 'affiliate' THEN 1 ELSE 0 END) AS affiliate_clicks,
			        SUM(CASE WHEN c.click_type = 'blog' THEN 1 ELSE 0 END) AS blog_clicks,
			        SUM(CASE WHEN c.click_context = 'preview' AND c.click_type = 'affiliate' THEN 1 ELSE 0 END) AS preview_clicks,
			        SUM(CASE WHEN c.click_context = 'expanded' AND c.click_type = 'affiliate' THEN 1 ELSE 0 END) AS expanded_clicks,
			        COUNT(*) AS click_count
			 FROM {$wpdb->prefix}card_clicks c
			 JOIN {$wpdb->posts} p ON c.card_id = p.ID
			 {$where}
			 GROUP BY c.card_id
			 ORDER BY affiliate_clicks DESC
			 LIMIT %d",
			$limit
		) );

		// Attach impressions and CTR.
		$imp_where = $wpdb->prepare(
			"WHERE view_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)",
			$days
		);
		if ( $source_url ) {
			$imp_where .= $wpdb->prepare( " AND source_url = %s", $source_url );
		}
		$imp_rows = $wpdb->get_results(
			"SELECT card_id, SUM(impression_count) AS impressions
			 FROM {$wpdb->prefix}card_impressions
			 {$imp_where}
			 GROUP BY card_id"
		);
		$imp_map = array();
		foreach ( $imp_rows as $ir ) {
			$imp_map[ $ir->card_id ] = (int) $ir->impressions;
		}

		foreach ( $rows as &$row ) {
			$row->click_rate   = $total > 0 ? round( $row->affiliate_clicks / $total * 100, 1 ) : 0;
			$row->impressions  = $imp_map[ $row->card_id ] ?? 0;
			$row->ctr          = $row->impressions > 0 ? round( $row->affiliate_clicks / $row->impressions * 100, 2 ) : 0;
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
			"SELECT c.clicked_at, c.source_url, c.click_type, c.click_context, p.post_title
			 FROM {$wpdb->prefix}card_clicks c
			 JOIN {$wpdb->posts} p ON c.card_id = p.ID
			 ORDER BY c.clicked_at DESC
			 LIMIT %d",
			$limit
		) );
	}

	/**
	 * Total impressions for the given period.
	 *
	 * @param int $days Look-back window.
	 * @return int
	 */
	public static function total_impressions( $days = 30 ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COALESCE(SUM(impression_count), 0)
			 FROM {$wpdb->prefix}card_impressions
			 WHERE view_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)",
			$days
		) );
	}

	/**
	 * Total affiliate clicks for the given period.
	 *
	 * @param int $days Look-back window.
	 * @return int
	 */
	public static function total_affiliate_clicks( $days = 30 ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*)
			 FROM {$wpdb->prefix}card_clicks
			 WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
			   AND click_type = 'affiliate'",
			$days
		) );
	}
}
