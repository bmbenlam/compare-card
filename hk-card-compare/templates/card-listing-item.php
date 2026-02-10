<?php
/**
 * Template part: Card listing item.
 *
 * Used by the [cc_comparison] shortcode to render each card.
 * Variables expected: $card (WP_Post), $view (string).
 *
 * This template can be overridden by copying it to
 * yourtheme/hk-card-compare/card-listing-item.php
 *
 * @package HK_Card_Compare
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Fallback if called directly.
if ( ! isset( $card ) || ! isset( $view ) ) {
	return;
}

HKCC_Card_Display::render_listing_card( $card, $view );
