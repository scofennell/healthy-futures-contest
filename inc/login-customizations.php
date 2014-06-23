<?php

/**
 * Functions for altering the appearance of the defualt wordpress login screens.
 * Contains hoarded code for further altering login screens.
 *
 * @package WordPress
 * @subpackage healthy
 * @since healthy 1.0
 */

/**
 * Replaces the WordPress logo with a branded logo by outputting CSS.
 */
function healthy_login_logo() { ?>
    <style>
        body.login div#login h1 a {
            background-image: url( <?php echo get_stylesheet_directory_uri(); ?>/images/logo.png );
            padding-bottom: 30px;
            height: 200px;
            width: 200px;
            -webkit-background-size: contain;
            background-size: contain;
        }
    </style>
<?php }
add_action( 'login_enqueue_scripts', 'healthy_login_logo' );

/**
 * Changes the wordpress login screen link.
 */
function healthy_login_logo_url() {
    return home_url();
}
add_filter( 'login_headerurl', 'healthy_login_logo_url' );

/**
 * Changes the wordpress login screen title.
 */
function healthy_login_logo_url_title() {
    
    // Translate.
    $return_to = __( 'Return to', 'healthy' );
    
    // Sanitize.
    $out = esc_attr( $return_to.' '.get_bloginfo('name') );

    return $out;
}
add_filter( 'login_headertitle', 'healthy_login_logo_url_title' );

/*
body.login {}
body.login div#login {}
body.login div#login h1 {}
body.login div#login h1 a {}
body.login div#login form#loginform {}
body.login div#login form#loginform p {}
body.login div#login form#loginform p label {}
body.login div#login form#loginform input {}
body.login div#login form#loginform input#user_login {}
body.login div#login form#loginform input#user_pass {}
body.login div#login form#loginform p.forgetmenot {}
body.login div#login form#loginform p.forgetmenot input#rememberme {}
body.login div#login form#loginform p.submit {}
body.login div#login form#loginform p.submit input#wp-submit {}
body.login div#login p#nav {}
body.login div#login p#nav a {}
body.login div#login p#backtoblog {}
body.login div#login p#backtoblog a {}
*/