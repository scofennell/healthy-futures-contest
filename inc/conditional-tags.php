<?php
/**
 * Functions to sniff data from the front end.
 *
 * @package WordPress
 * @subpackage healthy
 * @since healthy 1.0
 */

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
	if( ( $object_type != 'post' ) && ( $object_type != 'user' ) && ( $object_type != 'week' ) && ( $object_type != 'report' ) ) { return false; }

	// If we're acting on a post
	if( $object_type == 'post' ) {

		// Get the post object to act upon.
		$post = get_post( $object_id );

		// If the post is not an object, something is wrong.
		if ( ! is_object( $post ) ) { return false; }

		// The ID of the author of the post to act upon.
		$post_author_id = absint( $post -> post_author );

		// If the user is not done filling his profile data, return false.
		if ( ! healthy_is_profile_complete() ) { return false; }

		// If we're editing or deleting a post
		if( ( $action == 'edit' ) || ( $action == 'delete' ) ) { 

			// The contenst must be happening in order to edit or delete posts.
			if ( ! healthy_contest_is_happening() ) { return false; }
			
			// If the post is not for the current week, it's too late to edit or delete.
			if( ! healthy_is_post_for_current_week( $object_id ) ) { return false; }

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

	// If we're browsing a week...
	} elseif( $object_type == 'week' ) {
		
		// If the user is not done filling his profile data, return false
		if ( ! healthy_is_profile_complete() ) { return false; }

		// There's only one thing you can do to a week, and that's review.
		if ( $action != 'review' ) { return false; }
	
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
	if( $minutes >= $daily_min ) {return true; }

	return false;
}

/**
 * Determines if a week is complete -- that is, does it meet the minimum level of exercise days for a week?
 *
 * @param  int $week_id The id of the week we're checking.
 * @param  int $user_id The id of the user we're checking.
 * @return  boolean Returns true if week is complete, otherwise false.
 */
function healthy_is_week_complete( $week_id, $user_id = '' ) {
	
	// Grab the user ID.
	$user_id = absint( $user_id );
	
	// If empty, default to the active user.
	if ( empty( $user_id ) ){
		$user_id = healthy_get_active_user_id();
	}

	// Grab the week ID we're checking in.
	$week_id = absint( $week_id );

	// Grab the daily min value.
	$weekly_min = healthy_weekly_minimum();

	// How many days this week are complete?
	$days_complete = healthy_days_complete( $week_id, $user_id );

	// Is the running total more than the minimum?
	if( $days_complete >= $weekly_min ) { return true; }

	return false;
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

	return false;

}

/**
 * Determine if the contest is happening right now.
 * 
 * @return boolean Return true if the contest is happening right now, otherwise false.
 */
function healthy_contest_is_happening() {

	// Grab the current week.
	$current_week = date( 'W' );
	
	// Grab the first week of the contest.
	$first_week = healthy_first_week_of_contest();
	
	// Grab the last week of the contest.
	$last_week = healthy_last_week_of_contest();

	// If the contest has not started...
	if ( $current_week < $first_week ) { return false; }
	
	// If the contest is over...
	if ( $current_week > $last_week ) { return false; }

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

		// If it's not a meta field, like email or password, don't bother.
		$is_meta = $field['is_meta'];
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
 * Determine if a post is for the current week.
 *
 * @param int $post_id The ID of the post to check.
 * @return bool Returns tue if the post is from the current week, otherwise false.
 */
function healthy_is_post_for_current_week( $post_id ) {

	// The id of the post to check
	$post_id = absint( $post_id );
	
	// The post object of the post to check
	$post = get_post( $post_id );
	
	// The post date of the post to check, by week #
	$post_date = date( 'W', strtotime( $post->post_date ) );
	
	// The # of the current week.
	$current_week = date( 'W' );

	// If the post is from this week, return true.
	if ( $current_week == $post_date ) { return true; }

	// Otherwise, return false.
	return false;

}

/**
 * Determine if there is already an entry for this day for this user.
 *
 * @param int $post_author_id The ID of the author from whom we're checking for posts.
 * @param sting $day The text version of the date on which we're checking for posts.
 * @return bool Returns true if there is already an entry for this day, otherwise false.
 */
function healthy_already_an_entry_for_this_day( $post_author_id, $day ){
	
	// The author we're checking against.  If his ID is weird, bail.
	$post_author_id = absint( $post_author_id );
	if( empty( $post_author_id ) ) {
		return false;
	}

	// The timestamp of the day we're checking.  Will be used in wp query date query.
	$stamp = strtotime( $day );

	// The year we're checking in.
	$year = date( 'Y', $stamp );
	
	// The month we're checking in.
	$month = date( 'm', $stamp );

	// The day we're checking in.
	$day = date( 'd', $stamp );

	// A WP query to look for posts.
	$query = healthy_get_posts( $post_author_id, 1, $year, $month, $day );

	// If we found posts, return false.
	$posts = $query->found_posts;
	if( empty( $posts ) ) {
		return false;
	}

	return true;
}

/**
 * Determine if a week is full for an author.
 *
 * @param  int $week The current week: 1, 2, 3, ... 51, 52
 * @return bool If any days this week are empty, return false, otherwise, return true.
 */
function healthy_is_week_full( $week = false ) {

	$post_author_id = healthy_get_active_user_id();
	if( empty( $post_author_id ) ) { wp_die( "There has been a problem. 86" ); }

	// If week is not provided, grab current week.
	if( ! $week ) { $week = date( 'W' ); }

	// Grab the first day of the week.
	$first_day_of_week = healthy_get_first_day_of_week();
	$first_day_of_week_in_seconds = strtotime( $first_day_of_week );

	// Will get incremented as we loop through days.	
	$next_day_in_seconds = $first_day_of_week_in_seconds;

	// Grab the current day for this week -- no need to loop past it.
	$current_week_day_as_int = date( 'N' );

	// Loop through each say of the week.
	$i=0;
	while( $i <= 7 ) {

		// 1 = monday, 2 = tuesday, etc..
		$i++;

		// If we've looped past $first_day_of_week, calculate the day we're on in seconds
		if( $i > 1 ) {
			$next_day_in_seconds = $first_day_of_week_in_seconds + ( ( DAY_IN_SECONDS * $i ) -1 );
		}

		// The next day in text for easier string comparision.
		$next_day_in_text = esc_attr( date( 'l, F d, Y', $next_day_in_seconds ) ); 

		// If this author does not have post for this day, return false -- the week in not full.
		if( ! healthy_already_an_entry_for_this_day( $post_author_id, $next_day_in_text ) ) {
			return false;		
		}

		// If we made it all the way to today, then the only days left are future days, which we cant post to.
		if ( $i == $current_week_day_as_int ) { return true; }
		
	} // end looping through days.

	return true;
}