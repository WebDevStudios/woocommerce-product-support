<?php

define( 'PLUGINIZE_LICENSE_PAGE_WCPS', 'wds_wcps_license_page' );

/**
 * Add our menu item.
 *
 * @since 1.4.0
 */
function wds_wcps_license_menu() {
	add_options_page( __( 'WooCommerce Product Support License', 'wcps' ), __( 'WooCommerce Product Support License', 'wcps' ), 'manage_options', 'wds_wcps_license_page', 'wds_wcps_license_page' );
}
add_action( 'admin_menu', 'wds_wcps_license_menu' );

/**
 * Render our EDD-based license page.
 *
 * @since 1.4.0
 */
function wds_wcps_license_page() {
	$license = get_option( 'wds_wcps_license_key' );
	$status  = get_option( 'wds_wcps_license_status' );
	$active = false;
	?>
	<div class="wrap">
		<h2><?php echo get_admin_page_title(); ?></h2>
		<form method="post" action="options.php">

			<?php settings_fields( 'wds_wcps_license' ); ?>

			<p><?php esc_html_e( 'Thank you for activating your WooCommerce Product Support license.', 'wcps' ); ?></p>
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row" valign="top">
							<?php esc_html_e( 'License Key', 'wcps' ); ?>
						</th>
						<td>
							<input id="wds_wcps_license_key" name="wds_wcps_license_key" type="text" class="regular-text" value="<?php esc_attr_e( $license ); ?>" />
							<label class="description" for="wds_wcps_license_key"><?php esc_html_e( 'Enter your license key', 'wcps' ); ?></label>
						</td>
					</tr>
					<?php if( false !== $license ) {
						$active = ( $status !== false && $status == 'valid' );
						?>
						<tr valign="top">
							<th scope="row" valign="top">
								<?php esc_html_e( 'Activate License', 'wcps' ); ?>
							</th>
							<td>
								<?php wp_nonce_field( 'wds_wcps_license_nonce', 'wds_wcps_license_nonce' ); ?>
								<?php if ( $active ) { ?>
									<input type="submit" class="button-secondary" name="wds_wcps_license_deactivate" value="<?php esc_attr_e( 'Deactivate License', 'wcps' ); ?>"/>
								<?php } else { ?>
									<input type="submit" class="button-secondary" name="wds_wcps_edd_license_activate" value="<?php esc_attr_e( 'Activate License', 'wcps' ); ?>"/>
								<?php } ?>
							</td>
						</tr>
					<?php }

					if ( $active ) { ?>
						<tr valign="top">
							<th scope="row" valign="top">
								<?php esc_html_e( 'Status:', 'wcps' ); ?>
							</th>
							<td>
								<strong style="color:green;"><?php esc_html_e( 'active', 'wcps' ); ?></strong>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
			<?php submit_button(); ?>
		</form>
	<?php
}

/**
 * Register our setting.
 *
 * @since 1.4.0
 */
function wds_wcps_register_option() {
	// Creates our settings in the options table.
	register_setting( 'wds_wcps_license', 'wds_wcps_license_key', 'wds_wcps_sanitize_license' );
}
add_action('admin_init', 'wds_wcps_register_option');

/**
 * Sanitize our license.
 *
 * @since 1.4.0
 *
 * @param string $new License key.
 * @return mixed
 */
function wds_wcps_sanitize_license( $new ) {
	$old = get_option( 'wds_wcps_license_key' );
	if ( $old && $old != $new ) {
		delete_option( 'wds_wcps_license_status' ); // New license has been entered, so must reactivate.
	}
	return $new;
}

/**
 * Activate our license.
 *
 * @since 1.4.0
 */
function wds_wcps_activate_license() {

	if ( empty( $_POST ) || ! isset( $_POST['wds_wcps_edd_license_activate'] ) ) {
		return;
	}

	// Run a quick security check.
 	if ( ! check_admin_referer( 'wds_wcps_license_nonce', 'wds_wcps_license_nonce' ) ) {
 	    return;
	}

	$response = $response = wds_wcps_activate_deactivate( 'activate_license' );

	// Make sure the response came back okay.
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

		if ( is_wp_error( $response ) ) {
			$message = $response->get_error_message();
		} else {
			$message = __( 'An error occurred, please try again.', 'wcps' );
		}

	} else {

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( false === $license_data->success ) {
			switch( $license_data->error ) {

				case 'expired' :
					$message = sprintf(
						__( 'Your license key expired on %s.', 'wcps' ),
						date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
					);
					break;

				case 'revoked' :
					$message = __( 'Your license key has been disabled.', 'wcps' );
					break;

				case 'missing' :
					$message = __( 'Invalid license.', 'wcps' );
					break;

				case 'invalid' :
				case 'site_inactive' :
					$message = __( 'Your license is not active for this URL.', 'wcps' );
					break;

				case 'item_name_mismatch' :
					$message = sprintf( __( 'This appears to be an invalid license key for %s.', 'wcps' ), 'WooCommerce Product Support' );
					break;

				case 'no_activations_left':
					$message = __( 'Your license key has reached its activation limit.', 'wcps' );
					break;

				default :
					$message = __( 'An error occurred, please try again.', 'wcps' );
					break;
			}
		}
	}

	if ( ! empty( $message ) ) {
		$base_url = admin_url( 'admin.php?page=' . PLUGINIZE_LICENSE_PAGE_WCPS );
		$redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

		wp_redirect( $redirect );
		exit();
	}

	update_option( 'wds_wcps_license_status', $license_data->license );
	wp_redirect( admin_url( 'admin.php?page=' . PLUGINIZE_LICENSE_PAGE_WCPS ) );
	exit();
}
add_action( 'admin_init', 'wds_wcps_activate_license' );

/**
 * Deactivate our license.
 *
 * @since 1.4.0
 */
function wds_wcps_deactivate_license() {

	if ( empty( $_POST ) || ! isset( $_POST['wds_wcps_license_deactivate'] ) ) {
		return;
	}

	// Run a quick security check.
    if ( ! check_admin_referer( 'wds_wcps_license_nonce', 'wds_wcps_license_nonce' ) ) {
		return;
	}

	$response = wds_wcps_activate_deactivate( 'deactivate_license' );

	// Make sure the response came back okay.
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		if ( is_wp_error( $response ) ) {
			$message = $response->get_error_message();
		} else {
			$message = __( 'An error occurred, please try again.', 'wcps' );
		}

		$base_url = admin_url( 'plugins.php?page=' . PLUGINIZE_LICENSE_PAGE_WCPS );
		$redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

		wp_redirect( $redirect );
		exit();
	}

	// Decode the license data.
	$license_data = json_decode( wp_remote_retrieve_body( $response ) );

	// $license_data->license will be either "deactivated" or "failed"
	if( $license_data->license == 'deactivated' ) {
		delete_option( 'wds_wcps_license_status' );
	}

	wp_redirect( admin_url( 'admin.php?page=' . PLUGINIZE_LICENSE_PAGE_WCPS ) );
	exit();
}
add_action( 'admin_init', 'wds_wcps_deactivate_license' );

/**
 * Process a license request.
 *
 * @since 1.4.0
 *
 * @param string $action Action being performed. Either deactivate or activate. Default activate.
 * @return array|WP_Error
 */
function wds_wcps_activate_deactivate( $action = 'activate_license' ) {
	// Retrieve the license from the database.
	$license = trim( get_option( 'wds_wcps_license_key' ) );

	// Data to send in our API request.
	$api_params = array(
		'edd_action' => $action,
		'license'    => $license,
		'item_name'  => urlencode( 'Product Support Extension' ), // The name of our product in EDD.
		'url'        => home_url()
	);

	return wp_remote_post( wds_wcps_woocommerce_store_url(), array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
}

/**
 * This is a means of catching errors from the activation method above and displaying it to the customer.
 *
 * @since 1.4.0
 */
function wds_wcps_admin_notices() {
	if ( isset( $_GET['sl_activation'] ) && ! empty( $_GET['message'] ) ) {
		if ( isset( $_GET['page'] ) && PLUGINIZE_LICENSE_PAGE_WCPS === $_GET['page'] ) {
			switch( $_GET['sl_activation'] ) {
				case 'false':
					$message = urldecode( $_GET['message'] );
					?>
					<div class="error">
						<p><?php echo $message; ?></p>
					</div>
					<?php
					break;

				case 'true':
				default:
					break;
			}
		}
	}
}
add_action( 'admin_notices', 'wds_wcps_admin_notices' );
