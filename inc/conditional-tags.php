<?php
/**
 * Functions to sniff data from the front end.
 *
 * @package WordPress
 * @subpackage healthy
 * @since healthy 1.0
 */

/**
 * Is the user eligible for the grand prize?
 * 
 * @param  int $user_id The user id to check.
 * @return boolean      If the user has recorded the minimum amount of exercise, return true.  Else, false.
 */
function healthy_user_is_all_star( $user_id ) {

	// The user ID to check.
	$user_id = absint( $user_id );

	// Determine how many days complete the user has.
	$days_complete = healthy_days_complete( $user_id );

	if( $days_complete < healthy_get_min_days() ) { return FALSE; }

	// We made it!  the user is still eligible.
	return TRUE;
}

/**
 * Is the user eligible for the grand prize?
 * 
 * @param  int $user_id The user id to check.
 * @return boolean      If the user has recorded the minimum amount of exercise, return true.  Else, false.
 */
function healthy_user_is_on_pace( $user_id ) {

	// The user ID to check.
	$user_id = absint( $user_id );

	// Determine how many days we are into the contest.
	if( date( 'n' ) != healthy_get_month_of_contest( 'n' ) ) {
		$day = healthy_get_days_of_contest_month();
	} else {
		$day = date( 'd' );
	}

	$days_in_month = healthy_get_days_of_contest_month();

	// Determine how many days complete the user has.
	$days_complete = healthy_days_complete( $user_id );
	$batting_average = $days_complete / $day;

	$minimum = healthy_get_min_days() / $days_in_month;

	if( $batting_average < $minimum ) { return FALSE; }

	// We made it!  the user is still eligible.
	return TRUE;
}

/**
 * Determine if the current user has the authority to act on a "thing" in our app.
 *
 * @param  int $user_id The ID of the user acting.
 * @param  int $post_id The ID of the post being acted upon.
 * @param  string $action The action being attempted.
 * @return bool Returns true if user can edit, otherwise false.
 */
function healthy_current_user_can_act_on_object( $object_id, $action, $object_type ) {

	// Users must be logged in.
	if( ! is_user_logged_in() ) { return false; }

	// The current user.
	$current_user_id = absint( get_current_user_id() );
	if( empty( $current_user_id ) ) { return false; }

	// The user on behalf of which the current user is acting.
	$active_user_id = healthy_get_active_user_id();

	// Whitelist our object types.
	if( ( $object_type != 'post' ) && ( $object_type != 'user' ) && ( $object_type != 'report' ) ) { return false; }

	// If we're acting on a post
	if( $object_type == 'post' ) {

		// Get the post object to act upon.
		$post = get_post( $object_id );

		// If the post is not an object, something is wrong.
		if ( $object_id != 'new' ) {
		
			if ( ! is_object( $post ) ) { return false; }

			// The ID of the author of the post to act upon.
			$post_author_id = absint( $post -> post_author );

		}

		// If the user is not done filling his profile data, return false.
		if ( ! healthy_is_profile_complete() ) { return false; }

		// If we're editing or deleting a post
		if( ( $action == 'edit' ) || ( $action == 'delete' ) ) { 

			// The contenst must be happening in order to edit or delete posts.
			if ( ! healthy_contest_is_happening() ) { return false; }

			// If the post is not a "healthy_day", bail.
			if( $post -> post_type != 'healthy_day' ) { return false; }

			// If the author ID is weird, bail.
			if( empty( $post_author_id ) ) { return false; }

			// If the current user is not the author of the post we're checking, bail.
			if( $post_author_id != $active_user_id ) { return false; }

		// If the user is trying to review, he must be the author or active user
		} elseif ( $action == 'review' ) {

			// If the user is trying to view a post he doesn't own, bail.
			if( ( $active_user_id !=  $post_author_id ) && ( $current_user_id !=  $post_author_id ) ) {
				return false;
			}
		
		// The contest must be happening in order to create posts
		} elseif ( $action == 'create' ) {

			if ( ! healthy_contest_is_happening() ) { return false; }
		} 

	// If we're CRUD'ing a user...
	} elseif( $object_type == 'user' ) {

		// If we're editing a user...
		if ( $action == 'edit' ) {

			// Only the active or current user may be edited
			if ( $object_id != $active_user_id ) { return false; }

			// Only priveleged users can act.
			if ( ! healthy_does_user_own_user( $current_user_id, $active_user_id ) ) { return false; }

		// If we're deleting a user...
		} elseif ( $action == 'delete' ) {

			// Only priveleged users can act.
			if ( ! healthy_does_user_own_user( $current_user_id, $object_id ) ) { return false; }

			// Users can't delete themselves.
			if ( $current_user_id == $object_id ) { return false; }		
			if ( $active_user_id == $object_id ) { return false; }

		// Only teachers can create users.
		} elseif ( $action == 'create'  ) {
			if ( ( ! healthy_user_is_role	( false, 'teacher' ) && ! healthy_user_is_role ( false, 'boss' ) ) ) { return false; }

		// Only teachers can review users.	
		} elseif( $action == 'review' ) {

			// Is the current user a teacher?
			if ( ( ! healthy_user_is_role	( false, 'teacher' ) && ! healthy_user_is_role ( false, 'boss' ) ) ) { return false; }

			// Only all users may be reviewed.
			if ( $object_id != 'all' ) { return false; }
		
		// Only teachers can switch to students.	
		} elseif( $action == 'switch' ) {

			// Grab the id of the user to switch to.
			$user_to_switch_to = absint( $object_id );
			if ( empty( $user_to_switch_to ) ) { return false; }

			// Teachers can only switch to their students.
			if ( ! healthy_does_user_own_user( $current_user_id, $user_to_switch_to ) ) { return false; }

		}
	
	// If we;re dealing with reports...
	} elseif( $object_type == 'report' ) {

		// Students can't view reports at all.
		if ( healthy_user_is_role( false, 'student' ) ) { return false; }

		// Teachers can view reports for their school.
		if ( ! healthy_user_is_role( false, 'boss' ) ) {

			// Which school are they viewing?
			$school = $_REQUEST[ 'object_id' ];

			// To which school does the teacher belong?
			$user_school = healthy_get_user_school( get_current_user_id() );

			// If the user does not belong to this school, bail.
			if ( $school != $user_school ) { return false; }

		}

	// If we haven't captured what the user is doing, return false.
	} else {
		return false;
	}

	// We made it!  The user can edit this post.
	return true;
}

/**
 * Determines if a day is complete -- that is, does it meet the minimum level of exercise for a day?
 *
 * @param  int $post_id The id of the post we're checking.
 * @return  boolean Returns true if day is complete, otherwise false.
 */
function healthy_is_day_complete( $post_id ) {
	
	// Grab the daily min value.
	$daily_min = healthy_daily_minimum();

	// Our plugin-wide definition of a day.
	$day = healthy_day();

	// Meta fields for a day.
	$components = $day['components'];

	// This will hold the amount of exercise for this day.
	$minutes = 0;

	// For each meta key that relates to exercise, read it into our $keys array.
	foreach( $components as $c ) {
		$slug = $c[ 'slug' ];
		if ( ! isset( $c['is_exercise'] ) ) { continue; }
	
		// The number of minutes for a type of exercise.
		$meta = get_post_meta( $post_id, $slug, TRUE );
		
		// Sanitize it.
		$this_minutes = absint ( $meta );
		
		// Add it to our running total.
		$minutes = $minutes + $this_minutes;

	}

	// Is the running total more than the minimum?
	if( $minutes < $daily_min ) { return FALSE; }

	// Did this day get ruined by too much sugar?
	if( healthy_is_sugary( $post_id ) ) { return FALSE; }

	return TRUE;
}

function healthy_is_sugary( $post_id ) {
	$sugary_drinks = get_post_meta( $post_id, 'sugary_drinks', TRUE );
	if( $sugary_drinks > 2 ) { return TRUE; }

	return FALSE;

}

/**
 * Detemrines if the a user has switched to being a different user.
 *
 * @return boolean|int Returns the ID of the user switched to, otherwise false.
 */
function healthy_has_switched_users() {

	// Our app-wide cookie key.
	$cookie_key = healthy_switched_user_cookie_key();

	// The state of having switched to a user is determined by this cookie.
	if ( ! isset( $_COOKIE[ $cookie_key ] ) ) { return false; }

	// $active_user refers to the user whose been switched to, or, failiing that, the active user.
	$active_user_id = absint( $_COOKIE[ $cookie_key ] );

	// If there is no active user ID in the cookie, bail.
	if ( empty( $active_user_id ) ) { return false; }

	// If the cookie appears to be trying to switch the active user to a user he does not own, bail.
	if ( ! healthy_does_user_own_user( get_current_user_id(), $active_user_id ) ) { return false; }

	return $active_user_id;

}

/**
 * Determines if the active user is a given role.
 *
 * @param boolean Get the active user (true) or the current user (false).
 * @param string Any role slug, such as teacher or student.
 * @return boolean Returns true if the active user is a teacher, otherwise false.
 */
function healthy_user_is_role( $active_user = true, $role = 'teacher' ) {

	// We can either sniff the active user
	if ( $active_user ) {
		$user =  healthy_get_active_user();
	
	// Or the current user.
	} else {
		$user = get_userdata( get_current_user_id() );
	}

	// If the user is not an object, we're sunk.
	if ( ! is_object ( $user ) ) {
		return false;
	}

	// If there is not a roles array, we're sunk.
	$roles = $user -> roles;
	if (! is_array( $roles ) ) { return false; }

	// If there is a teacher role, return true.
	if ( in_array( $role, $roles ) ) {
		return true;
	}

	// Super admin gets boss privileges.
	if( is_super_admin( $user -> ID ) && $role == 'boss' ) { return TRUE; }

	return false;

}

/**
 * Determine if the contest is happening right now.
 * 
 * @return boolean Return true if the contest is happening right now, otherwise false.
 */
function healthy_contest_is_happening() {

	return TRUE;

	// Grab the current month.
	$current_month = healthy_get_current_month();
	
	// Grab the month of the contest.
	$contest_month = healthy_get_month_of_contest();

	// If the contest is not happening...
	if ( $current_month != $contest_month ) { return false; }
	
	// We made it this far, the contest is on!
	return true;

}

/**
 * Sniffs the url to determine if the user is trying to act upon a post.
 * 
 * @param  string $action What action the user is attempting (delete, edit)
 * @return bool|int Returns false if user lacks authority, otherwise returns post_id.
 */
function healthy_current_user_is_acting( $action, $object_type, $object_id ) {

	// If there is not a url var to sniff, bail.
	if( ! isset( $_REQUEST[ 'action' ] ) ) { return false; }
	
	// If the url var does not match the action we're expecting, bail.
	if( ( $_REQUEST[ 'action' ] ) != $action ) { return false; }

	// If there is not a url var to sniff, bail.
	if( ! isset( $_REQUEST[ 'object_type' ] ) ) { return false; }

	// If the url var does not match the action we're expecting, bail.
	if( ( $_REQUEST[ 'object_type' ] ) != $object_type ) { return false; }

	// If there is no post specified to act upon, bail.
	if( ! isset( $_REQUEST[ 'object_id' ] ) ) { return false; }

	// If there is no post specified to act upon, bail.
	if( $_REQUEST[ 'object_id' ] != $object_id  ) { return false; }

	// The post id to act upon.  We can act on all:
	if ( $_REQUEST[ 'object_id' ] == 'all' ) {
		$object_id = 'all';

	// Or we can act on a new object:
	} elseif( $_REQUEST[ 'object_id' ] == 'new' ) {
		$object_id = 'new';

	// Or we can act on a school object:
	} elseif( $_REQUEST[ 'object_type' ] == 'report' ) {

		// Get the schools in our app.
		$schools = healthy_get_schools();

		// Grab the school slugs.
		$school_slugs = array();
		foreach( $schools as $s ) {
			$school_slugs []= $s['slug'];
		}

		// Which school are we trying to act upon?
		$school = $_REQUEST[ 'object_id' ];

		// If it's a school in our app, that's the object ID.
		if ( in_array( $school, $school_slugs ) ) {
			$object_id = $_REQUEST[ 'object_id' ];
		}
		
	// Or we can act on a specific object:
	} else {
		$object_id = absint( $_REQUEST[ 'object_id' ] );
	}

	// Can the user perform this action on this object?
	if ( ! healthy_current_user_can_act_on_object( $object_id, $action, $object_type ) ) {
		return false;
	}

	// We made it!  The user can act on this post.
	return $object_id;
}

/**
 * Returns true if the user has completed their profile information, otherwise 
 * returns false.
 *
 * @return bool Returns true or false, depending on profile completion.
 */
function healthy_is_profile_complete() {

	// Get the current user.
	$current_user_id = get_current_user_id();

	// Get the fields that apply to users in our app.
	$user_fields = healthy_profile_fields();

	// For each field, see if it's complete.
	foreach( $user_fields as $field ) {

		// Certain fields like grade and school aren't mandatory for teachers.
		if( isset( $field[ 'is_hidden_from_teachers' ] ) && ( healthy_user_is_role( false, 'teacher' ) || healthy_user_is_role( false, 'boss' ) ) ) {
			continue;
		}

		// See if the field is required.
		if( ! isset ( $field[ 'required' ] ) ) { continue; }

		// If it's not a meta field, like email or password, don't bother.
		$is_meta = $field[ 'is_meta' ];
		if ( empty ( $is_meta ) ) { continue; }

		// Get the slug to use as meta key.
		$slug = $field[ 'slug' ];

		// Check the meta value.
		$meta = get_user_meta( $current_user_id, $slug, TRUE );
		
		// If there is not such meta, this field is not complete, return false.
		if( ! $meta ) { return false ;}
		if( empty( $meta ) ) { return false ;}

	}

	// We made it this far!  The user profile is complete.
	return true;
}

/**
 * Does the active user belong to the current user?
 *
 * @param  int $owner_id The user ID of the owner user.
 * @param  int $owner_id The user ID of the owned user.
 * @return boolean Returns true if the owned user is a student of the owner user, otherwise false.
 */
function healthy_does_user_own_user( $owner_id, $owned_id ) {
	
	// The the user id of the alleged owner.
	$owner_id = absint( $owner_id );
	if ( empty( $owner_id ) ) { return false; }

	// Get the suer ID of the alleged owned user.
	$owned_id = absint( $owned_id );
	if ( empty( $owned_id ) ) { return false; }

	// If they are the same, return true.
	 if ( $owner_id == $owned_id ) { return true; }

	// Grab the school for the current user.
	$owner_school = healthy_get_user_school( $owner_id );
	if( empty( $owner_school ) ) { return false; }

	// Grab the school for the active user.
	$owned_school = healthy_get_user_school( $owned_id );
	if( empty( $owned_school ) ) { return false; }

	// If they're not the same, return false.
	if ( $owner_school != $owned_school ) { return false; }

	// Grab the role for the current user.  Only teachers can own.
	$owner_obj = get_userdata( $owner_id );
	$owner_roles = $owner_obj -> roles;
	if ( ! in_array( 'teacher', $owner_roles ) ) { return false; }

	// Grab the role for the active user.  Only students can be owned.
	$owned_obj = get_userdata( $owned_id );
	$owned_roles = $owned_obj -> roles;
	if ( ! in_array( 'student', $owned_roles ) ) { return false; }

	return true;

}

/**
 * Determine if there is already an entry for this day for this user.
 *
 * @param int $post_author_id The ID of the author from whom we're checking for posts.
 * @param int $timestamp The timestamp for which we are checking for posts.
 * @return bool Returns true if there is already an entry for this day, otherwise false.
 */
function healthy_already_an_entry_for_this_day( $post_author_id, $timestamp ) {
	
	// The author we're checking against.  If his ID is weird, bail.
	$post_author_id = absint( $post_author_id );
	if( empty( $post_author_id ) ) {
		return false;
	}

	// The year we're checking in.
	$year = date( 'Y', $timestamp );
	
	// The month we're checking in.
	$month = date( 'm', $timestamp );

	// The day we're checking in.
	$day = date( 'd', $timestamp );

	// A WP query to look for posts.
	$query = healthy_get_posts( $post_author_id, 1, $year, $month, $day );

	// If we found posts, return false.
	$posts = $query -> found_posts;
	if( empty( $posts ) ) {
		return false;
	}

	return true;
}

/**
 * Determine if the current month is full for an author.
 *
 * @return bool If any days this month are empty, return false, otherwise, return true.
 */
function healthy_is_month_full() {

	$post_author_id = healthy_get_active_user_id();
	if( empty( $post_author_id ) ) { wp_die( "There has been a problem. 86" ); }

	/*
	// Grab the first minute of the month.
	$first_minute_of_month_in_seconds = mktime( 0, 0, 0, date( 'n' ), 1 );
	
	// Grab the last minute of the month.
	$last_minute_of_month_in_seconds = mktime( 23, 59, 0, date( 'n' ), date( 't') );

	// Grab now in seconds.
	$current_time_in_seconds = current_time( 'timestamp' );

	// Determine the end of the time window in which we'd look for posts.
	$end = $last_minute_of_month_in_seconds;
	if( $current_time_in_seconds < $last_minute_of_month_in_seconds ) {
		$end = $current_time_in_seconds;
	}
	*/

	$year  = healthy_get_year_of_contest();
	$month = healthy_get_month_of_contest();
	$today = absint( date( 'd' ) );
	$first_day_of_month = 1;
	$last_day_of_month  = healthy_get_days_of_contest_month();

	$end = $last_day_of_month;
	if( $today < $last_day_of_month ) {
		$end = $today;
	}

	$i = 0;
	while( $i < $end ) {
		$i++;

		$get_posts = healthy_get_posts( $post_author_id , 1, $year, $month, $i );
		$posts = count( $get_posts -> posts );
		if( empty( $posts ) ) { return FALSE; }
	}
	
	return TRUE;

}