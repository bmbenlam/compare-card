<?php
/**
 * Card rendering helpers for frontend display.
 *
 * @package HK_Card_Compare
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKCC_Card_Display {

	/**
	 * Hook into WordPress.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue frontend CSS & JS.
	 */
	public static function enqueue_assets() {
		wp_enqueue_style(
			'hkcc-public',
			HKCC_PLUGIN_URL . 'public/css/public.css',
			array(),
			HKCC_VERSION
		);

		wp_enqueue_script(
			'hkcc-public',
			HKCC_PLUGIN_URL . 'public/js/public.js',
			array(),
			HKCC_VERSION,
			true
		);

		wp_localize_script( 'hkcc-public', 'hkccPublic', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'hkcc_public_nonce' ),
		) );
	}

	/**
	 * Get the display name for a card (card_name meta or fallback to post title).
	 *
	 * @param WP_Post $card Card post object.
	 * @return string
	 */
	public static function get_card_name( $card ) {
		$name = get_post_meta( $card->ID, 'card_name', true );
		return $name ? $name : $card->post_title;
	}

	/**
	 * Render a card for the [cc_suggest] shortcode.
	 *
	 * @param WP_Post $card Card post object.
	 */
	public static function render_suggest_card( $card ) {
		$card_name = self::get_card_name( $card );
		$tagline   = get_post_meta( $card->ID, 'tagline', true );
		$aff_link  = get_post_meta( $card->ID, 'affiliate_link', true );
		$permalink = get_permalink( $card->ID );
		?>
		<div class="hkcc-suggest-card">
			<?php if ( has_post_thumbnail( $card->ID ) ) : ?>
				<div class="hkcc-card-image">
					<?php echo get_the_post_thumbnail( $card->ID, 'card-thumb', array( 'loading' => 'lazy', 'alt' => esc_attr( $card_name ) ) ); ?>
				</div>
			<?php endif; ?>
			<h4 class="hkcc-card-name"><?php echo esc_html( $card_name ); ?></h4>
			<?php if ( $tagline ) : ?>
				<p class="hkcc-card-tagline"><?php echo esc_html( $tagline ); ?></p>
			<?php endif; ?>
			<div class="hkcc-card-actions">
				<?php if ( $permalink ) : ?>
					<a href="<?php echo esc_url( $permalink ); ?>" class="hkcc-btn hkcc-btn-secondary">了解更多</a>
				<?php endif; ?>
				<?php if ( $aff_link ) : ?>
					<a href="<?php echo esc_url( $aff_link ); ?>" class="hkcc-btn hkcc-btn-cta card-apply-link" data-card-id="<?php echo esc_attr( $card->ID ); ?>" target="_blank" rel="noopener nofollow">立即申請 &rarr;</a>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a card for the [cc_comparison] listing.
	 *
	 * @param WP_Post $card Card post object.
	 * @param string  $view 'miles' or 'cash'.
	 */
	public static function render_listing_card( $card, $view = 'miles' ) {
		$card_name = self::get_card_name( $card );
		$tagline   = get_post_meta( $card->ID, 'tagline', true );
		$aff_link  = get_post_meta( $card->ID, 'affiliate_link', true );
		$permalink = get_permalink( $card->ID );
		$system_id = (int) get_post_meta( $card->ID, 'points_system_id', true );

		// Featured parameters.
		$featured_values = self::get_featured_values( $card->ID, $view );

		// Expanded detail data.
		$bank_terms    = get_the_terms( $card->ID, 'card_bank' );
		$network_terms = get_the_terms( $card->ID, 'card_network' );
		$bank_name     = ( $bank_terms && ! is_wp_error( $bank_terms ) ) ? $bank_terms[0]->name : '';
		$network_name  = ( $network_terms && ! is_wp_error( $network_terms ) ) ? $network_terms[0]->name : '';

		// Check for free annual fee badge.
		$annual_fee_sortable = (float) get_post_meta( $card->ID, 'annual_fee_sortable', true );

		// Get welcome offer for collapsed preview.
		$welcome_short = get_post_meta( $card->ID, 'welcome_offer_short', true );
		if ( ! $welcome_short ) {
			$welcome_short = get_post_meta( $card->ID, 'welcome_offer_description', true );
			$welcome_short = $welcome_short ? wp_strip_all_tags( $welcome_short ) : '';
			if ( mb_strlen( $welcome_short ) > 60 ) {
				$welcome_short = mb_substr( $welcome_short, 0, 57 ) . '...';
			}
		}
		?>
		<div class="hkcc-listing-card" data-card-id="<?php echo esc_attr( $card->ID ); ?>" data-points-system="<?php echo esc_attr( $system_id ); ?>">
			<!-- Collapsed view -->
			<div class="hkcc-card-collapsed">
				<?php if ( has_post_thumbnail( $card->ID ) ) : ?>
					<div class="hkcc-card-image">
						<?php echo get_the_post_thumbnail( $card->ID, 'card-thumb', array( 'loading' => 'lazy', 'alt' => esc_attr( $card_name ) ) ); ?>
					</div>
				<?php endif; ?>

				<div class="hkcc-card-header">
					<h3 class="hkcc-card-name"><?php echo esc_html( $card_name ); ?></h3>
					<div class="hkcc-card-badges">
						<?php if ( $annual_fee_sortable <= 0 ) : ?>
							<span class="hkcc-badge hkcc-badge-free">免年費</span>
						<?php endif; ?>
						<?php if ( $welcome_short ) : ?>
							<span class="hkcc-badge hkcc-badge-welcome">迎新</span>
						<?php endif; ?>
					</div>
				</div>
				<?php if ( $tagline ) : ?>
					<p class="hkcc-card-tagline"><?php echo esc_html( $tagline ); ?></p>
				<?php endif; ?>

				<div class="hkcc-featured-params">
					<?php foreach ( $featured_values as $fv ) : ?>
						<div class="hkcc-featured-row">
							<span class="hkcc-featured-label"><?php echo esc_html( $fv['label'] ); ?></span>
							<span class="hkcc-featured-value"><?php echo esc_html( $fv['value'] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>

				<?php if ( $welcome_short ) : ?>
					<div class="hkcc-welcome-preview">
						<span class="hkcc-welcome-preview-icon">&#127873;</span>
						<span class="hkcc-welcome-preview-text"><?php echo esc_html( $welcome_short ); ?></span>
					</div>
				<?php endif; ?>

				<div class="hkcc-card-actions">
					<button type="button" class="hkcc-btn hkcc-btn-secondary hkcc-details-toggle" aria-expanded="false">查看詳情 &#9660;</button>
					<?php if ( $aff_link ) : ?>
						<a href="<?php echo esc_url( $aff_link ); ?>" class="hkcc-btn hkcc-btn-cta card-apply-link" data-card-id="<?php echo esc_attr( $card->ID ); ?>" target="_blank" rel="noopener nofollow">立即申請 &rarr;</a>
					<?php endif; ?>
				</div>
			</div>

			<!-- Expanded view -->
			<div class="hkcc-card-expanded" style="display:none;">
				<?php self::render_expanded_details( $card, $view, $bank_name, $network_name ); ?>

				<div class="hkcc-card-actions hkcc-card-actions-bottom">
					<?php if ( $permalink ) : ?>
						<a href="<?php echo esc_url( $permalink ); ?>" class="hkcc-btn hkcc-btn-secondary">了解更多</a>
					<?php endif; ?>
					<?php if ( $aff_link ) : ?>
						<a href="<?php echo esc_url( $aff_link ); ?>" class="hkcc-btn hkcc-btn-cta card-apply-link" data-card-id="<?php echo esc_attr( $card->ID ); ?>" target="_blank" rel="noopener nofollow">立即申請 &rarr;</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Return featured parameter label/value pairs for a card.
	 *
	 * @param int    $post_id Card ID.
	 * @param string $view    'miles' or 'cash'.
	 * @return array Array of ['label' => ..., 'value' => ...].
	 */
	private static function get_featured_values( $post_id, $view ) {
		$featurable = HKCC_Card_Meta::get_featurable_fields();

		$results = array();
		for ( $i = 1; $i <= 4; $i++ ) {
			$key = get_post_meta( $post_id, "featured_param_{$i}", true );
			if ( empty( $key ) ) {
				continue;
			}

			// Use clean label from featurable fields.
			$label = $featurable[ $key ] ?? $key;

			// If view is miles and a miles-display variant exists, prefer it.
			$display_key = $key;
			if ( 'miles' === $view ) {
				$miles_key = str_replace( '_cash_display', '_miles_display', $key );
				if ( $miles_key !== $key ) {
					$miles_val = get_post_meta( $post_id, $miles_key, true );
					if ( $miles_val ) {
						$display_key = $miles_key;
					}
				}
			}

			$value = get_post_meta( $post_id, $display_key, true );

			$results[] = array(
				'label' => $label,
				'value' => $value ?: '不適用',
			);
		}

		return $results;
	}

	/**
	 * Get footnote HTML if a footnote exists for the given meta key.
	 *
	 * @param int    $post_id   Card ID.
	 * @param string $meta_key  Meta key.
	 * @param array  $footnotes Collected footnotes (passed by reference).
	 * @return string Superscript HTML or empty string.
	 */
	private static function get_footnote_html( $post_id, $meta_key, &$footnotes ) {
		$footnote = get_post_meta( $post_id, $meta_key . '_footnote', true );
		if ( ! $footnote ) {
			return '';
		}
		$footnotes[] = $footnote;
		return '<sup class="hkcc-fn-ref">' . count( $footnotes ) . '</sup>';
	}

	/**
	 * Render the expanded detail section for a listing card.
	 *
	 * @param WP_Post $card         Card post.
	 * @param string  $view         'miles' or 'cash'.
	 * @param string  $bank_name    Bank taxonomy term name.
	 * @param string  $network_name Network taxonomy term name.
	 */
	private static function render_expanded_details( $card, $view, $bank_name, $network_name ) {
		$id = $card->ID;
		$footnotes = array();

		$fee_keys = array(
			'annual_fee_display'           => '年費',
			'fx_fee_display'               => '外幣兌換手續費',
			'cross_border_fee_display'     => '跨境結算手續費',
			'late_fee_display'             => '逾期還款費',
			'interest_free_period_display' => '免息還款期',
		);

		$fees = array();
		foreach ( $fee_keys as $fk => $fl ) {
			$val = ( $fk === 'annual_fee_display' ) ? self::display_with_waiver( $id ) : get_post_meta( $id, $fk, true );
			if ( $val ) {
				$fn = self::get_footnote_html( $id, $fk, $footnotes );
				$fees[] = array( 'label' => $fl, 'value' => $val, 'fn' => $fn );
			}
		}

		$txn_labels = HKCC_Points_System::get_transaction_labels();
		$system_id  = (int) get_post_meta( $id, 'points_system_id', true );

		// Rewards section.
		$rewards = array();
		foreach ( HKCC_Points_System::get_transaction_types() as $txn ) {
			$label = $txn_labels[ $txn ] ?? $txn;
			$val   = self::get_reward_display( $id, $txn, $view, $system_id );
			if ( $val ) {
				$fn_key = ( 'miles' === $view && $system_id > 0 ) ? "{$txn}_miles_display" : "{$txn}_cash_display";
				$fn_points = self::get_footnote_html( $id, "{$txn}_points", $footnotes );
				$fn = $fn_points ?: self::get_footnote_html( $id, $fn_key, $footnotes );
				$rewards[] = array( 'label' => $label, 'value' => $val, 'fn' => $fn );
			}
		}

		// Welcome offer.
		$welcome_desc   = get_post_meta( $id, 'welcome_offer_description', true );
		$welcome_expiry = get_post_meta( $id, 'welcome_offer_expiry', true );
		$cooling        = get_post_meta( $id, 'welcome_cooling_period_display', true );

		// Benefits.
		$lounge    = get_post_meta( $id, 'lounge_access_display', true );
		$insurance = get_post_meta( $id, 'travel_insurance', true );
		$lounge_fn    = $lounge ? self::get_footnote_html( $id, 'lounge_access_display', $footnotes ) : '';
		$insurance_fn = $insurance ? self::get_footnote_html( $id, 'travel_insurance', $footnotes ) : '';

		// Eligibility.
		$min_age    = get_post_meta( $id, 'min_age_display', true );
		$min_income = get_post_meta( $id, 'min_income_display', true );
		?>

		<div class="hkcc-details-section hkcc-section-meta">
			<p><strong>發卡機構:</strong> <?php echo esc_html( $bank_name ); ?></p>
			<p><strong>結算機構:</strong> <?php echo esc_html( $network_name ); ?></p>
		</div>

		<?php if ( ! empty( $rewards ) ) : ?>
		<div class="hkcc-details-section hkcc-section-rewards">
			<h4><span class="hkcc-section-icon">&#9733;</span> 回贈</h4>
			<?php foreach ( $rewards as $r ) : ?>
				<div class="hkcc-detail-row"><span class="hkcc-detail-label"><?php echo esc_html( $r['label'] ); ?>:</span> <span class="hkcc-detail-value hkcc-reward-value"><?php echo esc_html( $r['value'] ); ?><?php echo $r['fn']; ?></span></div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php if ( $welcome_desc ) : ?>
		<div class="hkcc-details-section hkcc-section-welcome">
			<div class="hkcc-welcome-banner">
				<h4><span class="hkcc-section-icon">&#127873;</span> 迎新優惠<?php echo $welcome_expiry ? ' <span class="hkcc-welcome-expiry">(至 ' . esc_html( $welcome_expiry ) . ')</span>' : ''; ?></h4>
			</div>
			<div class="hkcc-welcome-desc"><?php echo wp_kses_post( $welcome_desc ); ?></div>
			<?php if ( $cooling ) : ?>
				<p class="hkcc-cooling">冷河期: <?php echo esc_html( $cooling ); ?></p>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php if ( $lounge || $insurance ) : ?>
		<div class="hkcc-details-section hkcc-section-benefits">
			<h4><span class="hkcc-section-icon">&#10004;</span> 福利</h4>
			<?php if ( $lounge ) : ?>
				<div class="hkcc-detail-row"><span class="hkcc-detail-label">免費使用機場貴賓室:</span> <span class="hkcc-detail-value"><?php echo esc_html( $lounge ); ?><?php echo $lounge_fn; ?></span></div>
			<?php endif; ?>
			<?php if ( $insurance ) : ?>
				<div class="hkcc-detail-row"><span class="hkcc-detail-label">免費旅遊保險:</span> <span class="hkcc-detail-value"><?php echo esc_html( $insurance ); ?><?php echo $insurance_fn; ?></span></div>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php if ( $min_age || $min_income ) : ?>
		<div class="hkcc-details-section hkcc-section-eligibility">
			<h4>申請資格</h4>
			<?php if ( $min_age ) : ?>
				<div class="hkcc-detail-row"><span class="hkcc-detail-label">最低年齡:</span> <span class="hkcc-detail-value"><?php echo esc_html( $min_age ); ?></span></div>
			<?php endif; ?>
			<?php if ( $min_income ) : ?>
				<div class="hkcc-detail-row"><span class="hkcc-detail-label">最低收入:</span> <span class="hkcc-detail-value"><?php echo esc_html( $min_income ); ?></span></div>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php if ( ! empty( $fees ) ) : ?>
		<div class="hkcc-details-section hkcc-section-fees">
			<h4>費用</h4>
			<?php
			$annual_fee_sortable = (float) get_post_meta( $id, 'annual_fee_sortable', true );
			if ( $annual_fee_sortable <= 0 ) : ?>
				<span class="hkcc-badge hkcc-badge-free">免年費</span>
			<?php endif; ?>
			<?php foreach ( $fees as $f ) : ?>
				<div class="hkcc-detail-row"><span class="hkcc-detail-label"><?php echo esc_html( $f['label'] ); ?>:</span> <span class="hkcc-detail-value"><?php echo esc_html( $f['value'] ); ?><?php echo $f['fn']; ?></span></div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php if ( ! empty( $footnotes ) ) : ?>
		<div class="hkcc-details-section hkcc-section-footnotes">
			<h4>備註</h4>
			<?php foreach ( $footnotes as $i => $fn_text ) : ?>
				<p class="hkcc-footnote"><sup><?php echo ( $i + 1 ); ?></sup> <?php echo esc_html( $fn_text ); ?></p>
			<?php endforeach; ?>
		</div>
		<?php endif;
	}

	/**
	 * Get the display value for a transaction type considering view mode.
	 *
	 * @param int    $post_id   Card ID.
	 * @param string $txn       Transaction type slug.
	 * @param string $view      'miles' or 'cash'.
	 * @param int    $system_id Points system ID.
	 * @return string
	 */
	private static function get_reward_display( $post_id, $txn, $view, $system_id ) {
		if ( 'miles' === $view && $system_id > 0 ) {
			$miles = get_post_meta( $post_id, "{$txn}_miles_display", true );
			if ( $miles ) {
				return $miles;
			}
		}

		// Fallback to cash or points text.
		$cash = get_post_meta( $post_id, "{$txn}_cash_display", true );
		if ( $cash ) {
			return $cash;
		}

		$points = get_post_meta( $post_id, "{$txn}_points", true );
		if ( $points === '' || $points === false ) {
			return '';
		}

		// If the earning rate is 0 (e.g. "HK$1 = 0 points"), show 不適用.
		$rate = HKCC_Points_System::extract_earning_rate( $points );
		if ( $rate <= 0 ) {
			return '不適用';
		}

		return $points;
	}

	/**
	 * Get annual fee display with waiver note appended.
	 *
	 * @param int $post_id Card ID.
	 * @return string
	 */
	private static function display_with_waiver( $post_id ) {
		$fee    = get_post_meta( $post_id, 'annual_fee_display', true );
		$waiver = get_post_meta( $post_id, 'annual_fee_waiver', true );
		if ( $fee && $waiver ) {
			return $fee . ' (' . $waiver . ')';
		}
		return $fee ?: '';
	}
}
