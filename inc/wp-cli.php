<?php
/**
 * WP-CLI integration.
 */

WP_CLI::add_command( 'safe-redirect-manager', 'Safe_Redirect_Manager_CLI' );

/**
 * Manage redirects via Safe Redirect Manager.
 */
class Safe_Redirect_Manager_CLI extends WP_CLI_Command {

	/**
	 * List the current redirect rules.
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
	 * Create a new redirect rule.
	 *
	 * ## OPTIONS
	 *
	 * <from>
	 * : The path, relative to the WordPress root (or the sub-site, if you are
	 * using WordPress Multisite).
	 *
	 * <to>
	 * : The absolute URL or relative path that requests should be safely
	 * redirected to.
	 *
	 * [--status-code=<code>]
	 * : The HTTP status code that should be returned when a visitor arrives
	 * at the <from> URL. Default is 302 (Found).
	 *
	 * [--post-status=<status>]
	 * : The WordPress post status for the new redirect. Default is "publish".
	 *
	 * [--regex]
	 * : Enable regular expressions for this redirect rule?
	 *
	 * ## EXAMPLES
	 *
	 *   wp safe-redirect-manager create old-url new-url
	 *   wp safe-redirect-manager create old-url http://example.com
	 *   wp safe-redirect-manager create old-section/* new-section/* --status-code=301
	 *   wp safe-redirect-manager create "about-(me|us)" about --regex
	 *   wp safe-redirect-manager create old-url a-draft --post-status=draft
	 *
	 * @subcommand create
	 * @synopsis <from> <to> [--status-code=<code>] [--post-status=<status>] [--regex]
	 */
	public function create( $args, $assoc_args ) {
		global $safe_redirect_manager;

		$assoc_args = wp_parse_args( $assoc_args, array(
			'status-code' => 302,
			'post-status' => 'publish',
			'regex'       => false,
		) );

		$redirect   = $safe_redirect_manager->create_redirect(
			$args['0'],
			$args['1'],
			$assoc_args['status-code'],
			(bool) $assoc_args['regex'],
			$assoc_args['post-status']
		);

		if ( is_wp_error( $redirect ) ) {
			return WP_CLI::error( $redirect->get_error_message() );
		} else {
			return WP_CLI::success( sprintf(
				/** translators: %$1d is the post ID, %$2s is the "from" path, %$3s is the destination. */
				__( 'Created redirect #%1$d (%2$s => %3$s)', 'safe-redirect-manager' ),
				$redirect,
				$args['0'],
				$args['1']
			) );
		}
	}

	/**
	 * Delete a redirect rule by its ID.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The post ID for the redirect you wish to remove.
	 *
	 * ## EXAMPLES
	 *
	 *   wp safe-redirect-manager delete 1
	 *
	 * @subcommand delete
	 * @synopsis <id>
	 *
	 * @global $safe_redirect_manager
	 */
	public function delete( $args ) {
		global $safe_redirect_manager;

		$id = isset( $args['0'] ) ? (int) $args['0'] : 0;

		// Verify that the post exists and is a redirect.
		if ( $safe_redirect_manager->redirect_post_type !== get_post_type( $id ) ) {
			return WP_CLI::error( sprintf(
				__( 'Post ID #%d is not a valid redirect.', 'safe-redirect-manager' ),
				$id
			) );
		}

		// Remove the redirect and update the cache.
		wp_delete_post( $id );
		$safe_redirect_manager->update_redirect_cache();

		return WP_CLI::success( sprintf(
			__( 'Redirect #%d has been deleted.', 'safe-redirect-manager' ),
			$id
		) );
	}

	/**
	 * Update the redirect cache.
	 *
	 * @subcommand update-cache
	 *
	 * @global $safe_redirect_manager
	 */
	public function update_cache() {
		global $safe_redirect_manager;

		$safe_redirect_manager->update_redirect_cache();
		WP_CLI::success( __( 'Redirect cache has been updated.', 'safe-redirect-manager' ) );
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

	/**
	 * Imports redirects from CSV file.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : Path to one or more valid CSV file for import. This file should contain 
	 * redirection from and to URLs, regex flag and HTTP redirection code. Here
	 * is example table:
	 *   
	 * | source                     | target             | regex | code |
	 * |----------------------------|--------------------|-------|------|
	 * | /legacy-url                | /new-url           | 0     | 301  |
	 * | /category-1                | /new-category-slug | 0     | 302  |
	 * | /tes?t/[0-9]+/path/[^/]+/? | /go/here           | 1     | 302  |
	 * | ...                        | ...                | ...   | ...  |
	 *
	 * You can also use exported redirects from "Redirection" plugin, which you
	 * can download here: /wp-admin/tools.php?page=redirection.php&sub=modules
	 *
	 * <source-column>
	 * : Header title for sources column mapping.
	 *
	 * <target-column>
	 * : Header title for target column mapping.
	 *
	 * <regex-column>
	 * : Header title for regex column mapping.
	 *
	 * <code-column>
	 * : Header title for code column mapping.
	 *
	 * ## EXAMPLE
	 *
	 *     wp safe-redirect-manager import redirections.csv
	 *
	 * @synopsis <file> [--source=<source-column>] [--target=<target-column>] [--regex=<regex-column>] [--code=<code-column>]
	 *
	 * @since 1.7.6
	 * 
	 * @access public
	 * @global SRM_Safe_Redirect_Manager $safe_redirect_manager The plugin instance.
	 * @param array $args The array of input files.
	 * @param array $assoc_args The array of column mappings.
	 */
	public function import( $args, $assoc_args ) {
		global $safe_redirect_manager;

		$mapping = wp_parse_args( $assoc_args, array(
			'source' => 'source',
			'target' => 'target',
			'regex'  => 'regex',
			'code'   => 'code',
		) );

		$created = $skipped = 0;
		foreach ( $args as $file ) {
			$processed = $safe_redirect_manager->import_file( $file, $mapping );
			if ( ! empty( $processed ) ) {
				$created += $processed['created'];
				$skipped += $processed['skipped'];
				
				WP_CLI::success( basename( $file ) . ' file processed successfully.' );
			}
		}

		WP_CLI::success( "All done! {$created} redirects were imported, {$skipped} were skipped" );
	}

}