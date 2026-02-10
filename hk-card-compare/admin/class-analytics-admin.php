<?php
/**
 * Analytics dashboard admin page.
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

	/**
	 * Render the analytics page.
	 */
	public static function render_page() {
		$days       = isset( $_GET['days'] ) ? absint( $_GET['days'] ) : 30;
		$source_url = isset( $_GET['source'] ) ? sanitize_text_field( $_GET['source'] ) : '';

		$top_cards   = HKCC_Click_Tracker::top_cards( $days, $source_url );
		$top_sources = HKCC_Click_Tracker::top_sources( $days );
		$recent      = HKCC_Click_Tracker::recent( 100 );

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

				<?php if ( $source_url ) : ?>
					&nbsp;&nbsp;
					<strong>Filtered by source:</strong> <?php echo esc_html( $source_url ); ?>
					<a href="<?php echo esc_url( add_query_arg( 'days', $days, $base_url ) ); ?>" class="button button-small">Clear filter</a>
				<?php endif; ?>
			</div>

			<!-- Top Cards -->
			<h2>Top Cards by Clicks</h2>
			<?php if ( empty( $top_cards ) ) : ?>
				<p>No click data found for the selected period.</p>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr><th>Card Name</th><th>Clicks</th><th>Click Rate</th></tr>
					</thead>
					<tbody>
						<?php foreach ( $top_cards as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row->post_title ); ?></td>
								<td><?php echo esc_html( number_format( $row->click_count ) ); ?></td>
								<td><?php echo esc_html( $row->click_rate ); ?>%</td>
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
								<td>
									<a href="<?php echo esc_url( add_query_arg( array( 'days' => $days, 'source' => $row->source_url ), $base_url ) ); ?>">
										<?php echo esc_html( $row->source_url ); ?>
									</a>
								</td>
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
						<tr><th>Time</th><th>Card</th><th>Source Page</th></tr>
					</thead>
					<tbody>
						<?php foreach ( $recent as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row->clicked_at ); ?></td>
								<td><?php echo esc_html( $row->post_title ); ?></td>
								<td><?php echo esc_html( $row->source_url ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}
