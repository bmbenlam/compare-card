<?php
/**
 * Template: Full comparison page.
 *
 * This template can be overridden by copying it to
 * yourtheme/hk-card-compare/card-comparison.php
 *
 * It renders the [cc_comparison] shortcode output as a full-page template.
 *
 * @package HK_Card_Compare
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<div class="hkcc-comparison-page">
	<h1 class="hkcc-page-title"><?php the_title(); ?></h1>
	<div class="hkcc-page-content">
		<?php the_content(); ?>
	</div>
</div>

<?php
get_footer();
