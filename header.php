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

		<header id="blog-header" class="marquee inverse-color">

			<div class='outer-wrapper'>

				<div class='inner-wrapper content-holder'>

					<a class="screen-reader-text skip-link" href="#loop"><?php _e( 'Skip to content', 'healthy' ); ?></a>

					<h1 class="blog-title blog-title-blog-header">
						<a class="home-link" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" title="<?php echo esc_attr(get_bloginfo('name')); ?>"><img class='logo header-logo' width=150 height=150 src='<?php echo healthy_logo_src( 'logo.png' ); ?>' alt='<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>'><?php echo healthy_title_as_spans(); ?></a>
					</h1>
			
				</div>

			</div>

			<?php if( is_user_logged_in() ) { ?>

				<div class='header-nav-outer-wrapper outer-wrapper accent-color'>	
					<div class='inner-wrapper'>
						<?php
							echo healthy_nav_menu();
						?>
					</div>
				</div>
			<?php } ?>

		</header>

		<img class='logo body-logo' width=600 height=600 src='<?php echo healthy_logo_src( 'logo_full.png' ); ?>' alt='<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>'>