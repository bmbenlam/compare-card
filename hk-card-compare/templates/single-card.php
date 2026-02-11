<?php
/**
 * Template: Single Card page.
 *
 * Shows block editor content at top, then renders card details below.
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
	$card_name = HKCC_Card_Display::get_card_name( get_post() );
	$tagline   = get_post_meta( $post_id, 'tagline', true );
	$aff_link  = get_post_meta( $post_id, 'affiliate_link', true );

	// Card face image (no fallback to featured image).
	$card_face_id = (int) get_post_meta( $post_id, 'card_face_image', true );
	?>

	<article id="card-<?php echo esc_attr( $post_id ); ?>" class="hkcc-single-card">

		<header class="hkcc-single-header">
			<?php if ( $card_face_id ) : ?>
				<div class="hkcc-single-image">
					<?php echo wp_get_attachment_image( $card_face_id, 'card-thumb', false, array( 'alt' => esc_attr( $card_name ) ) ); ?>
				</div>
			<?php endif; ?>

			<h1 class="hkcc-single-title"><?php echo esc_html( $card_name ); ?></h1>

			<?php if ( $tagline ) : ?>
				<p class="hkcc-single-tagline"><?php echo esc_html( $tagline ); ?></p>
			<?php endif; ?>
		</header>

		<div class="hkcc-single-body">
			<?php
			// Block editor content.
			$content = get_the_content();
			if ( $content ) {
				echo '<div class="entry-content">';
				the_content();
				echo '</div>';
			}
			?>

			<div class="hkcc-single-card-details">
				<?php HKCC_Card_Display::render_single_card_details( get_post() ); ?>
			</div>
		</div>

		<footer class="hkcc-single-footer">
			<?php if ( $aff_link ) : ?>
				<a href="<?php echo esc_url( $aff_link ); ?>" class="hkcc-btn hkcc-btn-cta card-apply-link" data-card-id="<?php echo esc_attr( $post_id ); ?>" target="_blank" rel="noopener nofollow">立即申請 &rarr;</a>
			<?php endif; ?>
		</footer>

	</article>

<?php endwhile;

get_footer();
