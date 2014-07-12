<?php

/**
 * Functions to handle form submissions from the front end.
 *
 * @package WordPress
 * @subpackage healthy
 * @since healthy 1.0
 */

/**
 * Handle the form submission to delete a user.
 *  
 * @return boolean True on success, false on failure.
 */
function healthy_process_delete_a_user_form() {

	// If if this form is not submit, bail.
	if( ! isset( $_POST[ 'user_delete' ] ) ) { return false; }

	// If we've not specified a user to delete, bail.
	if( ! isset( $_POST[ 'object_id' ] ) ) { return false; }
	$user_to_delete_id = absint( $_POST['object_id'] );
	if ( empty( $user_to_delete_id ) ) { return false; }

	// If the nonce is bad, bail.
	if( ! isset( $_POST[ 'healthy_day_delete_user_nonce' ] ) ) { return false; }
	if( ! wp_verify_nonce( $_POST[ 'healthy_day_delete_user_nonce' ], 'healthy_delete_user' ) ) { return false; } 

	// If the current user cannot delete this user, bail.
	if( ! healthy_current_user_can_act_on_object( $user_to_delete_id, 'delete', 'user' ) ) {
		return false;
	}

	// We made it this far -- delete the user, also trashing their posts.
	require_once(ABSPATH.'wp-admin/includes/user.php' );	
	$delete = wp_delete_user( $user_to_delete_id );

	return $delete;

}

/**
 * Process the switch-to-user form.
 * 
 * @return boolean|void Returns false on failure, otherwise sets a cookie to switch users and exits.
 */
function healthy_process_switch_to_user_form() {

	// If the nonce is empty, bail.
	if( ! isset( $_POST[ 'healthy_switch_to_user_nonce' ] ) ) { return false; }

	// If the nonce is bad, bail.
	if( ! wp_verify_nonce( $_POST[ 'healthy_switch_to_user_nonce' ], 'healthy_switch_to_user' ) ) { return false; } 

	// Double check that we're on the right form page.
	if( ! isset( $_POST[ 'healthy_switch_to_user' ] ) ) { return false; }

	// If we've not specified a user to edit, bail.
	if( ! isset( $_POST[ 'object_id' ] ) ) { return false; }
	$object_id = absint( $_POST[ 'object_id' ] );

	// If the current user does not own the user we're switching to, bail.
	if ( ! healthy_does_user_own_user( get_current_user_id(), $object_id ) ) { return false; }

	// Grab the app-wide value for our cookie key so we don't have to keep repeating it.
	$cookie_key = healthy_switched_user_cookie_key();

	// If the user is switching back to himself, delete the cookie, redir, and bail.
	if ( get_current_user_id() == $object_id ) {
		
		// Delete the cookie if it exists.
		if ( isset( $_COOKIE[ $cookie_key ] ) ) {
			setcookie( $cookie_key, get_current_user_id(), time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
		}

		// Redir so as to get a fresh page updated with menu items and such that reflect the active user.
		wp_safe_redirect( esc_url( get_bloginfo( 'url' ) ) );
		exit;

	// If we're switching to a user other than the current user:
	} else {

		// Set the switch cookie.
		setcookie( $cookie_key, $object_id, time() + 3600, COOKIEPATH, COOKIE_DOMAIN );
		
		// Redir so as to get a fresh page updated with menu items and such that reflect the active user.
		wp_safe_redirect( esc_url( get_bloginfo( 'url' ) ) );
		exit;

	}

	return false;
}
add_action( 'init', 'healthy_process_switch_to_user_form' );

/**
 * Process the edit profile form.
 * 
 * @return boolean|int Returns the user id who's being edited, or false on error.
 */
function healthy_process_profile_form() {

	// If the nonce is empty, bail.
	if( ! isset( $_POST[ 'healthy_user_edit_profile' ] ) ) { return false; }

	// If the nonce is bad, bail.
	if( ! wp_verify_nonce( $_POST[ 'healthy_user_edit_profile' ], 'healthy_edit_profile' ) ) { return false; } 

	// If we've not specified a user to edit, bail.
	if( ! isset( $_POST[ 'object_id' ] ) ) { return false; }

	// If we're making a new user...
	if ( $_POST[ 'object_id' ] == 'new' ) {
		$editing = false;
		$user_to_edit_id = 'new';

	// Or if we're editing...
	} else {
		$user_to_edit_id = absint( $_POST[ 'object_id' ] ) ;
		
		// If the id of the user we're editing is weird, bail.
		if ( empty( $user_to_edit_id ) ) { return false; }
		$editing = true;
	}

	// Grab the current user ID.
	$current_user_id = get_current_user_id();
	if ( empty( $current_user_id ) ) { return false; }
	
	// Grab the active user ID.
	$active_user_id = healthy_get_active_user_id();
	if ( empty( $active_user_id ) ) { return false; }

	// The email submitted in the form.
	$user_email = $_POST[ 'user_email' ];

	// Sanitize the email address.
	$san_email = sanitize_email( $user_email );
	
	//  If the email address is not valid, bail.
	if ( $user_email != $san_email ) { wp_die( "Your email address was not valid, please try again." ); }
	
	// If the email address was empty, bail.
	if ( empty( $user_email ) ) { wp_die( "Please enter an email address." ); }

	// If we're editing, grab the current values for this user.
	if ( $editing ) {

		// If the current user cannot act on the user we're editing, bail.
		if ( ! healthy_current_user_can_act_on_object( $user_to_edit_id, 'edit', 'user' ) ) {
			return false;
		}

		// The user object we're editing.
		$user_obj_to_edit = get_userdata( $user_to_edit_id );
		if ( empty( $user_obj_to_edit ) ) { wp_die( "There has been an error. 313" ); }
	
		// The email of the user we're editing.
		$user_to_edit_email = $user_obj_to_edit -> user_email;
		if ( empty( $user_to_edit_email ) ) { wp_die( "There has been an error. 308" ); }

		// Although you an change a users email, you can't change it to one that's already taken.
		if( email_exists( $user_email ) && ( $user_email != $user_to_edit_email ) ) { wp_die( "A user with that email already exists, please try again." ); }

	// If we're not editing, just make sure the email is unique.
	} else {

		// If the current user cannot act on the user we're editing, bail.
		if ( ! healthy_current_user_can_act_on_object( $user_to_edit_id, 'create', 'user' ) ) {
			return false;
		}

		// You can't create a user with an email that already exists.
		if( email_exists( $user_email ) ) { wp_die( "A user with that email already exists, please try again." ); }
	}

	// This holder variable will get fed to wp_insert/update_user()
	$userdata = array();

	// Start populating the user data.
	$userdata[ 'user_email' ] = $user_email;

	// The role submitted in the form.
	if( isset( $_POST[ 'role' ] ) ) {
		$role = $_POST[ 'role' ];

		// The roles that users are allowed to occupy in our app.
		$allowed_roles = healthy_get_allowed_roles();

		// If it's not an allowed role, die.
		if( ! in_array( $role, $allowed_roles ) ) { wp_die( 'There has been a problem. 136' ); }
		
		// If we made it this far, assign the role.
		$userdata[ 'role' ] = $role;
	}

	// If we're editing...
	if( $editing ) {

		// ... we have to specify a user ID.
		$userdata[ 'ID' ] = $user_to_edit_id;

		// wp_update_user returns The ID of the user we just edited.
		$affected = wp_update_user( $userdata ) ;

	// If we're creating,
	} else {

		/*
		// Compare the pw & pw confirm.
		$user_pass = $_POST['password'];
		$user_pass_confirm = $_POST['password_confirm'];
		if ( $user_pass != $user_pass_confirm ) { wp_die( "The passwords did not match, please try again." ); }
		*/
		
		// Set the user PW.  Null means wp will assign a random one.
		$userdata[ 'user_pass' ] = null; // A string that contains the plain text password for the user. 
		
		// Convert the user email into the user name.
		$user_login = sanitize_user( $user_email );

		// User login must be unique.
		if ( username_exists( $user_login ) ) { wp_die( "A user with that email already exists, please try again." ); }
		$userdata[ 'user_login' ] = $user_login; 

		// Set the display name to something remotely recognizable.
		$first_name = sanitize_text_field( $_POST['first_name'] );
		$last_name = sanitize_text_field( $_POST['last_name'] );
		$userdata[ 'display_name' ] = $first_name.' '.$last_name;

		// Returns the ID of the newly created user.
		$affected = wp_insert_user( $userdata ) ;
	
	}

	// The fields for users in our app.
	$fields = healthy_profile_fields();
	
	// For each field, see if it's meta & edit that meta.
	foreach( $fields as $f ) {

		// Use the slug for that field as a meta key
		$slug = sanitize_key( $f[ 'slug' ] );

		// Some fields are not always submitted.
		if( ! isset( $_POST[ $slug ] ) ) { continue; }

		// email is not meta.
		if( $slug == 'user_email' ) { continue; }
		
		// pw is not meta.
		if( $slug == 'password' ) { continue; }
		if( $slug == 'password_confirm' ) { continue; }
		
		// Find the value in $_POST that corresponds with $slug.
		$value = strip_tags( $_POST[ $slug ] );

		// Sanitize $value.
		$value = trim( $value );
		$value = sanitize_text_field( $_POST[ $slug ] );
		$value = preg_replace( '/[^,a-zA-Z 0-9_-]|[,;]$/s', '', $value );
		
		// Make sure required fields are not skipped.
		$label = $f[ 'label' ];

		// Use update_user_meta regardless of if we're creating or editing.
		update_user_meta( $affected, $slug, $value );

	}

	// If we made it this far without doing something, something is wrong.
	if( empty( $affected ) ) { wp_die( "There has been a problem. 173" ); }

	return $affected;
}
add_action( 'init', 'healthy_process_profile_form' );


/**
 * Delete a post.
 *
 * @return bool|string Returns false upon error, otherwise returns thank you text.
 */
function healthy_process_delete_a_day_form() {

	// If we've not specified a day to delete, bail.
	if( ! isset( $_POST[ 'object_id' ] ) ) { return false; }
	if( ! isset( $_POST[ 'post_a_day_delete' ] ) ) { return false; }

	// If the nonce is bad, bail.
	if( ! wp_verify_nonce( $_POST[ 'healthy_day_delete_nonce' ], 'healthy_delete' ) ) { return false; } 

	// Which post are we deleting?
	$day_to_delete = absint( $_POST['object_id'] );
	if ( empty( $day_to_delete ) ) { return false; }

	// If the current user cannot delete this post, bail.
	if( ! healthy_current_user_can_act_on_object( $day_to_delete, 'delete', 'post' ) ) {
		return false;
	}

	// We made it this far -- delete the post, bypassing the trash can.	
	$delete = wp_delete_post( $day_to_delete, true );

	// If there was a problem deleting, err.  Otherwise, thank.
	if ( ! $delete ) {
		$deleted = esc_html__( 'There has been an error and your entry could not be deleted.', 'healthy' );
	} else {
		$deleted = esc_html__( 'Your entry has been deleted.', 'healthy' );
	}
	
	return $deleted;
}


/**
 * Processes the post-a-day form.
 *
 * @return bool|int On success, returns the ID of the post, otherwise returns false.
 */
function healthy_process_post_a_day_form() {

	// Nonce check.
	if( ! isset( $_POST[ 'healthy_day_nonce' ] ) ) { return false; }
	if( ! wp_verify_nonce( $_POST['healthy_day_nonce'], 'healthy_create' ) ) { return false; }
	
	// Make-sure-we're-in-the-right-place check.
	if( ! isset( $_POST[ 'post_a_day_submit' ] ) ) { return false; }
	
	// On what post are we acting?
	if( ! isset( $_POST['object_id'] ) ) { wp_die( 'There has been an error. 407' ); }
	$post_id_to_act_upon = $_POST['object_id'];
	
	// Are we creating a new post?
	if ( $post_id_to_act_upon == 'new' ) {
		
		// An ID of 'new' means it's a new post.
		$editing = false;
	
		// If the user can't create posts, bail.
		if( ! healthy_current_user_can_act_on_object( $post_id_to_act_upon, 'create', 'post' ) ) {
			return false;	
		}	
	
	// We're editing a post.
	} else {
		$editing = true;
		$post_id_to_act_upon = absint( $_REQUEST['object_id'] );
		
		// If the user can't edit this post, bail.
		if( ! healthy_current_user_can_act_on_object( $post_id_to_act_upon, 'edit', 'post' ) ) {
			return false;	
		}	
	
	}

	// The post author is the student who created the post.
	$post_author_id = absint( healthy_get_active_user_id() );

	// If there is no author ID, something is wrong, bail.
	if ( empty( $post_author_id ) ) { wp_die( 'There has been a problem. 586' ); }

	// Get the user for this ID.
	$post_author_obj = get_userdata( $post_author_id );

	// Grab user and post data to build the post title, to make for easier searching.
    $post_author_email = esc_html( $post_author_obj->user_email );
    $post_author_first_name = esc_html( $post_author_obj->user_firstname );
    $post_author_last_name = esc_html( $post_author_obj->user_lastname );
    
    // Adding the post date to the title makes for easier searching and browsing.
    $date_string = esc_html( $_POST[ 'date' ] );
    $date_stamp = strtotime( $date_string );
    $date = date( 'Y-m-d H:i:s', $date_stamp );
    $week = date( 'W', $date_stamp );

    // Build our search-friendly post title.
	$title = " author first name: $post_author_first_name | author last name: $post_author_last_name | author ID: $post_author_id | author email: $post_author_email | week $week | $date_string ";
	
	// The post slug
	$name = sanitize_html_class( $title );

	// Build the post array.
	$post = array(
		'post_title'   => $title,
	 	'post_name'    => $name,
		'post_status'  => 'publish',
	  	'post_type'    => 'healthy_day',	
		'post_date'    => $date,
		'post_author'  => $post_author_id,
	);  

	// If we're editing, add the ID of the post we want to edit.
	if( $editing ) {
		$post['ID'] = $post_id_to_act_upon;

	// If we're not editing, we have to worry about there already being a post for this date.	
	} elseif( healthy_already_an_entry_for_this_day( $post_author_id, $date ) ) {
		wp_die( 'There is already an entry for this day. Please go back and select a different day.' );
	}

	// The ID of the post created or updated.
	$inserted = absint( wp_insert_post( $post ) );

	// If the id is weird, something is wrong.
	if( empty( $inserted ) ) { wp_die( 'There has been a problem saving your day.' ); }

	// Grab our app-wide definition of a day.
	$day = healthy_day();
	$components = $day['components'];

	// build the post meta
	foreach( $components as $f ) {
		
		// Use the slug for that field as a meta key
		$slug = sanitize_key( $f[ 'slug' ] );

		// we already used the date to make the date for the post, so don't save it as meta.
		if( $slug == 'date' ) { continue; }
		
		// Find the value in $_POST that corresponds with $slug.
		$value = strip_tags( $_POST[ $slug ] );

		// Sanitize $value.
		$value = trim( $value );
		$value = sanitize_text_field( $_POST[ $slug ] );
		$value = preg_replace( '/[^,a-zA-Z 0-9_-]|[,;]$/s', '', $value );
		
		// If we're editing, do an update.
		if( $editing ) {
			update_post_meta( $inserted, $slug, $value );

		// Otherwise, do an add.
		} else {	
			add_post_meta( $inserted, $slug, $value, TRUE );
		}
	}
	
	// Return the ID of the post we're working on, or false.
	if( ! empty( $inserted ) ) {
		return $inserted;
	} else {
		return false;
	}
}