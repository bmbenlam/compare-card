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
	 * Helper: strip query string & fragment from a URL.
	 * ---------------------------------------------------------------- */

	/**
	 * Strip ?query and #fragment from a URL string.
	 *
	 * @param string $url Raw URL.
	 * @return string Clean URL (scheme + host + path only).
	 */
	public static function strip_url_params( $url ) {
		$pos = strpos( $url, '?' );
		if ( $pos !== false ) {
			$url = substr( $url, 0, $pos );
		}
		$pos = strpos( $url, '#' );
		if ( $pos !== false ) {
			$url = substr( $url, 0, $pos );
		}
		return rtrim( $url, '/' );
	}

	/* ------------------------------------------------------------------
	 * Query helpers used by the analytics page.
	 * ---------------------------------------------------------------- */

	/**
	 * All published cards with impression and click stats for the period.
	 *
	 * Returns every published card — even those with 0 clicks/impressions —
	 * sorted by impressions DESC, then affiliate clicks DESC.
	 *
	 * @param int $days Look-back window in days.
	 * @return array
	 */
	public static function all_cards_stats( $days = 30 ) {
		global $wpdb;

		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT p.ID AS card_id,
			        p.post_title,
			        COALESCE(imp.impressions, 0) AS impressions,
			        COALESCE(clk.affiliate_clicks, 0) AS affiliate_clicks,
			        COALESCE(clk.blog_clicks, 0) AS blog_clicks,
			        COALESCE(clk.preview_clicks, 0) AS preview_clicks,
			        COALESCE(clk.expanded_clicks, 0) AS expanded_clicks
			 FROM {$wpdb->posts} p
			 LEFT JOIN (
			     SELECT card_id, SUM(impression_count) AS impressions
			     FROM {$wpdb->prefix}card_impressions
			     WHERE view_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
			     GROUP BY card_id
			 ) imp ON imp.card_id = p.ID
			 LEFT JOIN (
			     SELECT card_id,
			            SUM(CASE WHEN click_type = 'affiliate' THEN 1 ELSE 0 END) AS affiliate_clicks,
			            SUM(CASE WHEN click_type = 'blog' THEN 1 ELSE 0 END) AS blog_clicks,
			            SUM(CASE WHEN click_context = 'preview' AND click_type = 'affiliate' THEN 1 ELSE 0 END) AS preview_clicks,
			            SUM(CASE WHEN click_context = 'expanded' AND click_type = 'affiliate' THEN 1 ELSE 0 END) AS expanded_clicks
			     FROM {$wpdb->prefix}card_clicks
			     WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
			     GROUP BY card_id
			 ) clk ON clk.card_id = p.ID
			 WHERE p.post_type = 'card' AND p.post_status = 'publish'
			 ORDER BY impressions DESC, affiliate_clicks DESC",
			$days,
			$days
		) );

		// Calculate CTR.
		foreach ( $rows as &$row ) {
			$row->ctr = $row->impressions > 0
				? round( $row->affiliate_clicks / $row->impressions * 100, 2 )
				: 0;
		}

		return $rows;
	}

	/**
	 * Top source pages, aggregated by base URL (query params & fragments stripped).
	 *
	 * @param int $days  Look-back window.
	 * @param int $limit Number of rows.
	 * @return array
	 */
	public static function top_sources( $days = 30, $limit = 10 ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT
			     SUBSTRING_INDEX(SUBSTRING_INDEX(source_url, '#', 1), '?', 1) AS source_page,
			     COUNT(*) AS total_clicks
			 FROM {$wpdb->prefix}card_clicks
			 WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
			 GROUP BY source_page
			 ORDER BY total_clicks DESC
			 LIMIT %d",
			$days,
			$limit
		) );
	}

	/**
	 * Recent clicks (source_url stripped of query/fragment in PHP for display).
	 *
	 * @param int $limit Number of rows.
	 * @return array
	 */
	public static function recent( $limit = 100 ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT c.clicked_at, c.source_url, c.click_type, c.click_context, p.post_title
			 FROM {$wpdb->prefix}card_clicks c
			 JOIN {$wpdb->posts} p ON c.card_id = p.ID
			 ORDER BY c.clicked_at DESC
			 LIMIT %d",
			$limit
		) );

		foreach ( $rows as &$row ) {
			$row->source_page = self::strip_url_params( $row->source_url );
		}

		return $rows;
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

	/**
	 * Cards with expiring or already-expired welcome offers.
	 *
	 * @return object[] Each with ->ID, ->post_title, ->expiry, ->days_left.
	 */
	public static function expiring_cards() {
		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT p.ID, p.post_title, pm.meta_value AS expiry
			 FROM {$wpdb->posts} p
			 JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			 WHERE p.post_type = 'card'
			   AND p.post_status = 'publish'
			   AND pm.meta_key = 'welcome_offer_expiry'
			   AND pm.meta_value != ''
			   AND pm.meta_value <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
			 ORDER BY pm.meta_value ASC"
		);

		foreach ( $rows as &$row ) {
			$ts = strtotime( $row->expiry );
			$row->days_left = $ts ? (int) round( ( $ts - time() ) / 86400 ) : null;
		}

		return $rows;
	}
}
