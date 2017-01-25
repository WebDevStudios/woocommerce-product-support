jQuery(document).ready(function($) {
	$("#product_forum").change(function () {
		// Setup our variables.
		var $this = jQuery(this);
		var $create_topic_wrap = jQuery(".enable-first-post");
		var $create_topic_input = jQuery("#create_first_post");
		var $limit_access_wrap = jQuery(".limit-access").show();
		var $limit_access_input = jQuery("#limit_access");
		var $set_forum_parent = $(".product_forum_parent");

		// If no forum is selected.
		if ("" === $this.val()) {
			// Hide and uncheck first post checkbox.
			$create_topic_wrap.hide(); // Hide the checkbox container.
			$create_topic_input.attr("checked", false); // Uncheck the checkbox (for good measure).

			// Hide and uncheck access limiter.
			$limit_access_wrap.hide(); // Hide the checkbox container.
			$limit_access_input.attr("checked", false); // Uncheck the checkbox (for good measure).

			$set_forum_parent.val( 'none' );
			$set_forum_parent.hide();

			// If a new forum is selected.
		} else if ("new" === $this.val()) {
			$limit_access_wrap.show();
			$create_topic_wrap.show();
			$set_forum_parent.show();

			// If any forum is selected.
		} else {
			// Show access limiter.
			$limit_access_wrap.show();

			// Hide and uncheck first post checkbox.
			$create_topic_wrap.hide(); // Hide the checkbox container.
			$set_forum_parent.hide();
			$create_topic_input.attr("checked", false); // Uncheck the checkbox (for good measure).
		}
	}).change();
});
