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
 * Returns a link prompting the user to post information, if applicable.
 *
 * @param  int $week The current week.
 * @return bool|string An html link to the create-post form, or false if no slots available for new posts.
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
	
	// The link text.  If it's a teacher who is acting on behalf of a student:
	if ( healthy_has_switched_users() ) {
		
		// Grab the name of the student being acted upon.
		$active_user_display_name = healthy_get_active_user() -> display_name;
		
		// The label for acting on a student.
		$record = sprintf( esc_html__( 'Record a Day for %s', 'healthy' ), $active_user_display_name );	
	
	// The link text for when the active user is not switched.
	} else {
		$record = esc_html__( 'Record my Day', 'healthy' );
	}

	// The output.
	$out = "<a href='$url'>$record</a>";

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
 * Returns an HTML select menu to browse data by week.
 *
 * @return An HTML select menu to browse data by week.
 */
function healthy_my_weeks_select() {

	// If the user is acting on behalf of another user:
	if ( healthy_has_switched_users() ) {
		
		// Grab the first name of the user being acted upon.
		$active_user_display_name = healthy_get_active_user() -> display_name;
		
		// Text prompting the user to browse data for another user.
		$browse = sprintf( esc_html__( 'Browse Data for %s', 'healthy' ), $active_user_display_name );	
	
	// Else, text prompting the user to browse his own data.
	} else {
		$browse = esc_html__( 'Browse my Data', 'healthy' );
	}

	// Our base url.
	$base = trailingslashit( esc_url( get_bloginfo( 'url' ) ) );

	// The default, empty option.
	$options="<option>$browse</option>";

	// We will increment this number for each week of the contest.
	$week = 1;

	// Grab the current week so we don't prompt the user to browse future weeks.
	$current_week_of_contest = healthy_current_week_of_contest();

	// If the user has switched users...
	if ( healthy_has_switched_users() ) {

		// Grab the name of the active user.
		$active_user_display_name = healthy_get_active_user() -> display_name;	
	}

	// For each week in the contest...
	while ( $week <= ( $current_week_of_contest ) ) {

		// Build a query to view posts from that week
		$query = healthy_controller_query_string( 'week', 'review', $week );
	
		// The url to which we'll navigate.
		$value = esc_url( $base.$query );
	
		// If the user has switched users...
		if ( healthy_has_switched_users() ) {

			// Draw a label to browse the week for that user.
			$week_label = sprintf( esc_html__( "See %s&#8217;s data from week %d", 'healthy' ), $active_user_display_name, $week );
		
		// Otherwise, speak in the first person.
		} else {
			$week_label = sprintf( esc_html__( 'See my data from week %d', 'healthy' ), $week );	
		}

		// Add this option to the output.
		$options .= "<option value = '$value'>$week_label</option>";
		
		// Increment the value for week.
		$week++;
	}

	// If the user has switched users...
	if ( healthy_has_switched_users() ) {
		
		// Prompt the user to browse week-by-week for that user.
		$week_by_week = sprintf( esc_html( 'Week by Week data for %s', 'healthy' ), $active_user_display_name );
	
	// Else, speak in the first person.
	} else {	
		$week_by_week = esc_html__( 'Week by week', 'healthy' );
	}

	// Query to view all weeks.
	$query = healthy_controller_query_string( 'week', 'review', 'all' );

	// A Url to view all weeks.
	$value = esc_url( $base.$query );

	// Add the all weeks option.
	$options .= "<option value='$value'>$week_by_week</option>";

	// Complete the output as a JS jump-menu.
	$out = "<select onchange='document.location.href=this.options[this.selectedIndex].value;'>$options</select>";

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

	// If the user is logged in, givehim a logout link.
	if( is_user_logged_in() ) {

		// A loginout link.
		$loginout = healthy_login_link();
	
		// Add the logout link.
		$menu_items []= $loginout;

	}

	// Grab the current week so we can see if it's full.
	$week = date( 'W' );

	// Some items only make sense for logged in users.
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