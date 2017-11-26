<?php
/**
 * Handle redirection
 */

class SRM_Redirect {
	/**
	 * Initialize redirect listening
	 *
	 * @since 1.8
	 */
	public function setup() {
		add_action( 'parse_request', array( $this, 'maybe_redirect' ), 0 );
	}

	/**
	 * Check current url against redirects
	 *
	 * @since 1.8
	 */
	public function maybe_redirect() {

		// Don't redirect from wp-admin
		if ( is_admin() ) {
			return;
		}

		$redirects = srm_get_redirects();

		// If we have no redirects, there is no need to continue
		if ( empty( $redirects ) ) {
			return;
		}

		// get requested path and add a / before it
		$requested_path = esc_url_raw( $_SERVER['REQUEST_URI'] );
		$requested_path = untrailingslashit( stripslashes( $requested_path ) );

		/**
		 * If WordPress resides in a directory that is not the public root, we have to chop
		 * the pre-WP path off the requested path.
		 */
		$parsed_home_url = parse_url( home_url() );
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
			// normalized path is used for matching but not for replace
			$normalized_requested_path = strtolower( $requested_path );
		} else {
			$regex_flag = '';
			$normalized_requested_path = $requested_path;
		}

		foreach ( (array) $redirects as $redirect ) {

			$redirect_from = untrailingslashit( $redirect['redirect_from'] );
			if ( empty( $redirect_from ) ) {
				$redirect_from = '/'; // this only happens in the case where there is a redirect on the root
			}

			$redirect_to = $redirect['redirect_to'];
			$status_code = $redirect['status_code'];
			$enable_regex = ( isset( $redirect['enable_regex'] ) ) ? $redirect['enable_regex'] : false;

			// check if the redirection destination is valid, otherwise just skip it
			if ( empty( $redirect_to ) ) {
				continue;
			}

			// check if requested path is the same as the redirect from path
			if ( $enable_regex ) {
				$matched_path = preg_match( '@' . $redirect_from . '@' . $regex_flag, $requested_path );
			} else {
				if ( $case_insensitive ) {
					$redirect_from = strtolower( $redirect_from );
				}

				$matched_path = ( $normalized_requested_path === $redirect_from );

				// check if the redirect_from ends in a wildcard
				if ( ! $matched_path && (strrpos( $redirect_from, '*' ) === strlen( $redirect_from ) - 1) ) {
					$wildcard_base = substr( $redirect_from, 0, strlen( $redirect_from ) - 1 );

					// mark as match if requested path matches the base of the redirect from
					$matched_path = (substr( $normalized_requested_path, 0, strlen( $wildcard_base ) ) === $wildcard_base);
					if ( (strrpos( $redirect_to, '*' ) === strlen( $redirect_to ) - 1 ) ) {
						$redirect_to = rtrim( $redirect_to, '*' ) . ltrim( substr( $requested_path, strlen( $wildcard_base ) ), '/' );
					}
				}
			}

			if ( $matched_path ) {
				// Allow for regex replacement in $redirect_to
				if ( $enable_regex ) {
					$redirect_to = preg_replace( '@' . $redirect_from . '@' . $regex_flag, $redirect_to, $requested_path );
				}

				$sanitized_redirect_to = esc_url_raw( $redirect_to );

				do_action( 'srm_do_redirect', $requested_path, $sanitized_redirect_to, $status_code );

				if ( defined( 'PHPUNIT_SRM_TESTSUITE' ) && PHPUNIT_SRM_TESTSUITE ) {
					// Don't actually redirect if we are testing
					return;
				}

				header( 'X-Safe-Redirect-Manager: true' );

				// if we have a valid status code, then redirect with it
				if ( in_array( $status_code, srm_get_valid_status_codes() ) ) {
					wp_redirect( $sanitized_redirect_to, $status_code );
				} else {
					wp_redirect( $sanitized_redirect_to );
				}

				exit;
			}
		}
	}

	/**
	 * Return singleton instance of class
	 *
	 * @return object
	 * @since 1.8
	 */
	public static function factory() {
		static $instance = false;

		if ( ! $instance  ) {
			$instance = new self();
			$instance->setup();
		}

		return $instance;
	}
}

SRM_Redirect::factory();
