<?php
/**
 * WooCommerce Product Support Forum Link Shortcode.
 *
 * @package WooCommerce Product Support Forum Link Shortcode.
 * @author  WebDevStudios.
 * @since   2.1.0
 */

/**
 * Register our product support shortcode.
 *
 * @since 2.1.0
 *
 * @param array $args Shortcode attribute args.
 * @return string $value Available forums list.
 */
function wds_wcps_product_support_forum_link_shortcode( $args = array() ) {
	$args = wp_parse_args( array(
		'product_id' => 0,
	), $args );

	$list = '';

	if ( $args['product_id'] ) {
		$list .= wds_get_the_forum_support_link( $args['product_id'] );
	}

	return $list;
}
add_shortcode( 'woo_product_support_forum_link', 'wds_wcps_product_support_forum_link_shortcode' );
