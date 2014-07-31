<?php

/**
 * healthy footer scripts.
 *
 * Scripts for minor UX improvements.
 *
 * @package WordPress
 * @subpackage healthy
 * @since healthy 1.0
 */

/**
 * Outputs JS to append a current-menu-item class to active nav links.
 */
function healthy_current_menu_class() {		
	?>

		<!-- Added by healthy to power form field autoempty -->
		<script>		
	
			jQuery( document ).ready( function( $ ) {

				current = location.href;

				$( "nav a" ).filter( function() {
    				return this.href == current;
				}).addClass( "current-menu-item" );

			});

		</script>

	<?php
}
add_action( 'wp_footer', 'healthy_current_menu_class' );

/**
 * Output some jQuery to auto_empty form fields
 *
 * @since healthy 1.0
 */
/*
function healthy_auto_empty_forms() {		
	?>

		<!-- Added by healthy to power form field autoempty -->
		<script>		
	
			jQuery( document ).ready( function( $ ) {
			
				$('input[type="text"], input[type="email"], input[type="search"], textarea').focus(function() {
					if (this.value == this.defaultValue){
						this.value = '';
					}
					if(this.value != this.defaultValue){
						this.select();
					}
				});

				$('input[type="text"], input[type="email"], input[type="search"], textarea').blur(function() {
					if (this.value == ''){
						this.value = this.defaultValue;
					}
				});

			});

		</script>

	<?php
	}
*/
//add_action( 'wp_footer', 'healthy_auto_empty_forms' );