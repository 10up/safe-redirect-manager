<?php
/**
 * wp-cli integration
 */

WP_CLI::add_command( 'safe-redirect-manager', 'Safe_Redirect_Manager_CLI' );

class Safe_Redirect_Manager_CLI extends WP_CLI_Command {


	/**
	 * List all of the currently configured redirects
	 *
	 * @subcommand list
	 */
	public function _list() {
		global $safe_redirect_manager;

		$fields = array(
				'ID',
				'redirect_from',
				'redirect_to',
				'status_code',
				'enable_regex',
				'post_status',
			);

		$table = new \cli\Table();
		$table->setHeaders( $fields );

		$redirects = $safe_redirect_manager->get_redirects( array( 'post_status' => 'any' ) );
		foreach( $redirects as $redirect ) {
			$line = array();
			foreach( $fields as $field ) {
				if ( 'enable_regex' == $field )
					$line[] = ( $redirect[$field] ) ? 'true' : 'false';
				else
					$line[] = $redirect[$field];
			}
			$table->addRow( $line );
		}
		$table->display();

		WP_CLI::line( "Total of " . count( $redirects ) . " redirects" );
	}

	/**
	 * Create a redirect
	 *
	 * @subcommand create
	 * @synopsis <from> <to> [<status-code>] [<enable-regex>] [<post-status>]
	 */
	public function create( $args ) {
		global $safe_redirect_manager;

		$defaults = array(
				'',
				'',
				302,
				false,
				'publish',
			);
		// array_merge() doesn't work here because our keys are numeric
		foreach( $defaults as $key => $value ) {
			if ( ! isset( $args[$key] ) )
				$args[$key] = $defaults[$key];
		}
		list( $from, $to, $status_code, $enable_regex, $post_status ) = $args;

		// User might've passed as string.
		if ( 'false' == $enable_regex )
			$enable_regex = false;

		if ( empty( $from ) || empty( $to ) )
			WP_CLI::error( "<from> and <to> are required arguments." );

		$ret = $safe_redirect_manager->create_redirect( $from, $to, $status_code, $enable_regex, $post_status );
		if ( is_wp_error( $ret ) )
			WP_CLI::error( $ret->get_error_message() );
		else
			WP_CLI::success( "Created redirect as #{$ret}" );
	}

	/**
	 * Delete a redirect
	 *
	 * @subcommand delete
	 * @synopsis <id>
	 */
	public function delete( $args ) {
		global $safe_redirect_manager;

		$id = ( ! empty( $args[0] ) ) ? (int)$args[0] : 0;

		$redirect = get_post( $id );
		if ( ! $redirect || $safe_redirect_manager->redirect_post_type != $redirect->post_type )
			WP_CLI::error( "{$id} isn't a valid redirect." );

		wp_delete_post( $id );
		$safe_redirect_manager->update_redirect_cache();
		WP_CLI::success( "Redirect #{$id} has been deleted." );
	}

	/**
	 * Update the redirect cache
	 *
	 * @subcommand update-cache
	 */
	public function update_cache() {
		global $safe_redirect_manager;

		$safe_redirect_manager->update_redirect_cache();
		WP_CLI::success( "Redirect cache has been updated." );
	}

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