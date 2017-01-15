<?php
/**
 * WooCommerce Product Support User Forums Widget.
 *
 * @package WooCommerce Product Support User Forums Widget.
 * @author  WebDevStudios.
 * @since   2.1.0
 */

/**
 * Create our user forums list widget.
 *
 * @since 2.1.0
 */
class Woo_User_Product_Support_Forum_List extends WP_Widget {

	/**
	 * Start things off.
	 */
	public function __construct() {
		$widget_ops = array(
			'description' => esc_html__( 'WooCommerce User\'s Product Support Links', 'wcps' ),
		);
		parent::__construct( 'wds_product_support', esc_html__( "WooCommerce's Product Support", 'wcps' ), $widget_ops );
	}

	/**
	 * Widget form method.
	 *
	 * @since 2.1.0
	 *
	 * @param array $instance Widget instance.
	 * @return void
	 */
	public function form( $instance = array() ) {
		$defaults = array(
			'title' => esc_attr__( 'Available product support forums', 'wcps' ),
		);
		$instance = wp_parse_args( (array) $instance, $defaults );

		$title = trim( strip_tags( $instance['title'] ) );

		$this->form_input(
			array(
				'label' => esc_attr__( 'Title:', 'wcps' ),
				'name'  => $this->get_field_name( 'title' ),
				'id'    => $this->get_field_id( 'title' ),
				'type'  => 'text',
				'value' => $title,
			)
		);
	}

	/**
	 * Widget output method.
	 *
	 * @since 2.1.0
	 *
	 * @param array $args Array of widget args.
	 * @param array $instance Widget instance.
	 * @return void
	 */
	public function widget( $args = array(), $instance = array() ) {

		$title = trim( strip_tags( $instance['title'] ) );

		echo $args['before_widget'];

		if ( ! empty( $title ) ) {

			/**
			 * Filters the Woo User's Product Support widget title.
			 *
			 * @since 2.1.0
			 *
			 * @param string $title    The widget title.
			 * @param array  $instance The settings for the particular instance of the widget.
			 * @param string $id_base  Root ID for all widgets of this type.
			 */
			$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );
			echo $args['before_title'] . $title . $args['after_title'];
		}

		$output = wds_wcps_get_user_product_support_forum_list( get_current_user_id() );

		if ( empty( $output ) ) {
			echo '<p>' . esc_html__( 'No support forums available', 'wcps' ) . '</p>';
		} else {

			/**
			 * Filters a blank value for users to provide custom output for widget.
			 *
			 * If result is empty, default `$output` will be used instead.
			 *
			 * @param string $value  Empty default value.
			 * @param string $output Default output tot be shown.
			 * @param int    $value  Current user ID.
			 */
			$custom_output = apply_filters( 'wds_wcps_user_forum_list_widget', '', $output, get_current_user_id() );

			echo ( ! empty( $custom_output ) ) ? $custom_output : $output;
		}

		echo $args['after_widget'];
	}

	/**
	 * Widget update method.
	 *
	 * @since 2.1.0
	 *
	 * @param array $new_instance New values for widget.
	 * @param array $old_instance Old values for widget.
	 * @return array
	 */
	function update( $new_instance = array(), $old_instance = array() ) {
		$instance          = $old_instance;
		$instance['title'] = trim( strip_tags( $new_instance['title'] ) );

		return $instance;
	}

	/**
	 * Render a form input for use in our form input.
	 *
	 * @since 2.1.0
	 *
	 * @param array $args Array of argus to use with the markup.
	 */
	function form_input( $args = array() ) {
		$label = esc_attr( $args['label'] );
		$name  = esc_attr( $args['name'] );
		$id    = esc_attr( $args['id'] );
		$type  = esc_attr( $args['type'] );
		$value = esc_attr( $args['value'] );

		printf(
			'<p><label for="%s">%s</label><input type="%s" class="widefat" name="%s" id="%s" value="%s" /></p>',
			$id,
			$label,
			$type,
			$name,
			$id,
			$value
		);
	}
}
