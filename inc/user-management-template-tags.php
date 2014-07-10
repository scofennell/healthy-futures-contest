<?php
/**
 * Template tags for displaying functions related to user self-management.
 *
 * @package WordPress
 * @subpackage healthy
 * @since healthy 1.0
 */

/**
 * Returns an HTML select menu for the roles in our app.
 *
 * @return An HTML select menu for the roles in our app.
 */
function healthy_get_roles_as_select() {
	
	if( healthy_user_is_role( true, 'student' ) ) { return false; }

	$out = '';
    
	$roles = healthy_get_roles();
	foreach( $roles as $r ) {
		
		if ( ! isset( $r [ 'is_public' ] ) ) { continue; }

		$slug = $r[ 'slug' ];
		$label = $r[ 'label' ];

		$out.= "<option value='$slug'>$label</option>";
	}

	$choose = esc_html__( 'Teacher or Student', 'healthy' );
	$role = esc_html__( 'Role', 'healthy' );

    $out = "
    	<label for='role'>$role
    	<select id='role' name='role' required='required'>
    		<option value=''>$choose</option>
    		$out
    	</select>
    	</label>
    ";
    return $out;
}

/**
 * Returns an HTML form to confirm that the user wants to delete a post.
 *
 * @param  int $deleting The ID of the post to delete
 * @return bool|string Returns false if error, otherwise returns delete form.
 */
function healthy_delete_user_confirm( $deleting ) {

	// If the post id is weird, bail.
	$deleting = absint ( $deleting );
	if( empty( $deleting ) ) { return false; }

	// If the user can't delete this post, bail
	if( ! healthy_current_user_can_act_on_object( $deleting, 'delete', 'user' ) ) {
		return false;
	}

	// The date of the post we're deleting.
	$isplay_name = get_userdata( $deleting ) -> display_name;
	
	// The method by which to submit our form.
	$method = "post";
	
	// The action by which to handle our form.
	$form_action = healthy_current_url();
	
	// A nonce field for our form.
	$nonce = wp_nonce_field( 'healthy_delete_user', 'healthy_day_delete_user_nonce', false, false );

	// Strings for translation
	$delete_text = esc_attr__( 'Delete', 'healthy' );
	$cancel_text = esc_attr__( 'No, go back.', 'healthy' );
	
	// The url of the referring page ( aka "back" )
	$back_url = '';
	if( isset (  $_SERVER['HTTP_REFERER']  ) ) {
		$back_url = esc_url( $_SERVER['HTTP_REFERER'] );
	}

	// A link to cancel the deletion and go back to the previous screen.
	$no = "<a href='$back_url'>$cancel_text</a>";

	// A hidden field to carry the post ID to delete to the handler script.
	$object_id = "<input type='hidden' name='object_id' value='$deleting' >";

	// A hidden field to carry the post ID to delete to the handler script.
	$object_type = "<input type='hidden' name='object_type' value='user' >";

	// A hidden field to carry the post ID to delete to the handler script.
	$action = "<input type='hidden' name='action' value='delete' >";

	// A submit button for out form.
	$submit = "<input type='submit' name='user_delete' value='$delete_text'>";

	// Build the form
	$out = "
		<form method='$method' action='$form_action'>
			$nonce
			$no
			$object_id
			$object_type
			$action
			$submit
		</form>
	";

	return $out;
}

/**
 * Return an HTML form offering the teacher to switch to or delete any of his students.
 * 
 * @return boolean Returns false on failure, otherwise returns HTML form to manage users.
 */
function healthy_switch_to_user_form() {

	// Start the output variable.
	$out='';

	// The base url off of which we'll build urls for this function.
	$base = trailingslashit( esc_url( get_bloginfo( 'url' ) ) );

	// Only teachers may use this function.
	if ( ! healthy_user_is_role( false, 'teacher' ) ) { return false; }

	// Grab the school for the teacher.
	$school = healthy_get_user_school( get_current_user_id() );

	// Get students from the same school as the teacher.
	$args = array(
		'role'         => 'student',
		'meta_key'     => 'school',
		'meta_value'   => $school,
//		'meta_compare' => '',
//		'meta_query'   => array(),	
//		'include'      => array(),
//		'exclude'      => array(),
//		'orderby'      => 'login',
//		'order'        => 'ASC',
//		'offset'       => '',
//		'search'       => '',
		'number'       => 999,
		'count_total'  => false,
		'fields'       => array( 'display_name', 'user_email', 'ID' ),
		'who'          => '',
	 );

	// Get the users.
	$users = get_users( $args );

	// If no users returned, bail.
	if ( ! $users ) { return false; }

	// Grab the email address of the active user to power checked().
	$active_user = healthy_get_active_user();
	$active_user_email = $active_user -> user_email;
	$active_user_email_san = sanitize_html_class( $active_user_email );

	// If the user has switched users, create a special input to switch back to himself.
	if ( healthy_has_switched_users() ) {
		
		// Grab the current user.
		$current_user_id = absint( get_current_user_id() );
		$current_user_display_name = esc_html( get_userdata( get_current_user_id() ) -> display_name );
		
		// Make an label to switch back to the current user.
		$switch_back_label = sprintf( esc_html__( 'Switch back to %s?', 'healthy' ), $current_user_display_name );

		// Make an input to switch back to the current user.
		$out = "
			<label class='switch-back' for='$current_user_id'>
				<input reuired id='$current_user_id' value='$current_user_id 'type='radio' name='object_id' >
				<strong>$switch_back_label</strong>
			</label>
		";
	
	}

	// Make a label for deleting users.
	$delete_label = esc_html( 'Delete this user?', 'healthy' );

	// For each user:
	foreach( $users as $u ) {

		// Grab the user email.
		$email = $u -> user_email;

		// The user email will be used to power <label for="">.
		$class = sanitize_html_class( $email );

		// The label text for the user.
		$display_name = esc_html( $u -> display_name );

		// Is this user currently switched to?
		$checked = checked( $active_user_email_san, $class, false );

		// The ID of this user.
		$user_id = absint( $u -> ID );

		// Build the href to delete this user.
		$delete_query =  healthy_controller_query_string( 'user', 'delete', $user_id );
		$delete_href = esc_url( $base.$delete_query );

		// Output the input for this user.
		$out.="
			<label for='$class' >
				<input required id='$class' name='object_id' value='$user_id' type='radio' $checked >
				$display_name | $email | <a href='$delete_href'>$delete_label</a>
			</label>
		";
	}

	// The submit button for this form.
	$switch_text = esc_html( 'Act on Behalf of Selected User', 'healthy' );
	$submit="<input type='submit' name='healthy_switch_to_user' value='$switch_text'>";

	// Make a nonce.
	$nonce = wp_nonce_field( 'healthy_switch_to_user', 'healthy_switch_to_user_nonce', false, false );
	
	// The method by which we'll submit this form.
	$method = 'post';

	// The action to which we'll submit this form.
	$form_action = $base;//.$query;

	// A hidden field to carry the object type to the handler script.
	$object_type = "<input type='hidden' value='user' name='object_type'>";

	// A hidden field to carry the action we're performing to the handler script.
	$action = "<input type='hidden' value='switch' name='action'>";

	// Complete the output.
	$out = "
	<form method='$method' action='$form_action'>
		$nonce
		$out
		$object_type
		$action
		$submit
	</form>
	";

	return $out;

}

/**
 * Returns a link to browse students of the current user.
 *
 * @return string Returns a link to browse students of the current user.
 */
function healthy_review_students_link() {

	// If the user is not a teacher, bail.
	if( ! healthy_user_is_role( false, 'teacher' ) ) { return false; }
	
	// The query string to browse all users.
	$query = healthy_controller_query_string( 'user', 'review', 'all' );
	
	// Base url.
	$base = esc_url( get_bloginfo( 'url' ) );

	// The url which will handle this request.
	$href = esc_url( $base.$query );

	$school = healthy_get_user_school( get_current_user_id() );

	// The clickable link text.
	$school = ucwords( $school );
	$label = sprintf( esc_html( 'Review Students from %s', 'healthy' ), $school );

	// Output.
	$out = "<a href='$href'>$label</a>";

	return $out;

}

/**
 * Returns links to login, register, or retrieve password.
 * 
 * @return string Links to login, register, or retrieve password.
 */
function healthy_login_panels() {

	// Start the output var, into which we'll loop html.
	$out = '';

	/**
	 * Create an array of items to output.  Each item has a name and a callback
	 * function.
	 */
	$panels = array(

		// A login link.
		'login' => array(
			'callback' => 'healthy_login_link',
		),

		// A register link.
		'register' => array(
			'callback' => 'healthy_register_link',
		),

		// A reset pw link.
		'reset_password' => array(
			'callback' => 'healthy_reset_password_link',
		),
	);

	// For each item, add it to the output var.
	foreach ( $panels as $key => $panel ) {

		// The callbacks are defined later in this file.
		$callback = $panel['callback'];
		$callback_out = call_user_func( $callback );

		$out.="
			<div class='login-panel $key'>
				<h3>$callback_out</h3>
			</div>
		";

	}
	
	// Wrap the output.
	$out = "<div class='login-panels'>$out</div>";

	return $out;

}

/**
 * Return HTML to prompt the user to switch back to being the current user.
 * 
 * @return string|boolean HTML to prompt the user to switch back to being the current user, or false if the user has not switched.
 */
function healthy_the_switched_warning() {

	// Has the user actually switched?
	if ( $switched_to_user_id = healthy_has_switched_users() ) {

		// The display name of the switched_to user.
		$switched_to_display_name = esc_html( healthy_get_active_user() -> display_name );
		$out = sprintf( esc_html__( 'You are acting on behalf of %s.', 'healthy' ), $switched_to_display_name );

		if ( ! healthy_current_user_is_acting( 'review', 'user', 'all' ) ) {

			
			// The display name of the current user.
			$current_user_display_name = get_userdata( get_current_user_id() ) -> display_name;

			// A label prompting the user to switch users.
			$switch_label = sprintf( esc_html( 'Switch to a different user?', 'healthy' ), $current_user_display_name );

			// Build a link to switch back to the current user.
			$switch_query = healthy_controller_query_string( 'user', 'review', 'all' );
			$base = get_bloginfo( 'url' );
			$switch_href = esc_url( $base.$switch_query );
			$switch_link = "<a href='$switch_href'>$switch_label</a>";

			$out .= ' '.$switch_link;
	
		}
	
		// The user has switched.  Throw some disclaimer text.
		return $out;

	}

	// The user has not switched.
	return false;

}

/**
 * Return a loginout link.
 * 
 * @return string A loginout link.
 */
function healthy_login_link() {

	// Upon login, the user goes to the home page, not wp-admin.
	$redirect = esc_url( get_bloginfo( 'url' ) );

	// Complete the output.
	$out = wp_loginout( $redirect, false );
	
	return $out;
}
add_shortcode( 'healthy_login_link', 'healthy_login_link' );

/**
 * Return a register link.
 * 
 * @return string A register link.
 */
function healthy_register_link() {
	$out = wp_register( '', '', false );
	return $out;
}

/**
 * Return a reset pw link.
 * 
 * @return string A register link.
 */
function healthy_reset_password_link() {

	// Redirect to home page after retrieving the lost password
	$url = esc_url( wp_lostpassword_url( get_bloginfo('url') ) );
	
	// Strings for translation.
	$lost = esc_attr( 'Lost Password', 'healthy' );
	$reset = esc_html( 'Reset Password', 'healthy' );

	// Complete the output.
	$out = "<a href='$url' title='$lost'>$reset</a>";
	
	return $out;
}

/**
 * Returns the schools for our app as HTML <option>'s.
 * 
 * @param  int $user_id Accepts a user ID so it can do selected().
 * @param  boolean $include_empty Include an empty option?
 * @return string The schools for our app as HTML <option>'s.
 */
function healthy_get_schools_as_options( $user_id = '', $include_empty = false ) {

	// Start the output, into which we'll loop <option>'s.'
	$out='';

	// Maybe start with an empty option for situations where school is not mandatory.
	if ( $include_empty ) {
		if ( ! healthy_user_is_role ( false, 'teacher' ) ) {
			$out.="<option value=''>Choose a school</option>";
		}
	}

	// Get the schools
	$schools = healthy_get_schools();

	//wp_die(var_dump($schools));

	// Get the user meta, so we can power selected().
	$user_id = absint( $user_id );
	$meta = healthy_get_user_school( $user_id );
	
	// Teachers don't get to change schools.
	$teacher_school = '';
	if ( healthy_user_is_role( false, 'teacher' ) ) {
		$teacher_school = healthy_get_user_school( get_current_user_id() );
	}

	
	// For each school, loop it into an <option>
	foreach ( $schools as $s ) {
	
		// The value for this option.
		$slug = $s['slug'];

		// Teachers don't get to change schools.
		if ( healthy_user_is_role( false, 'teacher' ) ) {
			//wp_die("$slug != $teacher_school");
			if ( $slug != $teacher_school ) { continue; }
		}

		// The label for this option.
		$label = $s['label'];

		// Maybe create a selected=selected.
		$selected = '';
		if ( ! empty ( $meta ) ) {
			$selected = selected( $slug, $meta, false );
		}

		// Add to output.
		$out.="<option $selected value='$slug'>$label</option>";
	}

	return $out;
}

/**
 * Return an HTML form for creating or editing users.
 * 
 * @param  boolean $creating Are we creating, as opposed to editing?
 * @return string  An HTML form for creating or editing users.
 */
function healthy_profile_form( $creating = false ) {

	// Start the output.
	$out ='';

	// If we're creating, set some values.
	if ( $creating ) {
		$action = 'create';
		$user_to_edit_id = 'new';
		
	// If we're not creating, we're editing.
	} else {
		$action = 'edit';

		// Get the ID of the user we're editing.
		$user_to_edit_id = healthy_get_active_user_id();
		
		// Get the obj of the user we're editing.
		$user_data = healthy_get_active_user();
	}

	// The fields we'll display on our form.
	$fields = healthy_profile_fields();

	// For each field...
	foreach( $fields as $f ) {

		// The meta key.
		$slug = $f['slug'];
		
		// The label.
		$label = $f['label'];
		
		// The type of inuput.
		$type = $f['type'];

		// The default value for the input.
		$default = $f['default'];

		// Is this input required?
		$required = '';
		if (isset ( $f[ 'required' ] ) ){
			$required = ' required="required" ';
		}

		// A min value.
		$min = '';
		if (isset ( $f[ 'min' ] ) ){
			$min = $f[ 'min' ];
			$min = " min='$min' ";
		}
		
		// A max value.
		$max = '';
		if (isset ( $f[ 'max' ] ) ){
			$max = $f[ 'max' ];
			$max = " max='$max' ";
		}

		// A holder for min/max ( slider ) inputs.
		$output = "";
		if ( $type == 'range' ) {
			$output ="
				<output $required name='amount_$slug' for='$slug'>$default</output>
			";
		}	

		// If we're editing, draw the appropriate fields.
		if( $action == 'edit' ) {

			// If we're editing, don't show the password fields.
			if( $type == 'password' ) { continue; }

			// Get the user meta to pre-pop each field.
			$meta = get_user_meta( $user_to_edit_id, $slug, TRUE );
			
			// If there no meta for this field, that's the default value.
			if ( !empty ( $meta ) ) {
				$default = $meta;

			//If there is userdata for this field, that's the default value.
			} elseif ( $user_data->$slug ) {
				$default = $user_data->$slug;
			}

		}

		// If we're on the school input, grab a special field for that.
		if( $type == 'school' ) {

			// Grab the schools as <option>'s and wrap them in a <select>.
			//if( $creating ) {
			//	$include_empty = true;
			//} else {
			//	$include_empty = false;
			//}

			// If a teacher is creating a user, don't let them choose a school.
			if ( healthy_user_is_role ( true, 'teacher' ) && ! healthy_current_user_is_acting( 'create', 'user', 'new' ) ) { continue; }

				$schools_as_options = healthy_get_schools_as_options( $user_to_edit_id, true );			
				$input = "
					<select $required name='school'>
						$schools_as_options
					</select>
				";
			
	//		}

		// Else, it's a fairly normal input.
		} else {

			// Draw the input.
			$input = "<input $required name='$slug' class='$type' $min $max type='$type' id='$slug' value='$default'>";
		}

		// Build the whole field and add it to the output. 
		$out.="
			<label for='$slug'>
				$label
				$input
				$output
			</label>
		";

	}

	// The method by which we'll submit our form.
	$method = 'post';

	// The action to which we'll submit our form.
	$form_action = healthy_current_url();

	// The submit button text for our form.
	if ( $creating ) {
		$submit_text = esc_attr__( 'Create', 'healthy' );
	} else {
		$submit_text = esc_attr__( 'Edit', 'healthy' );
	}

	// A nonce field for our form.
	$nonce = wp_nonce_field( 'healthy_edit_profile', 'healthy_user_edit_profile', false, false );

	// JS to power our slider inputs.
	$range_script = healthy_range_script();

	// A submit button for our form.
	$submit = "<input type='submit' value='$submit_text' name='edit_profile'>";

	// A hidden field to carry the object id to the handler script.
	$object_id = "<input type='hidden' value='$user_to_edit_id' name='object_id'>";

	// A hidden field to carry the object type to the handler script.
	$object_type = "<input type='hidden' value='user' name='object_type'>";

	// A hidden field to carry the action we're performing (create/edit) to the handler script.
	$action = "<input type='hidden' value='$action' name='action'>";

	// jQ Validation plugin.
	$validate = '
		<script>
			jQuery( document ).ready( function() {
				jQuery( "#profile-form" ).validate();
			});
		</script>
	';

	// Role.
	$role = '';		
	if( get_current_user_id() != $user_to_edit_id ) {
		$role = healthy_get_roles_as_select();
	}

	// Complete the output.
	$out = "
		<form id='profile-form' method='$method' action='$form_action'>
			$nonce
			$out
			$role
			$object_id
			$object_type
			$action
			$submit
			$validate
		</form>
		$range_script
	";

	// Grab the validation script.
	wp_enqueue_script( 'jquery-validation' );

	return $out;

}