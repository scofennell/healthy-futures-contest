<?php

/**
 * healthy sponsor concerns.
 *
 * Functions for the display and management of sponsors.
 *
 * @package WordPress
 * @subpackage healthy
 * @since healthy 1.0
 */

/**
 * 
 */
function healthy_register_sponsors() {

	$sponsors = array(

		array (
			'label' => 'Providence',
			'slug'	=> 'providence',
			'logo'	=> 'providence.png'
		),

		array (
			'label' => 'State of Alaska',
			'slug'	=> 'state-of-alaska',
			'logo'	=> 'state-of-alaska.png'
		),

		array (
			'label' => 'United Way',
			'slug'	=> 'united-way',
			'logo'	=> 'united-way.png'
		),

	);

	return $sponsors;
}

function healthy_get_sponsors() {

	$out = '';

	$sponsors = healthy_register_sponsors();

	foreach ( $sponsors as $s ) {
		$slug = $s[ 'slug' ];
		$label = $s[ 'label' ];
		$logo = $s[ 'logo' ];
		$out .= healthy_get_sponsor( $slug, $label, $logo );
	}

	if( empty( $out ) ) { return false; }

	$out = "<div class='sponsors'>$out</div>";

	return $out;
}

function healthy_get_sponsor( $slug, $label, $logo ) {

	$out = '';

	$slug = sanitize_html_class( $slug );

	$img_dir = get_template_directory_uri().'/images/sponsors/';

	$src = esc_url( $img_dir.$logo );

	$alt = esc_attr( $label );

	$out = "<img class='sponsor-logo sponsor-logo-$slug' src='$src' alt='$alt'>";

	return $out;
}