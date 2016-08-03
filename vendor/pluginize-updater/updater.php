<?php
/**
 * Pluginize.com License Loader.
 *
 * @package Pluginize Product License Menu
 * @author Pluginize Team
 * @copyright WebDevStudios
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once( dirname( __FILE__ ) . '/class-pluginize-product.php' );
include_once( dirname( __FILE__ ) . '/class-pluginize-product-api.php' );
include_once( dirname( __FILE__ ) . '/class-pluginize-product-license-menu.php' );

if ( ! function_exists( 'pluginize_plugin_woo_product_support' ) ) {
	/**
	 * Fire it up.
	 *
	 * @since 1.0.0
	 */
	function pluginize_plugin_woo_product_support() {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		// Won't double add, if existing already.
		add_option( 'pluginize_woo_product_support_settings', array() );
		add_option( 'pluginize_woo_product_support_instance', '' );

		// Needs to fetch saved values from options.
		// All values are demo.
		// Should probably get its own method.
		// Will need to be changed to match value provided below.
		$pluginize_options                = get_option( 'pluginize_woo_product_support_settings', array() );
		$instance                         = get_option( 'pluginize_woo_product_support_instance', '' );
		$details['email']                 = ( ! empty( $pluginize_options['pluginize_email'] ) ) ? $pluginize_options['pluginize_email'] : '';
		$details['license_key']           = ( ! empty( $pluginize_options['pluginize_api_key'] ) ) ? $pluginize_options['pluginize_api_key'] : '';
		$details['product_id']            = 'WooCommerce Product Support';
		$details['product_slug']          = 'WooCommerce-Product-Support';
		$details['platform']              = str_ireplace( array( 'http://', 'https://' ), '', home_url() );
		$details['instance']              = $instance;
		$details['software_version']      = '2.0.2';
		$details['upgrade_url']           = 'http://pluginize.com/';
		$details['changelog_restapi_url'] = '';
		$details['plugin_name']           = plugin_basename( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/woocommerce-product-support.php';
		$details['menu_page']             = array(
			'parent_slug'    => 'options-general.php',
			'page_title'     => esc_html__( 'WooCommerce Product Support License', 'wcps' ),
			'menu_title'     => esc_html__( 'WooCommerce Product Support License', 'wcps' ),
			'menu_slug'      => 'pluginize-woo-product-support-license',
			'management_tab' => esc_html__( 'License Management', 'wcps' ),
			'button_text'    => esc_attr__( 'Save Changes', 'wcps' ),
		);
		$details['option_group']          = 'pluginize';
		$details['option_name']           = 'pluginize_woo_product_support_settings';
		$details['instance_name']         = 'pluginize_woo_product_support_instance';
		$details['api_errors_key']        = 'pluginize_api_key_errors';

		$product = new Pluginize_Product( $details );

		// Check on our status.
		$api = new Pluginize_Product_API( $product );
		$api->do_hooks();

		// Set up our menu based on the information above.
		$menu_setup = new Pluginize_Product_License_menu( $product, $api );
		$menu_setup->do_hooks();
	}
	add_action( 'init', 'pluginize_plugin_woo_product_support' );
}
