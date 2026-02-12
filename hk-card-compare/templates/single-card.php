<?php
/**
 * Template: Single Card page.
 *
 * Shows the complete card preview (collapsed view with tagline, cardface,
 * 4-packs, welcome offer + expanded details) as a self-contained card.
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
	?>

	<article id="card-<?php echo esc_attr( $post_id ); ?>" class="hkcc-single-card">

		<!-- Full card preview (collapsed view: tagline, cardface, 4-packs, welcome + expanded details) -->
		<div class="hkcc-single-card-preview">
			<?php HKCC_Card_Display::render_listing_card( $card, 'cash' ); ?>
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
