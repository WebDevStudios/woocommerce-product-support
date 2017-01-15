<?php
/**
 * WooCommerce Product Support Widgets.
 *
 * @package WooCommerce Product Support Widgets.
 * @author  WebDevStudios.
 * @since   2.1.0
 */

/**
 * Register our widgets.
 *
 * @since 2.1.0
 */
function wds_wcps_register_widgets() {
	register_widget( 'Woo_User_Product_Support_Forum_List' );
}
add_action( 'widgets_init', 'wds_wcps_register_widgets' );

require_once( plugin_dir_path( __FILE__ ) . 'widget-user-forums-list.php' );
