<?php
/**
 * The Header template for our theme
 *
 * Displays all of the <head> section and everything up till <div id="main">
 *
 * @package WordPress
 * @subpackage healthy
 * @since healthy 1.0
 */
?><!DOCTYPE html>

<?php
	//echoes the opening html tag along with classes for different versions of IE
	echo healthy_the_html_classes();
?>

<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width">
	<title><?php wp_title( '|', true, 'right' ); ?></title>
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
	
	<?php
		//if it's less than html9, get the html5 shiv & print shiv
		//https://github.com/aFarkas/html5shiv
	?>
	<!--[if lt IE 9]>
	<script src="<?php echo get_template_directory_uri(); ?>/js/html5shiv-printshiv.js"></script>
	<![endif]-->
	
	<?php wp_head(); ?>

</head>

<body <?php body_class(); ?>>
	
	<div id="body-wrapper">

		<header id="blog-header" class="outer-wrapper marquee inverse-color clear">

			<a class="screen-reader-text skip-link" href="#loop"><?php _e( 'Skip to content', 'healthy' ); ?></a>
			
			<div id="blog-header-inner-wrapper" class="inner-wrapper has-halfs">

				<h1 class="blog-title blog-title-blog-header">
					<a class="home-link" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" title="<?php echo esc_attr(get_bloginfo('name')); ?>"><?php bloginfo('name'); ?></a>
				</h1>

				<?php
					echo healthy_nav_menu( $healthy_post_author_id );
				?>

			</div>

		</header>