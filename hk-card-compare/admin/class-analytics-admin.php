<?php
/**
 * Analytics dashboard admin page + WP Dashboard widget.
 *
 * @package HK_Card_Compare
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKCC_Analytics_Admin {

	/**
	 * Hook into WordPress.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'register_dashboard_widget' ) );
	}

	/**
	 * Add the Analytics submenu under Cards.
	 */
	public static function register_menu() {
		add_submenu_page(
			'edit.php?post_type=card',
			'Card Analytics',
			'Analytics',
			'edit_posts',
			'card-analytics',
			array( __CLASS__, 'render_page' )
		);
	}

	/* ==================================================================
	 * Full Analytics Page
	 * ================================================================ */

	/**
	 * Render the analytics page.
	 */
	public static function render_page() {
		$days = isset( $_GET['days'] ) ? absint( $_GET['days'] ) : 30;

		$all_cards   = HKCC_Click_Tracker::all_cards_stats( $days );
		$top_sources = HKCC_Click_Tracker::top_sources( $days );
		$recent      = HKCC_Click_Tracker::recent( 100 );

		$total_impressions = HKCC_Click_Tracker::total_impressions( $days );
		$total_aff_clicks  = HKCC_Click_Tracker::total_affiliate_clicks( $days );
		$overall_ctr       = $total_impressions > 0 ? round( $total_aff_clicks / $total_impressions * 100, 2 ) : 0;

		$base_url = admin_url( 'edit.php?post_type=card&page=card-analytics' );
		?>
		<div class="wrap">
			<h1>Card Analytics</h1>

			<div class="hkcc-analytics-filters" style="margin-bottom:20px;">
				<strong>Date Range:</strong>
				<?php foreach ( array( 7, 30, 90 ) as $d ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'days', $d, $base_url ) ); ?>" class="button <?php echo $days === $d ? 'button-primary' : ''; ?>">
						Last <?php echo $d; ?> days
					</a>
				<?php endforeach; ?>
			</div>

			<!-- Summary Stats -->
			<div style="display:flex;gap:20px;margin-bottom:24px;">
				<div style="background:#fff;padding:16px 24px;border:1px solid #ccd0d4;border-radius:4px;">
					<div style="font-size:28px;font-weight:600;color:#b38850;"><?php echo number_format( $total_impressions ); ?></div>
					<div style="color:#666;margin-top:4px;">Impressions</div>
				</div>
				<div style="background:#fff;padding:16px 24px;border:1px solid #ccd0d4;border-radius:4px;">
					<div style="font-size:28px;font-weight:600;color:#b20000;"><?php echo number_format( $total_aff_clicks ); ?></div>
					<div style="color:#666;margin-top:4px;">Affiliate Clicks</div>
				</div>
				<div style="background:#fff;padding:16px 24px;border:1px solid #ccd0d4;border-radius:4px;">
					<div style="font-size:28px;font-weight:600;color:#5099b3;"><?php echo esc_html( $overall_ctr ); ?>%</div>
					<div style="color:#666;margin-top:4px;">Overall CTR</div>
				</div>
			</div>

			<!-- All Cards -->
			<h2>All Cards — Impressions &amp; Clicks</h2>
			<?php if ( empty( $all_cards ) ) : ?>
				<p>No published cards found.</p>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th>Card Name</th>
							<th>Impressions</th>
							<th>Affiliate Clicks</th>
							<th>CTR</th>
							<th>Clicks from Preview / Expanded</th>
							<th>Blog Post Clicks</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $all_cards as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row->post_title ); ?></td>
								<td><?php echo number_format( $row->impressions ); ?></td>
								<td><?php echo number_format( $row->affiliate_clicks ); ?></td>
								<td><?php echo esc_html( $row->ctr ); ?>%</td>
								<td>
									<?php echo number_format( $row->preview_clicks ); ?> / <?php echo number_format( $row->expanded_clicks ); ?>
								</td>
								<td><?php echo number_format( $row->blog_clicks ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<!-- Top Sources -->
			<h2>Top Source Pages</h2>
			<?php if ( empty( $top_sources ) ) : ?>
				<p>No source data found.</p>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr><th>Source Page</th><th>Total Clicks</th></tr>
					</thead>
					<tbody>
						<?php foreach ( $top_sources as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row->source_page ); ?></td>
								<td><?php echo esc_html( number_format( $row->total_clicks ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<!-- Recent Clicks -->
			<h2>Recent Clicks (Last 100)</h2>
			<?php if ( empty( $recent ) ) : ?>
				<p>No recent clicks.</p>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th>Time</th>
							<th>Card</th>
							<th>Click Type</th>
							<th>Click Position</th>
							<th>Source Page</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $recent as $row ) :
							$type_label = ( 'blog' === ( $row->click_type ?? 'affiliate' ) ) ? 'Blog Post' : 'Affiliate';
							$ctx        = $row->click_context ?? '';
							$ctx_labels = array( 'preview' => 'Preview Card', 'expanded' => 'Expanded Details', 'image' => 'Card Image' );
							$ctx_label  = $ctx_labels[ $ctx ] ?? ( $ctx ?: '—' );
							?>
							<tr>
								<td><?php echo esc_html( $row->clicked_at ); ?></td>
								<td><?php echo esc_html( $row->post_title ); ?></td>
								<td><?php echo esc_html( $type_label ); ?></td>
								<td><?php echo esc_html( $ctx_label ); ?></td>
								<td><?php echo esc_html( $row->source_page ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/* ==================================================================
	 * WordPress Dashboard Widget (shown on wp-admin home)
	 * ================================================================ */

	/**
	 * Register the dashboard widget.
	 */
	public static function register_dashboard_widget() {
		wp_add_dashboard_widget(
			'hkcc_card_overview',
			'Card Compare — 7-Day Overview',
			array( __CLASS__, 'render_dashboard_widget' )
		);
	}

	/**
	 * Render the dashboard widget content.
	 */
	public static function render_dashboard_widget() {
		$days = 7;

		$total_impressions = HKCC_Click_Tracker::total_impressions( $days );
		$total_aff_clicks  = HKCC_Click_Tracker::total_affiliate_clicks( $days );
		$overall_ctr       = $total_impressions > 0 ? round( $total_aff_clicks / $total_impressions * 100, 2 ) : 0;

		$all_cards = HKCC_Click_Tracker::all_cards_stats( $days );
		// Top 5 by affiliate clicks.
		usort( $all_cards, function( $a, $b ) {
			return $b->affiliate_clicks <=> $a->affiliate_clicks;
		} );
		$top5 = array_filter( array_slice( $all_cards, 0, 5 ), function( $c ) {
			return $c->affiliate_clicks > 0;
		} );

		$expiring = HKCC_Click_Tracker::expiring_cards();

		$analytics_url = admin_url( 'edit.php?post_type=card&page=card-analytics&days=7' );
		?>
		<div style="display:flex;gap:12px;margin-bottom:12px;">
			<div style="flex:1;text-align:center;padding:8px;background:#f8f8f8;border-radius:4px;">
				<div style="font-size:20px;font-weight:600;color:#b38850;"><?php echo number_format( $total_impressions ); ?></div>
				<div style="font-size:11px;color:#666;">Impressions</div>
			</div>
			<div style="flex:1;text-align:center;padding:8px;background:#f8f8f8;border-radius:4px;">
				<div style="font-size:20px;font-weight:600;color:#b20000;"><?php echo number_format( $total_aff_clicks ); ?></div>
				<div style="font-size:11px;color:#666;">Affiliate Clicks</div>
			</div>
			<div style="flex:1;text-align:center;padding:8px;background:#f8f8f8;border-radius:4px;">
				<div style="font-size:20px;font-weight:600;color:#5099b3;"><?php echo esc_html( $overall_ctr ); ?>%</div>
				<div style="font-size:11px;color:#666;">CTR</div>
			</div>
		</div>

		<?php if ( ! empty( $top5 ) ) : ?>
		<h4 style="margin:12px 0 6px;">Top Applied Cards</h4>
		<table class="widefat striped" style="border:0;">
			<tbody>
				<?php foreach ( $top5 as $card ) : ?>
					<tr>
						<td style="padding:4px 6px;"><?php echo esc_html( $card->post_title ); ?></td>
						<td style="padding:4px 6px;text-align:right;white-space:nowrap;"><?php echo number_format( $card->affiliate_clicks ); ?> clicks</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>

		<?php if ( ! empty( $expiring ) ) : ?>
		<h4 style="margin:12px 0 6px;">Expiring / Expired Offers</h4>
		<table class="widefat striped" style="border:0;">
			<tbody>
				<?php foreach ( $expiring as $card ) :
					if ( $card->days_left < 0 ) {
						$status = 'Expired';
						$color  = '#999';
					} elseif ( $card->days_left <= 7 ) {
						$status = $card->days_left . 'd left';
						$color  = '#b20000';
					} else {
						$status = $card->days_left . 'd left';
						$color  = '#b38850';
					}
					?>
					<tr>
						<td style="padding:4px 6px;">
							<a href="<?php echo esc_url( get_edit_post_link( $card->ID ) ); ?>"><?php echo esc_html( $card->post_title ); ?></a>
						</td>
						<td style="padding:4px 6px;text-align:right;white-space:nowrap;">
							<span style="color:<?php echo esc_attr( $color ); ?>;"><?php echo esc_html( $card->expiry ); ?> (<?php echo esc_html( $status ); ?>)</span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>

		<p style="margin:12px 0 0;text-align:right;">
			<a href="<?php echo esc_url( $analytics_url ); ?>">View full analytics &rarr;</a>
		</p>
		<?php
	}
}
