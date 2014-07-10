<?php

/**
 * healthy setup functions.  Get and set app-wide values, do core WP configuration, enqueue scripts.
 *
 * @package WordPress
 * @subpackage healthy
 * @since  healthy 1.0
 */

/**
 * Set the text domain for translation.
 */
function healthy_textdomain() {
    load_theme_textdomain( 'healthy', get_template_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'healthy_textdomain' );

/**
 * Set the time zone for our app to Anchorage.
 */
function healthy_set_the_time_zone() {
	date_default_timezone_set( 'America/Anchorage' );
}
add_action( 'init', 'healthy_set_the_time_zone', 0 );

/**
 * Set the minimum amount of exercise to complete a day.
 *
 * @return int The number of minutes of exercise to achieve day_is_complete.
 */
function healthy_daily_minimum() {

	// Let's say 30 minutes of exercise completes a day.
	return 30;
}

/**
 * Set the minimum amount of exercise days to complete a week.
 *
 * @return int The number of days_complete to achieve week_is_complete.
 */
function healthy_weekly_minimum() {

	// Let's say 4 days of exercise completes a week.
	return 4;
}

/**
 * Set the length of the contest in weeks.
 *
 * @return int The length of the contest in weeks.
 */
function healthy_length_of_contest() {
	
	// Let's say the contest lasts for 6 weeks.
	return 6;
}

/**
 * Set the first week of the contest.
 *
 * @return int The date('W') for the first week of the contest.
 */
function healthy_first_week_of_contest() {
	
	// Let's say the contest starts on the 26th week of the year.
	return 26;
}

/**
 * Get the date('W') for the last week of the contest.
 *
 * @return int The date('W') for the last week of the contest.
 */
function healthy_last_week_of_contest() {
	
	// Start by grabbing the first week.
	$first = healthy_first_week_of_contest();
	
	// Next, grab the length.
	$length = healthy_length_of_contest();
	
	// Add the two.
	$off_by_one = $first + $length;

	// Subtract 1.
	$out = $off_by_one - 1;

	return $out;
}

/**
 * Get the roles used in our app.
 * 
 * @return array A multidimensional associative array of roles for users in our app.
 */
function healthy_get_roles() {
	$out = array(
		
		// Students.  Can edit profile and CRUD exercise data.
		array(

			// The slug is used as a key for accessing the role.
			'slug'  	=> 'student',

			// The label is used to printing the role to the screen.
			'label' 	=> 'Student',

			// is_public is used to decide if the role is available to normal users.
			'is_public' => 1,
		),

		// Teachers.  Can CRUD students and view reports for their school.
		array(
			'slug'  	=> 'teacher',
			'label' 	=> 'Teacher',
			'is_public' => 1,
		),

		// Meaghan and Nicole.  Can CRUD teachers and students, and view reports for any school.
		array(
			'slug'  	=> 'boss',
			'label' 	=> 'Boss',
		),
	);
	return $out;
}

/**
 * Add custom roles for out users.  Only needs to run on theme activation.
 */
function healthy_add_roles() {
 
	// If we're not in admin, bail.
	if( ! is_admin () ) { return false; }

 	// Get the current screen.
	$screen = get_current_screen();
	
	// Get the screen base.
	$base = $screen -> base;
	
	// If we're not in the themes section, bail.
	if( $base != 'themes' ) { return false; }

	// If we didn't just activate, bail.
	if( ! isset( $_GET[ 'activated' ] ) ) { return false; }

	// Grab the user roles.
	$roles = healthy_get_roles();

	// For each role:
	foreach( $roles as $r ) {
		
		// Grab the slug.
		$slug = $r[ 'slug' ];
		
		// Grab the label.
		$label = $r[ 'label' ];
		
		// Write the role to the db.
		add_role(
    		$slug,
    		$label
    	);
	}
}
add_action( 'admin_head', 'healthy_add_roles');

/**
 * Draw a favicon. Expects an image, favicon.png, in the parent theme root.
 *
 * @since healthy 1.0
 */
function healthy_favicon() {

	// The favicon href.
	$href = esc_url( get_template_directory_uri().'/favicon.png' );
	
	// The favicon tag.
	$out = "<link rel='shortcut icon' type='image/x-icon' href='$href'>";
	
	echo $out;
}
add_action( 'wp_head', 'healthy_favicon' );

/**
 * Set up the content width value based on the theme's design.
 */
if ( ! isset( $content_width ) ) {
	$content_width = 680;
}

/**
 * healthy setup.
 *
 * Sets up theme defaults and registers the various WordPress features that
 * healthy supports.
 *
 * @since healthy 1.0
 */
function healthy_setup() {

	// Adds RSS feed links to <head> for posts and comments.
	// add_theme_support( 'automatic-feed-links' );

	// This theme uses wp_nav_menu() in two locations.
	// register_nav_menu( 'primary-menu', __( 'Primary Navigation Menu', 'healthy' ) );
	// register_nav_menu( 'secondary-menu', __( 'Secondary Navigation Menu', 'healthy' ) );

	// Allow for post thumbnails.
	// add_theme_support( 'post-thumbnails' );

	// Allow for HTML5.	
	add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption' ) );

	// This theme uses its own gallery styles.
	add_filter( 'use_default_gallery_style', '__return_false' );

}
add_action( 'after_setup_theme', 'healthy_setup' );

/**
 * Grab editor styles from the main stylesheet.
 *
 * @since healthy 1.0
 */
function healthy_add_editor_styles() {
    add_editor_style( get_stylesheet_directory_uri().'/sass/output.css' );
}
add_action( 'init', 'healthy_add_editor_styles' );

/**
 * Get rid of the color picker.
 */
remove_action( 'admin_color_scheme_picker', 'admin_color_scheme_picker' );

/**
 * Enqueue scripts and styles for the front end.
 *
 * @since healthy 1.0
 */
function healthy_scripts_styles() {

	/**
	 * Loads our main stylesheet, complete with a value for date to break
	 * caching.  That date should be manually updated whenever the stylesheet
	 * is changed.
	 */  
	wp_enqueue_style( 'healthy-sass', get_stylesheet_directory_uri().'/sass/output.css' , array(), '2014-07-08' );
	
	// Grab wp-includes version of jQuery.
	wp_enqueue_script( 'jquery' );

	// Grab wp-includes version of jQuery.
	wp_register_script( 'jquery-validation', get_template_directory_uri().'/js/jquery.validate.min.js', 'jquery', false, true );

}
add_action( 'wp_enqueue_scripts', 'healthy_scripts_styles' );

/**
 * Enqueue scripts for wp-admin.
 */
/*
function healthy_admin_enqueue() {
    
	// Grab the current screen.
    $screen = get_current_screen();
    
    // Grab the screen base.
    $base = $screen -> base;

    // If we're editing a user, grab jq validation -- Mainly because teachers and students need to be assigned to a school.
    if ( ( $base == 'user-edit' ) || ( $base == 'profile' ) || ( $base == 'user' ) ) {
        wp_enqueue_script( 'jquery-validation', get_template_directory_uri().'/js/jquery.validate.min.js', 'jquery', false, true );
    }
    
}
add_action( 'admin_enqueue_scripts', 'healthy_admin_enqueue' );
*/

/**
 * Register our widget areas.
 *
 * @since healthy 1.0
 */
function healthy_widgets_init() {

	register_sidebar( array(
		'name'          => __( 'Footer Widget Area', 'healthy' ),
		'id'            => 'footer-widgets',
		'description'   => __( 'Appears in the footer section of the site.', 'healthy' ),
		'before_widget' => '<div id="%1$s" class="widget footer-widget content-holder %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h3 class="widget-title footer-widget-title">',
		'after_title'   => '</h3>',
	) );

}
add_action( 'widgets_init', 'healthy_widgets_init' );

/**
 * Register a post type for days.
 *
 * @link http://codex.wordpress.org/Function_Reference/register_post_type
 */
function healthy_reg_post_type() {
	$labels = array(
		'name'               => _x( 'Days', 'post type general name', 'healthy' ),
		'singular_name'      => _x( 'Day', 'post type singular name', 'healthy' ),
		'menu_name'          => _x( 'Days', 'admin menu', 'healthy' ),
		'name_admin_bar'     => _x( 'Day', 'add new on admin bar', 'healthy' ),
		'add_new'            => _x( 'Add New', 'day', 'healthy' ),
		'add_new_item'       => __( 'Add New Day', 'healthy' ),
		'new_item'           => __( 'New Day', 'healthy' ),
		'edit_item'          => __( 'Edit Day', 'healthy' ),
		'view_item'          => __( 'View Day', 'healthy' ),
		'all_items'          => __( 'All Days', 'healthy' ),
		'search_items'       => __( 'Search Days', 'healthy' ),
		'parent_item_colon'  => __( 'Parent Days:', 'healthy' ),
		'not_found'          => __( 'No days found.', 'healthy' ),
		'not_found_in_trash' => __( 'No days found in Trash.', 'healthy' )
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => false,
	//	'show_ui'            => true,
	//	'show_in_menu'       => true,
	//	'query_var'          => true,
	//	'rewrite'            => array( 'slug' => 'day' ),
	//	'capability_type'    => 'post',
		'has_archive'        => false,
		'hierarchical'       => false,
	//	'menu_position'      => null,
		'supports'           => array( 'title', 'author' )
	);

	register_post_type( 'healthy_day', $args );
}
add_action( 'init', 'healthy_reg_post_type' );