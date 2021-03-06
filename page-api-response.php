<?php

/*
Template Name: API Response
 */

if( isset( $_POST[ 'post_school' ] ) ) {
	$healthy_api_school = sanitize_text_field( $_POST[ 'post_school' ] );

	if( isset( $_POST[ 'post_teacher' ] ) ) {
		$healthy_api_teacher = sanitize_text_field( $_POST[ 'post_teacher' ] );
		echo healthy_get_teachers_as_options( $healthy_api_school, $healthy_api_teacher );
	}

	if( isset( $_POST[ 'post_team' ] ) ) {
		$healthy_api_team = sanitize_text_field( $_POST[ 'post_team' ] );
		echo healthy_get_teams_as_options( $healthy_api_school, $healthy_api_team );
	}

}

?>