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
	 * [cc_suggest]
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
		echo '<div class="hkcc-suggest hkcc-layout-' . esc_attr( $atts['layout'] ) . '">';
		echo '<h3 class="hkcc-suggest-title">相關信用卡推薦</h3>';
		echo '<div class="hkcc-suggest-grid">';

		foreach ( $cards as $card ) {
			HKCC_Card_Display::render_suggest_card( $card );
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
			'filters'       => 'bank,network,annual_fee',
			'default_sort'  => 'local_retail_cash_sortable',
			'default_order' => 'desc',
			'show_toggle'   => 'true',
			'default_view'  => 'miles',
		), $atts, 'cc_comparison' );

		$query_args = self::build_query_args( $atts );
		$cards      = get_posts( $query_args );
		$filter_keys = array_map( 'trim', explode( ',', $atts['filters'] ) );

		ob_start();
		?>
		<div class="hkcc-comparison"
			 data-sort="<?php echo esc_attr( $atts['default_sort'] ); ?>"
			 data-order="<?php echo esc_attr( $atts['default_order'] ); ?>"
			 data-view="<?php echo esc_attr( $atts['default_view'] ); ?>"
			 data-shortcode-atts="<?php echo esc_attr( wp_json_encode( $atts ) ); ?>">

			<!-- Filters -->
			<div class="hkcc-filters">
				<div class="hkcc-filters-header">
					<span class="hkcc-filters-toggle">
						<span class="hkcc-filter-icon">&#x1F50D;</span>
						篩選條件 <span class="hkcc-active-count"></span>
						<span class="hkcc-toggle-arrow">&#9660;</span>
					</span>
				</div>
				<div class="hkcc-filters-body" style="display:none;">
					<?php self::render_filters( $filter_keys ); ?>
					<button type="button" class="hkcc-clear-filters">清除所有篩選</button>
				</div>
			</div>

			<?php if ( 'true' === $atts['show_toggle'] ) : ?>
			<div class="hkcc-rebate-toggle">
				<span>顯示回贈方式：</span>
				<label><input type="radio" name="hkcc_view_mode" value="miles" <?php checked( $atts['default_view'], 'miles' ); ?> /> 飛行里數</label>
				<label><input type="radio" name="hkcc_view_mode" value="cash" <?php checked( $atts['default_view'], 'cash' ); ?> /> 現金回贈</label>
			</div>
			<?php endif; ?>

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

		$filters = isset( $_POST['filters'] ) ? (array) $_POST['filters'] : array();

		if ( ! empty( $filters['bank'] ) ) {
			$atts['bank'] = implode( ',', array_map( 'sanitize_text_field', (array) $filters['bank'] ) );
		}
		if ( ! empty( $filters['network'] ) ) {
			$atts['network'] = implode( ',', array_map( 'sanitize_text_field', (array) $filters['network'] ) );
		}

		$annual_fee_filter = sanitize_text_field( $filters['annual_fee'] ?? '' );
		$query_args = self::build_query_args( $atts );

		if ( $annual_fee_filter ) {
			if ( ! isset( $query_args['meta_query'] ) ) {
				$query_args['meta_query'] = array();
			}
			if ( 'free' === $annual_fee_filter ) {
				$query_args['meta_query'][] = array(
					'key'     => 'annual_fee_sortable',
					'value'   => 0,
					'compare' => '=',
					'type'    => 'NUMERIC',
				);
			} elseif ( 'first_year_free' === $annual_fee_filter ) {
				$query_args['meta_query'][] = array(
					'key'     => 'annual_fee_waiver',
					'value'   => '',
					'compare' => '!=',
				);
			}
		}

		$sort  = sanitize_text_field( $_POST['sort'] ?? 'local_retail_cash_sortable' );
		$order = sanitize_text_field( $_POST['order'] ?? 'desc' );
		$query_args['meta_key'] = $sort;
		$query_args['orderby']  = 'meta_value_num';
		$query_args['order']    = strtoupper( $order );

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
			$args['meta_key'] = $sort;
			$args['orderby']  = 'meta_value_num';
			$args['order']    = strtoupper( $atts['order'] ?? $atts['default_order'] ?? 'DESC' );
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
						echo '<div class="hkcc-filter-options">';
						foreach ( $terms as $term ) {
							printf(
								'<label><input type="checkbox" name="hkcc_filter_bank" value="%s" /> %s</label>',
								esc_attr( $term->slug ),
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
						echo '<div class="hkcc-filter-options">';
						foreach ( $terms as $term ) {
							printf(
								'<label><input type="checkbox" name="hkcc_filter_network" value="%s" /> %s</label>',
								esc_attr( $term->slug ),
								esc_html( $term->name )
							);
						}
						echo '</div></div>';
					}
					break;

				case 'annual_fee':
					echo '<div class="hkcc-filter-group" data-filter="annual_fee">';
					echo '<h4 class="hkcc-filter-heading">年費</h4>';
					echo '<div class="hkcc-filter-options">';
					echo '<label><input type="radio" name="hkcc_filter_annual_fee" value="" checked /> 任何</label>';
					echo '<label><input type="radio" name="hkcc_filter_annual_fee" value="free" /> 永久免年費</label>';
					echo '<label><input type="radio" name="hkcc_filter_annual_fee" value="first_year_free" /> 首年免年費</label>';
					echo '</div></div>';
					break;

				case 'min_income':
					echo '<div class="hkcc-filter-group" data-filter="min_income">';
					echo '<h4 class="hkcc-filter-heading">最低收入</h4>';
					echo '<div class="hkcc-filter-options">';
					echo '<label><input type="radio" name="hkcc_filter_min_income" value="" checked /> 任何</label>';
					echo '<label><input type="radio" name="hkcc_filter_min_income" value="50000" /> &lt; HK$50,000</label>';
					echo '<label><input type="radio" name="hkcc_filter_min_income" value="100000" /> HK$50,000 - 100,000</label>';
					echo '<label><input type="radio" name="hkcc_filter_min_income" value="100001" /> &gt; HK$100,000</label>';
					echo '</div></div>';
					break;

				case 'lounge_access':
					echo '<div class="hkcc-filter-group" data-filter="lounge_access">';
					echo '<h4 class="hkcc-filter-heading">機場貴賓室</h4>';
					echo '<div class="hkcc-filter-options">';
					echo '<label><input type="radio" name="hkcc_filter_lounge" value="" checked /> 任何</label>';
					echo '<label><input type="radio" name="hkcc_filter_lounge" value="yes" /> 有</label>';
					echo '<label><input type="radio" name="hkcc_filter_lounge" value="no" /> 無</label>';
					echo '</div></div>';
					break;

				case 'points_system':
					echo '<div class="hkcc-filter-group" data-filter="points_system">';
					echo '<h4 class="hkcc-filter-heading">回贈類型</h4>';
					echo '<div class="hkcc-filter-options">';
					echo '<label><input type="radio" name="hkcc_filter_points" value="" checked /> 任何</label>';
					echo '<label><input type="radio" name="hkcc_filter_points" value="cash" /> 現金回贈</label>';
					echo '<label><input type="radio" name="hkcc_filter_points" value="points" /> 積分系統</label>';
					echo '</div></div>';
					break;
			}
		}
	}
}
