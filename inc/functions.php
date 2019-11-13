<?php
/**
 * General plugin functions
 *
 * @package safe-redirect-manager
 */

/**
 * Get redirects from the database
 *
 * @since 1.8
 * @param array $args Any arguments to filter by
 * @param bool  $hard Force cache refresh
 * @return array $redirects An array of redirects
 */
function srm_get_redirects( $args = array(), $hard = false ) {

	$redirects = get_transient( '_srm_redirects' );

	if ( $hard || false === $redirects ) {

		$redirects = array();

		$posts_per_page = 100;

		$i = 1;

		$default_max_redirects = apply_filters( 'srm_max_redirects', 250 );

		while ( true ) {
			if ( count( $redirects ) >= $default_max_redirects ) {
				break;
			}

			$defaults = array(
				'posts_per_page' => $posts_per_page,
				'post_status'    => 'publish',
				'paged'          => $i,
				'fields'         => 'ids',
				'orderby'        => 'menu_order ID',
				'order'          => 'ASC',
			);

			$query_args = array_merge( $defaults, $args );

			// Some arguments that don't need to be configurable
			$query_args['post_type']         = 'redirect_rule';
			$query_args['update_term_cache'] = false;

			$redirect_query = new WP_Query( $query_args );

			if ( ! $redirect_query->have_posts() ) {
				break;
			}

			foreach ( $redirect_query->posts as $redirect_id ) {
				if ( count( $redirects ) >= $default_max_redirects ) {
					break 2;
				}

				$redirects[] = array(
					'ID'            => $redirect_id,
					'post_status'   => get_post_status( $redirect_id ),
					'redirect_from' => get_post_meta( $redirect_id, '_redirect_rule_from', true ),
					'redirect_to'   => get_post_meta( $redirect_id, '_redirect_rule_to', true ),
					'status_code'   => (int) get_post_meta( $redirect_id, '_redirect_rule_status_code', true ),
					'enable_regex'  => (bool) get_post_meta( $redirect_id, '_redirect_rule_from_regex', true ),
				);
			}

			$i++;

		}

		set_transient( '_srm_redirects', $redirects );
	}

	return $redirects;
}

/**
 * Returns true if max redirects have been reached
 *
 * @since 1.8
 * @return bool
 */
function srm_max_redirects_reached() {
	$default_max_redirects = apply_filters( 'srm_max_redirects', 250 );

	$redirects = srm_get_redirects();

	return ( count( $redirects ) >= $default_max_redirects );
}

/**
 * Get valid HTTP status codes
 *
 * @since  1.8
 * @return array
 */
function srm_get_valid_status_codes() {
	return apply_filters( 'srm_valid_status_codes', array( 301, 302, 303, 307, 403, 404 ) );
}

/**
 * Flush redirect cache
 *
 * @since 1.8
 */
function srm_flush_cache() {
	delete_transient( '_srm_redirects' );
}


/**
 * Check for potential redirect loops or chains
 *
 * @since 1.8
 * @return boolean
 */
function srm_check_for_possible_redirect_loops() {
	$redirects = srm_get_redirects();

	if ( function_exists( 'wp_parse_url' ) ) {
		$current_url = wp_parse_url( home_url() );
	} else {
		$current_url = parse_url( home_url() );
	}

	$this_host = ( is_array( $current_url ) && ! empty( $current_url['host'] ) ) ? $current_url['host'] : '';

	foreach ( $redirects as $redirect ) {
		$redirect_from = $redirect['redirect_from'];

		// check redirect from against all redirect to's
		foreach ( $redirects as $compare_redirect ) {
			$redirect_to = $compare_redirect['redirect_to'];

			if ( function_exists( 'wp_parse_url' ) ) {
				$redirect_url = wp_parse_url( $redirect_to );
			} else {
				$redirect_url = parse_url( $redirect_to );
			}

			$redirect_host = ( is_array( $redirect_url ) && ! empty( $redirect_url['host'] ) ) ? $redirect_url['host'] : '';

			// check if we are redirecting locally
			if ( empty( $redirect_host ) || $redirect_host === $this_host ) {
				$redirect_from_url = preg_replace( '/(http:\/\/|https:\/\/|www\.)/i', '', home_url() . $redirect_from );
				$redirect_to_url   = $redirect_to;
				if ( ! preg_match( '/https?:\/\//i', $redirect_to_url ) ) {
					$redirect_to_url = $this_host . $redirect_to_url;
				} else {
					$redirect_to_url = preg_replace( '/(http:\/\/|https:\/\/|www\.)/i', '', $redirect_to_url );
				}

				// possible loop/chain found
				if ( $redirect_to_url === $redirect_from_url ) {
					return true;
				}
			}
		}
	}

	return false;
}

/**
 * Creates a redirect post, this function will be useful for import/exports scripts
 *
 * @param string $redirect_from Redirect from location
 * @param string $redirect_to Redirect to location
 * @param int    $status_code Redirect status code
 * @param bool   $enable_regex Whether to enable regex or not
 * @param string $post_status Post status
 * @param int    $menu_order Menu order
 * @since 1.8
 * @uses wp_insert_post, update_post_meta
 * @return int|WP_Error
 */
function srm_create_redirect( $redirect_from, $redirect_to, $status_code = 302, $enable_regex = false, $post_status = 'publish', $menu_order = 0 ) {
	global $wpdb;

	$sanitized_redirect_from = srm_sanitize_redirect_from( $redirect_from );
	$sanitized_redirect_to   = srm_sanitize_redirect_to( $redirect_to );
	$sanitized_status_code   = absint( $status_code );
	$sanitized_enable_regex  = (bool) $enable_regex;
	$sanitized_post_status   = sanitize_key( $post_status );
	$sanitized_menu_order    = absint( $menu_order );

	// check and make sure no parameters are empty or invalid after sanitation
	if ( empty( $sanitized_redirect_from ) || empty( $sanitized_redirect_to ) ) {
		return new WP_Error( 'invalid-argument', esc_html__( 'Redirect from and/or redirect to arguments are invalid.', 'safe-redirect-manager' ) );
	}

	if ( ! in_array( $sanitized_status_code, srm_get_valid_status_codes(), true ) ) {
		return new WP_Error( 'invalid-argument', esc_html__( 'Invalid status code.', 'safe-redirect-manager' ) );
	}

	// Check to ensure this redirect doesn't already exist
	if ( $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key=%s AND meta_value=%s", '_redirect_rule_from', $sanitized_redirect_from ) ) ) {
		return new WP_Error( 'duplicate-redirect', sprintf( esc_html__( 'Redirect already exists for %s', 'safe-redirect-manager' ), $sanitized_redirect_from ) );
	}

	// create the post
	$post_args = array(
		'post_type'   => 'redirect_rule',
		'post_status' => $sanitized_post_status,
		'post_author' => 1,
		'menu_order'  => $sanitized_menu_order,
	);

	$post_id = wp_insert_post( $post_args );

	if ( 0 >= $post_id ) {
		return new WP_Error( 'error-creating', esc_html__( 'An error occurred creating the redirect.', 'safe-redirect-manager' ) );
	}

	// update the posts meta info
	update_post_meta( $post_id, '_redirect_rule_from', wp_slash( $sanitized_redirect_from ) );
	update_post_meta( $post_id, '_redirect_rule_to', $sanitized_redirect_to );
	update_post_meta( $post_id, '_redirect_rule_status_code', $sanitized_status_code );
	update_post_meta( $post_id, '_redirect_rule_from_regex', $sanitized_enable_regex );

	// We need to update the cache after creating this redirect
	srm_flush_cache();

	return $post_id;
}



/**
 * Sanitize redirect to path
 *
 * The only difference between this function and just calling esc_url_raw is
 * esc_url_raw( 'test' ) == 'http://test', whereas sanitize_redirect_path( 'test' ) == '/test'
 *
 * @since 1.8
 * @param string $path Path to sanitize
 * @return string
 */
function srm_sanitize_redirect_to( $path ) {
	$path = trim( $path );

	if ( preg_match( '/^www\./i', $path ) ) {
		$path = 'http://' . $path;
	}

	if ( ! preg_match( '/^https?:\/\//i', $path ) ) {
		if ( strpos( $path, '/' ) !== 0 ) {
			$path = '/' . $path;
		}
	}

	return esc_url_raw( $path );
}

/**
 * Sanitize redirect from path
 *
 * @since 1.8
 * @param string  $path Path to sanitize
 * @param boolean $allow_regex Whether to allow regex
 * @return string
 */
function srm_sanitize_redirect_from( $path, $allow_regex = false ) {

	$path = trim( $path );

	if ( empty( $path ) ) {
		return '';
	}

	// dont accept paths starting with a .
	if ( ! $allow_regex && strpos( $path, '.' ) === 0 ) {
		return '';
	}

	// turn path in to absolute
	if ( preg_match( '/https?:\/\//i', $path ) ) {
		$path = preg_replace( '/^(http:\/\/|https:\/\/)(www\.)?[^\/?]+\/?(.*)/i', '/$3', $path );
	} elseif ( ! $allow_regex && strpos( $path, '/' ) !== 0 ) {
		$path = '/' . $path;
	}

	// the @ symbol will break our regex engine
	$path = str_replace( '@', '', $path );

	return $path;
}

/**
 * Imports redirects from CSV file or stream.
 *
 * @since 1.8
 *
 * @access public
 * @param string|resource $file File path, file pointer or stream to read redirects from.
 * @param array           $args The array of arguments. Includes column mapping and CSV settings.
 * @return array Returns importing statistic on success, otherwise FALSE.
 */
function srm_import_file( $file, $args ) {
	$handle       = $file;
	$close_handle = false;
	$doing_wp_cli = defined( 'WP_CLI' ) && WP_CLI;

	// filter arguments
	$args = apply_filters( 'srm_import_file_args', $args );

	// enable line endings auto detection
	@ini_set( 'auto_detect_line_endings', true );

	// open file pointer if $file is not a resource
	if ( ! is_resource( $file ) ) {
		$handle = fopen( $file, 'rb' );
		if ( ! $handle ) {
			$doing_wp_cli && WP_CLI::error( sprintf( 'Error retrieving %s file', basename( $file ) ) );
			return false;
		}

		$close_handle = true;
	}

	// process all rows of the file
	$created = 0;
	$skipped = 0;
	$headers = fgetcsv( $handle );

	while ( ( $row = fgetcsv( $handle ) ) ) {
		// validate
		$rule = array_combine( $headers, $row );
		if ( empty( $rule[ $args['source'] ] ) || empty( $rule[ $args['target'] ] ) ) {
			$doing_wp_cli && WP_CLI::warning( 'Skipping - redirection rule is formatted improperly.' );
			$skipped++;
			continue;
		}

		// sanitize
		$redirect_from = srm_sanitize_redirect_from( $rule[ $args['source'] ] );
		$redirect_to   = srm_sanitize_redirect_to( $rule[ $args['target'] ] );
		$status_code   = ! empty( $rule[ $args['code'] ] ) ? $rule[ $args['code'] ] : 302;
		$regex         = ! empty( $rule[ $args['regex'] ] ) ? filter_var( $rule[ $args['regex'] ], FILTER_VALIDATE_BOOLEAN ) : false;
		$menu_order    = ! empty( $rule[ $args['order'] ] ) ? $rule[ $args['order'] ] : 0;

		// import
		$id = srm_create_redirect( $redirect_from, $redirect_to, $status_code, $regex, 'publish', $menu_order );

		if ( is_wp_error( $id ) ) {
			$doing_wp_cli && WP_CLI::warning( $id );
			$skipped++;
		} else {
			$doing_wp_cli && WP_CLI::line( "Success - Created redirect from '{$redirect_from}' to '{$redirect_to}'" );
			$created++;
		}
	}

	// close an open file pointer if we've opened it
	if ( $close_handle ) {
		fclose( $handle );
	}

	// return result statistic
	return array(
		'created' => $created,
		'skipped' => $skipped,
	);
}
