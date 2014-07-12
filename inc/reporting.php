<?php

/**
 * Functions to grab and report on bulk data.  Most of these will operate off of transients.
 *
 * @package WordPress
 * @subpackage healthy
 * @since healthy 1.0
 */

/**
 * Returns an HTML nav menu for gathering various reports.
 * 
 * @return An HTML nav menu for gathering various reports.
 */
function healthy_reporting_menu() {

	// Get the school for the current user.
	$school = healthy_get_user_school( get_current_user_id() );

	// A base url off of which we'll build links.
	$base = trailingslashit( esc_url( get_bloginfo( 'url' ) ) );
	
	// A query string to view reports from our school.
	$query = healthy_controller_query_string( 'report', 'review', $school, 'weekly' );
	
	// A query string to view weekly.
	$weekly_href = $base.$query;

	// A lebel to view weekly reports.
	$weekly_label = esc_html__( 'All Weeks', 'healthy' );

	// A link to view weekly reports.
	$weekly_link = "<a href='$weekly_href'>$weekly_label</a>";

	// Get the contest length.
	$max_week = healthy_length_of_contest();

	// Will hold links for each week.
	$week_by_week = '';

	// No need to go past the current week.
	$current_week_of_contest = healthy_current_week_of_contest();
	
	// For each week of the contest, show a link to view details from that week.
	$week = 0;

	// For each week, short of the final week...
	while( $week <= $max_week ) {

		// Increment the week ID.
		$week++;

		// If we're past the current week, bail.
		if ( $week > $current_week_of_contest ) { break; }

		// The label for the report for this week.
		$week_by_week_label = sprintf( esc_html__( 'Week %d', 'healthy' ), $week );

		// The href for the report for this week.
		$week_by_week_href = $base.$query."&unit_time=$week";

		// The link for the report for this week.
		$week_by_week.="<a href='$week_by_week_href'>$week_by_week_label</a> ";

	}

	// May hold a link to browse all schools.
	$by_school = '';
	
	// If the user is a boss, he can browse info from all schools.
	if( healthy_user_is_role( false, 'boss' ) ) {
		
		// A query string to browse all schools
		$by_school_query = healthy_controller_query_string( 'report', 'review', 'all', 'all' );

		// An href to browse all schools for all time.
		$by_school_href = $base.$by_school_query;

		// A label to browse all schools.
		$by_school_label = esc_html__( 'By School', 'healthy' );

		// A link to browse all schools.
		$by_school = "<a href='$by_school_href'>$by_school_label</a>";
	}

	// Build a link to view all-stars
	$all_stars_label = esc_html( 'All-Stars', 'healthy' );
	$all_stars_href = healthy_controller_query_string( 'report', 'review', $school, 'all', 1 );
	$all_stars_link = "<a href='$all_stars_href'>$all_stars_label</a>";

	// This may hold a value for a link to download the report as csv.
	$as_csv_link = '';

	// If the screen is downloadable:
	if ( isset( $_GET[ 'object_type' ] ) && isset( $_GET[ 'action' ] ) && isset( $_GET[ 'object_id' ] ) ) {

		// A label to grab as csv. 
		$as_csv_label = esc_html( 'Download as CSV', 'healthy' );
	
		// A query string to grab as csv.
		$as_csv_arg = '&as_csv=1';
		$as_csv_base = healthy_current_url();

		// An href to grab as csv.
		$as_csv_href = $as_csv_base.$as_csv_arg;
	
		// A link to grab as csv.
		$as_csv_link = "<a class='as_csv' href='$as_csv_href'>$as_csv_label</a>";

	}
	
	// Complete the output.
	$out = "<nav class='reporting-menu'>$by_school ".$weekly_link.' '.$week_by_week.' '.$all_stars_link.' '.$as_csv_link."</nav>";

	return $out;

}

/**
 * A transient key for our reports.
 *
 * @return string A transient key for our reports.
 */
function healthy_transient_key() {

	// The output format.
	$format = 'table';
	if( isset( $_GET[ 'as_csv' ] ) ) { $format = 'csv'; }

	// The object ID.
	$object_id = '';
	if( isset( $_GET[ 'object_id' ] ) ) { $object_id = sanitize_text_field( $_GET['object_id'] ); }

	// The unit time.
	$unit_time = '';
	if( isset( $_GET[ 'unit_time' ] ) ) { $unit_time = sanitize_text_field( $_GET['unit_time'] ); }

	// Bundle it up as a transient key.
	$out = "healthy_report_$format$object_id$unit_time";

	return $out;
}

/**
 * A transient lifespan in seconds for our reports.
 *
 * @return string A transient lifespan in seconds for our reports.
 */
function healthy_transient_time () {
	return '1';
}

/**
 * Append the next cell to an output variable in our report.
 * 
 * @param  string|array 	$out The value to which we're appending.
 * @param  string $cell   	The value we're appending.
 * @param  string $format 	Table or csv.
 * @param  string $before 	<td> or <th> for tables, or empty string for arrays.
 * @param  string $after  	</td> or </th> for tables, or empty string for arrays.
 * @return string|array  	The output var for the report, with the nw cell appended.
 */
function healthy_append_cell( $out, $cell, $format = 'table', $before='', $after = '' ) {

	// In the case of a table, add the table cell.
	if ( $format == 'table' ) {
		$out .= $before.$cell.$after;
		
	// If csv, append the cell as an array member.
	} elseif( $format == 'csv' ) {
		$out []= $cell;
	}
	
	return $out;
}

/**
 * When reporting on a school, offer these fields.
 *
 * @todo  I don't think slug is being used anywhere.
 * @return  array An array to denote what fileds to report on when reporting on a school.
 */
function healthy_school_report_cells( $school ) {
	
	// Start the output.
	$out = array(

		// The total students in the contest for this school.
		array(
			'slug' 	   => 'total_students_in_contest',
			'label'    => 'Total Students In Contest',
			'callback' => 'healthy_count_users_by_school'
		),
		
		// The total days complete for this school.
		array(
			'slug' 	   => 'total_days_complete',
			'label'    => 'Total Days Complete',
			'callback' => 'healthy_days_complete_by_school'
		),

		// The average days complete per student in this school.		
		array(
			'slug'     => 'days_complete_per_student',
			'label'    => 'Days Complete Per Student',
			'callback' => 'healthy_days_complete_per_student_by_school'
		),
	);
	return $out;
}

/**
 * Get a row of cells for a report.
 * 
 * @param  int|string $unit_time 	Grab one week (week ID number), or all weeks ("weekly").
 * @param  boolean $user_id   		The user ID from whom we'll grab data.  If none provided, grabs the table header cells.
 * @param  string  $format    		As table or csv.
 * @param  string  $school 			From what school are we grabbing?
 * @param  string  $all_stars 		Restrict our search to all-star users?
 * @return string|array             Returns an html <tr> or an array of cells.
 */
function healthy_get_row( $unit_time, $user_id = false, $format = 'table', $school = '', $all_stars = '' ) {

	// Start the output var.  If it's a table, start a string.
	if ( $format == 'table' ) {
		$out = '';
		
		// If csv, append the cell as an array member.
	} elseif( $format == 'csv' ) {
		$out = array();
	}

	// If the user is a boss and is grabbing from all weeks ( That is, grabbing by school. )
	if( healthy_user_is_role( false, 'boss' ) && ( $unit_time == 'all' ) && ( $all_stars != 1 ) ) {

		// The schools in our contest.
		$schools = healthy_get_schools();
	
		// The slugs for each school.
		$school_slugs = array();
		foreach( $schools as $s ) {
			$school_slugs []= $s['slug'];
		}

		// If school still equals 'all', we're on the first row of the table.
		if ( $school == 'all' ) {

			// Before each cell.
			$before = '<th>';

			// After each cell.
			$after = '</th>';

		// If school refers to a specific school, we're past the header row.
		} elseif ( in_array( $school, $school_slugs ) ) {

			// Before each cell.
			$before = '<td>';
		
			//After each cell.
			$after = '</td>';

		}

		// If we're in the header row, start with the label "School".
		if ( $school == 'all' ) {
			$cell = esc_html__( 'School', 'healthy' );
		
		// Else if we're past the header row, grab the label for that school.
		} else {
			
			// Convert the school slug to a readable label.
			$school_label = ucwords( $school );
			$school_label = str_replace( '_', ' ', $school_label );
			$cell = $school_label;
		}

		// Append to the output var.
		$out = healthy_append_cell( $out, $cell, $format, $before, $after );

		// The fields on which we report for each school.
		$school_report_cells = healthy_school_report_cells( $school );

		// For each field
		foreach( $school_report_cells as $c ) {
			
			// If we're on the header row, start with the label.
			if ( $school == 'all' ) {
				$label = $c[ 'label' ];
				$cell = $label;

			// If we're past the header row, grab the data with a callback function.
			} else {
				$callback = $c[ 'callback' ];
				$cell = call_user_func( $callback, $school );
			}


			// Append to the output var.
			$out = healthy_append_cell( $out, $cell, $format, $before, $after );
		}  

	// Endif the user is a boss and is grabbing from all weeks (that is, grabbing by school).  We're grabbing data by users now.
	} else {

		// If a user id is provided, we'll be getting cells for that user.
		if ( $user_id ) {

			// Before each cell.
			$before = '<td>';
			
			//After each cell.
			$after = '</td>';

			// The data for the user we're grabbing.
			$user = get_userdata( $user_id );

			// If there is no such user, something's wrong.
			if ( ! $user ) { return false; }

		// If no user ID is provided, grab the table header.
		} else {

			// Before each cell.
			$before = '<th>';

			// After each cell.
			$after = '</th>';
		}

		// The user meta fields which we'll grab for each user.
		$user_fields = healthy_profile_fields();

		// For each user field...
		foreach( $user_fields as $f ) {

			// If this field is not exportable, don't worry about it.
			if ( ! isset( $f[ 'exportable' ] ) ) { continue; }
			
			// If we're grabbing from a user...
			if( $user_id ) {

				if ( ! empty( $all_stars ) && ! healthy_user_is_all_star( $user_id ) ) { continue; }

				// Start the cell.
				$cell = '';

				// The slug for this field.
				$slug = $f[ 'slug' ];

				// The meta for this field.
				$meta = get_user_meta( $user_id, $slug, TRUE );
			
				// If we're grabbing the user school, convert the school to a readable label.
				if( $slug == 'school' ) {
					$meta = ucwords( $meta );
					$meta = str_replace( '_', ' ', $meta  );
				}

				// If there is meta, populate the cell.
				if( ! empty( $meta ) ) {
					$cell = $meta;

				// Else, check in the user data.
				} elseif ( $meta = $user -> $slug ) {
					$cell = $meta;
				}
			
				// Sanitize the meta.
				$meta = esc_html( $meta );
			
			// If we're not grabbing from a user, just grab the label for the table header.
			} else {
				$cell = $f['label'];
			}

			// Append to the outout var.
			$out = healthy_append_cell( $out, $cell, $format, $before, $after );

		// End for each user field.
		}

		// Our app-wide definition of a day.
		$day = healthy_day();

		// Fields of data for a day.
		$day_fields = $day['components'];

		// The current year.
		$year = date( 'Y' );

		// If we're grabbing a report of each week...
		if( $unit_time == 'weekly' ) {

			// Determine the current week, so we don't loop past it.
			$current_week_of_contest = healthy_current_week_of_contest();

			// Will increment to refer to each contest week.
			$i = 0;

			// While the week is less than the current week.
			while( $i < $current_week_of_contest ) {
			
				// Increment the week ID.	
				$i++;
				
				// Convert the week ID to a date( 'W' ).
				$query_week = healthy_convert_contest_week_to_query_week( $i );	

				// If we're grabbing data from a user...
				if( $user_id ) {

					// For each part of a day...
					foreach( $day_fields as $f ) {
				
						// Only concern ourselves with weekly values.
						if ( ! isset( $f[ 'is_weekly_metric' ] ) ) { continue; }
						
						// The slug for this value.
						$slug = $f[ 'slug' ];

						// Get the average for this user/week/value.
						$cell = healthy_get_weekly_average( $i, $slug, $user_id );
				
						// If we're writing a table, append this cell.
						if( $format == 'table' ) {
							$out.="$before$cell$after";

						// If we're writing a spreadsheet, append to the array for this row.
						} elseif( $format == 'csv' ) {
							$out []= $cell;
						}
					
					// End foreach day field.
					}

					// Is the week complete?
					if ( healthy_is_week_complete( $i, $user_id ) ) {
						$cell = esc_html__( 'yep', 'healthy' );
					} else {
						$cell = esc_html__( 'nope', 'healthy' );
					}
					$out = healthy_append_cell( $out, $cell, $format, $before, $after );

					// How many days are complete this week?
					$cell = healthy_days_complete( $i, $user_id );
					$out = healthy_append_cell( $out, $cell, $format, $before, $after );	

					// Total exercise for this week?
					$cell = healthy_get_total_exercise_for_week( $user_id, $i );
					$out = healthy_append_cell( $out, $cell, $format, $before, $after );	

				// If we're not grabbing from a user, just output the header row.
				} else {

					// For each day field...
					foreach( $day_fields as $f ) {

						// If it's not a week metric, ignore it.
						if ( ! isset( $f[ 'is_weekly_metric' ] ) ) { continue; }

						// The label for this field.
						$label = $f['label'];	

						// The week ID.
						$cell = sprintf( esc_html__( 'Week %d Average %s', 'healthy' ), $i, $label );
						$out = healthy_append_cell( $out, $cell, $format, $before, $after );
				
					// End for each day field.
					} 

					// Is this week complete?
					$cell = sprintf( esc_html__( 'Week %d Complete?', 'healthy' ), $i );
					$out = healthy_append_cell( $out, $cell, $format, $before, $after );

					// How many days are complete this week?
					$cell = sprintf( esc_html__( 'Week %d Days Complete', 'healthy' ), $i );			
					$out = healthy_append_cell( $out, $cell, $format, $before, $after );

					// Total exercise for this week?
					$cell = sprintf( esc_html__( 'Week %d Total Exercise', 'healthy' ), $i );			
					$out = healthy_append_cell( $out, $cell, $format, $before, $after );

				}
			
			// End for each week of the contest.
			}

		// Endif we're grabbing a report from all weeks.  Instead, if we're grabbing a report for each day of a week...
		} elseif ( is_numeric( $unit_time ) ) {

			// If we're grabbing data from a user...
			if( $user_id ) {
					
				// Is the week complete?
				if ( healthy_is_week_complete( $unit_time, $user_id ) ) {
					$cell = esc_html( 'yep' );
				} else {
					$cell = esc_html( 'nope' );	
				}		
				$out = healthy_append_cell( $out, $cell, $format, $before, $after );

				// How many days are complete this week?
				$cell = healthy_days_complete( $unit_time, $user_id );
				$out = healthy_append_cell( $out, $cell, $format, $before, $after );

				// Total exercise for this week?
				$cell = healthy_get_total_exercise_for_week( $user_id, $unit_time );
				$out = healthy_append_cell( $out, $cell, $format, $before, $after );	

			// If we're not grabbing data from a user, just make the table header.
			} else {

				// Is the week complete?
				$cell = sprintf( esc_html__( "Week %d Complete?", 'healthy' ), $unit_time );
				$out = healthy_append_cell( $out, $cell, $format, $before, $after );

				// How many days complete this week?
				$cell = sprintf( esc_html__( 'Week %d Days Complete', 'healthy' ), $unit_time );
				$out = healthy_append_cell( $out, $cell, $format, $before, $after );

				// Total exercise for this week?
				$cell = sprintf( esc_html__( 'Week %d Total Exercise', 'healthy' ), $unit_time );			
				$out = healthy_append_cell( $out, $cell, $format, $before, $after );

			}

			// Establish which day is the first day of this week.
			$first_day_of_week = healthy_get_first_weekday_of_week();

			// Grab the current year.
			$year = date( "Y" );

			// Establish from which week we're querying posts.
			$query_week = healthy_convert_contest_week_to_query_week( $unit_time );
			$off_by_one_week = $query_week - 1;

			// Grab a time from this week.
			$sometime_this_week = strtotime("January 1, $year + $off_by_one_week week ");

			// Grab a time from the first day this week.
			$first_day_of_week_in_seconds = strtotime( "last $first_day_of_week", $sometime_this_week );

			// The date for the first day this week.
			$first_day_of_week = date("l, F d, Y", $first_day_of_week_in_seconds  );

			// For each day this week...
			$day = 1;	
			while( $day <= 7 ) {
				
				// If it's the first day, the date is the first day.
				if ( $day == 1 ) {
					$date = $first_day_of_week;
				
				// If we've been through the loop at least once now, calculate which day it is.
				} elseif ( $day > 1 ) {

					// Add the correct # of days to the first day.
					$off_by_one = $day - 1;
					$date = strtotime( "$first_day_of_week + $off_by_one day" );
					
					// A date for this day.
					$date = date("l, F d, Y", $date );
				}

				// For each day field...
				foreach( $day_fields as $f ) {
				
					// If it's not a weekly metric, bail.
					if ( ! isset( $f[ 'is_weekly_metric' ] ) ) { continue; }
					
					// Reset the cell var.
					$cell = '';


					// If we're grabbing data from a user...
					if( $user_id ) {

						// The slug for this field.
						$slug = $f[ 'slug' ];

						// From what day of the week are we grabbing posts? In mysql, sunday is 1, but in php monday is 1.
						$query_day = $day + 1;
						
						// If day == 7, that means we made it to sunday, and mysql thinks sunday is 1.
						if( $day == 7 ) { $query_day = 1; } 

						// Posts from this day.
						$r = healthy_get_posts( $user_id, 1, false, false, false, $query_week, $query_day );
						
						// If there are posts from this day...
						if( $r -> posts ) {

							// The posts.
							$posts = $r -> posts;
							
							// The post.
							$post = $posts[0];
							
							// The post ID.
							$post_id = $post -> ID;
							
							// The meta for this day.
							$meta = get_post_meta( $post_id, $slug, TRUE );

							// If there was meta, that's the cell value.
							if ( ! empty( $meta ) ) { $cell = $meta; }

						}

					// End if we're grabbing data from a user.	If we're not, just grab a header value.
					} else {

						// The label for this field.
						$label = $f['label'];

						// The cell isthe date and label for this field.
						$cell = $date." ".$label;

					}
			
					// Append to output.
					$out = healthy_append_cell( $out, $cell, $format, $before, $after );

				// End for each day cell.					
				}

				// Go to the next day.
				$day ++;
			
			// End for each day.
			}

		// End if unit_time is numeric.
		}

	}
	return $out;
}

/**
 * Get a report in table or csv format.
 * 
 * @param  string $school    From which school are we grabbing data?
 * @param  string $filter_by Unused.
 * @param  string $format    In what format are we reporting?
 * @return string|array      An HTML table or a CSV array.
 */
function healthy_get_report( $school = '', $filter_by = '', $format = 'table' ) {

	// Will hold the table head.
	$head = '';

	// Will hold the table body.
	$body = '';

	// If we're outputting a table, start with a  menu.
	if ( $format == 'table' ) {

		// Nav links for viewing reports.
		$menu = healthy_reporting_menu();

		// Start by outputting the menu.
		$out = $menu;

	// If we're outputting a csv, start with an empty array.
	} else {
		$out = array();
	}

	// If we have all the fields we need:
	if ( isset( $_GET[ 'object_type' ] ) && isset( $_GET[ 'action' ] ) && isset( $_GET[ 'object_id' ] ) ) {

		// From what time are we grabbing?
		if ( isset ( $_GET[ 'unit_time' ] ) ) {
			$unit_time = $_GET[ 'unit_time' ];

			// We either grab all weeks or a given week.
			if( ( $unit_time != 'weekly' ) && ( $unit_time != 'all' ) && ( ! is_numeric( $unit_time ) ) ) { return false; }

		// Default to all weeks.
		} else {
			$unit_time = 'weekly';
		}

		// Are we just grabbing all-star users?
		$all_stars = '';
		if ( isset( $_GET[ 'all_stars' ] ) ) {
			$all_stars = absint( $_GET[ 'all_stars' ] );
		}

		// Get the header row.
		$header_cells = healthy_get_row( $unit_time, false, $format, $school, $all_stars );

		// If we're outputting as a table, wrap it as a row.
		if ( $format == 'table' ) {
			$head = "<tr>$header_cells</tr>";
		
		// If we're outputting as csv, read the header into an array.
		} elseif( $format == 'csv' ) {
			
			// Will hold the cells for this row.
			$row = array();

			// For each header cell, add it to the array for this row.
			foreach( $header_cells as $h ) {
				$row []= $h;
			}

			// Add this row to the output.  Each row is an array.
			$out []= $row;

		}

		// If the user is a boss and is not browsing a specific school, get all users.
		if( ( empty( $school ) || ( $school == 'all' ) ) && ( healthy_user_is_role( false, 'boss' ) ) ) {

			// Returns all users.
			$args = array(
				'meta_key'     => 'school',
				'meta_value'   => 'all',
				'offset'	   => 0,
				'number'	   => 9999,
				'format' 	   => $format,
				'unit_time'	   => $unit_time,
				'role' 		   => 'student',
				'all_stars'	   => $all_stars,
			);

			$body = healthy_get_rows_for_report( $args );

		// Else, the user is not a boss, so we have to grab from a specific school.
		} else {

			// Build an args array for a call to get_users.
			$args = array();

			// The schools in our contest.
			$schools = healthy_get_schools();
		
			// The slugs for each school.
			$school_slugs = array();
			foreach( $schools as $s ) {
					$school_slugs []= $s['slug'];
			}

			// Only grab users from the specified school.
			if ( ! in_array( $school, $school_slugs ) ) { return false; }

			// Where to start in pagination.
			$offset = 0;

			// If it's a table view and we are paginating:
			if ( ( $format == 'table' ) && isset( $_GET[ 'offset' ] ) ) {
				$offset = absint( $_GET[ 'offset' ] );
			}

			// How many users per page?
			$per_page = 0;
			if ( $format == 'table' ) {
				$per_page = healthy_users_per_page();
			}

			// Meta user query.
			$args = array(
				'meta_key'     => 'school',
				'meta_value'   => $school,
				'offset'	   => $offset,
				'number'	   => $per_page,
				'format' 	   => $format,
				'unit_time'	   => $unit_time,
				'role' 		   => 'student',
				'all_stars'	   => $all_stars,
			);

			$body = healthy_get_rows_for_report( $args );

		}
		
		// If table, append each user to this value.
		if ( $format == 'table' ) {
		
			// Grab pagination if necessary.
			$pagination = '';
			if( $school != 'all' ) {
				$pagination = healthy_report_pagination( $school, $all_stars );
			}

			// Grab a count of users for this school.
			$count = healthy_count_users_by_school( $school, $all_stars );

			// Translate all-star for all-star reports.
			if( ! empty( $all_stars ) ) {
				$all_star_str = esc_html( 'all-star', 'healthy' );
			} else {
				$all_star_str = '';	
			}

			// Add a label to explain how many users.  If we're grabbing from all schools:
			if ( $school == 'all' ) {
				$count_label = sprintf( _n( 'There is one %s student in the contest.', 'There are %d %s students in the contest.', $count, 'healthy' ), $count, $all_star_str );
			
			// Else, we're grabbing from a specific school.
			} else {
				// Convert the school slug into a nice label.
				$school_label = ucwords( $school );
				$school_label = str_replace( '_', ' ', $school_label );
				$count_label = sprintf( _n( 'There is %d %s %s student in the contest.', 'There are %d %s %s students in the contest.', $count, 'healthy' ), $count, $all_star_str, $school_label );
			
			}

			// Wrap the user count for output.
			$count_label = "<p>$count_label</p>";

			// Complete the table.
			$table = "<table id='healthy_report'>$head$body</table>$pagination $count_label";

			$out .= $table;

		} elseif ( $format == 'csv' ) {
	
			$out = array_merge( $out, $body );

		}

	}

	return $out;

}

/**
 * How many users per page?
 * 
 * @return int Number of users per page in reports.
 */
function healthy_users_per_page() {
	return 1;
}

/**
 * Return an HTML nav for paging through a report.
 * 
 * @param  string $school For which school to show page links.
 * @param  int $all_stars Are we grabbing only all-stars?
 * @return string         An HTML nav for paging through a report.
 */
function healthy_report_pagination( $school, $all_stars = '' ) {

	// How many users in this school?
	$count = healthy_count_users_by_school( $school, $all_stars );

	// Is there a url var for offset?
	$offset = '';
	if ( isset( $_GET[ 'offset' ] ) ) {
		$offset = absint( $_GET[ 'offset' ] );
	}

	// How many users per page?
	$per_page = healthy_users_per_page();

	// Prevent divide by zero.
	if( empty( $per_page ) ) { return false; }

	// How many pages do we need?
	$num_pages = ceil( $count / $per_page );

	// If there is only one page, don't bother.
	if ( $num_pages == 1 ) { return false; }

	// A base url for making page links.
	$base = trailingslashit( esc_url( get_bloginfo( 'url' ) ) );

	// The object id to observe.
	$object_id = '';
	if ( isset( $_GET[ 'object_id' ] ) ) {
		$object_id = $_GET[ 'object_id' ];
	}

	// The type of object ot observe.
	$object_type = 'report';
	
	// What to do to the object.
	$action = 'review';

	// For what unit time is the report?
	$unit_time='weekly';
	if( isset( $_GET[ 'unit_time' ] ) ) {
		$unit_time = $_GET[ 'unit_time' ];
	}

	// Are we grabbing all-stars?
	$all_stars = absint( $all_stars );

	// The url for this page link.
	$query = healthy_controller_query_string( $object_type, $action, $school, $unit_time, $all_stars );
	
	//?object_id=$object_id&object_type=$object_type&action=$action&unit_time=$unit_time";
	$url = $base.$query;
	
	// Will increment for each page.
	$i = 0;

	// Will hold the html for the page links.
	$links = '';

	// For each page link...
	while( $i < $num_pages ) {
		
		// The href for this link.
		$href = $url."&offset=$i";
		
		// Is it the current link?
		$maybe_current = '';
		if( $i == $offset ) {
			$maybe_current = 'current-pagination-item';
		}

		// An offset of 0 equals page 1, etc.
		$off_by_one = $i + 1;
		
		// Add to the links.
		$links .= "<a class='$maybe_current' href='$href'>$off_by_one</a> ";

		// Increment the counter.
		$i++;

	}

	// Wrap the links in a nav element.
	$out = "<nav class='pagination user-report-pagination'>$links</nav>";

	// Sanitize the output.
	$out = strip_tags( $out, '<a><nav>' );

	return $out;
}

/**
 * Given a school, count the students in that school.  If none provided, counts all students.
 * 
 * @param  string $school For which school to count users.
 * @return int The number of users for this school.
 */
function healthy_count_users_by_school( $school, $all_stars = '' ) {
	
	// The schools in our contest.
	$schools = healthy_get_schools();
	
	// The slugs for each school.
	$school_slugs = array();
	foreach( $schools as $s ) {
		$school_slugs []= $s['slug'];
	}

	// Only grab users from the specified school.
	if ( ! in_array( $school, $school_slugs ) ) { 
		$school = null;
	}

	// The args for a user query.
	$args = array(
		'meta_key'     => 'school',
		'meta_value'   => $school,
		'count_total'  => true,
		'role' 		   => 'student',
		'fields'	   => array( 'ID' ),
	);

	// THe users.
	$r = new WP_User_Query( $args );

	// The user count.
	if( empty ( $all_stars ) ) {
		$count = $r -> get_total();
	} else {
		$count = 0;
		$users = $r -> results;
		foreach( $users as $u ) {
			if( healthy_user_is_all_star( $u -> ID) ) {
				$count++;
			}
		}
	}


	// Sanitize the output.
	$out = absint( $count );

	return $out;
}

/**
 * Given a school, find how many days are complete for that school.
 * 
 * @param  string $school Which school?
 * @return int  How many days are complete for that school.
 */
function healthy_days_complete_by_school( $school ) {
	
	// Start the output.
	$out = 0;

	// Args for a get users query.
	$args = array(
		'meta_key'     => 'school',
		'meta_value'   => $school,
		'role' 		   => 'student',
	);

	// The users.
	$users = get_users( $args );

	// For each user, get all their posts.
	foreach( $users as $user ) {

		// Get the posts.
		$r = healthy_get_posts( $user -> ID, 9999 );
		$posts = $r -> posts;

		// For each post, is it a complete day?
		foreach ( $posts as $p ) {

			// If it's complete, increment th output.
			if ( healthy_is_day_complete( $p -> ID ) ) {
				$out++;
			}
		}
	
	// End for each user.
	}

	return $out;
}

/**
 * Return the average days complete per student for a school.
 * 
 * @param  string $school From which school to grab an average.
 * @return float The average days complete per student for a school.
 */
function healthy_days_complete_per_student_by_school( $school ) {

	// Start the output.
	$out = 0;

	// How many students in this school?
	$num_students = healthy_count_users_by_school( $school );

	// If empty, bail.
	if( empty( $num_students ) ) { return 0; }

	// How many days complete in this school?
	$days_complete = healthy_days_complete_by_school( $school );

	// The average.
	$out = $days_complete / $num_students;

	// Round to tenths.
	$out = round( $out, 1 );

	return $out;	
}

/**
 * Return rows for a report.
 * 
 * @param  array  $args An array of args for our function.
 * @return array|string An array of rows if outputting as csv, else a tring of html <tr>'s.
 */
function healthy_get_rows_for_report( $args = array() ) {

	// For what unit time are we grabbing?
	$unit_time = $args['unit_time'];

	// If we're grabbing from all schools, we actually need to pass an empty string.
	if( $args[ 'meta_value' ] == 'all' ) {
		$args[ 'meta_value' ] = '';
	}
	
	// What's our output format?
	$format = $args['format'];

	// If we're outputitng as a table, start with a string.
	if ( $format == 'table' ) {
		$out = '';

	// If we're otuputting as csv, start with an empty array.
	} else {
		$out = array();
	}

	// If a boss is browsing by school...
	if( ( $args[ 'meta_value' ] == '' ) && ( $unit_time == 'all' ) && healthy_user_is_role( false, 'boss' ) && $args[ 'all_stars' ] != 1 ) {

		// Each table row is a school.
		$rows = healthy_get_schools();

	// Else, each table row is a user.
	} else {

		// get users.
		$r = new WP_User_Query( $args );

		// Each row is a user.
		$rows = $r -> results;

	}

	// For each row...
	foreach ($rows as $row ) {
		
		// If a boss is browsing by school...
		if( ( $args[ 'meta_value' ] == '' ) && ( $unit_time == 'all' ) && healthy_user_is_role( false, 'boss' ) && $args[ 'all_stars' ] != 1 ) {

			// The key for this row is the school slug.
			$school = $row[ 'slug' ];

			// Get the row.
			$cells = healthy_get_row( $unit_time, false, $format, $school );

		// Else, we're browsing by user.
		} else {

			// The user id.
			$user_id = absint( $row -> ID );

			// The cells for this user.
			$cells = healthy_get_row( $unit_time, $user_id, $format, '', $args[ 'all_stars' ] );

		}

		// If it's a table, add and wrap this row.
		if ( $format == 'table' ) {
			$row = "<tr>$cells</tr>";
			$out .= $row;

		// If it's a CSV, append this row as an array.
		} elseif( $format == 'csv' ) {

			// This user is an array.
			$row = array();

			// For each cell...
			foreach( $cells as $cell ) {
				
				// Add the cell to this array.
				$row []= $cell;
			}
		
			// Add this row to the output.
			$out []= $row;
		}


	// End for each row.
	}

	return $out;
}

/**
 * Get a csv for the current view.
 * @return bool|void Returns false on error, otherwise sends a csv to desktop.
 */
function healthy_get_csv() {

	// Sniff the url to seee if we're getting by csv.
	if( ! isset( $_GET[ 'as_csv' ] ) ) { return false; }

	// From what school are we grabbing?
	if ( isset( $_GET[ 'object_id' ] ) ) {
		$object_id = $_GET[ 'object_id' ];
	} elseif ( healthy_user_is_role( false, 'boss' ) ) {
		$object_id = 'all';
	}

	// Our action is to review.
	$action = 'review';

	// Our object is a report.
	$object_type = 'report';

	// Determine if the user is allowed to view this.
	if ( ! healthy_current_user_can_act_on_object( $object_id, $action, $object_type ) ) {
		wp_die( "There has been an error. 349" );
	}

	// From what unit time are we grabbing?
	$unit_time = '';
	if ( isset( $_GET[ 'unit_time' ] ) ) {
		$unit_time = $_GET[ 'unit_time' ];
	} elseif ( healthy_user_is_role( false, 'boss' ) ) {
		$unit_time = 'all';
	}

	//get the current date so that the file has a more meaningful name
	$current_time = date( "Y-m-d-G-i-s" );

	// Use the unit time to give the file a better name.
	if( is_numeric( $unit_time ) ) { $unit_time = "week-".$unit_time; }

	// Establish the name of the file that the user will download.
	$fileName = $object_id.'-'.$unit_time.'-'.$current_time.'.csv';
 
 	// The rows of the csv.
	$rows = healthy_get_report( $object_id, '', 'csv' );

 	//set the headers of the file
	header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
	header( 'Content-Description: File Transfer' );
	header( 'Content-type: text/csv' );
	header( "Content-Disposition: attachment; filename={$fileName}" );
	header( 'Expires: 0' );
	header( 'Pragma: public' );
 
 	// Start a file.
	$fh = @fopen( 'php://output', 'w' );
	
	// If it's not an array, we're in trouble.
	if( ! is_array( $rows ) ) { wp_die( "There has been a problem. 681" ); }

	// If there are no rows, we're in trouble.
	$count = count ( $rows );
	if ( empty( $count ) ) { wp_die( 'There has been a problem. 685' ); }

	// For each row...
	foreach ( $rows as $row ) {
     
		// Put the data into the stream.
		fputcsv( $fh, $row );
	}

	// Close the file.
	fclose( $fh );
	
	// Make sure nothing else is sent, our file is done.
	exit;

	return true;
	
}
add_action( 'template_redirect', 'healthy_get_csv' );