<?php
/**
 * Functions to create/edit/delete data from the front end.
 *
 * @package WordPress
 * @subpackage healthy
 * @since healthy 1.0
 */

/**
 * Returns a definition list of average stats for the week.
 *
 * @param  int $week_id From what contest week are we grabbing?
 * @return  string An HTML definitition list of data for the week.
 */
function healthy_get_weekly_averages( $week_id ) {

	// Our plugin-wide definition of a day.
	$day = healthy_day();

	$week_id = absint( $week_id );

	// Meta fields for a day.
	$components = $day['components'];

	// This will hold meta keys that relate to exercise time.
	$keys = array();

	// Start the output.
	$out = "";

	// For each meta key that relates to exercise...
	foreach( $components as $c ) {

		// If this isn't measured weekly, skip it.
		if ( ! isset( $c[ 'is_weekly_metric' ] ) ) { continue; }
		
		// Will be used as a key to get the weekly average
		$slug = $c[ 'slug' ];

		// The label for this component.
		$label = esc_html( $c[ 'label' ] );

		// The weekly average for this component.
		$value = healthy_get_weekly_average( $week_id, $slug );

		// The week_id for last week.
		$last_week = $week_id - 1;

		// This will hold the result of comparing this week to last week for this stat.
		$compare ='';

		// If there was a last week...
		if ( ! empty ( $last_week ) ) {

			// Get the value from last week.
			$last_week_value = healthy_get_weekly_average( $last_week, $slug );
			
			// Compare last week to this week.
			$difference = $value - $last_week_value;
			
			// Make it absolute.
			$difference = str_replace( '-', '', $difference );
			
			// Output a string based on the result of the comparison.  More than the week before:
			if ( $value > $last_week_value ) {
				$compare = sprintf( esc_html__( " &mdash; That's %s more than the week before.", 'healthy' ), $difference );
			
			// Less than the week before.
			} elseif ( $value < $last_week_value ) {
				$compare = sprintf( esc_html__( " &mdash; That's %s less than the week before.", 'healthy' ), $difference );	
			
			// The same as the week before.
			} elseif ( $value == $last_week_value ) {
				esc_html__( "That\'s the same as last week.", 'healthy' );
			}
		}

		// Translate 'per day'.
		$per_day = esc_html( 'Per Day', 'healthy' );

		// Add this stat to the output.
		$out .= "<dt>$label $per_day </dt><dd>$value $compare</dd>";

	}

	// Complete the output.
	$out = "<dl>$out</dl>";

	return $out;

}

/**
 * Returns an HTML form to confirm that the user wants to delete a post.
 *
 * @param  int $deleting The ID of the post to delete
 * @return bool|string Returns false if error, otherwise returns delete form.
 */
function healthy_delete_confirm( $deleting ) {

	// If the post id is weird, bail.
	$deleting = absint ( $deleting );
	if( empty( $deleting ) ) { return false; }

	// If the user can't delete this post, bail
	if( ! healthy_current_user_can_act_on_object( $deleting, 'delete', 'post' ) ) {
		return false;
	}

	// The date of the post we're deleting.
	$date = healthy_get_post_date_by_id( $deleting );
	
	// The method by which to submit our form.
	$method = "post";
	
	// The action by which to handle our form.
	$form_action = healthy_current_url();
	
	// A nonce field for our form.
	$nonce = wp_nonce_field( 'healthy_delete', 'healthy_day_delete_nonce', false, false );

	// Strings for translation
	$delete_text = esc_attr__( 'Delete', 'healthy' );
	$cancel_text = esc_attr__( 'No, go back.', 'healthy' );
	
	// The url of the referring page ( aka "back" )
	$back_url = esc_url( $_SERVER['HTTP_REFERER'] );

	// A link to cancel the deletion and go back to the previous screen.
	$no = "<a href='$back_url'>$cancel_text</a>";

	// A hidden field to carry the post ID to delete to the handler script.
	$object_id = "<input type='hidden' name='object_id' value='$deleting'>";

	// A hidden field to carry the post ID to delete to the handler script.
	$object_type = "<input type='hidden' name='object_type' value='post'>";

	// A hidden field to carry the post ID to delete to the handler script.
	$action = "<input type='hidden' name='action' value='delete'>";

	// A submit button for out form.
	$submit = "<input type='submit' name='post_a_day_delete' value='$delete_text'>";

	// Build the form
	$out = "
		<form method='$method' action='$form_action'>
			$nonce
			$no
			$object_id
			$object_type
			$action
			$submit
		</form>
	";

	return $out;
}

/**
 * Returns an HTML form for creating or editing a post.
 * 
 * @param  boolean $editing Is the user editing a post?
 * @return bool|string  An HTML form for creating/editing a post, or false on error.
 */
function healthy_post_a_day_form( $editing = false ) {

	// Only logged in users can use this.
	if ( ! is_user_logged_in() ) { return false; }

	// Start the output var.
	$out = '';

	// The base url off of which we'll build various links and for actions.
	$base_url = trailingslashit( esc_url( get_bloginfo( 'url' ) ) );

	// The author to whom we'll attribute this post.
	$post_author_id = healthy_get_active_user_id();

	// Will get pre filled if we are editing.
	$post_date_to_edit = '';

	// If we're editing, do some checks and set some values
	if ( $editing ) {
		$action = 'edit';
		
		// Editing requires that we have a url var telling us what post to edit.
		if( ! isset( $_REQUEST[ 'object_id' ] ) ) { return false; }
		$post_id_to_edit = absint( $_REQUEST[ 'object_id' ] );

		// Can the user edit that post?  If not, bail.
		if( ! healthy_current_user_can_act_on_object( $post_id_to_edit, 'edit', 'post' ) ) {
			return false;
		} 

		// Build the date of the post we're editing.
		$post_date_to_edit = healthy_get_post_date_by_id( $post_id_to_edit );

	// If we're not editing, we're creating.
	} else {
		$action = 'create';
	}

	// The method by which we'll submit the form.
	$method = "post";

	// The action by which we'll handle the form.
	$form_action = healthy_current_url();

	/**
	 * A flag to check if the user has filled the current week.  If so, he can't
	 * add more posts this week.
	 * 
	 * @var bool A flag to detect if the current week is full.
	 */
	$week_is_full = false;

	// Our app-wide array that defines what a day is.
	$day = healthy_day();

	// Contains definitions for form fields
	$components = $day['components'];
	
	// For each field, add it to the form.
	foreach($components as $f ) {

		// The human readable name for the field.
		$label 	= $f[ 'label' ];
		
		// The unique name for the field.
		$slug 	= $f[ 'slug' ];
		
		// The input type.
		$type 	= $f[ 'type' ];
		
		// A min value.
		$min = '0';
		if (isset ( $f[ 'min' ] ) ){
			$min = $f[ 'min' ];
			$min = " min='$min' ";
		}
		
		// A max value.
		$max = '0';
		if (isset ( $f[ 'max' ] ) ){
			$max = $f[ 'max' ];
			$max = " max='$max' ";
		}
		
		// A step value.
		$step = '';
		if (isset ( $f[ 'step' ] ) ){
			$step = $f[ 'step' ];
			$step = " step='$step' ";
		}

		// A default value.
		$default = $f['default'];

		// If we're editing, things are a little different.
		if( $editing ) {

			// If we're editing and we're dealing with the date, grab that from the post object.
			if( $slug == 'date' ) {
				$default = $post_date_to_edit;
			} else {
				// If we're editing any other field, grab it from meta.
				$default = get_post_meta( $post_id_to_edit, $slug, TRUE );

				// If it's a range, grab zero instead of empty string for empty values.
				if( ( $type == 'range' ) && empty( $default ) ) { $default = 0; }

			}

		// If we're not editing, just grab the app-wide default for that field.
		} else {
			$default = $f['default'];
		}

		// Draw the input.
		if( $type == 'range' ) {
			$input = "<input name='$slug' class='$type' $min $max $step type='$type' id='$slug' value='$default'>";
		} elseif ( $type == 'date' ) {
			
			// determine the post author, as different dates will be avaiulable for different authors.
			$post_author_id = absint( $post_author_id );
			if ( empty ( $post_author_id ) ) { wp_die( 'There has been a problem. 433' ); }

			// Try to draw the date input, will fail if week is already full for this user.
			$input = healthy_days_of_week_select( $post_author_id, $post_date_to_edit );
			
			// If we can't draw it, it's because the week is full, so set that flag.
			if ( ! $input ) {
				$week_is_full = true;
			}

		} else {
			$input = "<input name='$slug' class='$type' type='$type' id='$slug' value='$default'>";
		}

		// if the week is full, don't throw a date input
		if( ( $type == 'date' ) && ( $week_is_full ) ) { continue; }

		// A holder for min/max ( slider ) inputs.
		$output = "";
		if ( $type == 'range' ) {
			$output ="
				<output name='amount_$slug' for='$slug'>$default</output>
			";
		}	

		// Build the whole field and add it to the output. 
		$out.="
			<label for='$slug'>
				$label
				$input
				$output
			</label>
		";

	} // End for each field.

	// A nonce for our form.
	$nonce = wp_nonce_field( 'healthy_create', 'healthy_day_nonce', false, false );

	// The text for our form submit.
	if ( $editing ) {
		$submit_text = esc_attr__( 'Edit', 'healthy' );
	} else {
		$submit_text = esc_attr__( 'Go', 'healthy' );
	}

	// The submnit button for out form.
	$submit = "<input type='submit' name='post_a_day_submit' value='$submit_text'>";

	// JS to power our slider inputs.
	$range_script = healthy_range_script();

	//wp_enqueue_script( 'jquery-ui-datepicker' );//$date_script = healthy_date_script();

	/**
	 * If we're editing, add a delete link and a hidden form field to carry this
	 * to the processor script.
	 */
	$edit_hidden = '';
	$delete = '';
	if ( $editing ) {
		
		// Build a delete link. 
		$delete_text = esc_html__( 'Delete this entry.', 'healthy' );
		$query = healthy_controller_query_string( 'post', 'delete', $post_id_to_edit );
		$delete_href = $base_url.$query;
		$delete = "<a class='deemphasize' href='$delete_href'>$delete_text</a>";
		
	}

	// A field to carry the object type.
	$object_type = "<input type='hidden' name='object_type' value='post'>";
	
	// A field to carry the action.
	$action = "<input type='hidden' name='action' value='$action'>";

	// Create a field to carry the id of the post we're editing.
	if( $editing ) {
		$object_id = "<input type='hidden' name='object_id' value='$post_id_to_edit'>";
	} else {
		$object_id = "<input type='hidden' name='object_id' value='new'>";
	}

	// Build the form.
	$out = "
		<form method='$method' action='$form_action'>
			$nonce
			$out
			$object_id
			$object_type
			$action
			$submit
			$delete
		</form>
		$range_script
	";

	return $out;

}

/**
 * Return <option>s for HTML select, to determine post date when entering a day.
 *
 * @param string $post_author_id The author of the post this <select> applies to, used to filter out days for which he has already posted.
 * @param string $post_date_to_edit The date of the post we're editing, to drive selected().
 * @return bool|string Returns false if current week is full, otherwise returns html <option>s.
 */
function healthy_days_of_week_select( $post_author_id, $post_date_to_edit = '' ) {
	
	// Start the output.  This will remain empty if there are no days available this week.
	$options ="";

	// Get the time in seconds for the first day of the week.
	$first_day_of_week = healthy_get_first_day_of_week();
	
	// The current day.
	$current_day_in_days = date( 'z', strtotime( 'today' ) );
	$current_day_in_text = date( 'l, F d, Y', strtotime( 'today' )  );
	
	// Are we editing an existing post?  If so, grab the date to power selected().
	$post_date_to_edit_in_text = date( 'l, F d, Y', strtotime( $post_date_to_edit ) );

	// Increment each day of the week.
	$i=0;
	while( $i < 7 ) {
				
		// Grab the text value for easier comparision to the current day
		$next_day_in_text = date( 'l, F d, Y', strtotime( "$first_day_of_week + $i days" ) ); 

		// Jan 1 = 0, dec 31 = 364.
		$next_day_in_days = date( 'z', strtotime( $next_day_in_text ) );

		// If we're editing, select that date.
		if ( ! empty( $post_date_to_edit ) ) {
			$selected = selected( $post_date_to_edit_in_text, $next_day_in_text, false );
		
		// Otherwise, select the current date.
		} else {
			$selected = selected( $current_day_in_text, $next_day_in_text, false );	
		}

		// If there's already an entry on this day and we're not editing it, don't offer it.
		if( healthy_already_an_entry_for_this_day( $post_author_id, $next_day_in_text ) && ( $post_date_to_edit_in_text != $next_day_in_text ) ) {
			$i++;
			continue;
		}

		// If the date is in the future, don't offer it.
		if( $next_day_in_days > ( $current_day_in_days ) ) {
			$i++;
			break;
		}

		// Increment to the next day of the week.
		$i++;

		// Add this day the output.
		$options.="<option $selected value='".esc_attr( $next_day_in_text )."'>".esc_html( $next_day_in_text )."</option>";	

	}
	
	// If there is nothing to output, all the days this week are taken.
	if( empty( $options ) ) {
		return false;
	}

	// Wrap the output, return.
	$out = "
		<select name='date'>$options</select>
	";

	return $out;

}

/**
 * Return an HTML list of days this week for browsing.
 * 
 * @return bool|string Returns a list of posts for the week, or false if no posts.
 */
function healthy_choose_day_from_week( $week ) {

	// We need a week to check in.  Used in a wp query date query.
	$week = absint( $week );
	if( empty( $week ) ) { return false; }

	// Grab the active user so we can show only his posts.
	$post_author_id = absint( healthy_get_active_user_id() );
	if( empty( $post_author_id ) ) { return false; }
	
	// Query for posts by this author in this week -- there should only ever be 1 per day, so we only look for 7.
	$query_week = healthy_convert_contest_week_to_query_week( $week );

	// $r is a query object.
	$r = healthy_get_posts( $post_author_id, 7, false, false, false, $query_week );

	// The posts for thsi week for this author.
	$found_posts = $r->found_posts;

	// If there are no entries this week, bail.
	if( empty( $found_posts ) ) {
		
		// Tell the user there are no posts this week.
		$no_entries = sprintf( esc_html__( 'No entries for week %s.', 'healthy' ), $week );

		// Holder var to build a link prompting the user to add some posts for this week.
		$prompt = '';

		// Is it the current week of the contest?  If so, we can add posts for this week.
		$current_week_of_contest = healthy_current_week_of_contest();
		
		// Build a link prompting the user to add some posts for this week.		
		if ( $week == $current_week_of_contest ) {
			$prompt = "<a href='".get_bloginfo('url')."'>Enter a day for this week.</a>";
		}

		// Return text to instruct the user on the status of this week.
		return $no_entries.' '.$prompt;
	}

	// The posts tht we found.
	$posts = $r->posts;	

	// Will hold links for each post.
	$days = '';

	// We'll increment this to make sure we don't loop past the current day.
	$i = 0;

	// Foreach post
	foreach( $posts as $p ) {
		
		// The post ID
		$post_id = absint( $p->ID );
		
		// The date of the post.
		$date = esc_html( $p->post_date );
		$date = date ( 'l, F d, Y', strtotime( $date ) );
		
		// The link to edit that post
		$base_url = trailingslashit( esc_url( get_bloginfo( 'url' ) ) );

		// If the user can edit this post, give him a link to do so.
		if ( healthy_current_user_can_act_on_object( $post_id, 'edit', 'post' ) ) {
			$query = healthy_controller_query_string( 'post', 'edit', $post_id );
			$href = $base_url.$query;

		// Else, give him a link to review the post.
		} else {
			$query = healthy_controller_query_string( 'post', 'review', $post_id );
			$href = $base_url.$query;

		}	

		// The link to delete that post
		$delete_link = '';
		
		// If the user can delete this post...
		if ( healthy_current_user_can_act_on_object( $post_id, 'delete', 'post' ) ) {

			// Build a delete link.
			$delete_text = esc_html__( 'Delete this entry', 'healthy' );
			$query = healthy_controller_query_string( 'post', 'delete', $post_id );
			$delete_href = $base_url.$query;
			$delete_link = "<a class='deemphasize' href='$delete_href'>$delete_text</a>";
		}

		// If the day is/not complete, offer a message to describe the day.
		if ( healthy_is_day_complete( $post_id ) ) {
			$status = esc_html__( 'Complete!', 'healthy' );
		} else {
			$status = esc_html__( 'Not quite enough exercise this day.', 'healthy' );
		}

		// Output the text for this day.
		$days.="<li><a href='$href'>$date</a> &mdash; <em>$status</em> $delete_link</li>";
		
		// Increment the day for this week.
		$i++;
	}

	// Build the output if there are any days.
	if ( empty( $days ) ) { return false; } 
	$out ="
		<ul class='days_from_week'>
			$days
		</ul>
	";

	return $out;

}

/**
 * Browse vague data from each week of the contest.
 * 
 * @return string Data from each week of the contest.
 */
function healthy_week_by_week(){

	// Start our output variable.
	$out = '';

	// The base url off of which we'll build links.
	$base_url = trailingslashit( esc_url( get_bloginfo( 'url' ) ) );

	// The date( 'W' ) for the first week of the contest
	$week = healthy_first_week_of_contest() - 1;
	
	// The # of weeks for the contest
	$length_of_contest = healthy_length_of_contest();
	
	// The date( 'W' ) for the final week of the contest
	$final_week = $week + $length_of_contest;
	
	// Carry the value for which week of the contest ( 1, 2, 3 ... )
	$i = 0;

	// The current week.
	$current_week = date( 'W' );
	
	// Increment through the weeks of the competition.
	while( $week <= $final_week ) {

		// Increment the values for $i and $week.
		$i++;
		$week++;

		// Check to see if there were posts in this week.
		$posts = healthy_get_posts( false, 100, false, false, false, $week );

		// The number of posts for that week.
		$number_of_days_entered = $posts -> found_posts;

		// Build a link prompting the user to CRUD that week.
		$maybe_edit_or_create_or_review = '';
	
		// If we're in the current week, we can create and edit.
		if( $current_week == $week ) {

			// If there are days, we can edit them.
			if ( ! empty ( $number_of_days_entered ) ) {
				
				// Build a link prompting the user to edit posts in that week.
				$query = healthy_controller_query_string( 'week', 'review', $i );
				$edit_href = $base_url.$query;
				$edit = esc_html__( 'Edit', 'healthy' );
				$maybe_edit_or_create_or_review = "<a href='$edit_href'>$edit</a>";
			
			// If there are no posts, prompt the user to create some.
			} else {
		
				// Build a link prompting the user to create posts in that week.
				$query = healthy_controller_query_string( 'post', 'create', 'new' );
				$create_href = $base_url.$query;
				$create = esc_html__( 'Create an entry', 'healthy' );
				$maybe_edit_or_create_or_review = "<a href='$create_href'>$create</a>";

			}

		// If we're not in the current week, we can review the past week -- if it has days entered in it.
		} elseif ( ( $current_week > $week ) && $number_of_days_entered > 0 ) {

			// Build a link prompting the user to review posts in this week.
			$query = healthy_controller_query_string( 'week', 'review', $i );
			$review_href = $base_url.$query;
			$review = esc_html__( 'Review these entries', 'healthy' );
			$maybe_edit_or_create_or_review = "<a href='$review_href'>$review</a>";

		// If we're not in the current week and it does not have days, skip it.
		} else {
			continue;
		}

		// Is the week complete?
		if( healthy_is_week_complete( $i ) ) {
			$complete = sprintf( esc_html( 'Nice! This week is complete.', 'healthy' ), $i );
		} else {
			$complete = sprintf( esc_html( 'Rest week, eh?', 'healthy' ), $i );
		}

		// Translate.
		$week_string = sprintf( __( 'Week %d:', 'healthy' ), $i );
		$week_string = "<span class='week-label'>$week_string</span>";

		// Add a label for the week, tensed for past/present. 
		if ( $current_week == $week ) {
			$you_entered = sprintf( _n( "You&#8217;ve entered %d day so far.", "You&#8217;ve entered %d days so far.", $number_of_days_entered, 'healthy' ), $number_of_days_entered );
		} else {
			$you_entered = sprintf( _n( "You&#8217;ve entered %d day.", "You&#8217;ve entered %d days.", $number_of_days_entered, 'healthy' ), $number_of_days_entered );		
		}
		$you_entered = "<p>$you_entered $complete $maybe_edit_or_create_or_review</p>";

		// Get the average values for this week.
		$averages = healthy_get_weekly_averages( $i );

		// Add this week to the output.
		$out .= "<li>$week_string $you_entered $averages</li>";

	}

	// Wrap the output, return.
	$out = "<ul>$out</ul>";
	return $out;

}

/**
 * Browse specific data from a day of the contest.
 *
 * @param  int The post id we're reviewing.
 * @return string Data for that post.
 */
function healthy_review_a_post( $post_id ) {

	// The post id we're reviewing.
	$post_id = absint( $post_id );
	if( empty( $post_id ) ) { return false; }

	// The post obj we're reviewing.
	$post_obj = get_post( $post_id );
	if( empty( $post_obj ) ) { return false; }

	// The date for the post we're reviewing.
	$post_date = $post_obj -> post_date;
	$post_date = date( 'l, F d, Y', strtotime( $post_date ) );

	// Use the date as the title.
	$title = "<h3>$post_date</h3>";

	// Our app-wide definition of what a day contains.
	$day = healthy_day();

	// Contains definitions for day fields.
	$components = $day['components'];
	
	// For each field, add it to the output.
	foreach($components as $f ) {

		// The human readable name for the field.
		$label 	= $f[ 'label' ];
		
		// The unique name for the field.
		$slug 	= $f[ 'slug' ];
		
		// We already used date.
		if( $slug == 'date' ) { continue; }

		// Get the value for this field.
		$meta = get_post_meta( $post_id, $slug, TRUE );

		// Output.
		$out.="
			<dt>$label</dt>
			<dd>$meta</dd>
		";

	}

	// If there is not post data, bail.
	if( empty( $out ) ) { return false; }

	// Wrap the output as a dl.
	$out = "$title<dl>$out</dl>";

	return $out;
}