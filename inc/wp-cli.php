<?php
/**
 * wp-cli integration
 */

WP_CLI::add_command( 'safe-redirect-manager', 'Safe_Redirect_Manager_CLI' );

class Safe_Redirect_Manager_CLI extends WP_CLI_Command {


	/**
	 * Import .htaccess file redirects
	 *
	 * @subcommand import-htaccess
	 * @synopsis <file>
	 */
	public function import_htaccess( $args, $assoc_args ) {
		global $safe_redirect_manager;

		list( $file ) = $args;

		$contents = file_get_contents( $file );
		if ( ! $contents )
			WP_CLI::error( "Error retrieving .htaccess file" );

		$pieces = explode( PHP_EOL, $contents );
		$created = 0;
		$skipped = 0;
		foreach( $pieces as $piece ) {
			
			// Ignore if this line isn't a redirect
			if ( ! preg_match( '/^Redirect( permanent)?/i', $piece ) )
				continue;

			// Parse the redirect
			$redirect = preg_replace( '/\s{2,}/', ' ', $piece );
			$redirect = preg_replace( '/^Redirect( permanent)? (.*)$/i', '$2', trim( $redirect ) );
			$redirect = explode( ' ', $redirect );
			
			// if there are three parts to the redirect, we assume the first part is a status code
			if ( 2 == count( $redirect ) ) {
				$from = $redirect[0];
				$to = $redirect[1];
				$http_status = 301;
			} elseif ( 3 == count( $redirect ) ) {
				$http_status = $redirect[0];
				$from = $redirect[1];
				$to = $redirect[2];
			} else {
				continue;
			}

			// Validate
			if ( ! $from || ! $to ) {
				WP_CLI::warning( "Skipping - '{$piece}' is formatted improperly." );
				continue;
			}

			$sanitized_redirect_from = $safe_redirect_manager->sanitize_redirect_from( $from );
			$sanitized_redirect_to = $safe_redirect_manager->sanitize_redirect_to( $to );

			$id = $safe_redirect_manager->create_redirect( $sanitized_redirect_from, $sanitized_redirect_to, $http_status );
			if ( is_wp_error( $id ) ) {
				WP_CLI::warning( "Error - " . $id->get_error_message() );
				$skipped++;
			} else {
				WP_CLI::line( "Success - Created redirect from '{$sanitized_redirect_from}' to '{$sanitized_redirect_to}'" );
				$created++;
			}
		}
		WP_CLI::success( "All done! {$created} redirects were created, {$skipped} were skipped" );
	}


}