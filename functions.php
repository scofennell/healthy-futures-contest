<?php

/**
 * healthy manifest.
 *
 * require_once()'s other files for theme functionality.  This file is just a 
 * manifest:  It contains no function definitions, only calls to other files.
 *
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
 */
if( is_admin() ) {
	require_once( get_template_directory()."/admin/user-fields.php" );
}

/**
 * Customizations for login template files.
 */
require_once( get_template_directory()."/inc/login-customizations.php" );

/**
 * Template tags for managing user data.
 */
require_once( get_template_directory()."/inc/user-management-template-tags.php" );

/**
 * Template tags do draw forms and such for posting from front end.
 */
if( !is_admin() ){
	require_once( get_template_directory()."/inc/post-from-front-end-template-tags.php" );
}

/**
 * Custom template tags used in theme template files.
 */
if( !is_admin() ){
	require_once( get_template_directory()."/inc/template-tags.php" );
}

/**
 * Custom conditional tags used in theme template files.
 */
if( !is_admin() ){
	require_once( get_template_directory()."/inc/conditional-tags.php" );
}

/**
 * Functions to handle the processing of forms from the front end.
 */
if( !is_admin() ){
	require_once( get_template_directory()."/inc/handler-functions.php" );
}

/**
 * Body classes, posts classes, wp_title, etc.
 * @todo See if the menu item filter is still necessary in recent wordpress 
 * versions.
 */
require_once( get_template_directory()."/inc/misc-filters.php" );

/**
 * jQuery snippets for minor UX improvements.
 */
require_once( get_template_directory()."/inc/footer-scripts.php" );