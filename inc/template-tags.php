<?php

/**
 * healthy template tags.
 *
 * Tags to draw UI elements.
 *
 * @package WordPress
 * @subpackage healthy
 * @since healthy 1.0
 */

/**
 * Return the fist name for the active user.
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

	// Only teachers can do this.
	if ( ! healthy_user_is_role( true, 'teacher' ) ) { return false; }

	//The base url for our link.
	$base = trailingslashit( esc_url( get_bloginfo( 'url' ) ) );

	// The query for making a new post.
	$query = healthy_controller_query_string( 'user', 'create', 'new' );

	// The url to the post-a-day view.
	$url = esc_url( $base.$query );
	
	// The link text.
	$create = esc_html__( 'Create a User', 'healthy' );

	// The outpsut.
	$out = "<a href='$url'>$create</a>";

	return $out;

}

/**
 * Returns a link prompting the user to post information, if applicaple.
 *
 * @param  int $post_author_id The ID of the author we're prompting.
 * @param  int $week The current week.
 * @return string An html link to the create-post form.
 */
function healthy_enter_day_link( $week ) {
	
	// Only logged-in users would need this.
	if ( ! is_user_logged_in () ) { return false; }
	
	// If this week is full for this author, don't bother showing it.
	if ( healthy_is_week_full() ) { return false; }

	//The base url for our link.
	$base = trailingslashit( esc_url( get_bloginfo( 'url' ) ) );

	// The query for making a new post.
	$query = healthy_controller_query_string( 'post', 'create', 'new' );

	// The url to the post-a-day view.
	$url = esc_url( $base.$query );
	
	// The link text.
	if ( healthy_has_switched_users() ) {
		$active_user_display_name = healthy_get_active_user() -> display_name;
		$record = sprintf( esc_html__( 'Record a Day for %s', 'healthy' ), $active_user_display_name );	
	} else {
		$record = esc_html__( 'Record My Day', 'healthy' );
	}

	// The output.
	$out = "<a href='$url'>$record</a>";

	return $out;
}

/**
 * Sniffs to see if user is logged in, is fo, gives a link to edit profile.
 * 
 * @return string A url to the new post form.
 */
function healthy_edit_profile_link() {
	
	// Only logged in users can edit profiles.
	if ( ! is_user_logged_in () ) { return false; }
	
	// Our base url.
	$home = trailingslashit( esc_url( get_bloginfo( 'url' ) ) );

	// Translate.
	if ( healthy_has_switched_users() ) {
		$active_user_display_name = healthy_get_active_user() -> display_name;
		$edit = sprintf( esc_html__( "Edit %s's Profile", 'healthy' ), $active_user_display_name );	
	} else {
		$edit = esc_html__( 'Edit Profile', 'healthy' );
	}

	// The ID for whom we'll edit.
	$user_id = healthy_get_active_user_id();

	// A query to navigate to the edit profile page.
	$query = healthy_controller_query_string( 'user', 'edit', $user_id );
	
	// Complete the output.
	$out = "<a href='$home$query'>$edit</a>";
	return $out;
}

/**
 * Returns an HTML select menu to browse data by week.
 *
 * @return An HTML select menu to browse data by week.
 */
function healthy_my_weeks_select() {

	// Translate.
	if ( healthy_has_switched_users() ) {
		$active_user_display_name = healthy_get_active_user() -> display_name;
		$browse = sprintf( esc_html__( 'Browse data for %s', 'healthy' ), $active_user_display_name );	
	} else {
		$browse = esc_html__( 'Browse my data&hellip;', 'healthy' );
	}

	// Our base url.
	$home = trailingslashit( esc_url( get_bloginfo( 'url' ) ) );

	// The default, empty option.
	$options="<option>$browse</option>";

	// We will incement this number.
	$week = 1;

	// watch out for teacher mode here, teachers want to be able to edit stuff from any week
	$current_week_of_contest = healthy_current_week_of_contest();

	// For each week in the contest...
	while ( $week <= ( $current_week_of_contest ) ) {

		// Build a query to view posts from that week
		$query = healthy_controller_query_string( 'week', 'review', $week );
	
		// The url to which we'll navigate.
		$value = esc_url( $home.$query );
	
		// Add this option to the output.
		if ( healthy_has_switched_users() ) {
			$active_user_display_name = healthy_get_active_user() -> display_name;
			$week_label = sprintf( esc_html__( "See %s's data from week %d", 'healthy' ), $active_user_display_name, $week );
		} else {
			$week_label = sprintf( esc_html__( 'See my data from week %d', 'healthy' ), $week );	
		}

		$options .= "<option value = '$value'>$week_label</option>";
		
		// Increment the value for week.
		$week++;
	}

	// Translate.
	if ( healthy_has_switched_users() ) {
		$active_user_display_name = healthy_get_active_user() -> display_name;
		$week_by_week = sprintf( esc_html( 'Week by Week data for %s', 'healthy' ), $active_user_display_name );
	} else {	
		$week_by_week = esc_html__( 'Week by Week', 'healthy' );
	}

	// Query to view all weeks.
	$query = healthy_controller_query_string( 'week', 'review', 'all' );

	// A Url to view all weeks.
	$value = $home.$query;

	// Add the all weeks option.
	$options .= "<option value='$value'>$week_by_week</option>";

	// Complete the output as a JS jump-menu.
	$out = "<select onchange='document.location.href=this.options[this.selectedIndex].value;'>$options</select>";

	return $out;
}

/**
 * Returns a nav menu for our app.
 * 
 * @param  int $post_author_id The ID of the user to whom these links apply.
 * @return string An HTML nave menu.
 */
function healthy_nav_menu( $post_author_id ) {

	// A loginout link.
	$loginout = healthy_login_link();
	
	// We'll add items to this array, conditionally.
	$menu_items = array( $loginout );

	// watch out for teacher mode here -- teachers want to be able to edit stuff for any date
	$week = date( 'W' );

	if( is_user_logged_in() ) {

		// Logged in users can edit their profile.
		$menu_items []= healthy_edit_profile_link();

		// If the user is able, give links to create and browse data.
		if( healthy_is_profile_complete() && healthy_user_is_role( true, 'student' ) ) {
	
			// Browse by week
			$my_weeks = healthy_my_weeks_select();
			$menu_items []= $my_weeks;
		
			// Enter a new day
			$enter_day = healthy_enter_day_link( $week );
			$menu_items []= $enter_day;

		}

		// Teachers can create new users.
		if( healthy_user_is_role( true, 'teacher' ) ) {


			// Create a new user.
			$create_user = healthy_create_user_link();
			$menu_items []= $create_user;

			// Review users from your school.
			$healthy_review_students_link = healthy_review_students_link();
			$menu_items []= $healthy_review_students_link;

		}

	}

	// Start the output.
	$out ='';

	// Foreach item in our array...
	foreach( $menu_items as $i ){
		
		// Make sure it's not empty.
		if( empty( $i ) ){ continue; }
		
		// Add it to the output.
		$out.= $i;

	}

	// Complete the output.
	$out = "
		<nav>
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