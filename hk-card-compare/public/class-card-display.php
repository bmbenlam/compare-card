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

	/** Cache for conversion value-per-point by system_id. */
	private static $vpp_cache = array();

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
	 */
	public static function get_card_name( $card ) {
		$name = get_post_meta( $card->ID, 'card_name', true );
		return $name ? $name : $card->post_title;
	}

	/**
	 * Get the card face image HTML using card_face_image meta field.
	 * No fallback — returns empty string if not set.
	 */
	private static function get_card_face_html( $card_id, $card_name, $size = 'card-thumb' ) {
		$img_id = (int) get_post_meta( $card_id, 'card_face_image', true );
		if ( ! $img_id ) {
			return '';
		}
		return wp_get_attachment_image( $img_id, $size, false, array(
			'loading' => 'lazy',
			'alt'     => esc_attr( $card_name ),
		) );
	}

	/**
	 * Format welcome_offer_expiry date as "優惠有效期為即日至 yyyy 年 mm 月 dd 日".
	 */
	private static function format_expiry_date( $date_str ) {
		if ( ! $date_str ) {
			return '';
		}
		$ts = strtotime( $date_str );
		if ( ! $ts ) {
			return $date_str;
		}
		return '優惠有效期為即日至 ' . date( 'Y', $ts ) . ' 年 ' . date( 'n', $ts ) . ' 月 ' . date( 'j', $ts ) . ' 日';
	}

	/**
	 * Render a card for the [cc_suggest] shortcode.
	 */
	public static function render_suggest_card( $card ) {
		self::render_listing_card( $card, 'miles' );
	}

	/**
	 * Render a card for the [cc_comparison] listing.
	 */
	public static function render_listing_card( $card, $view = 'miles' ) {
		$card_name = self::get_card_name( $card );
		$tagline   = get_post_meta( $card->ID, 'tagline', true );
		$aff_link  = get_post_meta( $card->ID, 'affiliate_link', true );
		$blog_link = get_post_meta( $card->ID, 'blog_post_link', true );
		$system_id = (int) get_post_meta( $card->ID, 'points_system_id', true );

		// Shared footnotes array between 4-pack and expanded details.
		$footnotes = array();

		// Featured parameters (with numbered footnotes).
		$featured_values = self::get_featured_values( $card->ID, $view, $footnotes );

		// Get welcome offer for collapsed preview.
		$welcome_short = get_post_meta( $card->ID, 'welcome_offer_short', true );
		if ( ! $welcome_short ) {
			$welcome_short = get_post_meta( $card->ID, 'welcome_offer_description', true );
			$welcome_short = $welcome_short ? wp_strip_all_tags( $welcome_short ) : '';
			if ( mb_strlen( $welcome_short ) > 60 ) {
				$welcome_short = mb_substr( $welcome_short, 0, 57 ) . '...';
			}
		}

		// Card face image (no fallback to featured image).
		$card_face_html = self::get_card_face_html( $card->ID, $card_name );
		$has_cardface   = ! empty( $card_face_html );

		// Dim cards without both affiliate link AND blog post link.
		$is_dimmed = empty( $aff_link ) && empty( $blog_link );

		$card_classes = 'hkcc-listing-card';
		if ( ! $has_cardface ) {
			$card_classes .= ' hkcc-no-cardface';
		}
		if ( $is_dimmed ) {
			$card_classes .= ' hkcc-card-dimmed';
		}
		?>
		<div class="<?php echo esc_attr( $card_classes ); ?>" data-card-id="<?php echo esc_attr( $card->ID ); ?>" data-points-system="<?php echo esc_attr( $system_id ); ?>">
			<?php if ( $tagline ) : ?>
				<div class="hkcc-tagline-bookmark"><?php echo esc_html( $tagline ); ?></div>
			<?php endif; ?>

			<!-- Collapsed view -->
			<div class="hkcc-card-collapsed">

				<?php if ( $has_cardface ) : ?>
					<div class="hkcc-card-image">
						<?php if ( $aff_link ) : ?>
							<a href="<?php echo esc_url( $aff_link ); ?>" class="card-apply-link" data-card-id="<?php echo esc_attr( $card->ID ); ?>" target="_blank" rel="noopener nofollow"><?php echo $card_face_html; ?></a>
						<?php else : ?>
							<?php echo $card_face_html; ?>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<div class="hkcc-card-header">
					<h3 class="hkcc-card-name"><?php echo esc_html( $card_name ); ?></h3>
				</div>

				<?php if ( $welcome_short ) : ?>
					<div class="hkcc-welcome-preview">
						<span class="hkcc-welcome-preview-icon">&#127873;</span>
						<span class="hkcc-welcome-preview-text"><?php echo nl2br( esc_html( $welcome_short ) ); ?></span>
					</div>
				<?php endif; ?>

				<div class="hkcc-featured-params">
					<?php foreach ( $featured_values as $fv ) : ?>
						<div class="hkcc-featured-row">
							<span class="hkcc-featured-label"><?php echo esc_html( $fv['label'] ); ?></span>
							<span class="hkcc-featured-value"><?php echo esc_html( $fv['value'] ); ?><?php if ( $fv['fn'] ) { echo $fv['fn']; } ?></span>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="hkcc-card-actions">
					<button type="button" class="hkcc-btn hkcc-btn-secondary hkcc-details-toggle" aria-expanded="false">查看詳情 &#9660;</button>
					<?php if ( $aff_link ) : ?>
						<a href="<?php echo esc_url( $aff_link ); ?>" class="hkcc-btn hkcc-btn-cta card-apply-link" data-card-id="<?php echo esc_attr( $card->ID ); ?>" target="_blank" rel="noopener nofollow">立即申請 &rarr;</a>
					<?php endif; ?>
				</div>
			</div>

			<!-- Expanded view -->
			<div class="hkcc-card-expanded" style="display:none;">
				<?php self::render_expanded_details( $card, $view, $footnotes ); ?>

				<div class="hkcc-card-actions hkcc-card-actions-bottom">
					<?php if ( $blog_link ) : ?>
						<a href="<?php echo esc_url( $blog_link ); ?>" class="hkcc-btn hkcc-btn-secondary">詳細玩法</a>
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
	 * Render a spotlight card for [cc_card] — single card, no expand/collapse.
	 * Shows: tagline, card face, name, short welcome, 4-pack, apply button,
	 *        long welcome bubble, blog + apply buttons, footnotes.
	 */
	public static function render_spotlight_card( $card, $view = 'miles' ) {
		$card_name = self::get_card_name( $card );
		$tagline   = get_post_meta( $card->ID, 'tagline', true );
		$aff_link  = get_post_meta( $card->ID, 'affiliate_link', true );
		$blog_link = get_post_meta( $card->ID, 'blog_post_link', true );
		$system_id = (int) get_post_meta( $card->ID, 'points_system_id', true );

		$footnotes = array();
		$featured_values = self::get_featured_values( $card->ID, $view, $footnotes );

		$welcome_short = get_post_meta( $card->ID, 'welcome_offer_short', true );
		if ( ! $welcome_short ) {
			$welcome_short = get_post_meta( $card->ID, 'welcome_offer_description', true );
			$welcome_short = $welcome_short ? wp_strip_all_tags( $welcome_short ) : '';
			if ( mb_strlen( $welcome_short ) > 60 ) {
				$welcome_short = mb_substr( $welcome_short, 0, 57 ) . '...';
			}
		}

		$welcome_desc   = get_post_meta( $card->ID, 'welcome_offer_description', true );
		$welcome_expiry = get_post_meta( $card->ID, 'welcome_offer_expiry', true );
		$cooling        = get_post_meta( $card->ID, 'welcome_cooling_period_display', true );

		$card_face_html = self::get_card_face_html( $card->ID, $card_name );
		$has_cardface   = ! empty( $card_face_html );

		$card_classes = 'hkcc-listing-card hkcc-spotlight-card';
		if ( ! $has_cardface ) {
			$card_classes .= ' hkcc-no-cardface';
		}
		?>
		<div class="<?php echo esc_attr( $card_classes ); ?>" data-card-id="<?php echo esc_attr( $card->ID ); ?>" data-points-system="<?php echo esc_attr( $system_id ); ?>">
			<?php if ( $tagline ) : ?>
				<div class="hkcc-tagline-bookmark"><?php echo esc_html( $tagline ); ?></div>
			<?php endif; ?>

			<div class="hkcc-card-collapsed">
				<?php if ( $has_cardface ) : ?>
					<div class="hkcc-card-image">
						<?php if ( $aff_link ) : ?>
							<a href="<?php echo esc_url( $aff_link ); ?>" class="card-apply-link" data-card-id="<?php echo esc_attr( $card->ID ); ?>" target="_blank" rel="noopener nofollow"><?php echo $card_face_html; ?></a>
						<?php else : ?>
							<?php echo $card_face_html; ?>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<div class="hkcc-card-header">
					<h3 class="hkcc-card-name"><?php echo esc_html( $card_name ); ?></h3>
				</div>

				<?php if ( $welcome_short ) : ?>
					<div class="hkcc-welcome-preview">
						<span class="hkcc-welcome-preview-icon">&#127873;</span>
						<span class="hkcc-welcome-preview-text"><?php echo nl2br( esc_html( $welcome_short ) ); ?></span>
					</div>
				<?php endif; ?>

				<div class="hkcc-featured-params">
					<?php foreach ( $featured_values as $fv ) : ?>
						<div class="hkcc-featured-row">
							<span class="hkcc-featured-label"><?php echo esc_html( $fv['label'] ); ?></span>
							<span class="hkcc-featured-value"><?php echo esc_html( $fv['value'] ); ?><?php if ( $fv['fn'] ) { echo $fv['fn']; } ?></span>
						</div>
					<?php endforeach; ?>
				</div>

				<?php if ( $aff_link ) : ?>
				<div class="hkcc-card-actions">
					<a href="<?php echo esc_url( $aff_link ); ?>" class="hkcc-btn hkcc-btn-cta card-apply-link" data-card-id="<?php echo esc_attr( $card->ID ); ?>" target="_blank" rel="noopener nofollow">立即申請 &rarr;</a>
				</div>
				<?php endif; ?>
			</div>

			<?php if ( $welcome_desc ) : ?>
			<div class="hkcc-card-expanded" style="display:block;">
				<div class="hkcc-details-section hkcc-section-welcome">
					<div class="hkcc-welcome-banner">
						<h4><span class="hkcc-section-icon">&#127873;</span> 迎新優惠</h4>
					</div>
					<?php if ( $welcome_expiry ) : ?>
						<p class="hkcc-welcome-expiry-date"><?php echo esc_html( self::format_expiry_date( $welcome_expiry ) ); ?></p>
					<?php endif; ?>
					<div class="hkcc-welcome-desc"><?php echo wp_kses_post( $welcome_desc ); ?></div>
					<?php if ( $cooling ) : ?>
						<p class="hkcc-cooling">冷河期: <?php echo esc_html( $cooling ); ?></p>
					<?php endif; ?>
				</div>

				<?php if ( $blog_link || $aff_link ) : ?>
				<div class="hkcc-card-actions hkcc-card-actions-mid">
					<?php if ( $blog_link ) : ?>
						<a href="<?php echo esc_url( $blog_link ); ?>" class="hkcc-btn hkcc-btn-secondary">詳細玩法</a>
					<?php endif; ?>
					<?php if ( $aff_link ) : ?>
						<a href="<?php echo esc_url( $aff_link ); ?>" class="hkcc-btn hkcc-btn-cta card-apply-link" data-card-id="<?php echo esc_attr( $card->ID ); ?>" target="_blank" rel="noopener nofollow">立即申請 &rarr;</a>
					<?php endif; ?>
				</div>
				<?php endif; ?>

				<?php if ( ! empty( $footnotes ) ) : ?>
				<div class="hkcc-details-section hkcc-section-minor">
					<h4>備註</h4>
					<?php foreach ( $footnotes as $i => $fn_text ) : ?>
						<p class="hkcc-footnote"><sup><?php echo ( $i + 1 ); ?></sup> <?php echo esc_html( $fn_text ); ?></p>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render card details for the single-card template (expanded, no toggle).
	 */
	public static function render_single_card_details( $card, $view = 'cash' ) {
		self::render_expanded_details( $card, $view );
	}

	/**
	 * Return featured parameter label/value pairs with numbered footnote markers.
	 * Uses shared $footnotes array so numbering is consistent with expanded details.
	 */
	private static function get_featured_values( $post_id, $view, &$footnotes = null ) {
		if ( $footnotes === null ) {
			$footnotes = array();
		}
		$featurable = HKCC_Card_Meta::get_featurable_fields();
		$system_id  = (int) get_post_meta( $post_id, 'points_system_id', true );

		$results = array();
		for ( $i = 1; $i <= 4; $i++ ) {
			$key = get_post_meta( $post_id, "featured_param_{$i}", true );
			if ( empty( $key ) ) {
				continue;
			}

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

			// Live-calculate for points-system cards when display value is missing.
			if ( ! $value && $system_id > 0 ) {
				$txn = preg_replace( '/_(cash|miles)_(display|sortable)$/', '', $key );
				if ( $txn !== $key ) {
					$value = self::live_calc_display( $post_id, $txn, $view, $system_id );
				}
			}

			// Footnote — numbered, with dedup.
			$fn_html = '';
			$footnote = get_post_meta( $post_id, $key . '_footnote', true );
			if ( $footnote ) {
				$fn_html = self::add_footnote( $footnote, $footnotes );
			}

			$results[] = array(
				'label' => $label,
				'value' => $value ?: '不適用',
				'fn'    => $fn_html,
			);
		}

		return $results;
	}

	/**
	 * Add a footnote to the shared array, deduplicating identical text.
	 * Returns the numbered superscript HTML.
	 */
	private static function add_footnote( $text, &$footnotes ) {
		$existing = array_search( $text, $footnotes, true );
		if ( $existing !== false ) {
			return '<sup class="hkcc-fn-ref">' . ( $existing + 1 ) . '</sup>';
		}
		$footnotes[] = $text;
		return '<sup class="hkcc-fn-ref">' . count( $footnotes ) . '</sup>';
	}

	/**
	 * Get footnote HTML if a footnote exists for the given meta key.
	 * Deduplicates identical footnotes.
	 */
	private static function get_footnote_html( $post_id, $meta_key, &$footnotes ) {
		$footnote = get_post_meta( $post_id, $meta_key . '_footnote', true );
		if ( ! $footnote ) {
			return '';
		}
		return self::add_footnote( $footnote, $footnotes );
	}

	/**
	 * Render expanded detail sections for a listing card.
	 *
	 * Order: welcome → CTA → rewards → points system → benefits → issuer/network → eligibility → fees → footnotes.
	 */
	private static function render_expanded_details( $card, $view, &$footnotes = null ) {
		$id = $card->ID;
		if ( $footnotes === null ) {
			$footnotes = array();
		}

		$aff_link  = get_post_meta( $id, 'affiliate_link', true );
		$blog_link = get_post_meta( $id, 'blog_post_link', true );

		// Taxonomy terms.
		$bank_terms    = get_the_terms( $id, 'card_bank' );
		$network_terms = get_the_terms( $id, 'card_network' );
		$bank_name     = ( $bank_terms && ! is_wp_error( $bank_terms ) ) ? $bank_terms[0]->name : '';
		$network_name  = ( $network_terms && ! is_wp_error( $network_terms ) ) ? $network_terms[0]->name : '';

		// Fee data — split 年費 & 豁免 into separate rows.
		$annual_fee_display = get_post_meta( $id, 'annual_fee_display', true );
		$annual_fee_waiver  = get_post_meta( $id, 'annual_fee_waiver', true );

		$other_fee_keys = array(
			'fx_fee_display'               => '外幣兌換手續費',
			'cross_border_fee_display'     => '跨境結算手續費',
			'late_fee_display'             => '逾期還款費',
			'interest_free_period_display' => '免息還款期',
		);

		$fees = array();
		if ( $annual_fee_display ) {
			$fn = self::get_footnote_html( $id, 'annual_fee_display', $footnotes );
			$fees[] = array( 'label' => '年費', 'value' => $annual_fee_display, 'fn' => $fn );
		}
		if ( $annual_fee_waiver ) {
			$fn = self::get_footnote_html( $id, 'annual_fee_waiver', $footnotes );
			$fees[] = array( 'label' => '年費豁免', 'value' => $annual_fee_waiver, 'fn' => $fn );
		}
		foreach ( $other_fee_keys as $fk => $fl ) {
			$val = get_post_meta( $id, $fk, true );
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

		// Points system info.
		$points_system_name  = get_post_meta( $id, 'points_system_name', true );
		$redemption_fee      = get_post_meta( $id, 'points_redemption_fee_display', true );
		$transferable_air    = get_post_meta( $id, 'transferable_airlines', true );
		$transferable_hotel  = get_post_meta( $id, 'transferable_hotels', true );

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

		<?php /* Welcome BEFORE rewards */ ?>
		<?php if ( $welcome_desc ) : ?>
		<div class="hkcc-details-section hkcc-section-welcome">
			<div class="hkcc-welcome-banner">
				<h4><span class="hkcc-section-icon">&#127873;</span> 迎新優惠</h4>
			</div>
			<?php if ( $welcome_expiry ) : ?>
				<p class="hkcc-welcome-expiry-date"><?php echo esc_html( self::format_expiry_date( $welcome_expiry ) ); ?></p>
			<?php endif; ?>
			<div class="hkcc-welcome-desc"><?php echo wp_kses_post( $welcome_desc ); ?></div>
			<?php if ( $cooling ) : ?>
				<p class="hkcc-cooling">冷河期: <?php echo esc_html( $cooling ); ?></p>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php if ( $blog_link || $aff_link ) : ?>
		<div class="hkcc-card-actions hkcc-card-actions-mid">
			<?php if ( $blog_link ) : ?>
				<a href="<?php echo esc_url( $blog_link ); ?>" class="hkcc-btn hkcc-btn-secondary">詳細玩法</a>
			<?php endif; ?>
			<?php if ( $aff_link ) : ?>
				<a href="<?php echo esc_url( $aff_link ); ?>" class="hkcc-btn hkcc-btn-cta card-apply-link" data-card-id="<?php echo esc_attr( $id ); ?>" target="_blank" rel="noopener nofollow">立即申請 &rarr;</a>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php if ( ! empty( $rewards ) ) : ?>
		<div class="hkcc-details-section hkcc-section-rewards">
			<h4><span class="hkcc-section-icon">&#9733;</span> 回贈</h4>
			<?php foreach ( $rewards as $r ) : ?>
				<div class="hkcc-detail-row"><span class="hkcc-detail-label"><?php echo esc_html( $r['label'] ); ?>:</span> <span class="hkcc-detail-value hkcc-reward-value"><?php echo esc_html( $r['value'] ); ?><?php echo $r['fn']; ?></span></div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php /* Points system & redemption info */ ?>
		<?php if ( $points_system_name || $redemption_fee || ( is_array( $transferable_air ) && ! empty( $transferable_air ) ) || ( is_array( $transferable_hotel ) && ! empty( $transferable_hotel ) ) ) : ?>
		<div class="hkcc-details-section hkcc-section-minor">
			<h4>積分系統</h4>
			<?php if ( $points_system_name ) : ?>
				<div class="hkcc-detail-row"><span class="hkcc-detail-label">積分計劃:</span> <span class="hkcc-detail-value"><?php echo esc_html( $points_system_name ); ?></span></div>
			<?php endif; ?>
			<?php if ( $redemption_fee ) : ?>
				<div class="hkcc-detail-row"><span class="hkcc-detail-label">積分兌換費用:</span> <span class="hkcc-detail-value"><?php echo esc_html( $redemption_fee ); ?></span></div>
			<?php endif; ?>
			<?php if ( is_array( $transferable_air ) && ! empty( $transferable_air ) ) : ?>
				<div class="hkcc-detail-row"><span class="hkcc-detail-label">可轉換航空里程:</span> <span class="hkcc-detail-value"><?php echo esc_html( implode( ', ', $transferable_air ) ); ?></span></div>
			<?php endif; ?>
			<?php if ( is_array( $transferable_hotel ) && ! empty( $transferable_hotel ) ) : ?>
				<div class="hkcc-detail-row"><span class="hkcc-detail-label">可轉換酒店積分:</span> <span class="hkcc-detail-value"><?php echo esc_html( implode( ', ', $transferable_hotel ) ); ?></span></div>
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

		<?php /* Minor info sections — unified background & text */ ?>
		<?php if ( $bank_name || $network_name ) : ?>
		<div class="hkcc-details-section hkcc-section-minor">
			<h4>發卡 / 結算機構</h4>
			<?php if ( $bank_name ) : ?>
				<div class="hkcc-detail-row"><span class="hkcc-detail-label">發卡機構:</span> <span class="hkcc-detail-value"><?php echo esc_html( $bank_name ); ?></span></div>
			<?php endif; ?>
			<?php if ( $network_name ) : ?>
				<div class="hkcc-detail-row"><span class="hkcc-detail-label">結算機構:</span> <span class="hkcc-detail-value"><?php echo esc_html( $network_name ); ?></span></div>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php if ( $min_age || $min_income ) : ?>
		<div class="hkcc-details-section hkcc-section-minor">
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
		<div class="hkcc-details-section hkcc-section-minor">
			<h4>費用</h4>
			<?php foreach ( $fees as $f ) : ?>
				<div class="hkcc-detail-row"><span class="hkcc-detail-label"><?php echo esc_html( $f['label'] ); ?>:</span> <span class="hkcc-detail-value"><?php echo esc_html( $f['value'] ); ?><?php echo $f['fn']; ?></span></div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php if ( ! empty( $footnotes ) ) : ?>
		<div class="hkcc-details-section hkcc-section-minor">
			<h4>備註</h4>
			<?php foreach ( $footnotes as $i => $fn_text ) : ?>
				<p class="hkcc-footnote"><sup><?php echo ( $i + 1 ); ?></sup> <?php echo esc_html( $fn_text ); ?></p>
			<?php endforeach; ?>
		</div>
		<?php endif;
	}

	/**
	 * Get the display value for a transaction type considering view mode.
	 * Falls back to live calculation for preview / unsaved data.
	 */
	private static function get_reward_display( $post_id, $txn, $view, $system_id ) {
		// Try pre-calculated display value.
		if ( 'miles' === $view && $system_id > 0 ) {
			$miles = get_post_meta( $post_id, "{$txn}_miles_display", true );
			if ( $miles ) {
				return $miles;
			}
		}

		$cash = get_post_meta( $post_id, "{$txn}_cash_display", true );
		if ( $cash ) {
			return $cash;
		}

		// Check if points data exists.
		$points = get_post_meta( $post_id, "{$txn}_points", true );
		if ( $points === '' || $points === false ) {
			return '';
		}

		$rate = HKCC_Points_System::extract_earning_rate( $points );
		if ( $rate <= 0 ) {
			return '不適用';
		}

		// For points-system cards: live-calculate instead of showing raw points.
		if ( $system_id > 0 ) {
			$live = self::live_calc_display( $post_id, $txn, $view, $system_id );
			return $live ?: '不適用';
		}

		return $points;
	}

	/**
	 * Live-calculate display value for a transaction type.
	 * Used as fallback when pre-calculated meta doesn't exist (preview, unsaved).
	 */
	private static function live_calc_display( $post_id, $txn, $view, $system_id ) {
		$points_text = get_post_meta( $post_id, "{$txn}_points", true );
		if ( $points_text === '' || $points_text === false ) {
			return '';
		}

		$earning_rate = HKCC_Points_System::extract_earning_rate( $points_text );
		if ( $earning_rate <= 0 ) {
			return '不適用';
		}

		$vpp = self::get_value_per_point( $system_id );

		if ( 'miles' === $view ) {
			$miles_vpp = $vpp['asia_miles'] ?? 0;
			if ( $miles_vpp > 0 ) {
				$m = $earning_rate * $miles_vpp;
				if ( $m > 0 ) {
					return 'HK$' . round( 1 / $m, 1 ) . '/里';
				}
			}
			return '';
		}

		// Cash view.
		$cash_vpp = $vpp['cash'] ?? 0;
		if ( $cash_vpp > 0 ) {
			return round( $earning_rate * $cash_vpp * 100, 2 ) . '% 現金回贈';
		}

		return '';
	}

	/**
	 * Get cached value-per-point map for a points system.
	 */
	private static function get_value_per_point( $system_id ) {
		if ( ! isset( self::$vpp_cache[ $system_id ] ) ) {
			$convs = HKCC_Points_System::get_conversions( $system_id );
			$vpp = array();
			foreach ( $convs as $c ) {
				if ( $c->points_required > 0 ) {
					$vpp[ $c->reward_type ] = $c->reward_value / $c->points_required;
				}
			}
			self::$vpp_cache[ $system_id ] = $vpp;
		}
		return self::$vpp_cache[ $system_id ];
	}
}
