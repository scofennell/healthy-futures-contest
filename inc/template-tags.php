<?php

/**
 * healthy template tags.
 *
 * Tags to draw UI elements for the front end.
 *
 * @package WordPress
 * @subpackage healthy
 * @since healthy 1.0
 */

/**
 * Grab the blog title and wrap each word in a span for lettering.
 *
 * @return $string The blog title, word-wrapped.
 */
function healthy_title_as_spans() {
	
	$word_array = explode(" ", get_bloginfo( 'name' ) );
	$word_count = count( $word_array );
	$out = '';
	$i=0;
	
	foreach( $word_array as $word ) {
		$i++;
		
		$out .= " <span class='word_$i'>$word</span> ";
	}
	
	return $out;

}

/**
 * Return the first name for the active user.
 *
 * @return The first name for the active user.
 */
function healthy_active_user_first_name() {

	// Get the active user ID.
	$active_user_id = healthy_get_active_user_id();
	
	// Get the first name.
	$first_name = get_user_meta( $active_user_id, 'first_name', TRUE );
	
	// Sanitize it.
	$out = esc_html( $first_name );

	return $out;
}

function healthy_hello_user() {
	
	// Grab the user first name.
	$first_name = get_user_meta( get_current_user_id(), 'first_name', TRUE	);
	
	// If the user has a first name:
	if ( ! empty ( $first_name ) ) {

		// Create a "Hi user!" string.
		$hello = sprintf( esc_html__( 'Hi %s!', 'healthy' ), $first_name );
			
		// Wrap the string for CSS.
		$hello = "<em class='healthy-hello'>$hello</em>";

		return $hello;

	}

	return false;
	
}

/**
 * Returns a link prompting the user to create a user, if applicaple.
 *
 * @return string An html link to the create-user form.
 */
function healthy_create_user_link() {

	// Only logged-in users would need this.
	if ( ! is_user_logged_in () ) { return false; }

	// Only profile complete users can do this.
	if ( ! healthy_is_profile_complete() ) { return false; }

	// Only priveleged users can do this.
	if ( ! healthy_user_is_role( true, 'teacher' ) && ! healthy_user_is_role( true, 'boss' ) ) { return false; }

	// The base url for our link.
	$base = trailingslashit( esc_url( get_bloginfo( 'url' ) ) );

	// The query for making a new user.
	$query = healthy_controller_query_string( 'user', 'create', 'new' );

	// The url to the make-a-user view.
	$url = esc_url( $base.$query );
	
	// The link text.
	$create = esc_html__( 'Enroll a User', 'healthy' );

	// The output.
	$out = "<a href='$url'>$create</a>";

	return $out;
}

/**
 * Returns a link to allow a teacher to view reports for her school.
 *
 * @return string A link to allow a teacher to view reports for her school.
 */
function healthy_review_reports_link() {
	
	// If the user is a boss, he can see reports from all schools.
	if( healthy_user_is_role( true, 'boss' ) ) {

		// The label for bosses.
		$report_label = esc_html__( 'View Reports', 'healthy' );
		
		// Which school to offer.
		$school = 'all';

	// If the suer is a teacher, show a link for his school.
	} elseif( healthy_user_is_role( true, 'teacher' ) ) {
		
		// Get the school for the user.
		$school = healthy_get_user_school( get_current_user_id() );
		
		// Convert the school into a label.
		$school_label = ucwords( $school );
		$school_label = str_replace( '_', ' ', $school_label );

		// The label for teachers.
		$report_label = sprintf( esc_html__( 'View Reports from %s', 'healthy' ), $school_label );
	}


	// The base for our link.
	$base = trailingslashit( esc_url( get_bloginfo( 'url' ) ) );

	// The query string for getting to the reports screen.
	$query = healthy_controller_query_string( 'report', 'review', $school );

	// The href for our link.
	$report_href = esc_url( $base.$query );

	// Output the link.
	$out = "<a href='$report_href'>$report_label</a>";

	return $out;
}

/**
 * Sniffs to see if user is logged in, if so, gives a link to edit profile.
 * 
 * @return string A url to the new post form.
 */
function healthy_edit_profile_link() {
	
	// Only logged in users can edit profiles.
	if ( ! is_user_logged_in () ) { return false; }
	
	// Our base url.
	$base = trailingslashit( esc_url( get_bloginfo( 'url' ) ) );

	// The link text for editing a profile.  If the user is acting on behalf of a student:
	if ( healthy_has_switched_users() ) {

		// Grab the name of the student being acted upon.
		$active_user_display_name = healthy_get_active_user() -> display_name;
		
		// Link text for editing a students profile.
		$edit = sprintf( esc_html__( "Edit %s's Profile", 'healthy' ), $active_user_display_name );	
	
	// Else, text to edit one's own profile.
	} else {
		$edit = esc_html__( 'Edit Profile', 'healthy' );
	}

	// The ID for whom we'll edit.
	$user_id = healthy_get_active_user_id();

	// A query to navigate to the edit profile page.
	$query = healthy_controller_query_string( 'user', 'edit', $user_id );

	// The href for editing a profile.	
	$href = esc_url( $base.$query );

	// Complete the output.
	$out = "<a href='$href'>$edit</a>";
	return $out;
}

/**
 * Returns a nav menu for our app.
 * 
 * @return string An HTML nave menu.
 */
function healthy_nav_menu() {

	// Will hold each menu item.
	$menu_items = array();

	// Some items only make sense for logged in users.
	if( is_user_logged_in() ) {

		// Say hello to our little friends.
		$menu_items []= healthy_hello_user();

		// Teachers can create new users and browse reports.
		if( healthy_user_is_role( true, 'teacher' ) || healthy_user_is_role( true, 'boss' ) ) {

			// Create a new user.
			$create_user = healthy_create_user_link();
			$menu_items []= $create_user;

			// Review users from your school.
			$healthy_review_students_link = healthy_review_students_link();
			$menu_items []= $healthy_review_students_link;

			// Review reports from your school.
			$healthy_review_reports_link = healthy_review_reports_link();
			$menu_items []= $healthy_review_reports_link;
		}

		// Logged in users can edit their profile.
		$menu_items []= healthy_edit_profile_link();
	
		// If the user is logged in, givehim a logout link.
		if( is_user_logged_in() ) {

			// A loginout link.
			$loginout = healthy_login_link();
		
			// Add the logout link.
			$menu_items []= $loginout;

		}

	// End if user is logged in.
	}

	// Start the output.
	$out ='';

	// Foreach item in our array of menu items:
	foreach( $menu_items as $item ){
		
		// Make sure it's not empty.
		if( empty( $item ) ){ continue; }
		
		// Add it to the output.
		$out.= $item;

	}

	// Complete the output.
	$out = "
		<nav class='healthy-menu content-holder'>
			$out
		</nav>
	";
	return $out;
}

/**
 * Outputs the opening HTML tag with IE CC's.
 */
function healthy_the_html_classes() {
	?>
	<!--[if IE 7]>
		<html class="ie ie7" <?php language_attributes(); ?>>
	<![endif]-->
	<!--[if IE 8]>
		<html class="ie ie8" <?php language_attributes(); ?>>
	<![endif]-->
	<!--[if IE 9]>
		<html class="ie ie9" <?php language_attributes(); ?>>
	<![endif]-->
	<!--[if !(IE 7) | !(IE 8) | !(IE 9)  ]><!-->
		<html <?php language_attributes(); ?>>
	<!--<![endif]-->
	
	<?php
}