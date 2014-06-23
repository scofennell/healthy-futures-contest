<?php
/**
 * healthy post content.  Calls the controller function to direct the user to the proper template.
 *
 * @package WordPress
 * @subpackage healthy
 * @since healthy 1.0
 */
?>

<section itemprop="articleBody" class="entry-content editable-content content-holder">
	
	<?php echo healthy_controller(); ?>
	
	<?php the_content( esc_html__( 'Read More&hellip;', 'healthy' ) ); ?>				

</section>