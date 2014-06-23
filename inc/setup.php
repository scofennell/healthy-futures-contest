<?php

/**
 * healthy setup functions.  Establish app-wide values.
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
 * Set the length of the contest in weeks.
 */
function healthy_length_of_contest() {
	
	// let's say the contest lasts for 6 weeks
	return 6;
}

/**
 * Set the first week of the contest.
 */
function healthy_first_week_of_contest() {
	
	// let's say the contest starts on the 23rd week of the year
	return 23;
}

/**
 * Get the last week of the contest.
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
 * Add custom roles for out users.  Only needs to run once, as it is saved to the db.
 */
function healthy_add_roles() {
    
	// Add a role for students.
    add_role(
    	'student', // Slug
    	'Student', // Label
    	array(
    		'read' 					 => true,
    		'level_0'				 => true,
    		'delete_posts' 			 => true,
			'delete_published_posts' => true,
			'edit_posts' 			 => true,
			'edit_published_posts' 	 => true,
			'publish_posts' 		 => true,
    	)
    );

    // Add a role for teachers.
    add_role(
    	'teacher', // Slug
    	'Teacher', // Label
    	array(
    		'level_0' 				 => true,
    		'delete_posts' 			 => true,
			'delete_published_posts' => true,
			'delete_others_posts' 	 => true,
			'delete_private_posts' 	 => true,
			'edit_posts' 			 => true,
			'edit_published_posts' 	 => true,
			'edit_others_posts' 	 => true,
			'edit_private_posts' 	 => true,			
			'publish_posts' 		 => true,
			'read' 					 => true,
			'read_private_posts ' 	 => true,
    	)
    );

}
//add_action( 'init', 'healthy_add_roles');

/**
 * healthy favicon.
 *
 * @since healthy 1.0
 */
function healthy_favicon() {
	?>
	<link rel="shortcut icon" type="image/x-icon" href="<?php echo get_template_directory_uri(); ?>/favicon.png">
	<?php
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
 * Enqueue scripts and styles for the front end.
 *
 * @since healthy 1.0
 */
function healthy_scripts_styles() {

	/**
	 * Loads our main stylesheet, complete with a value for date to break
	 * caching.  That date should be manually updated whenever the stylesheet
	 * is changed,
	 */  
	// wp_enqueue_style( 'healthy-style', get_stylesheet_uri(), array(), '2014-05-30' );
	wp_enqueue_style( 'healthy-sass', get_stylesheet_directory_uri().'/sass/output.css' , array(), '2014-05-30' );
	
	// Grab wp-includes version of jQuery.
	wp_enqueue_script( 'jquery' );

	// Grab wp-includes version of jQuery.
	wp_register_script( 'jquery-validation', get_template_directory_uri().'/js/jquery.validate.min.js', 'jquery', false, true );

}
add_action( 'wp_enqueue_scripts', 'healthy_scripts_styles' );

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