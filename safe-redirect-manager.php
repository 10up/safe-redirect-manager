<?php
/**
 * Plugin Name: Safe Redirect Manager
 * Plugin URI: https://10up.com
 * Description: Easily and safely manage HTTP redirects.
 * Author: 10up
 * Version: 1.9
 * Text Domain: safe-redirect-manager
 * Domain Path: /lang/
 * Author URI: https://10up.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * Localize plugin
 *
 * @since 1.8
 */
function srm_load_textdomain() {
	load_plugin_textdomain( 'safe-redirect-manager', false, dirname( __FILE__ ) . '/lang' );
}
add_action( 'plugins_loaded', 'srm_load_textdomain' );

require_once( dirname( __FILE__ ) . '/inc/functions.php' );
require_once( dirname( __FILE__ ) . '/inc/classes/class-srm-post-type.php' );
require_once( dirname( __FILE__ ) . '/inc/classes/class-srm-redirect.php' );


if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once( dirname( __FILE__ ) . '/inc/classes/class-srm-wp-cli.php' );
	WP_CLI::add_command( 'safe-redirect-manager', 'SRM_WP_CLI' );
}

SRM_Post_Type::factory();
SRM_Redirect::factory();
