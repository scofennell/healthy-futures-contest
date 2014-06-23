<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme and one of the
 * two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * For example, it puts together the home page when no home.php file exists.
 *
 * @link http://codex.wordpress.org/Template_Hierarchy
 *
 * @package WordPress
 * @subpackage healthy
 * @since healthy 1.0
 */

get_header(); ?>

<?php if( is_archive() || is_search() || is_404() ) { ?>
	
	<section class='outer-wrapper'>
	
		<div class='inner-wrapper'>
	
			<?php echo healthy_archive_header(); ?>
	
		</div>
	
	</section>

<?php } ?>

<main id="loop" class="outer-wrapper" role="main">

	<?php if( is_404() || ! have_posts() ) { ?>
		
		<article class='hentry no-posts inner-wrapper entry-content'>
			
			<?php echo healthy_no_posts(); ?>

		</article>

	<?php } elseif ( have_posts() ) { ?>

		<?php /* The loop */ ?>
		
		<?php while ( have_posts() ) : the_post(); ?>

			<article <?php post_class(); ?> itemscope itemtype="http://schema.org/Article">
				
				<?php echo healthy_controller(); ?>

			</article><!-- #post -->

		<?php endwhile; ?>

	<?php } ?>

</main> <!-- end #loop-wrapper -->

<?php get_footer(); ?>