<?php
/**
 * Shortcode implementations: [cc_suggest], [cc_comparison], and [cc_card].
 *
 * @package HK_Card_Compare
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKCC_Card_Shortcodes {

	/**
	 * Hook into WordPress.
	 */
	public static function init() {
		add_shortcode( 'cc_suggest', array( __CLASS__, 'shortcode_suggest' ) );
		add_shortcode( 'cc_comparison', array( __CLASS__, 'shortcode_comparison' ) );
		add_shortcode( 'cc_card', array( __CLASS__, 'shortcode_card' ) );

		add_action( 'wp_ajax_hkcc_filter_cards', array( __CLASS__, 'ajax_filter_cards' ) );
		add_action( 'wp_ajax_nopriv_hkcc_filter_cards', array( __CLASS__, 'ajax_filter_cards' ) );

		// Fix multiline shortcodes: WordPress shortcode regex can fail with
		// newlines inside the tag. Collapse to single line before parsing.
		add_filter( 'the_content', array( __CLASS__, 'fix_multiline_shortcodes' ), 5 );
	}

	/**
	 * Collapse multiline [cc_suggest], [cc_comparison], [cc_card] shortcodes
	 * into single-line so WordPress regex parses attributes correctly.
	 */
	public static function fix_multiline_shortcodes( $content ) {
		// Match our shortcodes that may span multiple lines.
		$content = preg_replace_callback(
			'/\[(cc_suggest|cc_comparison|cc_card)\b([^\]]*)\]/s',
			function ( $m ) {
				// Replace newlines/tabs with a single space, collapse multiple spaces.
				$attrs = preg_replace( '/\s+/', ' ', $m[2] );
				return '[' . $m[1] . $attrs . ']';
			},
			$content
		);
		return $content;
	}

	/* ==================================================================
	 * [cc_suggest] — same card style as cc_comparison, no filters/sort.
	 * ================================================================ */

	/**
	 * Metric → meta_key mapping for [cc_suggest metric="..."].
	 */
	private static function get_metric_map() {
		return array(
			'cashback_local'    => array( 'key' => 'local_retail_cash_sortable',    'order' => 'DESC' ),
			'cashback_overseas' => array( 'key' => 'overseas_retail_cash_sortable', 'order' => 'DESC' ),
			'asia_miles_local'  => array( 'key' => 'local_retail_miles_sortable',   'order' => 'ASC' ),
			'lounge_access'     => array( 'key' => 'lounge_access_sortable',        'order' => 'DESC' ),
			'annual_fee_low'    => array( 'key' => 'annual_fee_sortable',           'order' => 'ASC' ),
		);
	}

	public static function shortcode_suggest( $atts ) {
		$atts = shortcode_atts( array(
			'category' => '',
			'bank'     => '',
			'airline'  => '',
			'hotel'    => '',
			'metric'   => '',
			'sort'     => '',
			'order'    => '',
			'limit'    => 5,
			'exclude'  => '',
			'layout'   => 'grid',
		), $atts, 'cc_suggest' );

		// Resolve metric to sort key + order if no explicit sort provided.
		$metric_map = self::get_metric_map();
		if ( empty( $atts['sort'] ) && ! empty( $atts['metric'] ) && isset( $metric_map[ $atts['metric'] ] ) ) {
			$resolved     = $metric_map[ $atts['metric'] ];
			$atts['sort'] = $resolved['key'];
			if ( empty( $atts['order'] ) ) {
				$atts['order'] = strtolower( $resolved['order'] );
			}
		}

		// Default order to desc if still empty.
		if ( empty( $atts['order'] ) ) {
			$atts['order'] = 'desc';
		}

		$query_args = self::build_query_args( $atts );

		// Only show cards that have an application link.
		if ( ! isset( $query_args['meta_query'] ) ) {
			$query_args['meta_query'] = array();
		}
		$query_args['meta_query'][] = array(
			'key'     => 'affiliate_link',
			'value'   => '',
			'compare' => '!=',
		);

		// For metric-based queries, filter out cards with 0 or missing values.
		if ( ! empty( $atts['metric'] ) && isset( $metric_map[ $atts['metric'] ] ) ) {
			$sort_key = $metric_map[ $atts['metric'] ]['key'];
			if ( ! isset( $query_args['meta_query'] ) ) {
				$query_args['meta_query'] = array();
			}
			$query_args['meta_query'][] = array(
				'key'     => $sort_key,
				'value'   => 0,
				'compare' => '>',
				'type'    => 'NUMERIC',
			);
		}

		$cards = get_posts( $query_args );

		// Sort: recommendation when no sort/metric, tie-breaker when sorted.
		if ( empty( $atts['sort'] ) && empty( $atts['metric'] ) ) {
			$cards = self::recommendation_sort( $cards );
		} elseif ( ! empty( $atts['sort'] ) ) {
			$cards = self::tie_breaker_sort( $cards, $atts['sort'], $atts['order'] );
		}

		if ( empty( $cards ) ) {
			return '<p class="hkcc-no-results">暫無相關信用卡推薦。</p>';
		}

		ob_start();
		echo '<div class="hkcc-comparison hkcc-suggest">';
		echo '<h2 class="hkcc-suggest-title">相關信用卡推薦</h2>';
		echo '<div class="hkcc-card-list">';

		foreach ( $cards as $card ) {
			HKCC_Card_Display::render_listing_card( $card, 'miles' );
		}

		echo '</div></div>';
		return ob_get_clean();
	}

	/* ==================================================================
	 * [cc_card] — Spotlight single-card shortcode.
	 * Shows: tagline, card face, name, short welcome, 4-pack,
	 *        apply button, long welcome bubble, blog + apply, footnotes.
	 * No rewards, perks, issuer, etc.
	 * ================================================================ */

	public static function shortcode_card( $atts ) {
		$atts = shortcode_atts( array(
			'id'   => 0,
			'slug' => '',
			'view' => 'miles',
		), $atts, 'cc_card' );

		$card = null;
		if ( $atts['id'] ) {
			$card = get_post( absint( $atts['id'] ) );
		} elseif ( $atts['slug'] ) {
			$card = get_page_by_path( $atts['slug'], OBJECT, 'card' );
		}

		if ( ! $card || 'card' !== $card->post_type ) {
			return '<p class="hkcc-no-results">找不到指定的信用卡。</p>';
		}

		ob_start();
		echo '<div class="hkcc-comparison hkcc-spotlight">';
		HKCC_Card_Display::render_spotlight_card( $card, $atts['view'] );
		echo '</div>';
		return ob_get_clean();
	}

	/* ==================================================================
	 * [cc_comparison]
	 * ================================================================ */

	public static function shortcode_comparison( $atts ) {
		$atts = shortcode_atts( array(
			'category'      => '',
			'bank'          => '',
			'network'       => '',
			'airline'       => '',
			'hotel'         => '',
			'filters'       => 'bank,network',
			'default_sort'  => '',
			'default_order' => 'desc',
			'show_toggle'   => 'true',
			'default_view'  => 'miles',
		), $atts, 'cc_comparison' );

		$query_args = self::build_query_args( $atts );
		$cards      = get_posts( $query_args );
		$filter_keys = array_map( 'trim', explode( ',', $atts['filters'] ) );

		// Sort: recommendation when no sort, tie-breaker when sorted.
		$sort = $atts['default_sort'] ?? '';
		if ( empty( $sort ) ) {
			$cards = self::recommendation_sort( $cards );
		} else {
			$cards = self::tie_breaker_sort( $cards, $sort, $atts['default_order'] );
		}

		$is_miles = ( 'miles' === $atts['default_view'] );
		ob_start();
		?>
		<div class="hkcc-comparison"
			 data-sort="<?php echo esc_attr( $atts['default_sort'] ); ?>"
			 data-order="<?php echo esc_attr( $atts['default_order'] ); ?>"
			 data-view="<?php echo esc_attr( $atts['default_view'] ); ?>"
			 data-shortcode-atts="<?php echo esc_attr( wp_json_encode( $atts ) ); ?>">

			<!-- Unified toolbar: toggle + sort + feature chips + filters + clear -->
			<div class="hkcc-toolbar">
				<div class="hkcc-toolbar-header">
					<span class="hkcc-toolbar-toggle">
						<span class="hkcc-filter-icon">&#x1F50D;</span>
						篩選 &amp; 排序 <span class="hkcc-active-count"></span>
						<span class="hkcc-toggle-arrow">&#9660;</span>
					</span>
				</div>
				<div class="hkcc-toolbar-body" style="display:none;">

					<!-- Primary: View toggle + Sort (most important controls on top) -->
					<div class="hkcc-toolbar-primary">
						<?php if ( 'true' === $atts['show_toggle'] ) : ?>
						<div class="hkcc-view-toggle">
							<span class="hkcc-toggle-option hkcc-toggle-miles<?php echo $is_miles ? ' active' : ''; ?>">飛行里數</span>
							<label class="hkcc-toggle-switch">
								<input type="checkbox" class="hkcc-view-toggle-input" <?php echo $is_miles ? '' : 'checked'; ?> />
								<span class="hkcc-toggle-slider"></span>
							</label>
							<span class="hkcc-toggle-option hkcc-toggle-cash<?php echo $is_miles ? '' : ' active'; ?>">現金回贈</span>
						</div>
						<?php endif; ?>

						<div class="hkcc-sort-bar">
							<label for="hkcc_sort">排序</label>
							<select class="hkcc-sort-select" id="hkcc_sort">
								<option value="|">推薦排序</option>
								<option value="local_retail_cash_sortable|desc">本地簽賬回贈 (%)</option>
								<option value="local_retail_miles_sortable|asc">本地簽賬回贈 (里數)</option>
								<option value="overseas_retail_cash_sortable|desc">海外簽賬回贈 (%)</option>
								<option value="overseas_retail_miles_sortable|asc">海外簽賬回贈 (里數)</option>
								<option value="min_income_sortable|asc">最低年薪要求</option>
								<option value="annual_fee_sortable|asc">年費</option>
							</select>
						</div>
					</div>

					<!-- Card feature chips -->
					<div class="hkcc-filter-chip-section">
						<h4 class="hkcc-filter-heading">卡片特色</h4>
						<div class="hkcc-filter-chips">
							<input type="checkbox" class="hkcc-filter-chip" id="hkcc_chip_free_annual" name="hkcc_feature_filter" value="free_annual_fee" />
							<label for="hkcc_chip_free_annual">永久免年費</label>

							<input type="checkbox" class="hkcc-filter-chip" id="hkcc_chip_lounge" name="hkcc_feature_filter" value="has_lounge" />
							<label for="hkcc_chip_lounge">可用貴賓室</label>

							<input type="checkbox" class="hkcc-filter-chip" id="hkcc_chip_insurance" name="hkcc_feature_filter" value="has_travel_insurance" />
							<label for="hkcc_chip_insurance">免費旅遊保險</label>

							<input type="checkbox" class="hkcc-filter-chip" id="hkcc_chip_cashback" name="hkcc_feature_filter" value="cashback_only" />
							<label for="hkcc_chip_cashback">純現金回贈卡</label>

							<input type="checkbox" class="hkcc-filter-chip" id="hkcc_chip_dining" name="hkcc_feature_filter" value="good_dining" />
							<label for="hkcc_chip_dining">食飯卡</label>

							<input type="checkbox" class="hkcc-filter-chip" id="hkcc_chip_supermarket" name="hkcc_feature_filter" value="good_supermarket" />
							<label for="hkcc_chip_supermarket">超市買餸卡</label>
						</div>
					</div>

					<!-- Airline & Hotel program chips -->
					<div class="hkcc-filter-chip-section">
						<h4 class="hkcc-filter-heading">里程 / 酒店計劃</h4>
						<div class="hkcc-filter-chips">
							<input type="checkbox" class="hkcc-filter-chip" id="hkcc_chip_asia_miles" name="hkcc_feature_filter" value="has_asia_miles" />
							<label for="hkcc_chip_asia_miles">Asia Miles</label>

							<input type="checkbox" class="hkcc-filter-chip" id="hkcc_chip_avios" name="hkcc_feature_filter" value="has_avios" />
							<label for="hkcc_chip_avios">Avios 家族</label>

							<input type="checkbox" class="hkcc-filter-chip" id="hkcc_chip_virgin" name="hkcc_feature_filter" value="has_virgin" />
							<label for="hkcc_chip_virgin">Virgin Atlantic</label>

							<input type="checkbox" class="hkcc-filter-chip" id="hkcc_chip_krisflyer" name="hkcc_feature_filter" value="has_krisflyer" />
							<label for="hkcc_chip_krisflyer">KrisFlyer</label>

							<input type="checkbox" class="hkcc-filter-chip" id="hkcc_chip_marriott" name="hkcc_feature_filter" value="has_marriott" />
							<label for="hkcc_chip_marriott">Marriott Bonvoy</label>

							<input type="checkbox" class="hkcc-filter-chip" id="hkcc_chip_hilton" name="hkcc_feature_filter" value="has_hilton" />
							<label for="hkcc_chip_hilton">Hilton Honors</label>
						</div>
					</div>

					<!-- Taxonomy filters (bank + network) — collapsible -->
					<div class="hkcc-filter-groups-row">
						<button type="button" class="hkcc-filter-groups-toggle">
							發卡 / 結算機構 <span class="hkcc-filter-groups-arrow">&#9660;</span>
						</button>
						<div class="hkcc-filter-groups-content" style="display:none;">
							<?php self::render_filters( $filter_keys ); ?>
						</div>
					</div>

					<button type="button" class="hkcc-clear-filters">清除所有篩選</button>
				</div>
			</div>

			<div class="hkcc-card-count">
				共 <span class="hkcc-count"><?php echo count( $cards ); ?></span> 張信用卡
			</div>

			<div class="hkcc-card-list">
				<?php
				if ( empty( $cards ) ) {
					echo '<p class="hkcc-no-results">沒有符合條件的信用卡。</p>';
				} else {
					foreach ( $cards as $card ) {
						HKCC_Card_Display::render_listing_card( $card, $atts['default_view'] );
					}
				}
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ==================================================================
	 * AJAX handler for real-time filtering.
	 * ================================================================ */

	public static function ajax_filter_cards() {
		check_ajax_referer( 'hkcc_public_nonce', 'nonce' );

		$atts = isset( $_POST['shortcode_atts'] ) ? json_decode( stripslashes( $_POST['shortcode_atts'] ), true ) : array();
		if ( ! is_array( $atts ) ) {
			$atts = array();
		}

		// Fix: properly decode JSON-encoded filters.
		$filters = array();
		if ( isset( $_POST['filters'] ) ) {
			$filters = json_decode( stripslashes( $_POST['filters'] ), true );
			if ( ! is_array( $filters ) ) {
				$filters = array();
			}
		}

		if ( ! empty( $filters['bank'] ) ) {
			$atts['bank'] = implode( ',', array_map( 'sanitize_text_field', (array) $filters['bank'] ) );
		}
		if ( ! empty( $filters['network'] ) ) {
			$atts['network'] = implode( ',', array_map( 'sanitize_text_field', (array) $filters['network'] ) );
		}

		$query_args = self::build_query_args( $atts );

		// Feature-based filters.
		if ( ! empty( $filters['features'] ) ) {
			if ( ! isset( $query_args['meta_query'] ) ) {
				$query_args['meta_query'] = array();
			}

			$features = array_map( 'sanitize_text_field', (array) $filters['features'] );

			foreach ( $features as $feature ) {
				switch ( $feature ) {
					case 'free_annual_fee':
						$query_args['meta_query'][] = array(
							'key'     => 'annual_fee_sortable',
							'value'   => 0,
							'compare' => '=',
							'type'    => 'NUMERIC',
						);
						break;

					case 'has_lounge':
						$query_args['meta_query'][] = array(
							'key'     => 'lounge_access_sortable',
							'value'   => 1,
							'compare' => '>=',
							'type'    => 'NUMERIC',
						);
						break;

					case 'has_travel_insurance':
						$query_args['meta_query'][] = array(
							'key'     => 'has_travel_insurance',
							'value'   => 1,
							'compare' => '=',
							'type'    => 'NUMERIC',
						);
						break;

					case 'cashback_only':
						$query_args['meta_query'][] = array(
							'relation' => 'OR',
							array( 'key' => 'points_system_id', 'value' => '0', 'compare' => '=' ),
							array( 'key' => 'points_system_id', 'compare' => 'NOT EXISTS' ),
						);
						break;

					case 'good_dining':
						$query_args['meta_query'][] = array(
							'key'     => 'local_dining_cash_sortable',
							'value'   => 0,
							'compare' => '>',
							'type'    => 'NUMERIC',
						);
						break;

					case 'good_supermarket':
						$query_args['meta_query'][] = array(
							'key'     => 'designated_supermarket_cash_sortable',
							'value'   => 0,
							'compare' => '>',
							'type'    => 'NUMERIC',
						);
						break;

					case 'has_asia_miles':
						$query_args['meta_query'][] = array(
							'key'     => 'transferable_airlines',
							'value'   => 'Asia Miles',
							'compare' => 'LIKE',
						);
						break;

					case 'has_avios':
						// Avios family: BA Avios, Qatar, or Finnair (OR logic).
						$query_args['meta_query'][] = array(
							'relation' => 'OR',
							array( 'key' => 'transferable_airlines', 'value' => 'Avios', 'compare' => 'LIKE' ),
							array( 'key' => 'transferable_airlines', 'value' => 'Qatar', 'compare' => 'LIKE' ),
							array( 'key' => 'transferable_airlines', 'value' => 'Finnair', 'compare' => 'LIKE' ),
						);
						break;

					case 'has_virgin':
						$query_args['meta_query'][] = array(
							'key'     => 'transferable_airlines',
							'value'   => 'Virgin',
							'compare' => 'LIKE',
						);
						break;

					case 'has_krisflyer':
						$query_args['meta_query'][] = array(
							'key'     => 'transferable_airlines',
							'value'   => 'KrisFlyer',
							'compare' => 'LIKE',
						);
						break;

					case 'has_marriott':
						$query_args['meta_query'][] = array(
							'key'     => 'transferable_hotels',
							'value'   => 'Marriott',
							'compare' => 'LIKE',
						);
						break;

					case 'has_hilton':
						$query_args['meta_query'][] = array(
							'key'     => 'transferable_hotels',
							'value'   => 'Hilton',
							'compare' => 'LIKE',
						);
						break;
				}
			}
		}

		$sort  = sanitize_text_field( $_POST['sort'] ?? '' );
		$order = sanitize_text_field( $_POST['order'] ?? 'desc' );

		if ( $sort ) {
			// Use EXISTS/NOT EXISTS so cards without the meta key still appear (sorted last).
			if ( ! isset( $query_args['meta_query'] ) ) {
				$query_args['meta_query'] = array();
			}
			$query_args['meta_query']['hkcc_sort'] = array(
				'key'     => $sort,
				'compare' => 'EXISTS',
				'type'    => 'NUMERIC',
			);
			$query_args['meta_query'][] = array(
				'relation' => 'OR',
				array( 'key' => $sort, 'compare' => 'EXISTS' ),
				array( 'key' => $sort, 'compare' => 'NOT EXISTS' ),
			);
			$query_args['orderby'] = array( 'hkcc_sort' => strtoupper( $order ) );
		}

		$view  = sanitize_text_field( $_POST['view'] ?? 'miles' );
		$cards = get_posts( $query_args );

		// Sort: recommendation when no sort, tie-breaker when sorted.
		if ( empty( $sort ) ) {
			$cards = self::recommendation_sort( $cards );
		} else {
			$cards = self::tie_breaker_sort( $cards, $sort, $order );
		}

		ob_start();
		if ( empty( $cards ) ) {
			echo '<p class="hkcc-no-results">沒有符合條件的信用卡。</p>';
		} else {
			foreach ( $cards as $card ) {
				HKCC_Card_Display::render_listing_card( $card, $view );
			}
		}

		wp_send_json_success( array(
			'html'  => ob_get_clean(),
			'count' => count( $cards ),
		) );
	}

	/* ==================================================================
	 * Recommendation sort.
	 * Batch 1: cards WITH affiliate_link → sort by overseas miles ASC,
	 *          then pure-cash cards by overseas cash DESC.
	 * Batch 2: cards WITHOUT affiliate_link → same sub-sort.
	 * ================================================================ */

	private static function recommendation_sort( $cards ) {
		if ( empty( $cards ) ) {
			return $cards;
		}

		// Pre-fetch meta to avoid repeated queries.
		$meta = array();
		foreach ( $cards as $card ) {
			$id = $card->ID;
			$meta[ $id ] = array(
				'has_link'  => ! empty( get_post_meta( $id, 'affiliate_link', true ) ),
				'miles'     => (float) get_post_meta( $id, 'overseas_retail_miles_sortable', true ),
				'cash'      => (float) get_post_meta( $id, 'overseas_retail_cash_sortable', true ),
			);
		}

		usort( $cards, function ( $a, $b ) use ( $meta ) {
			$am = $meta[ $a->ID ];
			$bm = $meta[ $b->ID ];

			// 1. Cards with affiliate link first.
			if ( $am['has_link'] !== $bm['has_link'] ) {
				return $am['has_link'] ? -1 : 1;
			}

			// 2. Cards with miles data before pure-cash cards.
			$a_has_miles = ( $am['miles'] > 0 );
			$b_has_miles = ( $bm['miles'] > 0 );

			if ( $a_has_miles && ! $b_has_miles ) return -1;
			if ( ! $a_has_miles && $b_has_miles ) return 1;

			// 3a. Both have miles → ascending (lower HK$/mile is better).
			if ( $a_has_miles && $b_has_miles ) {
				if ( $am['miles'] !== $bm['miles'] ) {
					return $am['miles'] < $bm['miles'] ? -1 : 1;
				}
				return 0;
			}

			// 3b. Both pure-cash → descending (higher % is better).
			if ( $am['cash'] !== $bm['cash'] ) {
				return $am['cash'] > $bm['cash'] ? -1 : 1;
			}

			return 0;
		} );

		return $cards;
	}

	/* ==================================================================
	 * Tie-breaker sort.
	 * Primary sort by the selected field; for equal values, fall back
	 * to recommendation logic (affiliate first → miles ASC → cash DESC).
	 * ================================================================ */

	private static function tie_breaker_sort( $cards, $sort_field, $sort_order ) {
		if ( empty( $cards ) || empty( $sort_field ) ) {
			return $cards;
		}

		$meta = array();
		foreach ( $cards as $card ) {
			$id = $card->ID;
			$meta[ $id ] = array(
				'sort_val' => (float) get_post_meta( $id, $sort_field, true ),
				'has_link' => ! empty( get_post_meta( $id, 'affiliate_link', true ) ),
				'miles'    => (float) get_post_meta( $id, 'overseas_retail_miles_sortable', true ),
				'cash'     => (float) get_post_meta( $id, 'overseas_retail_cash_sortable', true ),
			);
		}

		$asc = ( 'asc' === strtolower( $sort_order ) );

		usort( $cards, function ( $a, $b ) use ( $meta, $asc ) {
			$am = $meta[ $a->ID ];
			$bm = $meta[ $b->ID ];

			// Primary: sort by the selected field.
			if ( $am['sort_val'] !== $bm['sort_val'] ) {
				$diff = $am['sort_val'] - $bm['sort_val'];
				return $asc ? ( $diff < 0 ? -1 : 1 ) : ( $diff > 0 ? -1 : 1 );
			}

			// Tie-breaker: affiliate link first.
			if ( $am['has_link'] !== $bm['has_link'] ) {
				return $am['has_link'] ? -1 : 1;
			}

			// Tie-breaker: miles cards before pure-cash.
			$a_has_miles = ( $am['miles'] > 0 );
			$b_has_miles = ( $bm['miles'] > 0 );
			if ( $a_has_miles && ! $b_has_miles ) return -1;
			if ( ! $a_has_miles && $b_has_miles ) return 1;

			if ( $a_has_miles && $b_has_miles ) {
				if ( $am['miles'] !== $bm['miles'] ) {
					return $am['miles'] < $bm['miles'] ? -1 : 1;
				}
				return 0;
			}

			if ( $am['cash'] !== $bm['cash'] ) {
				return $am['cash'] > $bm['cash'] ? -1 : 1;
			}

			return 0;
		} );

		return $cards;
	}

	/* ==================================================================
	 * Helpers.
	 * ================================================================ */

	private static function build_query_args( $atts ) {
		$args = array(
			'post_type'      => 'card',
			'post_status'    => 'publish',
			'posts_per_page' => intval( $atts['limit'] ?? -1 ),
		);

		if ( -1 === $args['posts_per_page'] && empty( $atts['limit'] ) ) {
			$args['posts_per_page'] = -1;
		}

		$tax_query = array();

		if ( ! empty( $atts['category'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'card_network',
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $atts['category'] ) ),
			);
		}
		if ( ! empty( $atts['bank'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'card_bank',
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $atts['bank'] ) ),
			);
		}
		if ( ! empty( $atts['network'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'card_network',
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $atts['network'] ) ),
			);
		}

		if ( $tax_query ) {
			$tax_query['relation'] = 'AND';
			$args['tax_query']     = $tax_query;
		}

		if ( ! empty( $atts['exclude'] ) ) {
			$args['post__not_in'] = array_map( 'intval', explode( ',', $atts['exclude'] ) );
		}

		// Airline filter: match cards whose transferable_airlines serialized array contains the name.
		// Accepts comma-separated partial names (e.g. airline="Avios,Qatar").
		if ( ! empty( $atts['airline'] ) ) {
			if ( ! isset( $args['meta_query'] ) ) {
				$args['meta_query'] = array();
			}
			$airline_terms = array_map( 'trim', explode( ',', $atts['airline'] ) );
			if ( count( $airline_terms ) === 1 ) {
				$args['meta_query'][] = array(
					'key'     => 'transferable_airlines',
					'value'   => $airline_terms[0],
					'compare' => 'LIKE',
				);
			} else {
				$airline_group = array( 'relation' => 'AND' );
				foreach ( $airline_terms as $at ) {
					$airline_group[] = array(
						'key'     => 'transferable_airlines',
						'value'   => $at,
						'compare' => 'LIKE',
					);
				}
				$args['meta_query'][] = $airline_group;
			}
		}

		// Hotel filter: same as airline but for transferable_hotels.
		if ( ! empty( $atts['hotel'] ) ) {
			if ( ! isset( $args['meta_query'] ) ) {
				$args['meta_query'] = array();
			}
			$hotel_terms = array_map( 'trim', explode( ',', $atts['hotel'] ) );
			if ( count( $hotel_terms ) === 1 ) {
				$args['meta_query'][] = array(
					'key'     => 'transferable_hotels',
					'value'   => $hotel_terms[0],
					'compare' => 'LIKE',
				);
			} else {
				$hotel_group = array( 'relation' => 'AND' );
				foreach ( $hotel_terms as $ht ) {
					$hotel_group[] = array(
						'key'     => 'transferable_hotels',
						'value'   => $ht,
						'compare' => 'LIKE',
					);
				}
				$args['meta_query'][] = $hotel_group;
			}
		}

		$sort = $atts['sort'] ?? $atts['default_sort'] ?? '';
		if ( $sort ) {
			$args['meta_query']['hkcc_sort'] = array(
				'key'     => $sort,
				'compare' => 'EXISTS',
				'type'    => 'NUMERIC',
			);
			$args['meta_query'][] = array(
				'relation' => 'OR',
				array( 'key' => $sort, 'compare' => 'EXISTS' ),
				array( 'key' => $sort, 'compare' => 'NOT EXISTS' ),
			);
			$order = strtoupper( $atts['order'] ?? $atts['default_order'] ?? 'DESC' );
			$args['orderby'] = array( 'hkcc_sort' => $order );
		}

		return $args;
	}

	private static function render_filters( $filter_keys ) {
		foreach ( $filter_keys as $key ) {
			switch ( $key ) {
				case 'bank':
					$terms = get_terms( array( 'taxonomy' => 'card_bank', 'hide_empty' => true ) );
					if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
						echo '<div class="hkcc-filter-group" data-filter="bank">';
						echo '<h4 class="hkcc-filter-heading">發卡機構</h4>';
						echo '<div class="hkcc-filter-chips">';
						foreach ( $terms as $term ) {
							$id = 'hkcc_bank_' . esc_attr( $term->slug );
							printf(
								'<input type="checkbox" class="hkcc-filter-chip" id="%s" name="hkcc_filter_bank" value="%s" /><label for="%s">%s</label>',
								$id,
								esc_attr( $term->slug ),
								$id,
								esc_html( $term->name )
							);
						}
						echo '</div></div>';
					}
					break;

				case 'network':
					$terms = get_terms( array( 'taxonomy' => 'card_network', 'hide_empty' => true ) );
					if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
						echo '<div class="hkcc-filter-group" data-filter="network">';
						echo '<h4 class="hkcc-filter-heading">結算機構</h4>';
						echo '<div class="hkcc-filter-chips">';
						foreach ( $terms as $term ) {
							$id = 'hkcc_network_' . esc_attr( $term->slug );
							printf(
								'<input type="checkbox" class="hkcc-filter-chip" id="%s" name="hkcc_filter_network" value="%s" /><label for="%s">%s</label>',
								$id,
								esc_attr( $term->slug ),
								$id,
								esc_html( $term->name )
							);
						}
						echo '</div></div>';
					}
					break;
			}
		}
	}
}
