<?php
/**
 * WooCommerce Product Support.
 * @package    Woocommerce Product Support
 * @subpackage bbPress Content Restriction
 * @author     WebDevStudios.
 * @since      1.0.0
 */

/**
 * Check if forum is marked "restricted".
 *
 * @since 2.0.0
 *
 * @param integer $forum_id Forum post ID.
 * @return integer|bool Product ID if access is restricted, otherwise false.
 */
function wds_wcps_is_forum_restricted( $forum_id = 0 ) {

	// Look for a connected product.
	$product_id = absint( get_post_meta( $forum_id, '_wds_wcps_connected_product', true ) );

	// See if product is set to restrict access.
	if ( $product_id && $restricted = get_post_meta( $product_id, '_product_limit_access', true ) ) {
		return $product_id;
	}

	// If not, return false.
	return false;
}

/**
 * Determine if user owns a given product.
 *
 * @since 2.0.0
 *
 * @param integer $user_id    User ID.
 * @param integer $product_id Product post ID.
 * @return bool True if user owns product, otherwise false.
 */
function wds_wcps_user_has_product( $user_id = 0, $product_id = 0 ) {

	if ( class_exists( 'WooCommerce' ) ) {
		add_filter( 'woocommerce_reports_order_statuses', 'wds_wcps_user_has_product_status_filter', 20, 1 );

		$has_product = wc_customer_bought_product( null, $user_id, $product_id );

		remove_filter( 'woocommerce_reports_order_statuses', 'wds_wcps_user_has_product_status_filter' );

		return $has_product;
	}

	return false;
}

/**
 * Filter which statuses are allowed to determine if the current customer has valid products (order=processing,completed).
 *
 * @since 2.0.0
 *
 * @param array $status_list Array of statuses.
 * @return array
 */
function wds_wcps_user_has_product_status_filter( $status_list = array() ) {

	// Force 'processing', 'completed' status, don't allow others.
	$status_list = array(
		'processing',
		'completed',
	);

	return $status_list;

}

/**
 * Hide restricted forum topics.
 *
 * @since 2.0.0
 *
 * @param array $query Topic query.
 * @return array Potentially modified query.
 */
function wds_wcps_filter_bbp_topics_list( $query ) {

	$user_id = bbp_get_current_user_id();

	if ( current_user_can( 'manage_options' ) ) {
		return $query;
	}

	if ( bbp_is_single_forum() ) {

		$restricted = wds_wcps_is_forum_restricted( bbp_get_forum_id() );

		// If this forum is restricted and the user is not logged in nor a product owner.
		if ( $restricted && ( ! is_user_logged_in() || ! wds_wcps_user_has_product( $user_id, $restricted ) ) ) {
			$query['post_type'] = 'NO ACCESS';
		}
	}

	return $query;
}
add_filter( 'bbp_has_topics_query', 'wds_wcps_filter_bbp_topics_list' );

/**
 * Hide topic reply content.
 *
 * @since 2.0.0
 *
 * @param string  $content  Reply content.
 * @param integer $reply_id Reply post ID.
 * @return string Potentially modified reply content.
 */
function wds_wcps_filter_replies( $content, $reply_id ) {
	global $post;

	$user_id = bbp_get_current_user_id();

	if ( current_user_can( 'manage_options' ) ) {
		return $content;
	}

	$restricted_to = wds_wcps_is_forum_restricted( bbp_get_topic_id() );

	if ( ! $restricted_to ) {
		$restricted_to = wds_wcps_is_forum_restricted( bbp_get_forum_id() ); // Check for parent forum restriction.
	}

	if ( $restricted_to && ! wds_wcps_user_has_product( $user_id, $restricted_to ) ) {

		$return = '<div class="wds_wcps_message">' . sprintf(
			esc_html__( 'This content is restricted to owners of %s.', 'wcps' ),
			'<a href="' . get_permalink( $restricted_to ) . '">' . get_the_title( $restricted_to ) . '</a>'
		) . '</div>';

		return $return;

	}

	return $content; // Not restricted.
}
add_filter( 'bbp_get_reply_content', 'wds_wcps_filter_replies', 2, 999 );

/**
 * Hide "new topic" form.
 *
 * @since 2.0.0
 *
 * @param bool $can_access User's current topic access.
 * @return bool User's modified topic access.
 */
function wds_wcps_hide_new_topic_form( $can_access ) {

	$user_id = bbp_get_current_user_id();

	if ( current_user_can( 'manage_options' ) ) {
		return $can_access;
	}

	$restricted_to = wds_wcps_is_forum_restricted( bbp_get_forum_id() ); // Check for parent forum restriction.

	if ( $restricted_to && ! wds_wcps_user_has_product( $user_id, $restricted_to ) ) {
		$can_access = false;
	}
	return $can_access;
}
add_filter( 'bbp_current_user_can_access_create_topic_form', 'wds_wcps_hide_new_topic_form' );

/**
 * Hide "new reply" form.
 *
 * @since 2.0.0
 *
 * @param bool $can_access User's current reply access.
 * @return bool User's modified reply access.
 */
function wds_wcps_hide_new_replies_form( $can_access ) {

	$user_id = bbp_get_current_user_id();

	if ( current_user_can( 'manage_options' ) ) {
		return $can_access;
	}

	$restricted_to = wds_wcps_is_forum_restricted( bbp_get_topic_id() );

	if ( ! $restricted_to ) {
		$restricted_to = wds_wcps_is_forum_restricted( bbp_get_forum_id() ); // Check for parent forum restriction.
	}

	if ( $restricted_to && ! wds_wcps_user_has_product( $user_id, $restricted_to ) ) {
		$can_access = false;
	}
	return $can_access;
}
add_filter( 'bbp_current_user_can_access_create_reply_form', 'wds_wcps_hide_new_replies_form' );
add_filter( 'bbp_current_user_can_access_create_topic_form', 'wds_wcps_hide_new_replies_form' );

/**
 * Apply custom feedback messages on page load.
 *
 * @since 2.0.0
 */
function wds_wcps_apply_feedback_messages() {

	if ( ! function_exists( 'bbpress' ) ) {
		return;
	}

	if ( bbp_is_single_topic() ) {
		add_filter( 'gettext', 'wds_wcps_topic_feedback_messages', 20, 2 );
	} else if ( bbp_is_single_forum() && wds_wcps_is_forum_restricted( bbp_get_forum_id() ) ) {
		add_filter( 'gettext', 'wds_wcps_forum_feedback_messages', 20, 2 );
	}
}
add_action( 'template_redirect', 'wds_wcps_apply_feedback_messages' );

/**
 * Generate custom feedback messages for restricted topics.
 *
 * @since 2.0.0
 *
 * @param string $translated_text Translated content.
 * @param string $text            Original content.
 * @return string Updated content.
 */
function wds_wcps_topic_feedback_messages( $translated_text, $text ) {

	switch ( $text ) {
		case 'You cannot reply to this topic.':
			$translated_text = esc_html__( 'Topic creation is restricted to product owners.', 'wcps' );
			break;
	}
	return $translated_text;
}

/**
 * Generate custom feedback messages for restricted forums.
 *
 * @since 2.0.0
 *
 * @param string $translated_text Translated content.
 * @param string $text            Original content.
 * @return string Updated content.
 */
function wds_wcps_forum_feedback_messages( $translated_text, $text ) {

	switch ( $text ) {
		case 'Oh bother! No topics were found here!':
			$translated_text = esc_html__( 'This forum is restricted to product owners.', 'wcps' );
			break;
		case 'You cannot create new topics at this time.':
			$translated_text = esc_html__( 'Only product owners can create topics.', 'wcps' );
			break;
	}
	return $translated_text;
}
