<?php
/**
 * Plugin Name:       Safe Redirect Manager
 * Plugin URI:        https://wordpress.org/plugins/safe-redirect-manager
 * Description:       Easily and safely manage HTTP redirects.
 * Version:           2.1.1
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

namespace SafeRedirectManager;

/**
 * Get the minimum version of PHP required by this plugin.
 *
 * @since 2.0.2
 *
 * @return string Minimum version required.
 */
function minimum_php_requirement(): string {
	return '7.4';
}

/**
 * Whether PHP installation meets the minimum requirements
 *
 * @since 2.0.2
 *
 * @return bool True if meets minimum requirements, false otherwise.
 */
function site_meets_php_requirements(): bool {
	return version_compare( phpversion(), minimum_php_requirement(), '>=' );
}

// Try to load the plugin files, ensuring our PHP version is met first.
if ( ! site_meets_php_requirements() ) {
	add_action(
		'admin_notices',
		function() {
			?>
			<div class="notice notice-error">
				<p>
					<?php
					echo wp_kses_post(
						sprintf(
						/* translators: %s: Minimum required PHP version */
							__( 'Safe Redirect Manager requires PHP version %s or later. Please upgrade PHP or disable the plugin.', 'safe-redirect-manager' ),
							esc_html( minimum_php_requirement() )
						)
					);
					?>
				</p>
			</div>
			<?php
		}
	);
	return;
}

// Load helper functions and classes
require_once dirname( __FILE__ ) . '/inc/functions.php';
require_once dirname( __FILE__ ) . '/inc/classes/class-srm-post-type.php';
require_once dirname( __FILE__ ) . '/inc/classes/class-srm-redirect.php';
require_once dirname( __FILE__ ) . '/inc/classes/class-srm-loop-detection.php';

define( 'SRM_VERSION', '2.1.1' );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once dirname( __FILE__ ) . '/inc/classes/class-srm-wp-cli.php';
	\WP_CLI::add_command( 'safe-redirect-manager', 'SRM_WP_CLI' );
}

\SRM_Post_Type::factory();
\SRM_Redirect::factory();
