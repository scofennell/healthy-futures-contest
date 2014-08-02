<?php

/**
 * healthy filtration.
 *
 * Miscellaneous filter functions for things like post class, body class,
 * menu item classes, etc.
 *
 * @package WordPress
 * @subpackage healthy
 * @since  healthy 1.0
 */

/**
 * Personalize the loginout text.
 * 
 * @param  string $link The unfiltered loginout text.
 * @return string The filtered loginout text.
 */
function healthy_filter_login_link( $link ) {

	// If the user is logged in, use his name in the loginout text.
	if( is_user_logged_in() ) { 

		// Fix the casing of the log out link.  Find the default text.
		$logout_text_before = 'Log out';

		// Replace it with cased text.
		$logout_text_after = esc_html( 'Log Out', 'healthy');
		$link = str_replace( $logout_text_before, $logout_text_after, $link );
	} else {

		// Fix the casing of the log out link.  Find the default text.
		$login_text_before = 'Log in';

		// Replace it with cased text.
		$login_text_after = esc_html( 'Log In', 'healthy');
		$link = str_replace( $login_text_before, $login_text_after, $link );

	}

	// Always return something in filter functions.
	return $link;

}
add_filter( 'loginout', 'healthy_filter_login_link' );

function healthy_register_msg($msg) {
	$pattern = '/Register For This Site/';
	$custom_msg = esc_html__( 'Be sure to check your spam email folder after registering!', 'healthy' );
	return preg_replace( $pattern, $custom_msg, $msg );
}
add_filter('login_message','healthy_register_msg');

function healthy_change_registration_button_text ( $text ) {
	 if ( $text == 'Register' ) {
	 	$text = esc_html__( 'Register &mdash; check your spam folder!', 'healthy' );
	}
	return $text;
}
add_filter( 'gettext', 'healthy_change_registration_button_text' );

function healthy_change_password_email_text ( $text ) {
	 if ( $text == 'A password will be e-mailed to you.' ) {
	 	$text = esc_html__( 'A password will be e-mailed to you &mdash; check your spam folder!', 'healthy' );
	}
	return $text;
}
add_filter( 'gettext', 'healthy_change_password_email_text' );

function healthy_replace_register_button() {
	wp_enqueue_script( 'jquery' );
	?>
	<script>
		jQuery( document ).ready(function( $ ) {
			jQuery('#registerform [type="submit"]').val( '<?php echo __( "Get Started!", "healthy" ); ?>' );
		});
	</script>
	<?php	
}
add_action( 'login_footer', 'healthy_replace_register_button', 999 );

/**
 * Filter the document title, as in <title></title>.
 *
 * Creates a nicely formatted and more specific title element text for output
 * in head of document, based on current view.  Is mostly a rip-off of Twenty Thirteen.
 *
 * @since healthy 1.0
 *
 * @param string $title Default title text for current view.
 * @param string $sep   Optional separator.
 * @return string The filtered title.
 */
function healthy_wp_title( $title, $sep ) {

	global $paged, $page;

	// If we're on the feed, just return the title as-is.
	if ( is_feed() ) {
		return $title;
	}

	// Add the site name.
	$title .= get_bloginfo( 'name' );

	// Add the site description for the home/front page.
	$site_description = get_bloginfo( 'description', 'display' );
	if ( $site_description && ( is_home() || is_front_page() ) ) {
		$title = "$title $sep $site_description";
	}

	// Add a page number if necessary.
	if ( $paged >= 2 || $page >= 2 ) {
		$title = "$title $sep " . sprintf( esc_html__( 'Page %s', 'healthy' ), max( $paged, $page ) );
	}

	return $title;

}
add_filter( 'wp_title', 'healthy_wp_title', 10, 2 );

/**
 * Extend the default WordPress body classes.
 *
 * Adds body classes to denote:
 * 1. Single or multiple authors.
 * 2. When avatars are disabled in discussion settings.
 *
 * @since healthy 1.0
 *
 * @param array $classes A list of existing body class values.
 * @return array The filtered body class list.
 */
/*
function healthy_body_class( $classes ) {
	
	if ( ! is_multi_author() ) { $classes[] = 'single-author'; }

	if ( ! get_option( 'show_avatars' ) ){ $classes[] = 'no-avatars'; }

	return $classes;

}
add_filter( 'body_class', 'healthy_body_class' );
*/

/**
 * Extend the default WordPress post classes.
 *
 * Adds post classes:
 * 1. Adds our standard 'inner-wrapper' class, used for layout styles.
 *
 * @since healthy 1.0
 *
 * @param array $classes A list of existing body class values.
 * @return array The filtered body class list.
 */
function healthy_post_class( $classes ) {

	/*
	global $post;

	if( is_single( $post ) ) {
		$classes[] = 'hentry-single';
	}
	*/

	// A standard class in our CSS to control vertical spacing.
	$classes[] = 'inner-wrapper';

	// The filtered classes.
	return $classes;

}
add_filter( 'post_class', 'healthy_post_class' );