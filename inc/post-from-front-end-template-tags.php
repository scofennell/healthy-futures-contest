<?php
/**
 * Functions to create/edit/delete data from the front end.
 *
 * @package WordPress
 * @subpackage healthy
 * @since healthy 1.0
 */

/**
 * Returns an HTML form to confirm that the user wants to delete a post.
 *
 * @param  int $deleting The ID of the post to delete
 * @return bool|string Returns false if error, otherwise returns delete form.
 */
function healthy_delete_confirm( $deleting ) {

	// If the post id is weird, bail.
	$deleting = absint ( $deleting );
	if( empty( $deleting ) ) { return false; }

	// If the user can't delete this post, bail
	if( ! healthy_current_user_can_act_on_object( $deleting, 'delete', 'post' ) ) {
		return false;
	}

	// The date of the post we're deleting.
	$date = healthy_get_post_date_by_id( $deleting );
	
	// The method by which to submit our form.
	$method = "post";
	
	// The action by which to handle our form.
	$form_action = healthy_current_url();
	
	// A nonce field for our form.
	$nonce = wp_nonce_field( 'healthy_delete', 'healthy_day_delete_nonce', false, false );

	// Strings for translation
	$delete_text = esc_attr__( 'Delete', 'healthy' );
	$cancel_text = esc_attr__( 'No, go back.', 'healthy' );
	
	// The url of the referring page ( aka "back" )
	$back_url = esc_url( $_SERVER['HTTP_REFERER'] );

	// A link to cancel the deletion and go back to the previous screen.
	$no = "<p><a href='$back_url'>$cancel_text</a></p>";

	// A hidden field to carry the post ID to delete to the handler script.
	$object_id = "<input type='hidden' name='object_id' value='$deleting'>";

	// A hidden field to carry the post ID to delete to the handler script.
	$object_type = "<input type='hidden' name='object_type' value='post'>";

	// A hidden field to carry the post ID to delete to the handler script.
	$action = "<input type='hidden' name='action' value='delete'>";

	// A submit button for out form.
	$submit = "<input type='submit' name='post_a_day_delete' value='$delete_text'>";

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
 * Returns an HTML form for creating or editing a post.
 * 
 * @param  boolean $editing Is the user editing a post?
 * @param  int The timestamp for the day for this post.
 * @return bool|string  An HTML form for creating/editing a post, or false on error.
 */
function healthy_post_a_day_form( $editing = false, $which_date = false ) {

	// Only logged in users can use this.
	if ( ! is_user_logged_in() ) { return false; }

	// Post for today, if none specified.
	if( ! $which_date ) {
		$which_date = current_time( 'timestamp' );
	}

	// Start the output var.
	$out = '';

	// The base url off of which we'll build various links and for actions.
	$base_url = trailingslashit( esc_url( get_bloginfo( 'url' ) ) );

	// The author to whom we'll attribute this post.
	$post_author_id = healthy_get_active_user_id();

	// Will get pre filled if we are editing.
	$post_date_to_edit = '';

	// If we're editing, do some checks and set some values
	if ( $editing ) {

		$action = 'edit';
		
		// Editing requires that we have a url var telling us what post to edit.
		if( ! isset( $_REQUEST[ 'object_id' ] ) ) { return false; }
		$post_id_to_edit = absint( $_REQUEST[ 'object_id' ] );

		// Can the user edit that post?  If not, bail.
		if( ! healthy_current_user_can_act_on_object( $post_id_to_edit, 'edit', 'post' ) ) {
			return false;
		} 

		// Build the date of the post we're editing.
		$post_date_to_edit = healthy_get_post_date_by_id( $post_id_to_edit );

	// If we're not editing, we're creating.
	} else {
		$action = 'create';
	}

	// The method by which we'll submit the form.
	$method = "post";

	// The action by which we'll handle the form.
	$form_action = healthy_current_url();

	// Our app-wide array that defines what a day is.
	$day = healthy_day();

	// Contains definitions for form fields
	$components = $day['components'];
	
	// For each field, add it to the form.
	foreach( $components as $f ) {

		// The human readable name for the field.
		$label 	= '<span class="label-text">'.$f[ 'label' ].'</span>';
		
		// The unique name for the field.
		$slug 	= $f[ 'slug' ];
		
		// The input type.
		$type 	= $f[ 'type' ];
		
		// A min value.
		$min = '0';
		if (isset ( $f[ 'min' ] ) ){
			$min = $f[ 'min' ];
			//$min = " min='$min' ";
		}
		
		// A max value.
		$max = '0';
		if (isset ( $f[ 'max' ] ) ){
			$max = $f[ 'max' ];
			//$max = " max='$max' ";
		}
		
		// A step value.
		$step = '';
		if (isset ( $f[ 'step' ] ) ){
			$step = $f[ 'step' ];
			//$step = " step='$step' ";
		}

		// A default value.
		$default = $f['default'];

		// A notes value.
		$notes = '';
		if (isset ( $f[ 'notes' ] ) ){
			$notes = "<small class='notes'>".esc_html( $f[ 'notes' ] )."</small>";
		}

		// If we're editing, things are a little different.
		if( $editing ) {

			// If we're editing and we're dealing with the date, grab that from the post object.
			if( $slug == 'date' ) {
				$default = $post_date_to_edit;
			} else {
				// If we're editing any other field, grab it from meta.
				$default = get_post_meta( $post_id_to_edit, $slug, TRUE );

				// If it's a range, grab zero instead of empty string for empty values.
				if( ( $type == 'range' ) && empty( $default ) ) { $default = 0; }

			}

		// If we're not editing, just grab the app-wide default for that field.
		} else {
			$default = $f['default'];
		}

		// Draw the input.
		if( $type == 'range' ) {
			//$input = "<input name='$slug' class='$type' $min $max $step type='$type' id='$slug' value='$default'>";
		
			//$input = "<input name='$slug' class='range' value='$default' $min $max $step type='text' id='$slug' readonly>";

			$unit = '';
			if( isset( $f[ 'unit' ] ) ) {
				$unit = $f[ 'unit' ];
				$singular = $unit[0];
				$plural = $unit[1];

				if( $default == 1 ) {
					$unit_label = $singular;
				} else {
					$unit_label = $plural;	
				}

				$unit_label = esc_attr( $unit_label );
			}

			$input = healthy_get_slider( $slug, $min, $max, $step, $default, $unit_label );

		} elseif ( $type == 'date' ) {

			if( isset( $_GET['object_id'] ) && $_GET['action'] == 'edit' ) {
				$date = get_the_date( FALSE, $_GET['object_id'] );
				$timestamp = strtotime( $date );
			} else {

				if( isset( $_GET['timestamp'] ) ) {
					$timestamp = absint( $_GET['timestamp'] );
				} else {
					$timestamp = absint( current_time( 'timestamp' ) );
				}

				$date = date( get_option( 'date_format'), $timestamp );

			}

			// Just show the date, don't let the user edit it.
			$input = "<div class='healthy-date-input'><time>$date</time><input type='hidden' name='date' value='$timestamp'></div>";

		} else {
			$input = "<input name='$slug' class='$type' type='$type' id='$slug' value='$default'>";
		}

		// A holder for min/max ( slider ) inputs.

		// Build the whole field and add it to the output. 
		$out.="
			<label for='$slug'>
				$label
				$notes
				$input
			</label>
		";

	} // End for each field.

	// A nonce for our form.
	$nonce = wp_nonce_field( 'healthy_create', 'healthy_day_nonce', false, false );

	// The text for our form submit.
	//if ( $editing ) {
	$submit_text = esc_attr__( 'Submit', 'healthy' );
	//} else {
	//	$submit_text = esc_attr__( 'Submit', 'healthy' );
	//}

	// The submit button for out form.
	$submit = "<input type='submit' name='post_a_day_submit' value='$submit_text'>";

	// JS to power our slider inputs.
	$range_script = healthy_range_script();

	/**
	 * If we're editing, add a delete link and a hidden form field to carry this
	 * to the processor script.
	 */
	$edit_hidden = '';
	$delete = '';
	if ( $editing ) {
		
		// Build a delete link. 
		$delete_text = esc_html__( 'Delete this entry.', 'healthy' );
		$query = healthy_controller_query_string( 'post', 'delete', $post_id_to_edit );
		$delete_href = $base_url.$query;
		$delete = "<br><a class='deemphasize healthy-delete-link' href='$delete_href'>$delete_text</a>";
		
	}

	// A field to carry the object type.
	$object_type = "<input type='hidden' name='object_type' value='post'>";
	
	// A field to carry the action.
	$action = "<input type='hidden' name='action' value='$action'>";

	// Create a field to carry the id of the post we're editing.
	if( $editing ) {
		$object_id = "<input type='hidden' name='object_id' value='$post_id_to_edit'>";
	} else {
		$object_id = "<input type='hidden' name='object_id' value='new'>";
	}

	// Build the form.
	$out = "
		<form method='$method' action='$form_action'>
			$nonce
			$out
			$object_id
			$object_type
			$action
			$submit
			$delete
		</form>
		$range_script
	";

	return $out;

}

/**
 * Browse specific data from a day of the contest.
 *
 * @param  int The post id we're reviewing.
 * @return string Data for that post.
 */
function healthy_review_a_post( $post_id ) {

	$out = '';

	// The post id we're reviewing.
	$post_id = absint( $post_id );
	if( empty( $post_id ) ) { return false; }

	// The post obj we're reviewing.
	$post_obj = get_post( $post_id );
	if( empty( $post_obj ) ) { return false; }

	// The date for the post we're reviewing.
	$post_date = $post_obj -> post_date;
	$post_date = date( 'l, F d, Y', strtotime( $post_date ) );

	// Use the date as the title.
	$title = "<h3>$post_date</h3>";

	// Our app-wide definition of what a day contains.
	$day = healthy_day();

	// Contains definitions for day fields.
	$components = $day['components'];
	
	// For each field, add it to the output.
	foreach($components as $f ) {

		// The human readable name for the field.
		$label 	= '<span class="label-text">'.$f[ 'label' ].'</span>';
		
		// The unique name for the field.
		$slug 	= $f[ 'slug' ];
		
		// We already used date.
		if( $slug == 'date' ) { continue; }

		// Get the value for this field.
		$meta = get_post_meta( $post_id, $slug, TRUE );

		// The unit.
		$unit_label = '';
		if( isset(  $f[ 'unit' ] ) ) {
			
			$unit = $f[ 'unit' ];
			
			if( $meta == 1 ) {
				$unit_label = $unit[0];
			} else {
				$unit_label = $unit[1];	
			}

		}

		// Output.
		$out.="
			<dt>$label</dt>
			<dd>$meta $unit_label</dd>
		";

	}

	// If there is not post data, bail.
	if( empty( $out ) ) { return false; }

	// Wrap the output as a dl.
	$out = "$title<dl>$out</dl>";

	return $out;
}

function healthy_sugar_slogans() {

	$slogans = array(
		array(
			'title' 	=> esc_html__( 'A 20-ounce soda could have as much sugar as 16 mini chocolate doughnuts.', 'healthy' ),
			'subtitle' 	=> esc_html__( 'You wouldn\'t eat that much sugar, so why drink it?', 'healthy' ),
		),
		array(
			'title' 	=> esc_html__( 'Looking for sugar?  It goes by many names.', 'healthy' ),
			'subtitle' 	=> esc_html__( 'Brown Rice Syrup, honey, maple syrup, corn syrup, fruit nectar, glucose, sucrose, dextrose, fructose, and many more!', 'healthy' ),
		),
		array(
			'title' 	=> esc_html__( 'Some drinks say they\'re loaded with vitamins.  But the truth us, they\'re loaded with SUGAR.', 'healthy' ),
			'subtitle' 	=> esc_html__( 'Want something healthy?  Skip all those added sugars. Drink water or low-fat milk.', 'healthy' ),
		),	
		array(
			'title' 	=> esc_html__( 'A fruit-flavored drink must be healthy, right?  WRONG. A 20-ounce drink can have as much sugar as 2 regular-sized candy bars.', 'healthy' ),
			'subtitle' 	=> esc_html__( 'Want something healthy?  Eat a piece of fruit and drink water.', 'healthy' ),
		),
		array(
			'title' 	=> esc_html__( 'A powdered drink can have as much sugar as 6 puffed rice treats.', 'healthy' ),
			'subtitle' 	=> esc_html__( 'Want something healthy?  Eat a piece of fruit and drink water.', 'healthy' ),
		),
		array(
			'title' 	=> esc_html__( 'A 20-ounce sports drink can have as much sugar as 10 chocolate chip cookies.', 'healthy' ),
			'subtitle' 	=> esc_html__( 'Want to quench your thirst?  Skip all those added sugars and drink water.', 'healthy' ),
		),
		array(
			'title' 	=> esc_html__( 'A 16-ounce energy drink can have as much sugar as 5 ice cream sandwiches.', 'healthy' ),
			'subtitle' 	=> esc_html__( 'You wouldn\'t eat that much sugar, so why drink it?', 'healthy' ),
		),
	);

	return $slogans;
}

function healthy_get_sugar_slogan() {

	$slogans = healthy_sugar_slogans();

	$rand = rand( 0, 6 );

	$out = $slogans[ $rand ];

	return $out;

}