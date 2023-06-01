<?php
/**
 * Plugin Name:       Safe Redirect Manager
 * Plugin URI:        https://wordpress.org/plugins/safe-redirect-manager
 * Description:       Easily and safely manage HTTP redirects.
 * Version:           2.0.1
 * Requires at least: 5.7
 * Requires PHP:      7.4
 * Author:            10up
 * Author URI:        https://10up.com
 * License:           GPLv2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       safe-redirect-manager
 *
 * @package safe-redirect-manager
 */

// Load helper functions and classes
require_once dirname( __FILE__ ) . '/inc/functions.php';
require_once dirname( __FILE__ ) . '/inc/classes/class-srm-post-type.php';
require_once dirname( __FILE__ ) . '/inc/classes/class-srm-redirect.php';

define( 'SRM_VERSION', '2.0.1' );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once dirname( __FILE__ ) . '/inc/classes/class-srm-wp-cli.php';
	WP_CLI::add_command( 'safe-redirect-manager', 'SRM_WP_CLI' );
}

SRM_Post_Type::factory();
SRM_Redirect::factory();
