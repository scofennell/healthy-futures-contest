<?php

/**
 * healthy meta boxes.
 *
 * Register meta boxes.  Contains a lot of hoarded code left over from using a jquery 
 * datepicker.
 *
 * @package WordPress
 * @subpackage healthy
 */

//enqueue the scripts we'll need for date picker
/*function healthy_day_admin_enqueue() {

	//only call datepicker assets on a single events page
	global $pagenow, $typenow;
	if ( $pagenow == 'post.php' || $pagenow == 'post-new.php' ) {
		if( $typenow == 'healthy_day' ) {

			wp_enqueue_script( 'jquery-ui-datepicker' );
		}
	}
}
add_action('admin_enqueue_scripts', 'healthy_day_admin_enqueue');
*/
		
		
/*
function healthy_date_admin_styles() {
	echo "
    <style>
		.ui-datepicker {
		    border: 1px solid #b2b2b2;
		    padding: 10px;
		    background: #fff;
		}

		.ui-datepicker a {
    		cursor: pointer;
		}

		.ui-state-active {
	 	   font-weight: bold;
		}

		.ui-datepicker-prev {
		    float: left;
		}

		.ui-datepicker-next {
		    float: right;
		}

		.ui-datepicker-title {
    		clear: both;
    		font-style: italic;
    		text-align: center;
		}

    </style>
    ";
}
*/

/**
 * Add the Meta Box to posts of the 'healthy_day' post type.
 */
function healthy_add_custom_meta_box() {
	add_meta_box(

		// A unique ID for this meta box.
    	'healthy_day_custom_meta_box',

    	// The label for this box.
		'About this day',

		// The cb to draw the box.   
		'healthy_show_custom_meta_box',
		
		// On which type of post to show the box.
		'healthy_day',

		// Where horizontally on the page to show the box. 
		'normal', // $context  
		
		// Where vertically to show the box.
		'high'
	);
}
add_action('add_meta_boxes', 'healthy_add_custom_meta_box');

/**
 * Draw a custom meta box for posts of the 'healthy_day' post type.
 */
function healthy_show_custom_meta_box() {  
    
	// Affect the current post.
    global $post;
    $post_id = absint( $post->ID );  
   
    // Our theme-wide definition of a 'day'.
    $day = healthy_day();

    // An array of fields for a day.
    $healthy_meta_fields = $day[ 'components' ];
   
    // Use nonce for verification  
    echo '<input type="hidden" name="healthy_custom_meta_box_nonce" value="'.wp_create_nonce( basename( __FILE__ ) ).'" />';  
          
    // Begin the field table and loop  
    echo '<table class="form-table">';  
    
    // for each meta field, output a table row containing an input
    foreach( $healthy_meta_fields as $field ) {  
        
        // Slug will be used for meta key.
        $slug = esc_attr( $field['slug'] );
        
        // Date will be saved as the post date -- it's not meta.
        if( $slug == 'date' ) { continue; }
        
		// Controls what sort of form input we get.
  		$type = esc_attr( $field[ 'type' ] );         
        

        // Get the value for this field.
        $meta = get_post_meta( $post_id, $slug, true );  

		// If it's a range, we need to make sure the default is 0, not empty string.
		if( $type == 'range' ) {
			if( empty ( $meta ) ) { $meta = absint( $meta ); }
		}

        // Human-friendly label for this field.
  		$label = esc_html( $field['label'] );          
  		
        // begin a table row with the label for this field
        echo "
        	<tr> 
                	<th><label for='$slug'>$label</label></th> 
                	<td>
        ";  
		
		//if ( $type == 'date' ) {
		//	$input = "<input type='$type' name='$slug' id='$slug' value='$meta' >";
	
		// We can just use a simple text input for all of our fields.  This whole interface is just for debugging.
		$input = "<input type='text' name='$slug' id='$slug' value='$meta' >";
            
        echo "
        			$input
        		</td>
        	</tr>
        ";

    } // End foreach field.

    /*$date_script = healthy_date_script();
    echo  $date_script;

    $date_styles = healthy_date_admin_styles();
    echo $date_styles;
	*/

    echo '</table>'; // end table  
}

/** 
 * Save the Data
 *
 * @param int $post_id The ID of the current post as it's being saved.
 */  
function healthy_save_custom_meta( $post_id ) {  
 
	// Check if the nonce exists.
	if( ! isset( $_POST[ 'healthy_custom_meta_box_nonce' ] ) ) { return $post_id; }
	
	// Check if the nonce is valid.
	if( ! wp_verify_nonce( $_POST[ 'healthy_custom_meta_box_nonce' ], basename( __FILE__ ) ) ) { return $post_id; }    
 
	// Dont' bother trying to do this if it's an autosave
	if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return $post_id; }
 
	// Check user permissions  
	if ( ! current_user_can( 'edit_others_posts', $post_id ) ) { return $post_id; }  
 
	// Get the meta fields.
	$day = healthy_day();
    $healthy_meta_fields = $day[ 'components' ];

	// Loop through fields and save the data.
	foreach ( $healthy_meta_fields as $field ) { 
	
		// The meta key.
		$slug = $field[ 'slug' ];

		// Get the old value for this meta key.
		$old = get_post_meta( $post_id, $slug, true );  
	
		// Get and sanitize the posted value for this meta key
		$new = sanitize_text_field( $_POST[ $slug ] );  
	
		// If the new value is different, update the old value.
		if ( $new && $new != $old ) {
			update_post_meta( $post_id, $slug, $new );  
	
			// If the new value is present and empty, delete the old value.
		} elseif ( '' == $new && $old ) {  
			delete_post_meta( $post_id, $slug, $old );  
		}
		
    } // end foreach meta field  
}
add_action( 'save_post', 'healthy_save_custom_meta' );