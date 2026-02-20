<?php
/**
 * Template: Single Card page (preview).
 *
 * Shows the complete card with collapsed + expanded details.
 * Defaults to miles view; includes a toggle to switch views.
 *
 * @package HK_Card_Compare
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$post_id   = get_the_ID();
	$card      = get_post();
	$aff_link  = get_post_meta( $post_id, 'affiliate_link', true );
	$blog_link = get_post_meta( $post_id, 'blog_post_link', true );
	$system_id = (int) get_post_meta( $post_id, 'points_system_id', true );

	// Determine initial view: respect URL param, else miles for points-system cards.
	$view_param   = isset( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : '';
	if ( $view_param && in_array( $view_param, array( 'miles', 'cash' ), true ) ) {
		$default_view = $view_param;
	} else {
		$default_view = ( $system_id > 0 ) ? 'miles' : 'cash';
	}
	$is_miles     = ( 'miles' === $default_view );
	?>

	<article id="card-<?php echo esc_attr( $post_id ); ?>" class="hkcc-single-card">

		<?php if ( $system_id > 0 ) : ?>
		<!-- View toggle for single card preview -->
		<div class="hkcc-single-toggle" style="display:flex;justify-content:center;margin-bottom:16px;">
			<div class="hkcc-view-toggle">
				<span class="hkcc-toggle-option hkcc-toggle-miles<?php echo $is_miles ? ' active' : ''; ?>">飛行里數</span>
				<label class="hkcc-toggle-switch">
					<input type="checkbox" class="hkcc-view-toggle-input hkcc-single-view-toggle" data-card-id="<?php echo esc_attr( $post_id ); ?>" <?php echo $is_miles ? '' : 'checked'; ?> />
					<span class="hkcc-toggle-slider"></span>
				</label>
				<span class="hkcc-toggle-option hkcc-toggle-cash<?php echo $is_miles ? '' : ' active'; ?>">現金回贈</span>
			</div>
		</div>
		<?php endif; ?>

		<!-- Full card preview -->
		<div class="hkcc-single-card-preview" data-view="<?php echo esc_attr( $default_view ); ?>">
			<?php HKCC_Card_Display::render_listing_card( $card, $default_view ); ?>
		</div>

		<!-- Footer actions -->
		<footer class="hkcc-single-footer">
			<?php if ( $blog_link ) : ?>
				<a href="<?php echo esc_url( $blog_link ); ?>" class="hkcc-btn hkcc-btn-secondary">詳細玩法</a>
			<?php endif; ?>
			<?php if ( $aff_link ) : ?>
				<a href="<?php echo esc_url( $aff_link ); ?>" class="hkcc-btn hkcc-btn-cta card-apply-link" data-card-id="<?php echo esc_attr( $post_id ); ?>" target="_blank" rel="noopener nofollow">立即申請 &rarr;</a>
			<?php endif; ?>
		</footer>

	</article>

<?php endwhile;

get_footer();
