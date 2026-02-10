<?php
/**
 * Template: Single Card page.
 *
 * This template can be overridden by copying it to
 * yourtheme/hk-card-compare/single-card.php
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
	$tagline   = get_post_meta( $post_id, 'tagline', true );
	$aff_link  = get_post_meta( $post_id, 'affiliate_link', true );
	$blog_link = get_post_meta( $post_id, 'blog_post_link', true );

	$bank_terms    = get_the_terms( $post_id, 'card_bank' );
	$network_terms = get_the_terms( $post_id, 'card_network' );
	$bank_name     = ( $bank_terms && ! is_wp_error( $bank_terms ) ) ? $bank_terms[0]->name : '';
	$network_name  = ( $network_terms && ! is_wp_error( $network_terms ) ) ? $network_terms[0]->name : '';
	?>

	<article id="card-<?php echo esc_attr( $post_id ); ?>" class="hkcc-single-card">

		<header class="hkcc-single-header">
			<?php if ( has_post_thumbnail() ) : ?>
				<div class="hkcc-single-image">
					<?php the_post_thumbnail( 'card-thumb', array( 'alt' => esc_attr( get_the_title() ) ) ); ?>
				</div>
			<?php endif; ?>

			<h1 class="hkcc-single-title"><?php the_title(); ?></h1>

			<?php if ( $tagline ) : ?>
				<p class="hkcc-single-tagline"><?php echo esc_html( $tagline ); ?></p>
			<?php endif; ?>

			<div class="hkcc-single-meta">
				<?php if ( $bank_name ) : ?>
					<span class="hkcc-meta-item"><strong>發卡銀行:</strong> <?php echo esc_html( $bank_name ); ?></span>
				<?php endif; ?>
				<?php if ( $network_name ) : ?>
					<span class="hkcc-meta-item"><strong>結算機構:</strong> <?php echo esc_html( $network_name ); ?></span>
				<?php endif; ?>
			</div>
		</header>

		<div class="hkcc-single-body">
			<?php the_content(); ?>

			<?php
			// Render the expanded details reusing the display helper.
			HKCC_Card_Display::render_listing_card( get_post(), 'cash' );
			?>
		</div>

		<footer class="hkcc-single-footer">
			<?php if ( $blog_link ) : ?>
				<a href="<?php echo esc_url( $blog_link ); ?>" class="hkcc-btn hkcc-btn-secondary" target="_blank" rel="noopener">了解更多 &rarr;</a>
			<?php endif; ?>
			<?php if ( $aff_link ) : ?>
				<a href="<?php echo esc_url( $aff_link ); ?>" class="hkcc-btn hkcc-btn-primary card-apply-link" data-card-id="<?php echo esc_attr( $post_id ); ?>" target="_blank" rel="noopener nofollow">Apply Now &rarr;</a>
			<?php endif; ?>
		</footer>

	</article>

<?php endwhile;

get_footer();
