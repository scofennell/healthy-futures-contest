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

	if( ! healthy_user_is_role( false, 'boss' ) ) { return FALSE; }

	// Will hold selected="selected" for current menu item.
	$selected = '';

	// Get the current url to power selected().
	$current_url = healthy_current_url();

	// Get the school for the current user.
	$school = healthy_get_user_school( get_current_user_id() );

	// A base url off of which we'll build links.
	$base = trailingslashit( esc_url( get_bloginfo( 'url' ) ) );
	
	// A query string to view reports from our school.
	$query = healthy_controller_query_string( 'report', 'review', $school );
	
	// May hold a link to browse all schools if the user is priveleged enough to do so.
	$by_school = '';
	
	// A query string to browse all schools
	$by_school_query = healthy_controller_query_string( 'report', 'review', 'all', 'all', FALSE, FALSE, 1 );

	// An href to browse all schools for all time.
	$by_school_href = esc_url( $base.$by_school_query );

	// If we're viewing by school, this is the menu item.
	$selected = '';
	if( isset( $_GET['by_school'] ) && ! empty( $_GET['by_school'] ) ) {
		$selected = 'selected="selected"';
	}

	// A label to browse all schools.
	$by_school_label = esc_html__( 'By School', 'healthy' );

	// A link to browse all schools.
	$by_school = "<option $selected value='$by_school_href'>$by_school_label</option>";

	// Text pormpting the user to view a report.
	$all = esc_html__( 'All Students', 'healthy' );
	
	$all_href = esc_url( $base );

	// Provide a blank option.
	$all_option = "<option value='$all_href'>$all</option>";
	
	// concat the options.
	$options = $all_option.$by_school;
	
	// Create the select menu.
	$select = "<p><select onchange='document.location.href=this.options[this.selectedIndex].value;'>$options</select></p>";

	// Complete the output.
	$out = "<nav class='reporting-menu'>$select</nav>";

	return $out;

}

/**
 * A transient key for our reports.
 *
 * @return string A transient key for our reports.
 */
function healthy_reports_transient_key() {

	// The user role.
	$role = '';
	if( healthy_user_is_role( false, 'boss' ) ) { $role = 'boss'; }

	// The output format.
	$format = 'table';
	if( isset( $_GET[ 'as_csv' ] ) ) { $format = 'csv'; }

	// The object ID.
	$object_id = '';
	if( isset( $_GET[ 'object_id' ] ) ) { $object_id = sanitize_text_field( $_GET[ 'object_id' ] ); }

	// The page number.
	$offset = '';
	if( isset( $_GET[ 'offset' ] ) ) { $offset = sanitize_text_field( $_GET[ 'offset' ] ); }

	// Bundle it up as a transient key.
	$out = "hf__$role$format$object_id$offset";

	return $out;
}

/**
 * Append the next cell to an output variable in our report.
 * 
 * @param  string|array 	$out The value to which we're appending.
 * @param  string $cell   	The value we're appending.
 * @param  string $before 	<td> or <th> for tables, or empty string for arrays.
 * @param  string $after  	</td> or </th> for tables, or empty string for arrays.
 * @return string|array  	The output var for the report, with the nw cell appended.
 */
function healthy_append_cell( $out, $cell, $before='', $after = '' ) {

	// Are we appending to a table or a csv?
	$format = 'table';
	if( isset( $_GET[ 'as_csv' ] ) ) { $format = 'csv'; }

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
 * @return  array An array to denote what fileds to report on when reporting on a school.
 */
function healthy_school_report_cells( $school ) {
	
	// Start the output.
	$out = array(

		// The total students in the contest for this school.
		array(
			'label'    => esc_html__( 'School Name' ),
			'callback' => 'healthy_school_to_label',
		),

		// The total students in the contest for this school.
		array(
			'label'    => esc_html__( 'Total Students In Contest' ),
			'callback' => 'healthy_count_users_by_school',
		),
		
		// The total days complete for this school.
		array(
			'label'    => esc_html__( 'Total Days Complete' ),
			'callback' => 'healthy_days_complete_by_school',
		),

		// The average days complete per student in this school.		
		array(
			'label'    => esc_html__( 'Days Complete Per Student' ),
			'callback' => 'healthy_days_complete_per_student_by_school',
		),
	);

	return $out;
}

/**
 * Get a report in table or csv format.
 * 
 * @return string|array      An HTML table or a CSV array.
 */
function healthy_get_report( $format = 'table' ) {

	if ( $format == 'table' ) {
		$out = '';
	} else {
		$out = array();
	}

	// Grab the transient key for this view.
	$transient_key = healthy_reports_transient_key();

	//  Grab the transient value for this view.
	$transient = get_transient( $transient_key );

	// If there is no transient, build it, save it, and output it.
	if( empty( $transient ) ) {

		// If we're outputting a table, start with a  menu.
		if( isset( $_GET[ 'as_csv' ] ) ) { $format = 'csv'; }
		if ( $format == 'table' ) {

			// Nav links for viewing reports.
			$menu = healthy_reporting_menu();

			// Start by outputting the menu.
			$out = $menu;

		// If we're outputting a csv, start with an empty array.
		}

		// How many users per page?  CSV's don't do pagination.
		$per_page = 0;
		#if ( $format != 'csv' ) {
			$per_page = healthy_users_per_page();
		#}

		// Where to start in pagination.  CSV's dont do pagination.
		$offset = 0;
		#if ( $format != 'csv' ) {
			if ( isset( $_GET[ 'offset' ] ) ) {
				$offset = absint( $_GET[ 'offset' ] );
			}
		#}

		// Grab the school from the url.
		if( isset( $_GET[ 'object_id' ] ) ) {
			$school = sanitize_text_field( $_GET[ 'object_id' ] );
		} else {
			$school = healthy_get_user_school( get_current_user_id() );
		}

		// Is this is a by-school query?
		$by_school = '';
		if ( isset( $_GET['by_school'] ) ) {
			$by_school = absint( $_GET[ 'by_school' ] );
		}

		// Confirm that the current user can view data for the selected school.  Bosses can view any school.
		if ( ! healthy_user_is_role( false, 'boss' ) ) {

			// Grab the user school for comparison.
			$user_school = healthy_get_user_school( get_current_user_id() );
			
			// If the user isn't from this school, bail.
			if( $user_school != $school ) { wp_die( 'There has been an error. 706' ); }
		
		} else {

			$school = 'all';

		}

		// Will hold the table head.
		$head = '';

		// Will hold the table body.
		$body = '';

		// Get the header row.
		$header_cells = healthy_get_row( false, $by_school, TRUE, $format );

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

		// If it's a boss browsing by school...
		if( $by_school ) {

			$args = array(
				'by_school' => true,
				'format'    => $format,
			);

		// If the user is a boss and is not browsing by school, get all users.
		} elseif( ( empty( $school ) || ( $school == 'all' ) ) && ( healthy_user_is_role( false, 'boss' ) ) ) {

			// Returns all users.
			$args = array(
				'meta_key'     => 'school',
				'meta_value'   => 'all',
				'offset'	   => $offset,
				'number'	   => $per_page,
				'format' 	   => $format,
				'role' 		   => 'student',
			);

		// Else, the user is not a boss, so we have to grab from a specific school.
		} else {

			// Build an args array for a call to get_users.
			$args = array();

			// Meta user query.
			$args = array(
				'meta_key'     => 'school',
				'meta_value'   => $school,
				'offset'	   => $offset,
				'number'	   => $per_page,
				'format' 	   => $format,
				'role' 		   => 'student',
			);

		}
		
		// Get the body rows
		$body = healthy_get_rows_for_report( $args );

		// If table, append the rows this value.
		if ( $format == 'table' ) {
		
			// THe html table.
			$table = "<table id='healthy_report'>$head$body</table>";

			// Grab a count of users for this school.
			$count = healthy_count_users_by_school( $school );

			// Add a label to explain how many users.  If we're grabbing from all schools:
			if ( $school == 'all' ) {
				$count_label = sprintf( _n( 'There is one %s student in the contest.', 'There are %d students in the contest.', $count, 'healthy' ), $count );
			
			// Else, we're grabbing from a specific school.
			} else {
			
				$school_label = healthy_school_to_label( $school );
				$count_label = sprintf( _n( 'There is %d 1 %s student in the contest.', 'There are %d %s students in the contest.', $count, 'healthy' ), $count, $school_label );
			
			}

			// Wrap the user count for output.
			$count_label = "<p>$count_label</p>";

			// Grab a link to view as csv.
			$as_csv_link = healthy_csv_link( $school );

			// Grab pagination if necessary.
			$pagination = healthy_report_pagination( $school );

			// Complete the table.
			$out = "$menu $as_csv_link $count_label $pagination $table $pagination";

		// If we're outputting as csv, merge the arrays.
		} elseif ( $format == 'csv' ) {
			$out = array_merge( $out, $body );
		}

		// For how many seconds do we store tranients?
		$transient_time = healthy_transient_time();

		// Set the transient.
		set_transient( $transient_key, $out, $transient_time );

		return $out;

	// End if we have a transient for this view, output it.
	} else {

		return $transient;
	
	}
}

/**
 * Get a row of cells for a report.
 * 
 * @return string|array Returns an html <tr> or an array of cells.
 */
function healthy_get_row( $user_id = FALSE, $which_school = false, $is_header = FALSE, $format = 'table' ) {


	// Start the output var.  If it's a table, start a string.
	if ( $format == 'table' ) {
		$out = '';
		
	// If csv, append the cell as an array member.
	} elseif( $format == 'csv' ) {
		$out = array();
	} else {
		return FALSE;
	}

	// What school are we viewing?
	$school = '';
	if( isset( $_GET[ 'object_id' ] ) ) {
		$school = sanitize_text_field( $_GET[ 'object_id' ] );
	}

	// If school still equals 'all', we're on the first row of the table.
	if ( $is_header ) {

		// Before each cell.
		$before = '<th>';

		// After each cell.
		$after = '</th>';

	// If school refers to a specific school, we're past the header row.
	} else {

		// Before each cell.
		$before = '<td>';
	
		//After each cell.
		$after = '</td>';

	}

	// We're grabbing a report on a school itself.
	if( $which_school ) {

		// The fields on which we report for each school.
		$school_report_cells = healthy_school_report_cells( $which_school );

		// For each field
		foreach( $school_report_cells as $c ) {
		
			// If we're on the header row, start with the label.
			if ( $is_header ) {
				$label = $c[ 'label' ];
				$cell = $label;

			// If we're past the header row, grab the data with a callback function.
			} else {
				$callback = $c[ 'callback' ];
				$cell = call_user_func( $callback, $which_school );

			}

			// Append to the output var.
			$out = healthy_append_cell( $out, $cell, $before, $after );

		}  

	// We're not grabbing a report on the school itself, we're grabbing a report on students within that school.
	} else {

		// If it's not a header, grab the user data for later, before we are in a loop.
		if( ! $is_header ) {

			// The data for the user we're grabbing.
			$user = get_userdata( $user_id );

			// If there is no such user, something's wrong.
			if ( ! $user ) { return false; }

		}

		// The user meta fields which we'll grab for each user.
		$user_fields = healthy_profile_fields();

		// For each user field...
		foreach( $user_fields as $f ) {

			// If this field is not exportable, don't worry about it.
			if ( ! isset( $f[ 'exportable' ] ) ) { continue; }
			
			// If we're grabbing from a user...
			if( $is_header ) {
				
				$cell = $f['label'];
		
			} else {

				// Start the cell.
				$cell = '';

				// The slug for this field.
				$slug = $f[ 'slug' ];

				// The meta for this field.
				$meta = get_user_meta( $user_id, $slug, TRUE );
			
				// If we're grabbing the user school, convert the school to a readable label.
				if( $slug == 'school' ) {

					$meta = healthy_school_to_label( $meta );
				
				// If we're grabbing the teacher, change that to a label as well.
				} elseif( $slug == 'teacher' ) {

					$teacher_obj = get_user_by( 'id', $meta );
					$teacher_str = '';
					if( is_object( $teacher_obj ) ) {
						$teacher_str = $teacher_obj -> display_name;
					}
					$meta = $teacher_str;

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

			}
			
			// Append to the outout var.
			$out = healthy_append_cell( $out, $cell, $before, $after );

		// End for each user field.
		}

		// Add a column for on-pace handling.
		if( $is_header ) {
			$cell = esc_html__( 'On Pace to be an All-Star?', 'healthy' );
		} else {
			
			if( healthy_user_is_on_pace( $user_id ) ) {
				$cell = esc_html__( 'Yes', 'healthy' );
			} else {
				$cell = esc_html__( 'No', 'healthy' );
			}
		}
		$out = healthy_append_cell( $out, $cell, $before, $after );

		// Add a column for all-star handling.
		if( $is_header ) {
			$cell = esc_html__( 'Already is an All-Star?', 'healthy' );
		} else {	
			if( healthy_user_is_all_star( $user_id ) ) {
				$cell = esc_html__( 'Yes', 'healthy' );
			} else {
				$cell = esc_html__( 'No', 'healthy' );
			}
		}
		$out = healthy_append_cell( $out, $cell, $before, $after );

		// Add a column for # of days handling.
		if( $is_header ) {
			$cell = esc_html__( '# of Green Days', 'healthy' );
		} else {	
			$cell = healthy_days_complete( $user_id );
		}
		$out = healthy_append_cell( $out, $cell, $before, $after );

		// Add a column for # of drinks handling.
		if( $is_header ) {
			$cell = esc_html__( 'Total # of Sugary Drinks', 'healthy' );
		} else {	
			$cell = healthy_total_drinks( $user_id );
		}
		$out = healthy_append_cell( $out, $cell, $before, $after );

		if( healthy_contest_is_happening() ) {

			// Our app-wide definition of a day.
			$healhy_day = healthy_day();

			// Fields of data for a day.
			$day_fields = $healhy_day['components'];

			// The current year.
			$year = healthy_get_year_of_contest();

			// Grab the current month.
			$month     = healthy_get_month_of_contest( 'F' );
			$month_num = healthy_get_month_of_contest();

			// End the report either on today's date or the lesser of two dates.
			$days_in_month = healthy_get_days_of_contest_month();

			if( date( 'n' ) != healthy_get_month_of_contest( 'n' ) ) {
				$end = $days_in_month;
			} else {
				$current_date  = date( 'd' );
				$end = min( $days_in_month, $current_date );
			}

			// For each day this month...
			$date = 0;	
			while( $date < $end ) {
				
				// Go to the next day.
				$date ++;

				// For each day field...
				foreach( $day_fields as $f ) {
					
					// Determine if this info belongs in the report.
					if( ! isset( $f['is_monthly_metric'] ) ) { continue; }

					// Reset the cell var.
					$cell = '';

					// If we're grabbing data from a user...
					if( $user_id ) {

						// The slug for this field.
						$slug = $f[ 'slug' ];

						// Posts from this day.
						$r = healthy_get_posts( $user_id, 1, $year, $month_num, $date );
						
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
						$timestamp = strtotime( "$year-$month_num-$date" );

						$date_label = date( get_option( 'date_format' ), $timestamp );

						if( $format == 'table' ) {
							$date_label = "<em class='healthy-report-date-label'>$date_label</em><br>";
						}

						$cell = "$date_label $label";

					}
			
					// Append to output.
					$out = healthy_append_cell( $out, $cell, $before, $after );

				// End for each day cell.					
				}
			
			// End for each day.
			}

		// End if contest is happening.
		}

	// End school VS user report.
	} 

	return $out;
}

/**
 * How many users per page?
 *
 * @todo  Set this to 50 when before the project launches.
 * @return int Number of users per page in reports.
 */
function healthy_users_per_page() {
	// return 800;
	return 100;
}

/**
 * Return an HTML nav for paging through a report.
 * 
 * @param  string $school For which school to show page links.
 * @return string         An HTML nav for paging through a report.
 */
function healthy_report_pagination( $school ) {

	// How many users in this school?
	$count = healthy_count_users_by_school( $school );

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

	// The url for this page link.
	$query = healthy_controller_query_string( $object_type, $action, $school );
	
	// The url for this link.
	$url = $base.$query;
	
	// Will increment for each page.
	$i = 0;

	// Will hold the html for the page links.
	$links = '';

	// For each page link...
	while( $i < $num_pages ) {
		
		// The href for this link.
		$this_offset = absint( $i * $per_page );
		$href = $url."&offset=$this_offset";
		
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
function healthy_count_users_by_school( $school ) {
	
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

	// The users.
	$r = new WP_User_Query( $args );

	// The user count.
	$count = $r -> get_total();
	
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

	// If we're grabbing from all schools, we actually need to pass an empty string.
	if( isset( $args[ 'meta_value' ] ) ){
		if( $args[ 'meta_value' ] == 'all' ) {
			$args[ 'meta_value' ] = '';
		}
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
	if( isset( $args[ 'by_school' ] ) ) {

		if( $args[ 'by_school' ] ) {

			// Each table row is a school.
			$schools = healthy_get_schools();
			foreach ( $schools as $s ) {
				$rows []= $s[ 'slug' ];
			}
		
		}

	// Else, each table row is a user.
	} else {

		// get users.
		$r = new WP_User_Query( $args );

		// Each row is a user.
		$rows = $r -> results;

	}

	$out = '';

	// For each row...
	foreach ( $rows as $row ) {

		// If a boss is browsing by school...
		if( isset( $args[ 'by_school' ] ) ) {
			if( $args[ 'by_school' ] ) {

				// Get the row.
				$cells = healthy_get_row( false, $row, false, $format );

			}

		// Else, we're browsing by user.
		} else {

			// The user id.
			$user_id = absint( $row -> ID );

			// The cells for this user.
			$cells = healthy_get_row( $user_id );

		}

		// If it's a table, add and wrap this row.
		if ( $format == 'table' ) {
			$tr = "<tr>$cells</tr>";
			$out .= $tr;

		// If it's a CSV, append this row as an array.
		} elseif( $format == 'csv' ) {

			// This user is an array.
			$tr = array();

			// For each cell...
			foreach( $cells as $cell ) {
				
				// Add the cell to this array.
				$tr []= $cell;
			}
		
			// Add this row to the output.
			$out []= $tr;
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

	// The page number.
	$offset = '';
	if( isset( $_GET[ 'offset' ] ) ) { $offset = sanitize_text_field( $_GET[ 'offset' ] ); }

	// Our action is to review.
	$action = 'review';

	// Our object is a report.
	$object_type = 'report';

	$object_id = $_GET[ 'object_id' ];

	// Determine if the user is allowed to view this.
	if ( ! healthy_current_user_can_act_on_object( $object_id, $action, $object_type ) ) {
		wp_die( 'There has been an error. 1282' );
	}

	//get the current date so that the file has a more meaningful name
	$current_time = date( "Y-m-d-G-i-s" );

	// Establish the name of the file that the user will download.
	$file_name = $object_id . '-' . $current_time  . '-' . $offset . '.csv';
 
 	// The rows of the csv.
	$rows = healthy_get_report();

 	//set the headers of the file
	header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
	header( 'Content-Description: File Transfer' );
	header( 'Content-type: text/csv' );
	header( "Content-Disposition: attachment; filename={$file_name}" );
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

function healthy_csv_link( $school ) {

	// A label to grab as csv. 
	$as_csv_label = esc_html( 'Download as CSV', 'healthy' );
		
	// An href to grab as csv.
	$as_csv_href = add_query_arg( array( 'as_csv' => 1 ) );

	// An href to grab as csv.
	$as_csv_href = add_query_arg( array( 'object_id' => $school ), $as_csv_href );
		
	// A link to grab as csv.
	$out = "<p><a class='button island accent-color as_csv' href='$as_csv_href'><strong>$as_csv_label</strong></a></p>";

	return $out;

}