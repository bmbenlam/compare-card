<?php
/**
 * Shortcode implementations: [cc_suggest] and [cc_comparison].
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

		add_action( 'wp_ajax_hkcc_filter_cards', array( __CLASS__, 'ajax_filter_cards' ) );
		add_action( 'wp_ajax_nopriv_hkcc_filter_cards', array( __CLASS__, 'ajax_filter_cards' ) );
	}

	/* ==================================================================
	 * [cc_suggest] — same card style as cc_comparison, no filters/sort.
	 * ================================================================ */

	public static function shortcode_suggest( $atts ) {
		$atts = shortcode_atts( array(
			'category' => '',
			'bank'     => '',
			'metric'   => '',
			'sort'     => '',
			'order'    => 'desc',
			'limit'    => 5,
			'exclude'  => '',
			'layout'   => 'grid',
		), $atts, 'cc_suggest' );

		$query_args = self::build_query_args( $atts );
		$cards      = get_posts( $query_args );

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
	 * [cc_comparison]
	 * ================================================================ */

	public static function shortcode_comparison( $atts ) {
		$atts = shortcode_atts( array(
			'category'      => '',
			'bank'          => '',
			'network'       => '',
			'filters'       => 'bank,network',
			'default_sort'  => '',
			'default_order' => 'desc',
			'show_toggle'   => 'true',
			'default_view'  => 'miles',
		), $atts, 'cc_comparison' );

		$query_args = self::build_query_args( $atts );
		$cards      = get_posts( $query_args );
		$filter_keys = array_map( 'trim', explode( ',', $atts['filters'] ) );

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

					<!-- Feature-based filter chips -->
					<div class="hkcc-filter-chips">
						<input type="checkbox" class="hkcc-filter-chip" id="hkcc_chip_free_annual" name="hkcc_feature_filter" value="free_annual_fee" />
						<label for="hkcc_chip_free_annual">永久免年費</label>

						<input type="checkbox" class="hkcc-filter-chip" id="hkcc_chip_lounge" name="hkcc_feature_filter" value="has_lounge" />
						<label for="hkcc_chip_lounge">可用貴賓室</label>

						<input type="checkbox" class="hkcc-filter-chip" id="hkcc_chip_insurance" name="hkcc_feature_filter" value="has_travel_insurance" />
						<label for="hkcc_chip_insurance">免費旅遊保險</label>
					</div>

					<!-- Taxonomy filters (bank + network) -->
					<?php self::render_filters( $filter_keys ); ?>

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
