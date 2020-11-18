<?php
/**
 * Setup SRM WP CLI commands.
 *
 * @package safe-redirect-manager
 */

/**
 * WP CLI command class
 */
class SRM_WP_CLI extends WP_CLI_Command {


	/**
	 * List all of the currently configured redirects
	 *
	 * @subcommand list
	 */
	public function cli_list() {
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

		$redirects = srm_get_redirects( array( 'post_status' => 'any' ), true );

		foreach ( $redirects as $redirect ) {
			$line = array();
			foreach ( $fields as $field ) {
				if ( 'enable_regex' === $field ) {
					$line[] = ( $redirect[ $field ] ) ? 'true' : 'false';
				} else {
					$line[] = $redirect[ $field ];
				}
			}

			$table->addRow( $line );
		}

		$table->display();

		WP_CLI::line( 'Total of ' . count( $redirects ) . ' redirects' );
	}

	/**
	 * Create a redirect
	 *
	 * @param array $args Array of arguments
	 * @subcommand create
	 * @synopsis <from> <to> [<status-code>] [<enable-regex>] [<post-status>]
	 */
	public function create( $args ) {
		$defaults = array(
			'',
			'',
			302,
			false,
			'publish',
		);
		// array_merge() doesn't work here because our keys are numeric
		foreach ( $defaults as $key => $value ) {
			if ( ! isset( $args[ $key ] ) ) {
				$args[ $key ] = $defaults[ $key ];
			}
		}
		list( $from, $to, $status_code, $enable_regex, $post_status ) = $args;

		// User might've passed as string.
		if ( empty( $enable_regex ) || 'false' === $enable_regex ) {
			$enable_regex = false;
		}

		if ( empty( $from ) || empty( $to ) ) {
			WP_CLI::error( '<from> and <to> are required arguments.' );
		}

		$ret = srm_create_redirect( $from, $to, $status_code, $enable_regex, $post_status );

		if ( is_wp_error( $ret ) ) {
			WP_CLI::error( $ret->get_error_message() );
		} else {
			WP_CLI::success( "Created redirect as #{$ret}" );
		}
	}

	/**
	 * Delete a redirect
	 *
	 * @param array $args Array of arguments
	 * @subcommand delete
	 * @synopsis <id>
	 */
	public function delete( $args ) {
		$id = ( ! empty( $args[0] ) ) ? (int) $args[0] : 0;

		$redirect = get_post( $id );
		if ( ! $redirect || 'redirect_rule' !== $redirect->post_type ) {
			WP_CLI::error( "{$id} isn't a valid redirect." );
		}

		wp_delete_post( $id );

		srm_flush_cache();

		WP_CLI::success( "Redirect #{$id} has been deleted." );
	}

	/**
	 * Update the redirect cache
	 *
	 * @subcommand update-cache
	 */
	public function update_cache() {
		srm_flush_cache();

		WP_CLI::success( 'Redirect cache has been updated.' );
	}

	/**
	 * Import .htaccess file redirects
	 *
	 * @param array $args Array of arguments
	 * @param array $assoc_args Array of associate arguments
	 * @subcommand import-htaccess
	 * @synopsis <file>
	 */
	public function import_htaccess( $args, $assoc_args ) {
		list( $file ) = $args;

		$contents = file_get_contents( $file );
		if ( ! $contents ) {
			WP_CLI::error( 'Error retrieving .htaccess file' );
		}

		$pieces  = explode( PHP_EOL, $contents );
		$created = 0;
		$skipped = 0;
		foreach ( $pieces as $piece ) {

			// Ignore if this line isn't a redirect
			if ( ! preg_match( '/^Redirect( permanent)?/i', $piece ) ) {
				continue;
			}

			// Parse the redirect
			$redirect = preg_replace( '/\s{2,}/', ' ', $piece );
			$redirect = preg_replace( '/^Redirect( permanent)? (.*)$/i', '$2', trim( $redirect ) );
			$redirect = explode( ' ', $redirect );

			// if there are three parts to the redirect, we assume the first part is a status code
			if ( 2 === count( $redirect ) ) {
				$from        = $redirect[0];
				$to          = $redirect[1];
				$http_status = 301;
			} elseif ( 3 === count( $redirect ) ) {
				$http_status = $redirect[0];
				$from        = $redirect[1];
				$to          = $redirect[2];
			} else {
				continue;
			}

			// Validate
			if ( ! $from || ! $to ) {
				WP_CLI::warning( "Skipping - '{$piece}' is formatted improperly." );
				continue;
			}

			$sanitized_redirect_from = srm_sanitize_redirect_from( $from );
			$sanitized_redirect_to   = srm_sanitize_redirect_to( $to );

			$id = srm_create_redirect( $sanitized_redirect_from, $sanitized_redirect_to, $http_status );
			if ( is_wp_error( $id ) ) {
				WP_CLI::warning( 'Error - ' . $id->get_error_message() );
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
	 * | source                     | target             | regex | code | order |
	 * |----------------------------|--------------------|-------|------|-------|
	 * | /legacy-url                | /new-url           | 0     | 301  | 0     |
	 * | /category-1                | /new-category-slug | 0     | 302  | 1     |
	 * | /tes?t/[0-9]+/path/[^/]+/? | /go/here           | 1     | 302  | 3     |
	 * | ...                        | ...                | ...   | ...  | ...   |
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
	 * <order-column>
	 * : Header title for order column mapping.
	 *
	 *
	 * ## EXAMPLE
	 *
	 *     wp safe-redirect-manager import redirections.csv
	 *
	 * @synopsis <file> [--source=<source-column>] [--target=<target-column>] [--regex=<regex-column>] [--code=<code-column>]  [--order=<order-column>]
	 *
	 * @since 1.7.6
	 *
	 * @access public
	 * @param array $args The array of input files.
	 * @param array $assoc_args The array of column mappings.
	 */
	public function import( $args, $assoc_args ) {
		$mapping = wp_parse_args(
			$assoc_args,
			array(
				'source' => 'source',
				'target' => 'target',
				'regex'  => 'regex',
				'code'   => 'code',
				'order'  => 'order',
			)
		);

		$created = 0;
		$skipped = 0;

		foreach ( $args as $file ) {
			$processed = srm_import_file( $file, $mapping );
			if ( ! empty( $processed ) ) {
				$created += $processed['created'];
				$skipped += $processed['skipped'];

				WP_CLI::success( basename( $file ) . ' file processed successfully.' );
			}
		}

		WP_CLI::success( "All done! {$created} redirects were imported, {$skipped} were skipped" );
	}
}
