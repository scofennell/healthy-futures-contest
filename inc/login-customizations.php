<?php

/**
 * Functions for altering the default wordpress login experience.
 *
 * @package WordPress
 * @subpackage healthy
 * @since healthy 1.0
 */

function healthy_login_enqueue_scripts() {
    wp_enqueue_script('jquery');
}
add_action( 'login_enqueue_scripts', 'healthy_login_enqueue_scripts' );

/**
 * Prevent users from accessing wp_admin().
 */
function healthy_prevent_admin_access() {

    // Ajax actions always need to be able to use wp-admin.
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) { return false; }

    // If the user cannot manage options, he cannot access wp-admin.
    if( ! current_user_can( 'manage_options' ) ) {
        
        // If the user tries to access wp-admin:
        if ( is_admin() ) {

            // Grab the home url.
            $home = esc_url( get_bloginfo( 'url' ) );

            // Redir the user to it.
            wp_safe_redirect( $home );
            
            // Exit to prevent any chance of a redir loop.
            exit;
        }
    }
}
add_action( 'init', 'healthy_prevent_admin_access' );

/**
 * Replaces the WordPress login styles by outputting CSS.
 */
function healthy_login_styles() { ?>
    <style>

        body.login {
            padding-bottom: 60px;
            border-top: 10px solid #7CC24D;
        }

        html,
        body.login,
        input[type='text']  {
            font-family: georgia, times, serif;
            background-color: #fff;
        }

        input,
        a {
            transition: all 0.4s ease-in-out;
            transition-property: color, background-color;
        }

        input {
            float: none !important;
        }

        input[type='text'],
        input[type='password'] {
            border: 1px solid #000;
            font-weight: 400 !important;
        }

        input[type='submit'],
        input[type='text'],
        input[type='password'] {
            padding: 3px;
            border-radius: 3px;
            width: 100% !important;
        }

        input[type='submit'] {
            padding: 10px 14px 8px !important;
            height: auto !important;
            color: #fff;
            border: none !important;
            background-color: #0F2C52 !important;
        }

        a:hover { color: #7CC24D !important; }
        input[type='submit']:hover {
            background-color: #7CC24D !important;
        }

        .login form label,
        .login input[type=submit] {
            font-family: helvetica, arial, sans-serif;
            font-weight: 600;
        }

        .login #backtoblog, .login #nav {
            font-size: 15px;
        }

        body.login a {
            #0F2C52 !important;
        }

        body.login * {
            box-shadow: none;
        }

        #login {
            width: auto;
            max-width: 680px;
        }

        #login form, 
        .message {
             padding: 20px 20px 24px;           
        }

        #login form, 
        .message {
            border-radius: 5px;
            background-color: rgba(15, 44, 82, 0.1)  !important;
        }

        #login form, 
        .message,
        form,
        input[type='submit'] {
            box-shadow: 0 2px 4px -2px rgba(0, 0, 0, 0.75) !important;
        }

        .login form label {
            font-size: 15px !important;
            color: #000 !important;
        }

        .login form label,
        .login form .input,
        .login input[type=text],
        .login input[type=submit] {
            margin: 0;
        }

        .login input[type=submit],
        .login input[type=password],
        .login input[type=text] {
            font-size: 30px !important;
        }

        #login form > p {
            margin-bottom: 20px; 
        }

        #login form > p:last-child {
            margin-bottom: 0;
        }

        body.login div#login h1 a {
            background: url( <?php echo get_stylesheet_directory_uri(); ?>/images/logo.png ) no-repeat center top;
            padding-bottom: 20px;
            height: 200px;
            width: 200px;
            background-size: contain;
            -webkit-background-size: contain;
        }


        .login #backtoblog,
        .login #nav {
            text-align: center;
        }
        
        .login #backtoblog a,
        .login #nav a {
            text-decoration: underline;
            color: #000;
            text-align: center;
        }

        .login #backtoblog a:hover,
        .login #nav a:hover {
            text-decoration: none;
            color: #000;
        }

    </style>
<?php }
add_action( 'login_enqueue_scripts', 'healthy_login_styles' );

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
    $return_to = esc_attr__( 'Return to', 'healthy' );
    
    // Sanitize.
    $out = esc_attr( $return_to.' '.get_bloginfo('name') );

    return $out;
}
add_filter( 'login_headertitle', 'healthy_login_logo_url_title' );

/**
 * Deletes the switch-user cookie on logout.
 *
 * @return  boolean Returns true if the cookie was deleted, else false.
 */
function healthy_delete_switch_users_cookie_on_logout() {
    
    // Our app-wide cookie key.
    $cookie_key = healthy_switched_user_cookie_key();

    // Delete the cookie if it exists.
    if ( isset( $_COOKIE[ $cookie_key ] ) ) {
        setcookie( $cookie_key, get_current_user_id(), time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
        return true;
    }

    return false;
}
add_action( 'wp_logout', 'healthy_delete_switch_users_cookie_on_logout' );


add_filter( 'show_admin_bar', '__return_false' );

/**
 * [healthy_filter_wp_mail_from description]
 * @param  [type] $email [description]
 * @return [type]        [description]
 */
function healthy_filter_wp_mail_from($email){
    return get_bloginfo( 'admin_email' );
}
add_filter( 'wp_mail_from', 'healthy_filter_wp_mail_from', 999 );

/**
 * [xyz_filter_wp_mail_from_name description]
 * @param  [type] $from_name [description]
 * @return [type]            [description]
 */
function healthy_filter_wp_mail_from_name( $from_name ){
    return get_bloginfo( 'name' );
}
add_filter( 'wp_mail_from_name', 'healthy_filter_wp_mail_from_name', 999 );