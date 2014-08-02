<?php
/**
 * The template for displaying the footer
 *
 * Contains footer content and the closing of the #main and #page div elements.
 *
 * @package WordPress
 * @subpackage healthy
 * @since healthy 1.0
 */
?>

		<footer id="blog-footer" class="inverse-color outer-wrapper" role="contentinfo">
			<div id="blog-footer-inner-wrapper" class="inner-wrapper">		

			<?php
				echo healthy_get_sponsors();
			?>

				<?php if ( is_active_sidebar( 'footer-widgets' ) ) { ?>
			
					<aside id="footer-widgets" class="clear widgets footer-widgets" role="complementary">
						<?php dynamic_sidebar( 'footer-widgets' ); ?>
					</aside>

				<?php } ?>

			</div>
		</footer><!-- #colophon -->

	</div><!-- #body_wrapper -->

	<?php wp_footer(); ?>
	
</body>
</html>