<?php

/**
 * Functions to draw the calendar view, for choosing a day to add/delete/edit.
 *
 * @package WordPress
 * @subpackage healthy
 * @since healthy 2.0
 */

/**
 * The main template tag for drawing the calendar page.
 * 
 * @return string An HTML table as calendar.
 */
function healthy_calendar() {

	$month = healthy_get_month_of_contest( 'F' );

	$year = healthy_get_year_of_contest();

	$header = "<h4 class='healthy-calendar-header'>$month, $year</h4>";

	$out = "<div class='healthy-calendar'>$header" . healthy_calendar_table() . '</div>';

	return $out;

}

function healthy_calendar_table() {
	$out = healthy_calendar_header();
	$num_rows = 6;
	$i = 0;
	while( $i < $num_rows ) {

		$i++;
	
		$out .= healthy_calendar_row( $i );
	
	}

	$out = "<table class='healthy-calendar-table'>$out</table>";

	return $out;

}

function healthy_calendar_header() {
	
	$out = '';

	$days = array(
		esc_html__( 'Sun', 'healthy' ),
		esc_html__( 'Mon', 'healthy' ),
		esc_html__( 'Tu', 'healthy' ),
		esc_html__( 'Wed', 'healthy' ),
		esc_html__( 'Th', 'healthy' ),
		esc_html__( 'Fri', 'healthy' ),
		esc_html__( 'Sat', 'healthy' ),
	);

	foreach( $days as $day ) {
		$out .= "<th class='healthy-calendar-header-cell'>$day</th>";
	}

	$out = "<tr>$out</tr>";

	return $out;

}

function healthy_calendar_row( $which_row = 1 ) {

	$out = '';

	$num_cells = 7;

	$i = 0;
	while( $i < $num_cells ) {

		$i++;

		$out .= healthy_calendar_cell( $which_row, $i );

	}

	$out = "<tr>$out</tr>";

	return $out;

}

function healthy_calendar_cell( $which_row, $which_cell ) {

	$month = healthy_get_month_of_contest();

	$year = healthy_get_year_of_contest();

	$empty_days_at_start_of_month = healthy_get_empty_days_at_start_of_month();

	// Is it the first week of the month?
	if( $which_row == 1 ) {

		$content = $which_cell - $empty_days_at_start_of_month;

	// Okay it is at least the second week of the month.
	} else {

		$weeks_that_have_passed = $which_row - 1;	
		$last_day_of_last_week  = ( $weeks_that_have_passed * 7 ) - $empty_days_at_start_of_month;
		$this_day_of_this_week  = $last_day_of_last_week + $which_cell;
		
		$content = $this_day_of_this_week;
	
	}


	if( $which_row == 1 ) {
		if( $which_cell < healthy_get_weekday_of_first_day_of_month() ) {
			$content = '';
		}
	}

	if( $content > healthy_get_days_of_contest_month() ) {
		$content = '';
	}

	$timestamp = strtotime( "$year-$month-$content" );

	$maybe_complete = '';
	$link = '';
	if( ! empty( $content ) ) {

		$post_id = FALSE;

		$user_id = healthy_get_active_user_id();

		$get_posts = healthy_get_posts( $user_id, 1, $year, $month, $content );
		
		$post_id = FALSE;
		if( isset( $get_posts -> posts[0] ) ) {
			$p = $get_posts -> posts[0];
			if( isset( $p -> ID ) ) {
				$post_id = $p -> ID;
			}
		}

		$maybe_complete = '';

		if( date( 'n' ) != $month ) {
			$latest_valid_day = healthy_get_days_of_contest_month();
		} else {
			$latest_valid_day = date( 'd' );
		}

		if( $content > $latest_valid_day ) {
			$maybe_complete = 'healthy-calendar-cell-future';
		} elseif( $post_id && healthy_is_day_complete( $post_id ) ) {
			$maybe_complete = 'healthy-calendar-cell-complete';
		} else{
			$maybe_complete = 'healthy-calendar-cell-not-complete';
		}

		if( $content <= $latest_valid_day ) {

			$link = '';

			// A query to post a new day.
			$action = 'create';
			$object_id = 'new';

			if( $post_id && healthy_already_an_entry_for_this_day( $user_id, $timestamp ) ) {
				$action = 'edit';
				$object_id = $post_id;
			}
			$query = healthy_controller_query_string( 'post', $action, $object_id, FALSE, FALSE, $timestamp );
		
			// A base for the link.
			$base = trailingslashit( esc_url( get_bloginfo( 'url' ) ) );
		
			// The href to start a new day.
			$href = $base.$query;

			$content = "<a class='healthy-calendar-cell-link' href='$href'>$content</a>";

		}

	}

	$out = "<td class='$maybe_complete healthy-calendar-cell'>$content</td>";

	return $out;

}

function healthy_get_weekday_of_first_day_of_month() {

	$year     = healthy_get_year_of_contest();
	$month    = healthy_get_month_of_contest( 'n' );
	$date     = '1';
	$get_date = getdate( mktime( null, null, null, $month, $date, $year ) );

	$weekday = $get_date['wday'] + 1;

	//wp_die( var_dump( $weekday ) );

	return absint( $weekday );

}

/**
 * Most weeks in a month have 7 days in them.  But say for example the month starts on a wednesday.
 * In that case, there will only be 4 days in that week for that month.
 */
function healthy_get_number_of_days_in_week_for_month( $year = '', $month = '', $week = 1 ) {

	if( $week > 1 ) { return 7; }

	if( empty( $year ) ) {
		$year     = healthy_get_year_of_contest();
	}

	if( empty( $month ) ) {
		$month = healthy_get_month_of_contest( 'n' );
	}

	$first_day = healthy_get_weekday_of_first_day_of_month();

	$out = 7 - $first_day + 1;

	return $out;

}

function healthy_get_empty_days_at_start_of_month( $year = '', $month = '' ) {
	
	if( empty( $year ) ) {
		$year = healthy_get_year_of_contest();
	}

	if( empty( $month ) ) {
		$month = healthy_get_month_of_contest( 'n' );
	}

	$num_days_in_first_week = healthy_get_number_of_days_in_week_for_month( $year, $month );

	$out = 7 - $num_days_in_first_week;

	return $out;

}