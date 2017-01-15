<?php
/**
 * WooCommerce Product Support Template Tags.
 *
 * @package WooCommerce Product Support Template Tags.
 * @author  WebDevStudios.
 * @since   2.1.0
 */

/**
 * Return our raw URL for support forum.
 *
 * @since 2.1.0
 *
 * @param int $product_id Product ID to get support link for.
 * @param int $forum_id   Forum ID to use.
 * @return string $value Suport URL.
 */
function wds_wcps_get_raw_forum_support_link( $product_id = 0, $forum_id = 0 ) {

	if ( empty( $forum_id ) ) {
		// Fetch the forum ID if one is not specified.
		$forum_id = wds_wcps_get_support_forum_ids( $product_id );
	}

	$link = '';

	if ( $forum_id ) {
		if ( bbp_is_forum( $forum_id ) ) {
			$link .= get_permalink( $forum_id );
		}
	}

	return $link;
}

/**
 * Return the forum support link for provided product.
 *
 * @since 2.1.0
 *
 * @param int $product_id Product ID to get support forum for.
 * @return string $value Formatted html link.
 */
function wds_wcps_get_the_forum_support_link( $product_id = 0 ) {
	/*
	 * We are fetching at this point and passing in, so that we can more
	 * readily grab the forum title.
	 */
	$forum_id = wds_wcps_get_support_forum_ids( $product_id );

	if ( $forum_id ) {
		$raw_link = wds_wcps_get_raw_forum_support_link( $product_id, $forum_id );

		return sprintf(
			'<a href="%s">%s</a>',
			$raw_link,
			bbp_get_forum_title( $forum_id )
		);
	}

	return '';
}

/**
 * Echo URL for support forum.
 *
 * @since 2.1.0
 *
 * @param int $product_id Product ID to get support link for.
 */
function wds_wcps_the_forum_support_link( $product_id = 0 ) {
	echo wds_wcps_get_the_forum_support_link( $product_id );
}

/**
 * Return list of forum urls for user's purchased products.
 *
 * @since 2.1.0
 *
 * @param int $user_id User ID to get forum list for.
 * @return string $value Suport URL list.
 */
function wds_wcps_get_user_product_support_forum_list( $user_id = 0 ) {

	$list = '';

	$purchases = wds_wcps_get_customer_orders( $user_id );

	if ( ! $purchases ) {
		return $list;
	}

	$support_forum_list_classes = array_merge(

		/**
		 * Filters custom classes to add to list output.
		 *
		 * @since 2.1.0
		 *
		 * @param array $value Array of custom classes to add to output.
		 */
		apply_filters( 'wds_wcps_user_forum_list_classes', array() ),
		array( 'woocommerce-user-forum-list', 'woocommerce-user-' . $user_id )
	);

	/**
	 * Filters the html list type to use for the user forum list.
	 *
	 * @since 2.1.0
	 *
	 * @param string $value HTML to use for list wrap. Default `<ul>`.
	 */
	$list_type = apply_filters( 'wds_wcps_user_forum_list_html', 'ul' );

	$list = sprintf(
		'<%s class="%s">',
		$list_type,
		implode( ' ', $support_forum_list_classes )
	);

	foreach ( $purchases as $purchase ) {
		$link = wds_wcps_get_the_forum_support_link( $purchase->ID );
		if ( $link ) {
			$list .= sprintf(
				'<li>%s</li>',
				$link
			);
		}
	}

	$list .= sprintf(
		'</%s>',
		$list_type
	);

	/**
	 * Filters the output of the user's product support forum list.
	 *
	 * @since 2.1.0
	 *
	 * @param string $list    Output for the list.
	 * @param int    $user_id Current user ID being rendered.
	 */
	return apply_filters( 'wds_wcps_user_forum_list', $list, $user_id );
}

/**
 * Echo list of forum urls for user's purchased products.
 *
 * @since 2.1.0
 *
 * @param int $user_id User ID to get forum list for.
 */
function wds_wcps_the_user_product_support_forum_list( $user_id = 0 ) {
	echo wds_wcps_get_user_product_support_forum_list( $user_id );
}

/**
 * Query for and return support forum IDs for Woo product.
 *
 * @since 2.1.0
 *
 * @param int $product_id ID of product to get forum ID support for.
 * @return int $value Forum ID.
 */
function wds_wcps_get_support_forum_ids( $product_id = 0 ) {

	$args = array(
		'post_type'              => 'forum',
		'post_status'            => 'publish',
		'posts_per_page'         => -1,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
		'fields'                 => 'ids',
		'meta_query'             => array(
			array(
				'key'   => '_wds_product_support_connected_product',
				'value' => $product_id,
			),
		),
	);
	$forums = new WP_Query( $args );

	if ( ! empty( $forums->posts ) ) {
		// Return just the first one found.
		return $forums->posts[0];
	}

	return 0;
}

/**
 * Retrieve array of bbPress published forums.
 *
 * @since 2.1.0
 * @return array
 */
function wds_wcps_bbp_forum_list() {
	$args = array(
		'post_type' => 'forum',
		'posts_per_page' => -1,
		'post_status' => 'publish',
	);

	$ids = array( 'none' => 'None' );

	$forum_query = new WP_Query( $args );

	if ( $forum_query->have_posts() ) {
		foreach ( $forum_query->posts as $post ) {
			$ids[ $post->ID ] = $post->post_title;
		}
	}

	return $ids;
}

/**
 * Display the current product's available forums.
 *
 * @since 2.1.0
 *
 * @param int $product_id EDD product ID.
 */
function wds_wcps_forum_links_on_product( $product_id = 0 ) {
	$links = wds_wcps_get_the_forum_support_link( $product_id );

	if ( ! empty( $links ) ) {
		$forums = sprintf(
			'<li>%s</li>',
			$links
		);
	}

	if ( ! empty( $forums ) ) {

		$support_forum_list_classes = array_merge(

			/**
			 * Filters custom classes to add to list output.
			 *
			 * @since 2.1.0
			 *
			 * @param array $value Array of custom classes to add to output.
			 */
			apply_filters( 'wds_wcps_product_forum_list_classes', array() ),
			array( 'woocommerce-product-forum-list', 'woocommerce-product-forum-list-after-product', 'wds-forum-list-woocommerce-product-' . $product_id )
		);

		printf(
			'<div class="%s"><h4>%s</h4><ul>%s</ul></div>',
			implode( ' ', $support_forum_list_classes ),
			esc_html__( 'Visit this product\'s support forums', 'wds-product-support' ),
			$forums
		);
	}
}
#add_action( 'edd_after_download_content', 'wds_wcps_forum_links_on_product', 99 );

/**
 * Appends the associated forums for a user's purchase to their receipt.
 *
 * @since 2.1.0
 *
 * @param string $email_body   Email text to be sent.
 * @param int    $payment_id   ID of the payment made.
 * @param array  $payment_data Array of data related to payment.
 * @return string $email_body Amended email text to be sent.
 */
function wds_wcps_forum_links_in_email( $email_body, $payment_id, $payment_data ) {

	$forum_links = '';

	$the_links = wds_wcps_get_user_product_support_forum_list( $payment_data['user_info']['id'] );

	if ( ! empty( $the_links ) ) {
		$forum_links .= sprintf(
			'<h3>%s</h3>%s',
			esc_html__( 'Your available support forums', 'wds-product-support' ),
			$the_links
		);
	}

	$email_body .= $forum_links;

	return $email_body;
}
#add_filter( 'edd_purchase_receipt', 'wds_wcps_forum_links_in_email', 10, 3 );

/**
 * Grab a user's ordered products.
 *
 * @since 2.1.0
 *
 * @param int $user_id User ID to fetch.
 * @return array
 */
function wds_wcps_get_customer_orders( $user_id = 0 ) {

	// Get all customer orders.
	$customer_orders = get_posts( array(
		'numberposts' => -1,
		'meta_key'    => '_customer_user',
		'meta_value'  => $user_id,
		'post_type'   => wc_get_order_types(),
		'post_status' => array_keys( wc_get_order_statuses() ),
	) );

	if ( ! is_wp_error( $customer_orders ) ) {
		return $customer_orders;
	}

	return array();
}
