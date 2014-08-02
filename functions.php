<?php

/**
 * healthy manifest.
 *
 * require_once()'s other files for theme functionality.  This file is just a 
 * manifest:  It contains no function definitions, only calls to other files.
 *
 * @todo It's just about time to start working in transients for caching, especially with the report views.
 * @todo It seems like many of my functions have morphed or outgrew the file they're in.  Probably need to sweep through and re-think what functions live in what file.
 * @todo In many places, I convert a slug to a label by doing string manip, such as with school names.  Instead, grab the school label from the school definition function.
 * @todo In many places, I do this: $base = trailingslashit( esc_url( get_bloginfo( 'url' ) ) );  Instead, that should be abstracted into a helper function.
 * @package WordPress
 * @subpackage healthy
 * @since healthy 1.0
 */

/**
 * Enqueue scripts, establish theme-wide values, setup widgets,
 * call theme-supports.
 */
require_once( get_template_directory()."/inc/setup.php" );

/**
 * Functions used in mnay places throughout the theme.
 */
require_once( get_template_directory()."/inc/helper-functions.php" );

/**
 * Custom post meta fields.
 */
if( is_admin() ) {
	require_once( get_template_directory()."/admin/meta-boxes.php" );
}

/**
 * Custom user meta fields.
 *
 * @todo Grab user fields by a callback function instead of spelling out each field one by one. (DRY alert)
 */
if( is_admin() ) {
	require_once( get_template_directory()."/admin/user-fields.php" );
}

/**
 * Customizations for login template files.
 *
 * @todo Add styles for register & change password views.
 */
require_once( get_template_directory()."/inc/login-customizations.php" );

/**
 * Template tags for managing user data.
 */
require_once( get_template_directory()."/inc/user-management-template-tags.php" );

/**
 * Template tags do draw forms and such for posting from front end.
 */
if( ! is_admin() ) {
	require_once( get_template_directory()."/inc/post-from-front-end-template-tags.php" );
}

/**
 * Custom template tags used in theme template files.
 */
if( ! is_admin() ) {
	require_once( get_template_directory()."/inc/template-tags.php" );
}

/**
 * Custom conditional tags used in theme template files.
 * @todo In the function to check if a user can act on an object, return false by default, and only return true if a whitelisted scenario is met.
 */
require_once( get_template_directory()."/inc/conditional-tags.php" );

/**
 * Functions to handle the processing of forms from the front end.
 */
if( ! is_admin() ) {
	require_once( get_template_directory()."/inc/handler-functions.php" );
}

/**
 * Body classes, posts classes, wp_title, etc.
 * versions.
 */
require_once( get_template_directory()."/inc/misc-filters.php" );

/**
 * jQuery snippets for minor UX improvements.
 */
require_once( get_template_directory()."/inc/footer-scripts.php" );

/**
 * Functions to create data reports.
 */
require_once( get_template_directory()."/inc/reporting.php" );

/**
 * Functions to manage sponsors.
 */
require_once( get_template_directory()."/inc/sponsors.php" );