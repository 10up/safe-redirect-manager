<?php
/**
 * Plugin Name: Safe Redirect Manager
 * Plugin URI: https://wordpress.org/plugins/safe-redirect-manager
 * Description: Easily and safely manage HTTP redirects.
 * Author: 10up
 * Version: 1.10.0
 * Text Domain: safe-redirect-manager
 * Author URI: https://10up.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package safe-redirect-manager
 */

// Load helper functions and classes
require_once dirname( __FILE__ ) . '/inc/functions.php';
require_once dirname( __FILE__ ) . '/inc/classes/class-srm-post-type.php';
require_once dirname( __FILE__ ) . '/inc/classes/class-srm-redirect.php';


if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once dirname( __FILE__ ) . '/inc/classes/class-srm-wp-cli.php';
	WP_CLI::add_command( 'safe-redirect-manager', 'SRM_WP_CLI' );
}

SRM_Post_Type::factory();
SRM_Redirect::factory();
