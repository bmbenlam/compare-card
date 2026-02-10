<?php
/**
 * Schema.org FinancialProduct markup and auto-generated meta descriptions.
 *
 * @package HK_Card_Compare
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HKCC_Schema_Output {

	/**
	 * Hook into WordPress.
	 */
	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'output_schema' ) );
		add_action( 'wp_head', array( __CLASS__, 'output_meta_description' ) );
	}

	/**
	 * Output JSON-LD schema on single card pages.
	 */
	public static function output_schema() {
		if ( ! is_singular( 'card' ) ) {
			return;
		}

		$post_id = get_the_ID();
		$bank    = '';
		$network = '';

		$bank_terms = get_the_terms( $post_id, 'card_bank' );
		if ( $bank_terms && ! is_wp_error( $bank_terms ) ) {
			$bank = $bank_terms[0]->name;
		}

		$network_terms = get_the_terms( $post_id, 'card_network' );
		if ( $network_terms && ! is_wp_error( $network_terms ) ) {
			$network = $network_terms[0]->name;
		}

		$schema = array(
			'@context'                       => 'https://schema.org',
			'@type'                          => 'FinancialProduct',
			'name'                           => get_the_title( $post_id ),
			'category'                       => 'CreditCard',
			'provider'                       => array(
				'@type' => 'BankOrCreditUnion',
				'name'  => $bank,
			),
			'image'                          => get_the_post_thumbnail_url( $post_id, 'full' ),
			'url'                            => get_permalink( $post_id ),
			'feesAndCommissionsSpecification' => get_post_meta( $post_id, 'annual_fee_display', true ),
			'brand'                          => $network,
		);

		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}

	/**
	 * Output auto-generated meta description on single card pages.
	 */
	public static function output_meta_description() {
		if ( ! is_singular( 'card' ) ) {
			return;
		}

		$post_id      = get_the_ID();
		$card_name    = get_the_title( $post_id );
		$tagline      = get_post_meta( $post_id, 'tagline', true );
		$annual_fee   = get_post_meta( $post_id, 'annual_fee_display', true );
		$local_rebate = get_post_meta( $post_id, 'local_retail_cash_display', true );

		$bank      = '';
		$bank_terms = get_the_terms( $post_id, 'card_bank' );
		if ( $bank_terms && ! is_wp_error( $bank_terms ) ) {
			$bank = $bank_terms[0]->name;
		}

		$description = sprintf(
			'%s %s - %s。年費：%s，本地簽賬回贈：%s。立即申請！',
			$bank,
			$card_name,
			$tagline,
			$annual_fee,
			$local_rebate
		);

		if ( mb_strlen( $description ) > 160 ) {
			$description = mb_substr( $description, 0, 157 ) . '...';
		}

		echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
	}
}
