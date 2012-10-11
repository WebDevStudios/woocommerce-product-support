<?php
/*
Plugin Name: WooCommerce Product Support
Plugin URI: http://webdevstudios.com
Description: This extension adds BuddyPress Groups or bbPress integration to WooCommerce. This plugin allows you to associate a product with a group/forum (or create a new one), and automatically add a user to that group when they purchase the product. Visit WooCommerce > Settings > Integration to configure the default first support topic.
Author: WebDevStudios
Version: 1.0
Author URI: http://webdevstudios.com
*/

/**
 * Required functions
 **/
if ( ! function_exists( 'is_woocommerce_active' ) ) require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
if ( is_admin() ) {
	if ( ! class_exists( 'WooThemes_Plugin_Updater' ) )
		require_once 'woo-includes/class-woothemes-plugin-updater.php';

	$woo_plugin_updater_stamps_com = new WooThemes_Plugin_Updater( __FILE__ );
	$woo_plugin_updater_stamps_com->api_key = 'WOO-122141';
	$woo_plugin_updater_stamps_com->init();
}

// We need to wait until all plugins are loaded before we can continue so everything works right
add_action( 'plugins_loaded', 'wds_wcps_init', 0 );

function wds_wcps_init() {

	// Stop here if WooCommerce isn't present
	if ( ! class_exists( 'WC_Settings_API' ) )
		return false;

	/**
	 * Integrate our plugin with the WooCommerce Settings API
	 *
	 * @class 		WC_Product_Support
	 * @extends		WC_Settings_API
	 */
	class WC_Product_Support extends WC_Settings_API {

		/**
		 * Initialize all our checks and integration points
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {

			// Run our system checks
			$this->check_requirements();

			// Setup our extension name and description
			$this->id					= 'buddypress';
			$this->method_title     	= __( 'Product Support', 'wcps' );
			$this->method_description	= __( 'This extension allows you to associate Products with either BuddyPress or bbPress forums.<br/>Below you can specify the default title and content for an optional first topic.', 'wcps' );
			$this->method_description	.= $this->requirement_notice();

			// Load the form fields.
			$this->init_form_fields();

			// Load the settings.
			$this->init_settings();

			// Define user set variables
			$this->bp_topic_title		= $this->settings['bp_topic_title'];
			$this->bp_topic_text		= $this->settings['bp_topic_text'];

			// Only run our actions if we pass requiremets
			if ( $this->check_requirements() ) {
				add_action( 'woocommerce_update_options_integration_buddypress', array( &$this, 'process_admin_options' ) );
				add_action( 'admin_init', array( &$this, 'register_metaboxes' ) );
				add_action( 'publish_product', array( &$this, 'publish_product' ) );
				add_action( 'woocommerce_order_status_completed', array( &$this, 'bp_add_user_to_group' ) );
			} else {
				// Otherwise, display an admin error message
				add_action( 'admin_notices', array( &$this, 'woocommerce_error' ) );
			}
		}

		/**
		 * Check for required plugin dependencies
		 *
		 * @access public
		 * @return string Status messages reporting whether necesarry components are installed/enabled
		 */
		public function check_requirements() {

			// Assume neither BuddyPress nor bbPress are running
			$this->use_buddypress = false;
			$this->use_bbpress = false;

			// Check if BuddyPress and BuddyPress is available
			if ( class_exists( 'BuddyPress' ) && class_exists( 'BP_Groups_Group' ) && class_exists( 'BP_Forums_Component' ) ) {
				$this->use_buddypress = true;
				$this->use_bbpress = false;
			}

			// Check if bbPress is available, and if so use it instead of BuddyPress
			if ( class_exists( 'BBP_Component' ) ) {
				$this->use_bbpress = true;
				$this->use_buddypress = false;
			}

			// If neither BuddyPress nor bBPress are available, return false
			if ( ! $this->use_buddypress && ! $this->use_bbpress )
				return false;

			// Otherwise, we pass requirements
			return true;

		}

		/**
		 * Output a notice of required plugins
		 *
		 * @return string The concatenated list of requirements
		 */
		public function requirement_notice() {

			// Setup our message types
			$disabled = '<span style="color:red;">' . __( 'Not Enabled', 'wcps' ) . '</span>';
			$enabled = '<span style="color:green;"><strong>' . __( 'Enabled', 'wcps' ) . '</strong></span>';
			$not_used = '<span style="color:gray;"><em>' . __( 'Not Used', 'wcps' ) . '</em></span>';

			// Check for required components
			$bp = $this->use_buddypress ? $enabled : ( $this->use_bbpress ? $not_used : $disabled );
			$bbp = $this->use_bbpress ? $enabled : ( $this->use_buddypress ? $not_used : $disabled );

			// Concatenate our output
			$requirements = '';
			$requirements .= '<p>' . __( 'This extension requires at least one of the following plugins:', 'wcps' ) . '<br/>';
			$requirements .= '<strong>' . __( 'BuddyPress Group Forums:', 'wcps' ) . '</strong> ' . $bp . '<br/>';
			$requirements .= '<strong>' . __( 'bbPress Forums:', 'wcps' ) . '</strong> ' . $bbp;
			$requirements .= '</p>';

			// Return our notice
			return $requirements;
		}

		public function woocommerce_error() {
			// Include thickbox support
			add_thickbox();

			// Generate our error message
			$output = '<div id="message" class="error">';
			$output .= sprintf( __( '<p>WooCommerce Product Support requires either <a href="%s" target="_blank" class="thickbox onclick">BuddyPress</a> with <a href="%s">User Groups and Discussion Forums enabled</a> <em>-OR-</em> <a href="%s" target="_blank" class="thickbox onclick">bbPress</a>. Please install and activate <em>one</em> of these plugins.</p>', 'wcps' ), admin_url( '/plugin-install.php?tab=plugin-information&plugin=BuddyPress&TB_iframe=true&width=600&height=550' ), admin_url( '/admin.php?page=bp-components' ), admin_url( '/plugin-install.php?tab=plugin-information&plugin=bbpress&TB_iframe=true&width=600&height=550' ) );
			$output .= '</div>';
			echo $output;
		}

		/**
		 * Initialise our Settings Form Fields
		 *
		 * @access public
		 * @return void
		 */
		public function init_form_fields() {
			$this->form_fields = array(
			'bp_topic_title' => array(
				'title'		=> __( 'Topic Title', 'wcps' ),
				'type'		=> 'text',
				'css'		=> 'width:450px;',
				'default'	=> __( '[IMPORTANT] %product_title% Support Guidelines', 'wcps' ),
				'description'=> '<br/>'.__( 'Note: you can use %product_title% to output the product title.', 'wcps' )
				),
			'bp_topic_text' => array(
				'title'		=> __( 'Topic Content', 'wcps' ),
				'type'		=> 'textarea',
				'css'		=> 'width:450px; height:250px;',
				'default'	=> sprintf( __('Welcome to the %%product_title%% support forum!

<strong>To expedite your help requests,</strong> please include the version numbers you\'re currently running for %%product_title%% and for WordPress, along with the URL of the website in question. This helps us to research and test and provide faster support.

<strong>Please do <em>not</em> post</strong> your username, password, licenses or any other personal or sensitive information.

Thank you!
-The %s Team', 'wcps' ), get_bloginfo('name') ),
				'description'=> '<br/>'.__( 'Note: you can use %product_title% to output the product title.', 'wcps' )
				)
			);
		} // End init_form_fields()

		/**
		 * Admin Options within WooCommerce Integration settings
		 *
		 * @access public
		 * @return void
		 */
		public function admin_options() { ?>

			<h3><?php echo isset( $this->method_title ) ? $this->method_title : __( 'Settings', 'wcps' ) ; ?></h3>

			<?php echo isset( $this->method_description ) ? wpautop( $this->method_description ) : ''; ?>

			<table class="form-table">
				<?php $this->generate_settings_html(); ?>
			</table>

			<!-- Section -->
			<div><input type="hidden" name="section" value="<?php echo $this->id; ?>" /></div>

			<?php
		}

		/**
		 * Adds metabox to product editor so a group/forum can be created or existing selected
		 *
		 * @access public
		 * @return void
		 */
		public function register_metaboxes() {
			add_meta_box( 'support_metabox', __( 'Product Support', 'wcps' ), array( &$this, 'support_metabox'), 'product', 'side', 'default' );
		}

		/**
		 * Output for our support metabox
		 * @param object $post The post object
		 * @return void
		 */
		public function support_metabox( $post ) {

			// Determine whether we should say "group" or "forum"
			$this->group_or_forum = $this->use_buddypress ? __( 'group', 'wcps' ) : __( 'forum', 'wcps' );

			// Grab our current selections
			$enable_support = get_post_meta( $post->ID, '_enable_support', true );
			$current_selection = get_post_meta( $post->ID, '_support_group', true );

			// Concatenate our output
			$output = '';
			$output .= '<p><label for="enable_support"><input type="checkbox" id="enable_support" name="enable_support" value="true" ' . checked( $enable_support, 'true', false ) . '> '. __( 'Enable support for this product', 'wcps' ) . '</label></p>';
			$output .= '<p><label for="support_group">' . sprintf( __( 'Use this %s:', 'wcps' ), $this->group_or_forum ) . '</label> ';
			$output .= '<select name="support_group" id="support_group">';
			$output .= '<option value="">' . sprintf( __( 'Create new %s', 'wcps' ), $this->group_or_forum ) . '</option>';

			if ( $this->use_buddypress ) {
				// Loop through all existing BP groups and include them here
				$bp_groups = groups_get_groups( array( 'show_hidden' => true ) );
				foreach ( $bp_groups['groups'] as $group ) {
					$output .= '<option' . selected( $current_selection, $group->id, false ) . ' value="' . $group->id . '">' . $group->name . '</option>';
				}
			} elseif ( $this->use_bbpress ) {

				// Grab our current post object and store it for safe-keeping (necessary because wp_reset_postdata() doesn't work in this case)
				global $post;
				$temp_post = $post;

				// Loop through all existing bbPress forums and include theme here
				if ( bbp_has_forums() ) : while ( bbp_forums() ) : bbp_the_forum();
					$output .= '<option' . selected( $current_selection, bbp_get_forum_id(), false ) . ' value="' . bbp_get_forum_id() . '">' . bbp_get_forum_title() . '</option>';
				endwhile; endif;

				// Restore our original post object
				$post = $temp_post;

			}

			$output .= '</select></p>';

			// If we're on the new post screen, or the enable support has never been set, include an option for creating the first post
			// global $pagenow;
			if ( !isset( $enable_support ) || false == $enable_support )
				$output .= '<p><label for="create_first_post"><input type="checkbox" id="create_first_post" name="create_first_post" value="true"> '. sprintf( __( 'Create first topic using <a href="%s" target="_blank">default setings</a>.', 'wcps' ), admin_url('admin.php?page=woocommerce_settings&tab=integration&section=buddypress') ) . '</label></p>';

			// Echo our output
			echo $output;
		}

		/**
		 * Action that fires when a product is published
		 *
		 * @param  int $post_id The post ID we're using
		 * @return void
		 */
		public function publish_product( $post_id ) {

			// If this is just an autosave, bail here
			if ( wp_is_post_autosave( $post_id ) )
				return;

			// Grab our support variables
			$enable_support = isset( $_POST['enable_support'] ) ? $_POST['enable_support'] : false;
			$support_group = ( $enable_support && isset( $_POST['support_group'] ) ) ? $_POST['support_group'] : false;
			$create_first_post = isset( $_POST['create_first_post'] ) ? $_POST['create_first_post'] : false;

			// If we've enabled support, and the selected support group is empty, create a new group
			if ( $enable_support && empty( $support_group ) ) {
				if ( $this->use_buddypress ) {
					$support_group = $this->bp_create_group( $post_id, $create_first_post );
				} elseif ( $this->use_bbpress ) {
					$support_group = $this->bbp_create_forum( $post_id, $create_first_post );
				}
			}


			// Save our post meta
			update_post_meta( $post_id, '_enable_support', strip_tags( $enable_support ) );
			update_post_meta( $post_id, '_support_group', strip_tags( $support_group ) );

		}

		/**
		 * Create a BuddyPress group on product creation and adds all admins as group members
		 *
		 * @access public
		 * @return int The created group ID
		 */
		public function bp_create_group( $post_id, $create_first_post = false ) {

			// Get the product title
			$product_title = get_the_title( $post_id );

			// Get the product slug
			$product_slug = basename(get_permalink( $post_id) );

			// See if we already have a corresponding BP Group
			$group_id = BP_Groups_Group::group_exists( $product_slug );

			// If a group doesn't already exist
			if ( ! $group_id ) {

				// Create our group
				$group_id = groups_create_group(
					array(
						'creator_id'	=> bp_loggedin_user_id(),
						'name'			=> $product_title,
						'slug'			=> $product_slug,
						'description'	=> sprintf( __( 'This is the support group for %s', 'wcps' ), $product_title ),
						'status'		=> 'hidden',
						'date_created'	=> bp_core_current_time(),
						'enable_forum'	=> true
					)
				);

				// Update the member count and last activity
				groups_update_groupmeta( absint( $group_id ), 'total_member_count', 1 );
				groups_update_groupmeta( absint( $group_id ), 'last_activity', bp_core_current_time() );
				groups_update_groupmeta( absint( $group_id ), 'invite_status', 'admins' );

				// Create group forum
				groups_new_group_forum( absint( $group_id ), $product_title, $group_description );

				// Get all our current admins
				$wp_user_search = new WP_User_Query( array( 'role' => 'administrator' ) );
				$admins = $wp_user_search->get_results();

				// Add our admin users as members to this group
				foreach ( $admins as $admin ) {
					groups_join_group( $group_id, $admin->ID );
					groups_promote_member( $admin->ID, $group_id, 'admin' );
				}

				// If the option is set to autocreate our first topic, let's create it
				if ( $create_first_post ) {

					// Create the topic with our title and text settings
					$topic_id = bp_forums_new_topic(
						array(
							'forum_id'		=> groups_get_groupmeta( absint( $group_id ), 'forum_id' ),
							'topic_title'	=> preg_replace( '/%product_title%/', $product_title, $this->bp_topic_title ),
							'topic_text'	=> preg_replace( '/%product_title%/', $product_title, $this->bp_topic_text ),
							'topic_open'	=> 0 // Closed so no one can reply
						)
					);

					// Make this first topic sticky
					bb_stick_topic( $topic_id );

				}

			}

			// Return our newly created group ID
			return $group_id;

		}

		/**
		 * Create a bbPress forum on product creation
		 *
		 * @access public
		 * @return int The created forum ID
		 */
		public function bbp_create_forum( $post_id, $create_first_post = false ) {

			// Get the product title
			$product_title = get_the_title( $post_id );

			// Setup our default forum data
			$forum_data = array(
				'post_parent'		=> 0, // forum ID
				'post_status'		=> bbp_get_private_status_id(),
				'post_type'			=> bbp_get_forum_post_type(),
				'post_author'		=> bbp_get_current_user_id(),
				'post_password'		=> '',
				'post_content'		=> '',
				'post_title'		=> get_the_title( $post_id ),
				'menu_order'		=> 0,
				'comment_status'	=> 'closed'
			);

			// Create our forum and grab its ID
			$forum_id = bbp_insert_forum( $forum_data );

			// If the option is set to autocreate our first topic, let's create it
			if ( $forum_id && $create_first_post ) {

				// Create a closed topic with our title and text settings
				$topic_data = array(
					'post_parent'    => $forum_id,
					'post_status'    => bbp_get_closed_status_id(),
					'post_type'      => bbp_get_topic_post_type(),
					'post_author'    => bbp_get_current_user_id(),
					'post_password'  => '',
					'post_content'   => preg_replace( '/%product_title%/', $product_title, $this->bp_topic_text ),
					'post_title'     => preg_replace( '/%product_title%/', $product_title, $this->bp_topic_title ),
					'comment_status' => 'closed',
					'menu_order'     => 0,
				);
				$topic_id = bbp_insert_topic( $topic_data, array( 'forum_id' => $forum_id ) );

				// Make our new topic sticky
				bbp_stick_topic( $topic_id );

			}

			// Return our new forum ID
			return $forum_id;
		}



		/**
		 * Add a user to the corresponding BuddyPress group on product creation
		 *
		 * @access public
		 * @return void
		 */
		public function bp_add_user_to_group( $order_id ) {

			// If we're not using BuddyPress, we can skip the rest
			if ( ! $this->use_buddypress )
				return;

			// Get the user's ID
			$user_id = get_post_meta( absint( $order_id ), '_customer_user', true );

			// Get the purchased product ID(s)
			$products = get_post_meta( absint( $order_id ), '_order_items', true );

			// Loop through each product and add user to corresponding BP group
			foreach ( $products as $product ) {

				// Get the ID of the support forum associated with the product
				$product_support = get_post_meta( $product['id'], '_support_group', true ) ? get_post_meta( $product['id'], '_support_group', true ) : false;

				// If the product has enabled support, add the user to it's group
				if ( $product_support )
					groups_join_group( $product_support, $user_id );

			}
		}

	}
}

/**
 * Add the integration to WooCommerce.
 *
 * @param array $integrations
 * @return array
 */
add_filter( 'woocommerce_integrations', 'wds_wcps_integration' );

function wds_wcps_integration( $integrations ) {

	$integrations[] = 'WC_Product_Support';

	return $integrations;

}

/**
 * Initialize Localization
 */
add_action('plugins_loaded', 'wds_wcps_localization');
function wds_wcps_localization() {
	load_plugin_textdomain( 'wcps', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
