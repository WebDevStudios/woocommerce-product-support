<?php
/**
 * WooCommerce Product Support User Forums Shortcode.

 * @package WooCommerce Product Support User Forums Shortcode.
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
function wds_wcps_user_product_support_forum_list_shortcode( $args = array() ) {
	$args = wp_parse_args( array(
		'user_id' => get_current_user_id(),
		'title'   => '',
	), $args );

	$list = '';

	if ( ! empty( $args['title'] ) ) {
		/**
		 * Filters the html header type to use for the user forum list title.
		 *
		 * @since 2.1.0
		 *
		 * @param string $value HTML to use for header. Default `<h3>`.
		 */
		$heading = apply_filters( 'wds_woo_user_forum_list_shortcode_title_html', 'h3' );
		$list .= sprintf(
			'<%s>%s</%s>',
			$heading,
			$args['title'],
			$heading
		);
	}

	$list .= wds_wcps_get_user_product_support_forum_list( $args['user_id'] );

	return $list;
}
add_shortcode( 'woo_user_product_support_forum_list', 'wds_wcps_user_product_support_forum_list_shortcode' );
