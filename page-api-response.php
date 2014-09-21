<?php

/*
Template Name: API Response
 */

if( ! isset( $_POST[ 'post_school' ] ) ) { echo "There has been a problem. 7"; }

$healthy_api_school = sanitize_text_field( $_POST[ 'post_school' ] );

if( ! isset( $_POST[ 'post_teacher' ] ) ) { echo "There has been a problem. 11"; }

$healthy_api_teacher = sanitize_text_field( $_POST[ 'post_teacher' ] );

echo healthy_get_teachers_as_options( $healthy_api_school, $healthy_api_teacher );

?>