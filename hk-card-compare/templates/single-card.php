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

	$bank_terms    = get_the_terms( $post_id, 'card_bank' );
	$network_terms = get_the_terms( $post_id, 'card_network' );
	$bank_name     = ( $bank_terms && ! is_wp_error( $bank_terms ) ) ? $bank_terms[0]->name : '';
	$network_name  = ( $network_terms && ! is_wp_error( $network_terms ) ) ? $network_terms[0]->name : '';
	?>

	<article id="card-<?php echo esc_attr( $post_id ); ?>" class="hkcc-single-card">

		<header class="hkcc-single-header">
			<?php if ( has_post_thumbnail() ) : ?>
				<div class="hkcc-single-image">
					<?php the_post_thumbnail( 'card-thumb', array( 'alt' => esc_attr( $card_name ) ) ); ?>
				</div>
			<?php endif; ?>

			<h1 class="hkcc-single-title"><?php echo esc_html( $card_name ); ?></h1>

			<?php if ( $tagline ) : ?>
				<p class="hkcc-single-tagline"><?php echo esc_html( $tagline ); ?></p>
			<?php endif; ?>

			<div class="hkcc-single-meta">
				<?php if ( $bank_name ) : ?>
					<span class="hkcc-meta-item"><strong>發卡機構:</strong> <?php echo esc_html( $bank_name ); ?></span>
				<?php endif; ?>
				<?php if ( $network_name ) : ?>
					<span class="hkcc-meta-item"><strong>結算機構:</strong> <?php echo esc_html( $network_name ); ?></span>
				<?php endif; ?>
			</div>
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

			<?php
			// Render expanded card details (same as cc_comparison expanded view).
			?>
			<div class="hkcc-single-card-details">
				<?php HKCC_Card_Display::render_listing_card( get_post(), 'cash' ); ?>
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
