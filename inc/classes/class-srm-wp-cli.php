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
	 * List all of the currently configured redirects.
	 * Available fields: 'ID', 'redirect_from', 'redirect_to', 'status_code', 'enable_regex', 'post_status'.
	 *
	 * [--field=<field>]
	 * : Single field to dipslay, should be one of available fields.
	 *
	 * [--fields=<field1,field2>]
	 * : Multiple fields to dipslay, should be a list of available fields.
	 *
	 * [--format=<format>]
	 * : The command output format. Can be table, json, csv, yaml. Default to table.
	 *
	 * [--show_total=<bool>]
	 * : Show total redirects. Default to true.
	 *
	 * @subcommand list
	 *
	 * @param array $args       Array of arguments
	 * @param array $assoc_args Array of options.
	 */
	public function cli_list( $args, $assoc_args ) {
		$assoc_args = wp_parse_args(
			$assoc_args,
			[
				'show_total' => true,
				'format'     => 'table',
			]
		);

		if ( 'false' === $assoc_args['show_total'] ) {
			$assoc_args['show_total'] = false;
		}

		$fields = array(
			'ID',
			'redirect_from',
			'redirect_to',
			'status_code',
			'enable_regex',
			'post_status',
		);

		$redirects = srm_get_redirects( array( 'post_status' => 'any' ), true );
		$redirects = array_map(
			function( &$item ) use ( $assoc_args ) {
				if ( 'table' === $assoc_args['format'] ) {
					$item['enable_regex'] = $item['enable_regex'] ? 'true' : 'false';
				} else {
					$item['enable_regex'] = (bool) $item['enable_regex'];
				}
				return $item;
			},
			$redirects
		);

		$formatter = new \WP_CLI\Formatter( $assoc_args, $fields );
		$formatter->display_items( $redirects );

		if ( ! empty( $assoc_args['show_total'] ) ) {
			WP_CLI::line( 'Total of ' . count( $redirects ) . ' redirects.' );
		}
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

	/**
	 * Create test redirects
	 *
	 * @param array $args Array of arguments
	 * @subcommand create-test
	 * @synopsis <number>
	 */
	public function create_test_redirects( $args ) {

		$last_count = $args[0] + 1;
		for ( $i = 1; $i < $last_count; ++$i ) {
			$to    = sprintf( '/%d-to', $i );
			$from  = sprintf( '/%d-from', $i );
			$regex = false;

			switch ( $i + 1 ) {
				case 0 === $i % 6:
					$from   .= '/(.*)/';
					$regex = true;
					break;
				case 0 === $i % 5:
					$from   .= '/(.*)';
					$regex = true;
					break;
				case 0 === $i % 4:
					$from .= '*/';
					break;
				case 0 === $i % 3:
					$from .= '*';
					break;
				case 0 === $i % 2:
					$from .= '/';
					break;
				default:
					break;
			}

			wp_insert_post(
				array(
					'post_type'   => 'redirect_rule',
					'post_status' => 'publish',
					'post_title'  => 'Dynamically generated test redirect',
					'meta_input'  => array(
						'_redirect_rule_from'        => $from,
						'_redirect_rule_to'          => $to,
						'_redirect_rule_status_code' => 302,
						'_redirect_rule_from_regex'  => $regex,
						'_redirect_created_for_test' => true,
					),
				)
			);

			$num_created = $i + 1;
			if ( 0 === $num_created % 100 ) {
				WP_CLI::log( "Created {$num_created} rules" );
			}
		}

		$final_count = $i - 1;
		WP_CLI::success( "Created {$final_count} redirects. Run \"wp safe-redirect-manager delete-test\" to delete these tests" );

	}

	/**
	 * Delete test redirects
	 *
	 * @subcommand delete-test
	 */
	public function delete_test_redirects() {

		$num_deleted = 0;

		while ( true ) {
			$test_redirects = new WP_Query(
				array(
					'post_type'              => 'redirect_rule',
					'post_status'            => 'publish',
					'fields'                 => 'ids',
					'no_found_rows'          => true,
					'posts_per_page'         => 1000,
					'update_post_term_cache' => false,
					'meta_key'               => '_redirect_created_for_test',
				)
			);

			if ( ! $test_redirects->have_posts() ) {
				if ( 0 === $num_deleted ) {
					WP_CLI::log( 'No test redirects to delete.' );
				}
				break;
			}

			foreach ( $test_redirects->posts as $test_redirect ) {
				wp_delete_post( $test_redirect, true );
				++$num_deleted;
			}

			WP_CLI::log( "Deleted {$num_deleted} test redirects." );
		}

		WP_CLI::success( "Successfully deleted {$num_deleted} test redirects." );
	}
}
