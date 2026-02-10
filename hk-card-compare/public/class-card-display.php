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
	 * Render a card for the [cc_suggest] shortcode.
	 *
	 * @param WP_Post $card Card post object.
	 */
	public static function render_suggest_card( $card ) {
		$tagline   = get_post_meta( $card->ID, 'tagline', true );
		$aff_link  = get_post_meta( $card->ID, 'affiliate_link', true );
		$blog_link = get_post_meta( $card->ID, 'blog_post_link', true );
		?>
		<div class="hkcc-suggest-card">
			<?php if ( has_post_thumbnail( $card->ID ) ) : ?>
				<div class="hkcc-card-image">
					<?php echo get_the_post_thumbnail( $card->ID, 'card-thumb', array( 'loading' => 'lazy', 'alt' => esc_attr( $card->post_title ) ) ); ?>
				</div>
			<?php endif; ?>
			<h4 class="hkcc-card-name"><?php echo esc_html( $card->post_title ); ?></h4>
			<?php if ( $tagline ) : ?>
				<p class="hkcc-card-tagline"><?php echo esc_html( $tagline ); ?></p>
			<?php endif; ?>
			<div class="hkcc-card-actions">
				<?php if ( $blog_link ) : ?>
					<a href="<?php echo esc_url( $blog_link ); ?>" class="hkcc-btn hkcc-btn-secondary" target="_blank" rel="noopener">了解更多</a>
				<?php endif; ?>
				<?php if ( $aff_link ) : ?>
					<a href="<?php echo esc_url( $aff_link ); ?>" class="hkcc-btn hkcc-btn-primary card-apply-link" data-card-id="<?php echo esc_attr( $card->ID ); ?>" target="_blank" rel="noopener nofollow">立即申請 &rarr;</a>
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
		$tagline   = get_post_meta( $card->ID, 'tagline', true );
		$aff_link  = get_post_meta( $card->ID, 'affiliate_link', true );
		$blog_link = get_post_meta( $card->ID, 'blog_post_link', true );
		$system_id = (int) get_post_meta( $card->ID, 'points_system_id', true );

		// Featured parameters.
		$featured_values = self::get_featured_values( $card->ID, $view );

		// Expanded detail data.
		$bank_terms    = get_the_terms( $card->ID, 'card_bank' );
		$network_terms = get_the_terms( $card->ID, 'card_network' );
		$bank_name     = ( $bank_terms && ! is_wp_error( $bank_terms ) ) ? $bank_terms[0]->name : '';
		$network_name  = ( $network_terms && ! is_wp_error( $network_terms ) ) ? $network_terms[0]->name : '';
		?>
		<div class="hkcc-listing-card" data-card-id="<?php echo esc_attr( $card->ID ); ?>" data-points-system="<?php echo esc_attr( $system_id ); ?>">
			<!-- Collapsed view -->
			<div class="hkcc-card-collapsed">
				<?php if ( $tagline ) : ?>
					<p class="hkcc-card-tagline"><?php echo esc_html( $tagline ); ?></p>
				<?php endif; ?>

				<?php if ( has_post_thumbnail( $card->ID ) ) : ?>
					<div class="hkcc-card-image">
						<?php echo get_the_post_thumbnail( $card->ID, 'card-thumb', array( 'loading' => 'lazy', 'alt' => esc_attr( $card->post_title ) ) ); ?>
					</div>
				<?php endif; ?>

				<h3 class="hkcc-card-name"><?php echo esc_html( $card->post_title ); ?></h3>

				<div class="hkcc-featured-params">
					<?php foreach ( $featured_values as $fv ) : ?>
						<div class="hkcc-featured-row">
							<span class="hkcc-featured-label"><?php echo esc_html( $fv['label'] ); ?></span>
							<span class="hkcc-featured-value"><?php echo esc_html( $fv['value'] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="hkcc-card-actions">
					<button type="button" class="hkcc-btn hkcc-btn-secondary hkcc-details-toggle" aria-expanded="false">查看詳情 &#9660;</button>
					<?php if ( $aff_link ) : ?>
						<a href="<?php echo esc_url( $aff_link ); ?>" class="hkcc-btn hkcc-btn-primary card-apply-link" data-card-id="<?php echo esc_attr( $card->ID ); ?>" target="_blank" rel="noopener nofollow">立即申請 &rarr;</a>
					<?php endif; ?>
				</div>
			</div>

			<!-- Expanded view -->
			<div class="hkcc-card-expanded" style="display:none;">
				<?php self::render_expanded_details( $card, $view, $bank_name, $network_name ); ?>

				<div class="hkcc-card-actions hkcc-card-actions-bottom">
					<?php if ( $blog_link ) : ?>
						<a href="<?php echo esc_url( $blog_link ); ?>" class="hkcc-btn hkcc-btn-secondary" target="_blank" rel="noopener">了解更多</a>
					<?php endif; ?>
					<?php if ( $aff_link ) : ?>
						<a href="<?php echo esc_url( $aff_link ); ?>" class="hkcc-btn hkcc-btn-primary card-apply-link" data-card-id="<?php echo esc_attr( $card->ID ); ?>" target="_blank" rel="noopener nofollow">立即申請 &rarr;</a>
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
		$all_fields = HKCC_Card_Meta::get_fields();
		$flat       = array();
		foreach ( $all_fields as $group ) {
			$flat = array_merge( $flat, $group );
		}

		$results = array();
		for ( $i = 1; $i <= 4; $i++ ) {
			$key = get_post_meta( $post_id, "featured_param_{$i}", true );
			if ( empty( $key ) ) {
				continue;
			}

			// If view is miles and a miles-display variant exists, prefer it.
			$display_key = $key;
			if ( 'miles' === $view ) {
				$miles_key = str_replace( array( '_cash_display', '_points' ), '_miles_display', $key );
				$miles_val = get_post_meta( $post_id, $miles_key, true );
				if ( $miles_val ) {
					$display_key = $miles_key;
				}
			}

			$value = get_post_meta( $post_id, $display_key, true );
			$label = $flat[ $key ]['label'] ?? $key;

			$results[] = array(
				'label' => $label,
				'value' => $value ?: '不適用',
			);
		}

		return $results;
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

		$fees = array(
			'年費'       => self::display_with_waiver( $id ),
			'外幣兌換手續費' => get_post_meta( $id, 'fx_fee_display', true ),
			'跨境結算手續費' => get_post_meta( $id, 'cross_border_fee_display', true ),
			'逾期還款費'    => get_post_meta( $id, 'late_fee_display', true ),
			'免息還款期'    => get_post_meta( $id, 'interest_free_period_display', true ),
		);

		$txn_labels = array(
			'local_retail'          => '本地零售簽賬',
			'overseas_retail'       => '海外零售簽賬',
			'online_hkd'            => '網上港幣簽賬',
			'online_fx'             => '網上外幣簽賬',
			'local_dining'          => '本地餐飲簽賬',
			'online_bill_payment'   => '網上繳費',
			'payme_reload'          => 'PayMe 增值',
			'alipay_reload'         => 'AlipayHK 增值',
			'wechat_reload'         => 'WeChat Pay 增值',
			'octopus_reload'        => '八達通增值',
		);

		$system_id = (int) get_post_meta( $id, 'points_system_id', true );

		// Rewards section.
		$rewards = array();
		foreach ( $txn_labels as $txn => $label ) {
			$val = self::get_reward_display( $id, $txn, $view, $system_id );
			if ( $val ) {
				$rewards[ $label ] = $val;
			}
		}

		// Welcome offer.
		$welcome_desc   = get_post_meta( $id, 'welcome_offer_description', true );
		$welcome_expiry = get_post_meta( $id, 'welcome_offer_expiry', true );
		$cooling        = get_post_meta( $id, 'welcome_cooling_period_display', true );

		// Benefits.
		$lounge    = get_post_meta( $id, 'lounge_access_display', true );
		$insurance = get_post_meta( $id, 'travel_insurance', true );
		?>

		<div class="hkcc-details-section">
			<p><strong>發卡銀行:</strong> <?php echo esc_html( $bank_name ); ?></p>
			<p><strong>結算機構:</strong> <?php echo esc_html( $network_name ); ?></p>
		</div>

		<?php if ( array_filter( $fees ) ) : ?>
		<div class="hkcc-details-section">
			<h4>費用</h4>
			<?php foreach ( $fees as $label => $val ) : ?>
				<?php if ( $val ) : ?>
					<div class="hkcc-detail-row"><span class="hkcc-detail-label"><?php echo esc_html( $label ); ?>:</span> <span class="hkcc-detail-value"><?php echo esc_html( $val ); ?></span></div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php if ( ! empty( $rewards ) ) : ?>
		<div class="hkcc-details-section">
			<h4>回贈</h4>
			<?php foreach ( $rewards as $label => $val ) : ?>
				<div class="hkcc-detail-row"><span class="hkcc-detail-label"><?php echo esc_html( $label ); ?>:</span> <span class="hkcc-detail-value"><?php echo esc_html( $val ); ?></span></div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php if ( $welcome_desc ) : ?>
		<div class="hkcc-details-section">
			<h4>迎新優惠<?php echo $welcome_expiry ? ' (至 ' . esc_html( $welcome_expiry ) . ')' : ''; ?></h4>
			<div class="hkcc-welcome-desc"><?php echo wp_kses_post( $welcome_desc ); ?></div>
			<?php if ( $cooling ) : ?>
				<p class="hkcc-cooling">冷河期: <?php echo esc_html( $cooling ); ?></p>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php if ( $lounge || $insurance ) : ?>
		<div class="hkcc-details-section">
			<h4>福利</h4>
			<?php if ( $lounge ) : ?>
				<div class="hkcc-detail-row"><span class="hkcc-detail-label">免費使用機場貴賓室:</span> <span class="hkcc-detail-value"><?php echo esc_html( $lounge ); ?></span></div>
			<?php endif; ?>
			<?php if ( $insurance ) : ?>
				<div class="hkcc-detail-row"><span class="hkcc-detail-label">免費旅遊保險:</span> <span class="hkcc-detail-value"><?php echo esc_html( $insurance ); ?></span></div>
			<?php endif; ?>
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
		if ( ! $points ) {
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
