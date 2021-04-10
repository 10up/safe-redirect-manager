<?php
/**
 * Handle redirection
 *
 * @package safe-redirect-manager
 */

/**
 * Redirect functionality class
 */
class SRM_Redirect {
	/**
	 * Store whitelisted host
	 *
	 * @var array
	 */
	private $whitelist_host;

	/**
	 * Setup hook.
	 *
	 * @since 1.8
	 */
	public function setup() {
		add_action( 'init', array( $this, 'setup_redirect' ), 0 );
	}

	/**
	 * Initialize redirect listening
	 *
	 * @since 1.9.4
	 */
	public function setup_redirect() {
		/**
		 * To only redirect on 404 pages, use:
		 *   add_filter( 'srm_redirect_only_on_404', '__return_true' );
		 */
		if ( apply_filters( 'srm_redirect_only_on_404', false ) ) {
			add_action( 'template_redirect', array( $this, 'maybe_redirect' ), 0 );
		} else {
			add_action( 'parse_request', array( $this, 'maybe_redirect' ), 0 );
		}
	}

	/**
	 * Apply whitelisted host to allowed_redirect_hosts filter
	 *
	 * @since 1.8
	 * @param array $hosts Array of hosts
	 * @return array
	 */
	public function filter_allowed_redirect_hosts( $hosts ) {
		$without_www = preg_replace( '/^www\./i', '', $this->whitelist_host );
		$with_www    = 'www.' . $without_www;

		$hosts[] = $without_www;
		$hosts[] = $with_www;

		return array_unique( $hosts );
	}

	/**
	 * Matches a redirect given a path.
	 *
	 * @param string $requested_path The path to check redirects for.
	 *
	 * @return array|bool The redirect url. False if no redirect is found.
	 */
	public function match_redirect( $requested_path ) {
		$redirects = srm_get_redirects();

		// If we have no redirects, there is no need to continue
		if ( empty( $redirects ) ) {
			return false;
		}

		/**
		 * If WordPress resides in a directory that is not the public root, we have to chop
		 * the pre-WP path off the requested path.
		 */
		if ( function_exists( 'wp_parse_url' ) ) {
			$parsed_home_url = wp_parse_url( home_url() );
		} else {
			$parsed_home_url = parse_url( home_url() );
		}

		if ( isset( $parsed_home_url['path'] ) && '/' !== $parsed_home_url['path'] ) {
			$requested_path = preg_replace( '@' . $parsed_home_url['path'] . '@i', '', $requested_path, 1 );
		}

		if ( empty( $requested_path ) ) {
			$requested_path = '/';
		}

		// Allow redirects to be filtered
		$redirects = apply_filters( 'srm_registered_redirects', $redirects, $requested_path );

		// Allow for case insensitive redirects
		$case_insensitive = apply_filters( 'srm_case_insensitive_redirects', true );

		if ( $case_insensitive ) {
			$regex_flag = 'i';
			// Normalized path is used for matching but not for replace
			$normalized_requested_path = strtolower( $requested_path );
		} else {
			$regex_flag                = '';
			$normalized_requested_path = $requested_path;
		}

		if ( function_exists( 'wp_parse_url' ) ) {
			$parsed_requested_path = wp_parse_url( $normalized_requested_path );
		} else {
			$parsed_requested_path = parse_url( $normalized_requested_path );
		}
		// Normalize the request path with and without query strings, for comparison later
		$requested_query_params = '';
		if ( ! empty( $parsed_requested_path['query'] ) ) {
			$requested_query_params = $parsed_requested_path['query'];
		}

		$normalized_requested_path_no_query = untrailingslashit( stripslashes( $parsed_requested_path['path'] ) );

		foreach ( (array) $redirects as $redirect ) {

			$redirect_from = untrailingslashit( $redirect['redirect_from'] );
			if ( empty( $redirect_from ) ) {
				$redirect_from = '/'; // this only happens in the case where there is a redirect on the root
			}

			$redirect_to  = $redirect['redirect_to'];
			$status_code  = $redirect['status_code'];
			$enable_regex = ( isset( $redirect['enable_regex'] ) ) ? $redirect['enable_regex'] : false;
			$redirect_id  = $redirect['ID'];

			// check if the redirection destination is valid, otherwise just skip it
			if ( empty( $redirect_to ) ) {
				continue;
			}

			// check if requested path is the same as the redirect from path
			if ( $enable_regex ) {
				$match_query_params = false;
				$matched_path       = preg_match( '@' . $redirect_from . '@' . $regex_flag, $requested_path );
			} else {
				if ( $case_insensitive ) {
					$redirect_from = strtolower( $redirect_from );
				}

				// only compare query params if the $redirect_from value contains parameters
				$match_query_params = apply_filters( 'srm_match_query_params', strpos( $redirect_from, '?' ) );

				$to_match     = ( ! $match_query_params && ! empty( $normalized_requested_path_no_query ) ) ? $normalized_requested_path_no_query : $normalized_requested_path;
				$matched_path = ( $to_match === $redirect_from );

				// check if the redirect_from ends in a wildcard
				if ( ! $matched_path && ( strrpos( $redirect_from, '*' ) === strlen( $redirect_from ) - 1 ) ) {
					$wildcard_base = substr( $redirect_from, 0, strlen( $redirect_from ) - 1 );

					// Mark as path match if requested path matches the base of the redirect from.
					$matched_path = ( substr( trailingslashit( $normalized_requested_path ), 0, strlen( $wildcard_base ) ) === $wildcard_base );
					if ( ( strrpos( $redirect_to, '*' ) === strlen( $redirect_to ) - 1 ) ) {
						$redirect_to = rtrim( $redirect_to, '*' ) . ltrim( substr( $requested_path, strlen( $wildcard_base ) ), '/' );
					}
				}
			}

			if ( $matched_path ) {
				/**
				 * Whitelist redirect host
				 */
				if ( function_exists( 'wp_parse_url' ) ) {
					$parsed_redirect = wp_parse_url( $redirect_to );
				} else {
					$parsed_redirect = parse_url( $redirect_to );
				}

				if ( is_array( $parsed_redirect ) && ! empty( $parsed_redirect['host'] ) ) {
					$this->whitelist_host = $parsed_redirect['host'];
					add_filter( 'allowed_redirect_hosts', array( $this, 'filter_allowed_redirect_hosts' ) );
				}

				// Allow for regex replacement in $redirect_to
				if ( $enable_regex ) {
					$redirect_to = preg_replace( '@' . $redirect_from . '@' . $regex_flag, $redirect_to, $requested_path );
				}

				// re-add the query params if they've not already been added by the wildcard
				// query params are forwarded to allow for attribution and marketing params to be maintained
				if ( ! $match_query_params && ! empty( $requested_query_params ) && ! strpos( $redirect_to, '?' ) ) {
					$redirect_to .= '?' . $requested_query_params;
				}

				$sanitized_redirect_to = esc_url_raw( apply_filters( 'srm_redirect_to', $redirect_to ) );

				return [
					'redirect_to'  => $sanitized_redirect_to,
					'status_code'  => $status_code,
					'enable_regex' => $enable_regex,
					'redirect_id'  => $redirect_id,
				];
			}
		}

		return false;
	}

	/**
	 * Check current url against redirects
	 *
	 * @since 1.8
	 */
	public function maybe_redirect() {

		// Don't redirect unless not on admin. If 404 filter enabled, require query is a 404.
		if ( is_admin() || ( apply_filters( 'srm_redirect_only_on_404', false ) && ! is_404() ) ) {
			return;
		}

		// get requested path and add a / before it
		$requested_path = esc_url_raw( apply_filters( 'srm_requested_path', $_SERVER['REQUEST_URI'] ) );
		$requested_path = untrailingslashit( stripslashes( $requested_path ) );

		$matched_redirect = $this->match_redirect( $requested_path );

		if ( empty( $matched_redirect ) ) {
			return;
		}

		do_action(
			'srm_do_redirect',
			$requested_path,
			$matched_redirect['redirect_to'],
			$matched_redirect['status_code']
		);

		if ( defined( 'PHPUNIT_SRM_TESTSUITE' ) && PHPUNIT_SRM_TESTSUITE ) {
			// Don't actually redirect if we are testing
			return;
		}

		header( 'X-Safe-Redirect-Manager: true' );
		header( 'X-Safe-Redirect-ID: ' . esc_attr( $matched_redirect['redirect_id'] ) );

		// if we have a valid status code, then redirect with it
		if ( in_array( $matched_redirect['status_code'], srm_get_valid_status_codes(), true ) ) {
			wp_safe_redirect( $matched_redirect['redirect_to'], $matched_redirect['status_code'] );
		} else {
			wp_safe_redirect( $matched_redirect['redirect_to'] );
		}

		exit;
	}

	/**
	 * Return singleton instance of class
	 *
	 * @return object
	 * @since 1.8
	 */
	public static function factory() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
			$instance->setup();
		}

		return $instance;
	}
}

