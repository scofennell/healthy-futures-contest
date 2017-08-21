<?php

/**
 * Healthy user fields.
 *
 * Register user fields, unregister clutter fields.
 *
 * @package WordPress
 * @subpackage healthy
 * @since  healthy 1.0
 */

function healthy_add_date_to_user_table( $column ) {
    $column['date_registered'] = 'Date Registered';
    return $column;
}
add_filter( 'manage_users_columns', 'healthy_add_date_to_user_table' );

function healthy_add_school_to_user_table( $column ) {
    $column['school'] = 'School';
    return $column;
}
add_filter( 'manage_users_columns', 'healthy_add_school_to_user_table' );

function healthy_modify_user_table_row( $val, $column_name, $user_id ) {
    $udata = get_userdata( $user_id );
    if ( $column_name == 'date_registered' ) {
        $registered = $udata -> user_registered;
        $registered = date( "M j, Y", strtotime( $registered ) );
        return $registered;
    } elseif( $column_name == 'school' ) {
        $school = healthy_get_user_school( $user_id );
        return $school;
    }
    return $val;
}
add_filter( 'manage_users_custom_column', 'healthy_modify_user_table_row', 10, 3 );

/**
 * Unset and set contact methods for users.
 * 
 * @param  array $contactmethods An array of contact methods for users.
 * @return array $contactmethods An array of contact methods for users, customized.
 */
function healthy_contact_methods( $contactmethods ) {
	
    // Get rid of unwanted fields.
    unset($contactmethods['aim']);
	unset($contactmethods['yim']);
	unset($contactmethods['jabber']);

    // Add our custom fields
	$contactmethods[ 'grade' ] = 'Grade';

    // Return the customized array.
	return $contactmethods;
}
add_filter( 'user_contactmethods', 'healthy_contact_methods' );

/**
 * Output jQuery to remove unused fields. 
 */
function healthy_remove_user_fields() {
	?>

	<script>

		jQuery( document ).ready( function( $ ) {
			
            var ids = [
				'#rich_editing',        // Rich editing button
				'#admin_color_classic', // Admin color scheme
				'#comment_shortcuts',   // Keyboard shortcuts for comment moderation
				'#description',         // User description
				'#url',                 // User website
			];
			
            // For each field, remove it
            for ( var i = 0; i < ids.length; i++ ) {
				$( ids[i]).closest( 'tr' ).remove();
			}

            // While we're here, remove some other distracting things entirely
            $( '#wpbody form h3, .show-admin-bar' ).remove();
		
        });

	</script>

<?php
}
add_action( 'admin_footer-user-edit.php', 'healthy_remove_user_fields' );
add_action( 'admin_footer-profile.php', 'healthy_remove_user_fields' );

/**
 * Output jQuery to validate custom user fields. 
 */
/*
function healthy_validate_admin_user_fields() {
    ?>

    <script>

        jQuery( document ).ready( function( $ ) {
            
            

        });

    </script>

<?php
}
add_action( 'admin_footer-user-edit.php', 'healthy_validate_admin_user_fields' );
add_action( 'admin_footer-profile.php', 'healthy_validate_admin_user_fields' );
*/

/**
 * Output our custom user fields.
 * 
 * @param  object $user a WordPress user object.
 */
function healthy_add_custom_user_profile_fields( $user ) {
   
    // Grab the user id.
    $user_id = absint( $user -> ID );

    $school  = healthy_get_user_school( $user_id );
    $teacher = get_user_meta( $user_id, 'teacher', TRUE );
    $team    = get_user_meta( $user_id, 'team', TRUE );
       

    $out = '';

    $fields = healthy_profile_fields();

    foreach( $fields as $f ) {
        
        if( ! isset( $f[ 'add_to_wp_admin' ] ) ) { continue; }
        
        $slug  = $f[ 'slug' ];
        $label = $f[ 'label' ];
        $type  = $f[ 'type' ];

        $out .= "
            <tr>
                <th>
                    <label for='$slug'>$label</label>
                </th>
                <td>
        ";
                
        if( $slug == 'school' ) {                   
            $options = healthy_get_schools_as_options( $user_id, true );
        } elseif( $slug == 'teacher' ) {
            $options = healthy_get_teachers_as_options( $school, $teacher );
        } elseif( $slug == 'team' ) {
            $options = healthy_get_teams_as_options( $school, $team );
        }


        $out .= "
            <select name='$slug' id='$slug' class='regular-text' />
                $options
            <select>
        ";
                
        $out.="
                    <br />
                    <span class='description'>Select the $label for this user.</span>
                </td>
            </tr>
        ";
 
    }

    $out = " <table class='form-table healthy'>$out</table>
    
        <script>
            jQuery( document ).ready( function() {
                jQuery( '.healthy' ).insertAfter( '.form-table:eq(1)' );
            });
        </script>
    ";

    echo $out;
}
add_action( 'show_user_profile', 'healthy_add_custom_user_profile_fields' );
add_action( 'edit_user_profile', 'healthy_add_custom_user_profile_fields' );

/**
 * Save our custom user fields.
 * 
 * @param  int $user_id a WordPress user id.
 */
function healthy_save_custom_user_profile_fields( $user_id ) {
        
    // If the user can't edit users, bail.
    if ( ! current_user_can( 'edit_user', $user_id ) ) { return false; }
    
     $fields = healthy_profile_fields();

    foreach( $fields as $f ) {
        
        if( ! isset( $f[ 'add_to_wp_admin' ] ) ) { continue; }
        
        $slug = $f[ 'slug' ];
        
        if( isset( $_POST[ $slug ] ) ) {
            $meta = sanitize_text_field ( $_POST[ $slug ] );
        }
        update_user_meta( $user_id, $slug, $meta );
    }
}
add_action( 'personal_options_update', 'healthy_save_custom_user_profile_fields' );
add_action( 'edit_user_profile_update', 'healthy_save_custom_user_profile_fields' );