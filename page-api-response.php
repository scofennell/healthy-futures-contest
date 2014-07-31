<?php

/*
Template Name: API Response
 */

if( ! isset( $_POST[ 'post_school' ] ) ) { echo "There has been a problem. 7"; }

$school = sanitize_text_field( $_POST[ 'post_school' ] );

echo healthy_get_teachers_as_options( $school );

?>