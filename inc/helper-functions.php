<?php

/**
 * Utility functions used throughout the app.
 *
 * @package WordPress
 * @subpackage healthy
 * @since healthy 1.0
 */

/**
 * The controller for our application.  Routes the user to different views based 
 * on request.
 *
 * @return string HTML to compose the view appropriate for the user request.
 */
function healthy_controller() {

	// The user on behalf of whom we're CRUD'ing
	$active_user_id = healthy_get_active_user_id();

	// Grab the object id.
	$object_id = '';
	if( isset( $_REQUEST[ 'object_id' ] ) ) {
		$object_id = healthy_sanitize_object_id( $_REQUEST[ 'object_id' ] );
	}

	// The base url off of which we'll build absolute urls.
	$base_url = trailingslashit( esc_url( get_bloginfo( 'url' ) ) );

	// This function will output three chunks of html:  Title, subtitle, and content.
	$title = '';
	$content = '';
	$subtitle = '';

	// If you're not logged in, you're sunk.
	if( ! is_user_logged_in() ) {

		// It they're not logged in, return the login screen.
		$title = esc_html__( 'Log in or register to get started', 'healthy' );
		$subtitle = esc_html__( 'Only logged in users can take the challenge!', 'healthy' );
		$content = healthy_login_panels();

	// If your profile is not complete, you're sunk.
	} elseif( ! healthy_is_profile_complete() ) {

		// If they are not profile complete, bail.
		$title = esc_html__( 'Please fill out this information about yourself.', 'healthy' );
		$subtitle = esc_html__( 'Once your profile is complete, you can take the challenge!', 'healthy' );
		$content = healthy_profile_form();

	// Is the user deleting a user?
	} elseif ( healthy_current_user_is_acting( 'delete', 'user', $object_id ) ) {

		// Did he just delete something?
		$deleting = healthy_process_delete_a_user_form();
		
		// If he just deleted something...
		if ( $deleting ) {

			// Prompt the user to enter data.
			$title = esc_html__( 'That user is totally deleted.', 'healthy' );
			$subtitle = esc_html__( 'Totally.', 'healthy' );
			
			// The edit profile form.
			$content = healthy_switch_to_user_form();

		// If he's about to delete something...
		} else {

			// Grab the date of the post he's deleting.
			$user_to_delete_display_name = get_userdata( $_REQUEST['object_id'] ) -> display_name;

			// Prompt the user to enter data.
			$title = sprintf( esc_html__( 'Expel %s?', 'healthy' ), $user_to_delete_display_name );
			$subtitle = sprintf( esc_html__( 'Really delete your user, %s?', 'healthy' ), $user_to_delete_display_name );
			$content = healthy_delete_user_confirm( $_REQUEST['object_id'] );
		
		}

	// Is the user creating a user?
	} elseif ( healthy_current_user_is_acting( 'create', 'user', 'new' ) ) {

		// If the post is set, congratulate them on making a user.
		if ( isset( $_POST[ 'edit_profile' ] ) ) {
			$title = esc_html__( 'Your new user is alive and well.', 'healthy' );
			$subtitle = esc_html__( 'Create another?', 'healthy' );			

		// If post is not yet set...
		} else {

			// Prompt them to create a user.
			$title = esc_html__( 'Add Teachers & Students!', 'healthy' );
			$subtitle = esc_html__( 'You can create teachers and students for your school.', 'healthy' );

		}

		// The edit profile form.
		$content = healthy_profile_form( true );	

	// Is the user reviewing a user?
	} elseif ( healthy_current_user_is_acting( 'review', 'user', 'all' ) ) {

		// Prompt the user to assume the identity of a user
		$title = esc_html__( 'These are your students.', 'healthy' );
		$subtitle = esc_html__( 'You can act on behalf of any of your students.', 'healthy' );			

		// The edit profile form.
		$content = healthy_switch_to_user_form();	

	// Is the user switching to a new active user?
	} elseif ( healthy_current_user_is_acting( 'switch', 'user', $object_id ) ) {			

		// If the user has switched users...
		if( healthy_has_switched_users() ) {

			// Grab the display name of the active user.
			$active_user_display_name = healthy_get_active_user() -> display_name;

			// Prompt the user to assume the identity of a user
			$title = sprintf( esc_html__( 'You transformed yourself into %s.', 'healthy' ), $active_user_display_name );
			$subtitle = sprintf( esc_html__( 'You are now posting, editing, deleting, and reviewing data for %s ', 'healthy' ), $active_user_display_name );			

		// Else, something went wrong.
		} else {

			wp_die( "There has been a problem. 103" );		

		}

	// Is the user editing his profile?
	} elseif ( healthy_current_user_is_acting( 'edit', 'user', $object_id ) ) {

		// use their first name.
		$first_name = healthy_active_user_first_name();
	
		// If the contest has started:
		if ( healthy_contest_is_happening() ){

			if( healthy_user_is_role( true, 'teacher' ) ) {

				// A query to post a new day.
				$query = healthy_controller_query_string( 'report', 'review', 'all' );

			} else {

				// A query to post a new day.
				$query = healthy_controller_query_string( 'post', 'create', 'new' );

			}
		
			// A base for the link.
			$base = trailingslashit( esc_url( get_bloginfo( 'url' ) ) );
		
			// The href to start a new day.
			$href = $base.$query;
		
			// The label to start a new day.
			$label = esc_html__( 'Meet the challenge here.', 'healthy' );
		
			// The link to start a new day.
			$link = "<a href='$href'>$label</a>";
		
			// The page title.
			$title = sprintf( esc_html__( 'Hey %s, how\'s it going? %s', 'healthy' ), $first_name, $link );

			// The subtitle.
			$subtitle = esc_html__( 'Your profile is complete, but you can still edit it here anytime you want.', 'healthy' );
		
		// If the contest has not yet started.
		} else {

			$title = sprintf( esc_html__( 'Hey %s, how\'s it going?', 'healthy' ), $first_name );

			$subtitle = esc_html__( 'Come back soon, once the challenge starts!', 'healthy' );
		}

		// The edit profile form.
		$content = healthy_profile_form();	

	// Check to see if the user just posted a day, and grab thank you text.
	} elseif ( healthy_current_user_is_acting( 'create', 'post', 'new' ) ) {

		// Did the user just insert a day?
		$inserted = healthy_process_post_a_day_form();

		// If the user just posted a day, grab the new post ID.
		$inserted = absint( $inserted );
		if( ! empty( $inserted ) ) {
		
			$sugary_drinks = get_post_meta( $inserted, 'sugary_drinks', TRUE );
			$sugary = false;
			if( $sugary_drinks > 1 ) { $sugary = true; }

			$exercise_reminder = false;
			if( ! healthy_is_day_complete( $inserted ) ) {
				$daily_minimum = healthy_daily_minimum();
				$exercise_reminder = "<br><br>".sprintf( esc_html__( 'Remember to get at least %d minutes of exercise per day!', 'healthy' ), $daily_minimum );
			}

			// Translations.
			$edit_text = esc_html__( "You can go back and edit your entry if you want.", 'healthy' );
			
			// Build an href to edit the newly created post.
			$edit_query = healthy_controller_query_string( 'post', 'edit', $inserted );
			$edit_href = $base_url.$edit_query;

			// Build a link to edit the new post.
			$edit_link = "<p><a href='$edit_href'>$edit_text</a></p>";

			// Translations.
			$edit_week_text = esc_html__( "Or you can browse your recent posts.", 'healthy' );
			
			// Build an href to edit the newly created post.
			$edit_week_query = healthy_controller_query_string( 'week', 'review', 'all' );
			$edit_week_href = $base_url.$edit_week_query;
		
			// Build a link to edit the new post.
			$edit_week_link = "<p><a href='$edit_week_href'>$edit_week_text</a></p>";
		
			$subtitle = $edit_link.$edit_week_link;

			if( ! $sugary ) {

				// If the user just entered data and their week is full...
				if( healthy_is_fortnight_full() ) {
					// Tell the user the week is full.
					
					$title = __( 'Good job! All your days are full so far this week. Come back soon!', 'healthy' );
					$title .= $exercise_reminder;

				} else {
				
					$title = esc_html__( 'Your data has been recorded!', 'healthy' );
					$title .= $exercise_reminder;
					$content = healthy_post_a_day_form( false );
				}

			} else {

				$message = healthy_get_sugar_slogan();
				$title = '<span class="sugary-title">'.$message[ 'title' ].'</span>';
				$subtitle = $message[ 'subtitle' ];
				$subtitle .= $edit_link.$edit_week_link;

				if( ! healthy_is_fortnight_full() ) {
					$content = healthy_post_a_day_form( false );
				} else {
					$content = __( 'All your days are full so far this week. Come back soon!', 'healthy' );
				}

			}

		// If the user is about to insert a day...
		} else {

			// If the week is full and we're not editing congratulate the user, return.
			if( healthy_is_fortnight_full() ) {
		
				// Tell the user the week is full.
				$title = __( "Good job!  All your days are full so far this week. Come back soon!", 'healthy' );
		
				// Offer a link for editing entries.
				$edit = __( "You can also review your entries.", 'healthy' );

				// Build a link to edit/review other posts.
				$query = healthy_controller_query_string( 'week', 'review', 'all' );
				$edit_href = $base_url.$query;
				$subtitle = "<a href='$edit_href'>$edit</a>";

			// Else, give the post a day form.
			} else {

				$title = esc_html__( 'It\'s a brand new day!', 'healthy' );
				$subtitle = esc_html( 'If you could enter your data in this form, that would be great.', 'healthy' );
				$content = healthy_post_a_day_form( false );

			}
			
		}

	// Check to see if the user is editing
	} elseif ( healthy_current_user_is_acting( 'edit', 'post', $object_id ) ) {	

		// If the user is editing, grab the date of the post he's editing.
		$post_to_edit_date = healthy_get_post_date_by_id( $object_id );
		
		// If the user just edited, congratulate him.
		if( healthy_process_post_a_day_form() ) {

			$sugary_drinks = get_post_meta( $object_id, 'sugary_drinks', TRUE );
			$sugary = false;
			if( $sugary_drinks > 1 ) { $sugary = true; }

			$exercise_reminder = false;
			if( ! healthy_is_day_complete( $object_id ) ) {
				$daily_minimum = healthy_daily_minimum();
				$exercise_reminder = "<br><br>".sprintf( esc_html__( 'Remember to get at least %d minutes of exercise per day!', 'healthy' ), $daily_minimum );

			}

			if( ! $sugary ) {

				// Thank the usr for editing that day.
				$title = sprintf( esc_html__ ( 'You just edited your entry from %s.', 'healthy' ), $post_to_edit_date );
				$title .= $exercise_reminder;
				
				if( healthy_is_fortnight_full() ) {
					$subtitle = esc_html__( 'All your days are full.  Come back soon to enter more data!', 'healthy' );
				} else {
					$new_post_query = healthy_controller_query_string( 'post', 'create', 'new' );
					$new_post_href = esc_url( $base_url.$new_post_query );
					$new_post_label = esc_html__( 'You can post a new day here.', 'healthy' );
					$subtitle = "<a href='$new_post_href'>$new_post_label</a>";
				}

			} else {

				$message = healthy_get_sugar_slogan();
				$title = '<span class="sugary-title">'.$message[ 'title' ].'</span>';
				$title .= $exercise_reminder;
				$subtitle = $message[ 'subtitle' ];

			}

		// Else, prompt him to edit.
		} else {
			$subtitle = sprintf( esc_html__( 'Edit your entry from %s.', 'healthy' ), $post_to_edit_date );
			$title = esc_html__( 'Rewrite History!', 'healthy' );
		}

		// Either way, give him the edit form.
		$content = healthy_post_a_day_form( 'edit' );


	// If he's niether creating or editing, see if he's deleting.
	} elseif ( healthy_current_user_is_acting( 'delete', 'post', $object_id ) ) {

		// Did he just delete something?
		$deleting = healthy_process_delete_a_day_form();
		
		// If he just deleted something...
		if ( $deleting ) {

			// Prompt the user to enter data.
			$title = esc_html__( 'That day is totally deleted.  Make a new one.', 'healthy' );
			$subtitle = esc_html__( 'Greatest. Day. EVERrrrrrr.', 'healthy' );
			$content = healthy_post_a_day_form( false );

		// If he's about to delete something...
		} else {

			// Grab the date of the post he's deleting.
			$post_to_delete_date = healthy_get_post_date_by_id( $object_id );

			// Prompt the user to enter data.
			$title = sprintf( esc_html__( 'Was %s not such a good day?', 'healthy' ), $post_to_delete_date );
			$subtitle = sprintf( esc_html__( 'Really delete your entry from %s?', 'healthy' ), $post_to_delete_date );
			$content = healthy_delete_confirm( $_REQUEST['object_id'] );
		
		}

	// If the user is browsing a post...
	} elseif ( healthy_current_user_is_acting( 'review', 'post', $object_id ) ) {

		// Get the id of the post.
		$post_id = absint( $_REQUEST['object_id'] );

		// The date we're reviewing.
		$post_date_to_review = healthy_get_post_date_by_id( $post_id );

		// Subtitle for browsing a post.
		if( healthy_is_day_complete( $post_id ) ) {
			$title = esc_html__( 'Nice job!  This day is complete.', 'healthy' );
		} else {
			$title = esc_html__( 'So this was a rest day?', 'healthy' );
		}

		// Grab the min value of exercise for a day.
		$min = healthy_daily_minimum();

		// Use the date in the title.
		$subtitle = sprintf( esc_html__( '%d minutes of exercise completes each day!', 'healthy' ), $min );

		// The review a post view.
		$content = healthy_review_a_post( $post_id );

	// If the user is browsing all weeks
	} elseif( healthy_current_user_is_acting( 'review', 'week', 'all' ) ) {

		$title = esc_html__( 'Here&#8217;s what you did each week.', 'healthy' );
		$subtitle = esc_html__( 'You can edit data from the current week and browse data from past weeks.  Some of these values take about an hour to catch up after you enter data.', 'healthy' );
		$content = healthy_week_by_week();

	// If the user is browsing a week
	} elseif( healthy_current_user_is_acting( 'review', 'week', $object_id ) ) {

		// Sanitize the week id.
		$week_id = absint( $object_id );
		
		// Grab the min value of exercise for a day.
		$min = healthy_weekly_minimum();

		// If this week is complete, congratulate.
		if( healthy_is_week_complete( $week_id ) ) {
			$title = sprintf( esc_html__( 'Huzzah!  Week %d is complete.', 'healthy' ), $week_id );
		} else {
			$title = sprintf( esc_html__( 'You gotta get %s days per week!', 'healthy' ), $min );
		}

		$subtitle = sprintf( esc_html__( 'Here&#8217;s what you did in week %s:', 'healthy' ), $week_id );
		$content = healthy_choose_day_from_week( $week_id );

	// If the user is browsing a week
	} elseif( healthy_current_user_is_acting( 'review', 'report', $object_id ) ) {

		// Prompt the user to assume the identity of a user
		$title = esc_html__( 'Report Card', 'healthy' );
		$subtitle = esc_html__( 'You can review reports here.', 'healthy' );	

		$content = healthy_get_report();

	// As a default, if none of the above are met, offer appropriate defaults.
	} else {

		if( healthy_user_is_role( true, 'teacher' ) ) {

			// Prompt the user to browse reports.
			$title = esc_html__( 'Report Card', 'healthy' );
			$subtitle = esc_html__( 'You can view a broad report for the whole challenge, or more detailed reports for each week.', 'healthy' );			

			// The report view.
			$content = healthy_get_report();

		} elseif( healthy_user_is_role( false, 'boss' ) ) {

			// Prompt the user to assume the identity of a user
			$title = esc_html__( 'Contest Activity', 'healthy' );
			$subtitle = esc_html__( 'You can view reports on the challenge here.', 'healthy' );			

			// The edit profile form.
			$content = healthy_get_report();
		
		} elseif( healthy_is_fortnight_full() ) {

			// Prompt the user to browse past data.
			$title = esc_html__( 'So much data!  Come back tomorrow to enter a new day!', 'healthy' );
			$subtitle = esc_html__( 'You\'re doing good.  Right now there are no more days for you to enter! However you can browse your work thus far.', 'healthy' );
			$content = healthy_week_by_week();
	
		// If the week is not full yet
		} else {

			// Prompt the user to enter data.
			$title = esc_html__( 'This is the place to log your activity!', 'healthy' );
			$subtitle = esc_html__( 'So.  How was your day?', 'healthy' );
			$content = healthy_post_a_day_form( false );
	
		}
	
	}

	// Build the output.
	$out = healthy_get_the_title_and_content( $title, $content, $subtitle );

	return $out;
}

/**
 * A transient lifespan in seconds for our app.
 *
 * @todo  Set this to 3600 when the site goes live.
 * @return int A transient lifespan in seconds for our app.
 */
function healthy_transient_time () {
	return 1;
}

/**
 * Returns an array of slugs for roles that public users are allowed to occupy.
 * 
 * @return An array of slugs for roles that public users are allowed to occupy.
 */
function healthy_get_allowed_roles() {
	
	// Start the output.  WIll hold role slugs.
	$out = array();

	// All the roles in our app.
	$roles = healthy_get_roles();
	
	// For each role, see if it's public.
	foreach( $roles as $r ) {

		// If it's not public, bail.
		if( ! isset( $r[ 'is_public' ] ) ) { continue; }
		
		// If we made it this far, ass the slug to the output.
		$slug = $r[ 'slug' ];
		$out []= $slug;
	}

	return $out;
}

/**
 * There's no case in which an object ID should have special chars.  Sanitize them.
 *
 * @param string $object_id An object ID.
 * @return string An object ID, sanitized.
 */
function healthy_sanitize_object_id( $object_id ) {
	$object_id = sanitize_text_field( $object_id );
	return $object_id;
}

/**
 * Return the number of days complete in a week for a user.
 * 
 * @param  int $week_id Which week of our contest to check ( 1 - 6 )
 * @param  int $user_id The user ID to check.
 * @return int The number of days complete in a week for a user.
 */
function healthy_days_complete( $week_id, $user_id = ''  ) {
	
	// Which contest week.
	$week_id = absint( $week_id );

	// Convert the contest week to a query date( 'W' ) for querying.
	$query_week = healthy_convert_contest_week_to_query_week( $week_id );

	// $r is a query object.
	$user_id = absint( $user_id );
	if ( empty( $user_id ) ){
		$user_id = healthy_get_active_user_id();
	}

	// Get posts for this user in this week.
	$r = healthy_get_posts( $user_id, 7, false, false, false, $query_week );

	// The posts for thsi week for this author.
	$found_posts = $r -> found_posts;

	// If there are no entries this week, bail.
	if( empty( $found_posts ) ) { return 0; }

	// The entries for this week.
	$days = $r -> posts;

	// Will hold a counter for how many days complete so far this week.
	$out = 0;

	// For each day...
	foreach ( $days as $day ) {
		
		// if it's complete, increment the counter.
		if ( healthy_is_day_complete( $day -> ID ) ) {
			$out++;
		}
	}

	return $out;

}

/**
 * Return the User ID for the active user -- that's the user on whose
 * behalf a teacher would choose to act.
 * 
 * @return int The ID of the active user.
 */
function healthy_get_active_user_id() {

	// Our app-wide cookie key.
	$cookie_key = healthy_switched_user_cookie_key();

	// The state of having switched to a user is determined by this cookie.
	if ( ! isset( $_COOKIE[ $cookie_key ] ) ) { return get_current_user_id(); }

	// $active_user refers to the user whose been switched to, or, failiing that, the active user.
	$active_user_id = absint( $_COOKIE[ $cookie_key ] );

	return $active_user_id;

}

/**
 * Return the key used for the switched_user cookie.
 * 
 * @return string The key used for the switched_user cookie
 */
function healthy_switched_user_cookie_key() {
	return 'healthy_active_user';
}

/**
 * Return the school for the current or active user.
 * 
 * @param  boolean $active_user Grab from the active user (true) or the current user (false).
 * @return string|boolean Returns the school name or false on failure.
 */
function healthy_get_user_school( $user_id ) {

	// If the user is a boss, allow all schools.
	if( healthy_user_is_role( true, 'boss' ) ) { return 'all'; }

	// Grab the current user.
	$user_id = absint( $user_id );
	if( empty ( $user_id ) ) { return false; }

	// If the teacher has no school, bail.
	$school = get_user_meta( $user_id, 'school', TRUE );
	if( empty( $school ) ) { return false; }

	return $school;
}

/**
 * Return the object for the active user -- that's the user on whose
 * behalf a teacher would choose to act.
 * 
 * @return obj The object of the active user.
 */
function healthy_get_active_user() {

	// Get the active user ID.
	$healthy_get_active_user_id = healthy_get_active_user_id();

	// Get the user object for that ID.
	$userdata = get_userdata( $healthy_get_active_user_id );

	return $userdata;
}

/**
 * Returns the average for a stat in a given week.
 * 
 * @param  int $week_id The week of the contest to analyze.
 * @param  string $slug The slug for the stat we're analyzing.
 * @return string A number rounded to the nearest tenth, representing the average value.
 */
function healthy_get_weekly_average( $week_id, $slug, $user_id='' ) {
	
	// Start the output.
	$out = '';

	// Convert the contest week into a date( 'W' ).
	$query_week = healthy_convert_contest_week_to_query_week( $week_id );

	// Grab info for the active user.
	$user_id = absint( $user_id );
	if( empty( $user_id ) ) {
		$user_id = healthy_get_active_user_id();
	}

	// Get Posts from that week.
	$r = healthy_get_posts( $user_id, 7, false, false, false, $query_week );

	// The posts from that week.
	$days = $r -> posts;

	// Will hold the running tally for this stat in this week.
	$weekly_total = 0;

	// For each day...
	foreach( $days as $day ) {

		// Get the amount for that day
		$daily_amount = get_post_meta( $day -> ID, $slug, TRUE );
		$daily_amount = absint( $daily_amount );

		// Add it to the output
		$weekly_total = $weekly_total + $daily_amount;

	}

	// For past weeks, we divide by 7.
	$divide_by = 7;

	// For the current week, we divide by date( 'N' ).
	$current_week = date( 'W' );
	if( $current_week == $query_week ) {
		$divide_by = date( 'N' );
	}

	// Round it to the nearest 10th.
	$out = round( ( $weekly_total / $divide_by ), 1 );

	return $out;
}


/**
 * Returns the total for a stat in a given week.
 * 
 * @param  int $week_id The week of the contest to analyze.
 * @param  string $slug The slug for the stat we're analyzing.
 * @return string The total value.
 */
function healthy_get_weekly_total( $week_id, $slug, $user_id='' ) {
	
	// Start the output.
	$out = '';

	// Convert the contest week into a date( 'W' ).
	$query_week = healthy_convert_contest_week_to_query_week( $week_id );

	// Grab info for the active user.
	$user_id = absint( $user_id );
	if( empty( $user_id ) ) {
		$user_id = healthy_get_active_user_id();
	}

	// Get Posts from that week.
	$r = healthy_get_posts( $user_id, 7, false, false, false, $query_week );

	// The posts from that week.
	$days = $r -> posts;

	// Will hold the running tally for this stat in this week.
	$weekly_total = 0;

	// For each day...
	foreach( $days as $day ) {

		// Get the amount for that day
		$daily_amount = get_post_meta( $day -> ID, $slug, TRUE );
		$daily_amount = absint( $daily_amount );

		// Add it to the output
		$weekly_total = $weekly_total + $daily_amount;

	}

	$out = absint( $weekly_total );
	
	return $out;
}


/**
 * Returns an array of schools for our app.
 * 
 * @return An array of schools for our app.
 */
function healthy_get_schools() {

	$out = array(
			
		// Each school gets a slug and a label.
		array(
			'slug'  => sanitize_key( 'begich' ),
			'label' => 'Begich',
		),
	
		array(
			'slug'  => sanitize_key( 'goldenview' ),
			'label' => 'Goldenview',
		),
	
		array(
			'slug'  => sanitize_key( 'hanshew' ),
			'label' => 'Hanshew',
		),
	);

	return $out;

}

/**
 * Returns an array of fields for users in our app.
 * 
 * @return An array of fields for users in our app.
 */
function healthy_profile_fields() {

	$out = array(
		
		// The user first name.
		array(

			// The human-readable label for our field.
			'label' 	=> 'First Name',

			// The slug, used as a meta key, so we might as well sanitize it.
			'slug' 		=> sanitize_key( 'first_name' ),

			// The type of form input.
			'type' 		=> 'text',

			// A default value.
			'default' 	=> '',

			// Is this user meta?
			'is_meta' 	=> 1,

			// Is this field required?
			'required' 	=> 1,

			'exportable' => 1,
		),

		array(
			'label' 	=> 'Last Name',
			'slug' 		=> sanitize_key( 'last_name' ),
			'type' 		=> 'text',
			'default' 	=> '',
			'is_meta' 	=> 1,
			'required' 	=> 1,
			'exportable' => 1,
		),

		// Email is part of userdata, not usermeta.
		array(
			'label' 	=> 'Email',
			'slug' 		=> sanitize_key( 'user_email' ),
			'type' 		=> 'email',
			'default' 	=> '',
			'is_meta' 	=> 0,
			'required' 	=> 1,
			'exportable' => 1,
		),

	);

	// Grab the object id.
	$object_id = '';
	if( isset ( $_REQUEST['object_id'] ) ) {
		$object_id = $_REQUEST['object_id'];
	}

	// If the user is a student, or if we are creating a new user, allow him to pick a school & grade.
	if ( is_admin() || healthy_user_is_role( true, 'student' ) || healthy_current_user_is_acting( 'create', 'user', 'new' ) || healthy_current_user_is_acting( 'review', 'report', $object_id ) ) {

		// The school.
		$school = array(
			'label' 				  => 'School',
			'slug' 					  => sanitize_key( 'school' ),
			'type' 					  => 'school',
			'default' 				  => '',
			'is_meta' 				  => 1,
			'required' 				  => 1,
			'is_hidden_from_teachers' => 1,
			'exportable' 			  => 1,
			'add_to_wp_admin'		  => 1,
		);
		array_push( $out, $school );

		// The teacher of this student.
		$teacher = array(
			'label' 				  => 'Teacher',
			'slug' 					  => sanitize_key( 'teacher' ),
			'type' 					  => 'teacher',
			'default' 				  => '',
			'is_meta' 				  => 1,
			'is_hidden_from_teachers' => 1,
		//	'exportable' 			  => 1,
			'add_to_wp_admin'		  => 1,
		);
		array_push( $out, $teacher);

		// The grade.
		$grade = array(
			'label' 				  => 'Grade',
			'slug' 					  => sanitize_key( 'grade' ),
			'type' 					  => 'select',
			'options'				  => array( '6', '7', '8' ),
			'default' 				  => '',
			'is_meta' 				  => 1,
			'required' 				  => 1,
			'is_hidden_from_teachers' => 1,
			'exportable' 			  => 1,
		);
		array_push( $out, $grade);

	}

	// Password is part of userdata, not usermeta.  Expects plain text.
	$password = array(
		'label' 	=> 'Password',
		'slug' 		=> sanitize_key( 'password' ),
		'type' 		=> 'password',
		'default' 	=> '',
		'is_meta' 	=> 0,
		'required' 	=> 1,
	);
	array_push( $out, $password );

	// Password is part of userdata, not usermeta.  Expects plain text.
	$password_confirm =	array(
		'label' 	=> 'Password Confirm',
		'slug' 		=> sanitize_key( 'password_confirm' ),
		'type' 		=> 'password',
		'default' 	=> '',
		'is_meta' 	=> 0,
		'required' 	=> 1,
	);
	array_push( $out, $password_confirm );

	return $out;
}

function healthy_get_first_weekday_of_week() {
	return 'monday';
}

/**
 * Returns the date for $first_day_of_week of the current week.
 *
 * @return string The date for $first_day_of_week of the current week.
 */
function healthy_get_first_day_of_week() {
	
	// The epoch time for tomorrow.
	$tomorrow = strtotime('tomorrow');
	
	$first_day_of_week = healthy_get_first_weekday_of_week();

	// Whichever $first_day_of_week comes before tomorrow.
	$the_first_day_of_week_before_tomorrow = strtotime( "last $first_day_of_week", $tomorrow );
	
	// Format $first_day_of_week for display in our application.
	$first_day_of_week = date( 'l, F d, Y', $the_first_day_of_week_before_tomorrow );
	
	//$$first_day_of_week = date( 'l, F d, Y', strtotime('$first_day_of_week') );
	return $first_day_of_week;
}

/**
 * Returns the date for $first_day_of_week of the previous week.
 *
 * @return string The date for $first_day_of_week of the previous week.
 */
function healthy_get_first_day_of_last_week() {
	
	// The unix time for the first day of the current week.
	$first_day_of_current_week = strtotime( healthy_get_first_day_of_week() );

	$first_day_of_last_week = strtotime( '- 1 week', $first_day_of_current_week  );

	$first_day_of_last_week = date( 'l, F d, Y', $first_day_of_last_week );

	return $first_day_of_last_week;

}

/**
 * Returns HTML to build a post header and content from our controller function. 
 *
 * @param  string $title    The page title.
 * @param  string $content  The page content.
 * @param  string $subtitle The page subtitle.
 * @return string HTML to build a post header and content from our controller function.
 */
function healthy_get_the_title_and_content( $title, $content, $subtitle = '' ) {
	
	$out = '';

	// The title for our page.
	$title = "<h1>$title</h1>";

	// The subtitle for our page.
	if( ! empty ( $subtitle ) ) {
		$subtitle = "<p class='subtitle'>$subtitle</p>";
	}

	// Wrap the title & subtitle.
	$header = "<header class='entry-header editable-content'>$title$subtitle</header>";

	// The content for our page.
	$content = "<section class='entry-content editable-content content-holder'>$content</section>";

	// Warn the user if they have switched.
	$warning = healthy_the_switched_warning();

	// Complete the output.
	$out .= $warning.$header.$content;

	return $out;
}

/**
 * Given a post ID, return the datetime string for it.
 * 
 * @param  int $post_id The post id for which we're grabbing the date.
 * @return string The datetime string for a post.
 */
function healthy_get_post_date_by_id( $post_id ) {

	// The post ID we're grabbing from
	$post_id = $post_id;
	if( empty( $post_id ) ) { return false; }

	// The post obj we're grabbing from
	$post_obj = get_post( $post_id );
	if( empty( $post_obj ) ) { return false; }

	// The post date in WPDB format.
	$post_date = $post_obj -> post_date;

	// The post date in pretty format.
	$out = date( 'l, F d, Y', strtotime( $post_date ) );

	return $out;

}

/**
 * Returns a url query string for our application.
 * 
 * @param  string $object_type The type of object on which we'll act (user, post, week).
 * @param  string $action The agtion we'll perform (create, edit, delete, review).
 * @param  int $object_id  The ID upon which we'll act.
 * @param  string $unit_time The unit of time by which we'll query.
 * @param  int limit our query to all_star users.
 * @return string A url query string for our application.
 */
function healthy_controller_query_string( $object_type, $action, $object_id, $unit_time = '', $all_stars = '' ) {

	// User, post, week.
	$object_type = urlencode( $object_type );

	// Create, edit, delete, review.
	$action = urlencode( $action );

	// User id, post id, contest week #.
	$object_id = urlencode( $object_id );

	// 1, 2, 3, weekly.
	$unit_time = urlencode( $unit_time );

	// 0, 1.
	$all_stars = urlencode( $all_stars );


	// Output.
	$out = "?object_type=$object_type&action=$action&object_id=$object_id&all_stars=$all_stars";

	// Unit time is an optional param.
	if( ! empty( $unit_time ) ) {
		$out .= "&unit_time=$unit_time";
	} 
	
	return $out;
}

/**
 * Determine the current week of the contest.
 * 
 * @return int The number for the current week of the contest.
 */
function healthy_current_week_of_contest() {

	// Granb the current week.
	$current_week = date( 'W' );

	// What's the first week?
	$first_week = healthy_first_week_of_contest();

	// Subtract the first week from the current week.
	$off_by_one = $current_week - $first_week;

	// Add one.
	$out = $off_by_one + 1;

	return $out;
}

/**
 * Given a week of the contest, return the week( 'W' )
 * 
 * @param  int The week number for a week in the contest
 * @return int The week( 'W' ) for the given contest week.
 */
function healthy_convert_contest_week_to_query_week( $contest_week ) {

	// What week of the contest are we converting?
	$contest_week = absint( $contest_week );

	// What is the first week?
	$first_week = healthy_first_week_of_contest();

	// Add the two values.
	$off_by_one = $contest_week + $first_week;

	// subtract 1.
	$out = $off_by_one - 1;

	return $out;
}

/**
 * Grab the current url with query string.
 * @return [type] [description]
 */
function healthy_current_url(){
	
	// global $wp;

	// Start with the blog homepage.
	$out = trailingslashit( get_bloginfo('url') );

	// Break off the query string.
	$array = explode( '?', $_SERVER["REQUEST_URI"] );

	// If there was a query string, add it to the homepage.
	if( isset( $array[1] ) ) {
		if( ! empty( $array[1] ) ) {

			// Add the query string to the homepage.
			$query = $array[1];
			$out.='?'.$query;
	
		}

	}

	// Format the url.
	$out = esc_url( $out );

	return $out;
}

function healthy_get_slider( $slug, $min, $max, $step, $value, $unit_label='' ) {
	
	$slug = esc_attr( $slug );
	$min = absint( $min );
	$max = absint( $max );
	$step = absint( $step );
	$value = absint( $value );
	$unit_label = esc_html( $unit_label );

	$out = "
		<div data-unit-label='$unit_label' data-id='$slug' data-min='$min' data-max='$max' data-step='$step' data-value='$value' class='slider'></div>
		<input readonly type='text' name='$slug' id='$slug' value='$value'>
		<output class='output_$slug'>$value $unit_label</output>
	";

	return $out;
}

/**
 * Returns JS for powering the HTML range attr.
 * 
 * @return string JS for powering the HTML range attr.
 */
function healthy_range_script(){
	$out = <<<EOT
		<script>
		
			// DOM Ready
			jQuery( function( $ ) {
 				
				$( '.slider' ).each( function(){
					
					var value = parseInt( $( this ).attr( 'data-value' ) );
					var step = parseInt( $( this ).attr( 'data-step' ) );
					var min = parseInt( $( this ).attr( 'data-min' ) );
					var max = parseInt( $( this ).attr( 'data-max' ) );
					var id = $( this ).attr( 'data-id' );
					var unit_label = $( this ).attr( 'data-unit-label' );
										
					$( '#' + id ).attr( 'value', value );		
					
					$( this ).slider({
      					value: value,
      					min: min,
      					max: max,
      					step: step,
      					slide: function( event, ui ) {
      						$( '#' + id ).attr( 'value', ui.value );
      						$( '.output_' + id ).text( ui.value + ' ' + unit_label );
      					}
    				});
				});
			});

		</script>
EOT;
	return $out;
}

/**
 * Our app-wide function for getting posts.
 * 
 * @param  int $user_id The author ID we're querying.
 * @param  int $posts_per_page How many posts per page to return?
 * @param  int $year The date( 'Y' ).
 * @param  int $month The date( 'M' ).
 * @param  int $day  The date( 'd' ).
 * @param  int $week  The date( 'W' ).
 * @return boolean|object Returns a wp_query object or false on failure.
 */
function healthy_get_posts( $user_id = false, $posts_per_page = false, $year = false, $month = false, $day = false, $week = false, $dayofweek = false ) {

	// For what user are we querying?
	$user_id = absint( $user_id );
	if( empty( $user_id ) ) { $user_id = healthy_get_active_user_id(); }

	// How many posts per page?
	$posts_per_page = absint( $posts_per_page );
	if( empty ( $posts_per_page ) ) { $posts_per_page = 1; }

	// Start a holder array in case we're doing a date query.
	$date_query = array();

	// What year?
	$year = absint( $year );
	if( ! empty ( $year ) ) {
		$date_query['year']=$year;
	}

	// What month?
	$month = absint( $month );
	if( ! empty ( $month ) ) {
		$date_query['month']=$month;
	}

	// What date ( 1-31 )?
	$day = absint( $day );
	if( ! empty ( $day ) ) {
		$date_query['day']=$day;
	}

	// What week?
	$week = absint( $week );
	if( !empty ( $week ) ) {
		$date_query['week']=$week;
	}

	// What dayofweek (1-7)?
	$dayofweek = absint( $dayofweek );
	if( !empty ( $dayofweek ) ) {
		$date_query['dayofweek']=$dayofweek;
	}

	// Build the args.
	$args = array(
		'post_type' 	 => 'healthy_day',
		'post_status' 	 => array( 'any' ),
		'author' 	     => $user_id,
		'posts_per_page' => $posts_per_page,
		'date_query' 	 => $date_query,
		
	);

	// Find the posts.
	$query = new WP_Query( $args );
	
	return $query;
}

/**
 * Get the total minutes of exercise for a user for a week.
 * 
 * @param  int $user_id The User id.
 * @param  int $week_id The week id.
 * @return int          The total minutes of exercise for this week.
 */
function healthy_get_total_exercise_for_week( $user_id, $week_id ) {

	// The user id.
	$user_id = absint( $user_id );
	if( empty( $user_id ) ) { return false; }

	// The week id.
	$week_id = absint( $week_id );
	if( empty( $week_id ) ) { return false; }

	// Convert the contest week into a date( 'W' ).
	$query_week = healthy_convert_contest_week_to_query_week( $week_id );

	// Get Posts from that week.
	$r = healthy_get_posts( $user_id, 7, false, false, false, $query_week );

	// The posts from that week.
	$days = $r -> posts;

	// Will hold the running tally for this stat in this week.
	$weekly_total = 0;

	// App wide definition of a day.
	$healthy_day = healthy_day();

	// The fields that compose a day.
	$fields = $healthy_day[ 'components' ];

	// Will hold the minutes of exercise.
	$out = 0;

	// For each day...
	foreach( $days as $day ) {

		// Will hold the minutes of exercise for this day.
		$exercise_for_this_day = 0;

		// For each field...
		foreach( $fields as $f ) {
			
			// If it's not exercise, skip it.
			if ( ! isset( $f[ 'is_exercise' ] ) ) { continue; }
		
			$slug = $f[ 'slug' ];

			$this_exercise = get_post_meta( $day -> ID, $slug, TRUE );
			$this_exercise = absint( $this_exercise );
			$exercise_for_this_day = ( $this_exercise + $exercise_for_this_day ); 

		}

		// Add it to the output
		$out = $out + $exercise_for_this_day;

	}

	return $out;

}

/**
 * Grab our app-wide definition of what a day is.
 * @return array A multidimensional array containing all the facets of a day.
 */
function healthy_day() {

	// Grab the current day to power selected() if the user is making a new day.
	$current_date = date( 'l, F d Y' );

	// Here's what a day is:
	$day = array(

		// The label for a day.
		'label' => esc_html__( 'Day', 'healthy' ),
		
		// The sub components of a day, that drive form fields and post meta.
		'components' => array(
			
			// The post date.
			array(
				'label' 	=> esc_html__( 'Date', 'healthy' ),
				
				// The slugs will get santitized when used as meta keys, so we might as well prepare for that with sanitize_key()
				'slug' 		=> sanitize_key( 'date' ),
				'type' 		=> 'date',
				'default' 	=> $current_date,
				'script'	=> '',
			),

			// A text input for activity.
			array(
				'label' 	=> esc_html__( 'Activities', 'healthy' ),
				'slug' 		=> sanitize_key( 'activities' ),
				'type' 		=> 'text',
				'default' 	=> '',
			),

			// A range input for exercise.
			array(
				'label' 		=> esc_html__( 'Light Exercise', 'healthy' ),
				'slug' 			=> sanitize_key( 'light_exercise' ),
				'type' 			=> 'range',
				'min' 			=> 0,
				'max' 			=> 180,
				'default' 		=> 0,
				'notes'			=> esc_html__( 'You were moving, but your heart rate and breathing did not change much.', 'healthy' ),
				
				// Does the user deserve a smiley face is this number is higher than the week before?
				'more_is_good'  => true,

				// We'll flag things as exercise for computing the total minutes of exercise.
				'is_exercise' => 1,
				'step'		=> 5,

				// Does this field get rolled into the daily total for exercise time?
				'is_exercise' => 1,

				// Do we incude this field in the weekly average report?
				'is_weekly_metric' => 1,
			
				'unit'		=> array( 'minute', 'minutes' ),

			),

			// A range input for exercise.
			array(
				'label' 	=> esc_html__( 'Moderate Exercise', 'healthy' ),
				'slug' 		=> sanitize_key( 'moderate_exercise' ),
				'type' 		=> 'range',
				'min' 		=> 0,
				'max' 		=> 120,
				'default' 	=> 0,
				'notes'		=> esc_html__( 'Your breathing and heart rate were noticeably faster but you could have a conversation.', 'healthy' ),
				'more_is_good'  => true,
				'step'		=> 5,
				'is_exercise' => 1,
				'is_weekly_metric' => 1,

				'unit'		=> array( 'minute', 'minutes' ),
			),

			// A range input for exercise.
			array(
				'label' 	=> esc_html__( 'Vigorous Exercise', 'healthy' ),
				'slug' 		=> sanitize_key( 'vigorous_exercise' ),
				'type' 		=> 'range',
				'min' 		=> 0,
				'max' 		=> 120,
				'default' 	=> 0,
				'notes'		=> esc_html__( 'Your heart rate was increased and you were breathing too hard to have a conversation.', 'healthy' ),
				'more_is_good'  => true,				
				'step'		=> 5,
				'is_exercise' => 1,
				'is_weekly_metric' => 1,

				'unit'		=> array( 'minute', 'minutes' ),
			),

			// A range input for drinks.
			array(
				'label' 	=> esc_html__( 'Sugary Drinks', 'healthy' ),
				'slug' 		=> sanitize_key( 'sugary_drinks' ),
				'type' 		=> 'range',
				'min' 		=> 0,
				'step'		=> 1,
				'max' 		=> 8,
				'default' 	=> 0,
				'notes'		=> esc_html__( 'Sugary drink, sugar, corn syrup, or another type of caloric sweetener in the ingredient list', 'healthy' ),
				'more_is_good'  => false,				
				'is_weekly_metric' => 1,

				'unit'		=> array( 'drink', 'drinks' ),

			),
			/*array(
				'label' 	=> esc_html__( 'I declare, this is the truth.', 'healthy' ),
				'slug' 		=> 'honor',
				'type' 		=> 'checkbox',
				'default' 	=> 0,
			),*/
		),
	);

	return $day;
}

/**
 * Get the src for our logo.
 *
 * @return  The src for our logo.
 */
function healthy_logo_src( $filename = 'logo.png' ) {
	$filename = esc_attr( $filename );
	$out = esc_url( get_bloginfo( 'template_directory' ) )."/images/$filename";
	return $out;
}