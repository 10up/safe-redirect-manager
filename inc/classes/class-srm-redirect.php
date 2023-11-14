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
	 * Matched redirect
	 *
	 * @var array
	 */
	private $matched_redirect;

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
		 * Multisite redirect checks.
		 */
		$this->multisite_checks();

		/**
		 * Filter whether to only redirect on 404 File Not Found pages.
		 *
		 * @hook srm_redirect_only_on_404
		 * @param {bool} $404_redirects_only Whether to redirect file not found requests only. Default `false`.
		 * @param {bool} Bool to redirect file not found requests.
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
			$parsed_home_url = parse_url( home_url() ); // phpcs:ignore
		}

		if ( isset( $parsed_home_url['path'] ) && '/' !== $parsed_home_url['path'] ) {
			$requested_path = preg_replace( '@' . $parsed_home_url['path'] . '@i', '', $requested_path, 1 );
		}

		if ( empty( $requested_path ) ) {
			$requested_path = '/';
		}

		/**
		 * Filter registered redirects.
		 *
		 * Allows plugin developers to modify the available redirects.
		 *
		 * @hook srm_registered_redirects
		 * @param {array} $redirects Redirects found for the redirect path.
		 * @param {string} $requested_path Original path of the requested URL.
		 * @returns {array} Redirects for the redirect path.
		 */
		$redirects = apply_filters( 'srm_registered_redirects', $redirects, $requested_path );

		/**
		 * Allow or disallow case insensitive redirects.
		 *
		 * @hook srm_case_insensitive_redirects
		 * @param {bool} $case_insensitive Enable or disable case insensitive redirects. Default is `true` which means insensitive is enabled.
		 * @returns {bool} Bool to enable or disable case insensitive redirects.
		 */
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
			$parsed_requested_path = parse_url( $normalized_requested_path ); // phpcs:ignore
		}
		// Normalize the request path with and without query strings, for comparison later
		$normalized_requested_path_no_query = '';
		$requested_query_params             = '';

		if ( ! empty( $parsed_requested_path['path'] ) ) {
			$normalized_requested_path_no_query = untrailingslashit( stripslashes( $parsed_requested_path['path'] ) );
		}

		if ( ! empty( $parsed_requested_path['query'] ) ) {
			$requested_query_params = $parsed_requested_path['query'];
		}

		foreach ( (array) $redirects as $redirect ) {

			$redirect_from = untrailingslashit( $redirect['redirect_from'] );
			if ( empty( $redirect_from ) ) {
				$redirect_from = '/'; // this only happens in the case where there is a redirect on the root
			}

			$redirect_to  = $redirect['redirect_to'];
			$status_code  = $redirect['status_code'];
			$enable_regex = ( isset( $redirect['enable_regex'] ) ) ? $redirect['enable_regex'] : false;
			$redirect_id  = $redirect['ID'];
			$force_https  = ! empty( $redirect['force_https'] ) && true == $redirect['force_https'];
			$message      = $redirect['message'] ?? '';

			// check if the redirection destination is valid, otherwise just skip it (unless this is a 4xx request)
			if ( empty( $redirect_to ) && ! in_array( $status_code, array( 403, 404, 410 ), true ) ) {
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

				/**
				 * Filter whether to compare only query params.
				 *
				 * @hook srm_match_query_params
				 * @param {int} $matched_position The matched position of specific string in the URL. Default is the position of `?`.
				 * @returns {int} The matched position of specific string in the URL.
				 */
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
					$parsed_redirect = parse_url( $redirect_to ); // phpcs:ignore
				}

				if ( is_array( $parsed_redirect ) && ! empty( $parsed_redirect['host'] ) ) {
					$this->whitelist_host = $parsed_redirect['host'];
					add_filter( 'allowed_redirect_hosts', array( $this, 'filter_allowed_redirect_hosts' ) );
				}

				// Allow for regex replacement in $redirect_to
				if ( $enable_regex && ! filter_var( $redirect_to, FILTER_VALIDATE_URL ) ) {
					$redirect_to = preg_replace( '@' . $redirect_from . '@' . $regex_flag, $redirect_to, $requested_path );
					$redirect_to = '/' . ltrim( $redirect_to, '/' );
				}

				// re-add the query params if they've not already been added by the wildcard
				// query params are forwarded to allow for attribution and marketing params to be maintained
				if ( ! $match_query_params && ! empty( $requested_query_params ) && ! strpos( $redirect_to, '?' ) ) {
					$redirect_to .= '?' . $requested_query_params;
				}

				/**
				 * Filter the url to redirect to
				 *
				 * @hook srm_redirect_to
				 * @param {string} $redirect_url Final URL to redirect to.
				 * @returns {string} Final URL to redirect to.
				 */
				$filterd_redirect_to   = apply_filters( 'srm_redirect_to', $redirect_to );
				$sanitized_redirect_to = esc_url_raw( $filterd_redirect_to );

				return [
					'redirect_to'  => $sanitized_redirect_to,
					'status_code'  => $status_code,
					'enable_regex' => $enable_regex,
					'redirect_id'  => $redirect_id,
					'force_https'  => $force_https,
					'message'      => $message,
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

		/**
		 * Whether to redirect only on 404 error.
		 *
		 * @hook srm_redirect_only_on_404
		 * @param {bool} $redirect_only_on_404 Whether to redirect only on 404 error. Default is `false`.
		 * @returns {bool} Bool to redirect only on 404 error.
		 */
		$only_404 = apply_filters( 'srm_redirect_only_on_404', false );

		if ( is_admin() || ( $only_404 && ! is_404() ) ) {
			return;
		}

		/**
		 * Filter requested path.
		 *
		 * @hook srm_requested_path
		 * @param {string} $request_path Request path. Default `$_SERVER['REQUEST_URI']`.
		 * @returns {string} Request path.
		 */
		$requested_path   = esc_url_raw( apply_filters( 'srm_requested_path', sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ?? '' ) );
		$requested_path   = untrailingslashit( stripslashes( $requested_path ) );
		$matched_redirect = $this->match_redirect( $requested_path );

		if ( empty( $matched_redirect ) ) {
			return;
		}

		do_action(
			'srm_do_redirect',
			$requested_path,
			$matched_redirect['redirect_to'],
			$matched_redirect['status_code'],
			$matched_redirect['force_https']
		);

		if ( defined( 'PHPUNIT_SRM_TESTSUITE' ) && PHPUNIT_SRM_TESTSUITE ) {
			// Don't actually redirect if we are testing
			return;
		}

		header( 'X-Safe-Redirect-Manager: true' );
		header( 'X-Safe-Redirect-ID: ' . esc_attr( $matched_redirect['redirect_id'] ) );

		// Use default status code if an invalid value is set.
		if ( ! in_array( $matched_redirect['status_code'], srm_get_valid_status_codes(), true ) ) {
			/**
			 * Default status code to redirect with
			 *
			 * @hook srm_default_direct_status
			 * @param {int} The status code to redirect with. Default `302`.
			 * @returns {int} The status code to redirect with.
			 */
			$matched_redirect['status_code'] = apply_filters( 'srm_default_direct_status', 302 );
		}

		// Force http/https
		$this->matched_redirect = $matched_redirect;
		add_filter( 'wp_redirect', array( $this, 'modify_redirect_protocol' ) );

		// wp_safe_redirect only supports 'true' 3xx redirects; handle predefined 4xx here.
		if ( 403 === $matched_redirect['status_code'] || 410 === $matched_redirect['status_code'] ) {
			wp_die(
				esc_html( $matched_redirect['message'] ),
				'',
				$matched_redirect['status_code'] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
			return;
		}

		if ( 404 === $matched_redirect['status_code'] ) {
			/**
			 * We must do this manually and not rely on $wp_query->handle_404()
			 * to prevent default "Plain" permalinks from "soft 404"-ing
			 */
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
			include_once get_query_template( '404' );
			return;
		}

		wp_safe_redirect( $matched_redirect['redirect_to'], $matched_redirect['status_code'], 'Safe Redirect Manager' );
		exit;
	}

	/**
	 * Apply redirect protocol using route setting
	 *
	 * @param string $url URL to redirect to
	 *
	 * @return string Modified URL to redirect to
	 */
	public function modify_redirect_protocol( $url ) {
		if ( empty( $this->matched_redirect ) || ! $this->matched_redirect['force_https'] ) {
			return $url;
		}

		// Convert relative path to absolute URL before applying protocol
		if ( ! ( strpos( $url, 'http' ) === 0 ) ) {
			$url = home_url( $url );
		}

		$http     = 'http://';
		$position = strpos( $url, $http );

		if ( 0 === $position ) {
			$url = substr_replace( $url, 'https://', $position, strlen( $http ) );
		}

		return $url;
	}

	/**
	 * Check if on a multisite's subsite and its blog status,
	 * in case of an archived, deleted or spam status, check main site's redirect rules.
	 *
	 * @return void
	 */
	public function multisite_checks() {
		if ( is_multisite() && ! is_user_logged_in() ) {
			$blog_id = get_current_blog_id();

			if ( ! empty( $blog_id ) ) {
				$blog_details = get_blog_details( $blog_id );

				if (
					! empty( $blog_details->archived )
					|| ! empty( $blog_details->deleted )
					|| ! empty( $blog_details->spam )
				) {
					$main_site_id = get_main_site_id();

					if ( ! empty( $main_site_id ) ) {
						switch_to_blog( $main_site_id );

						$this->maybe_redirect();

						switch_to_blog( $blog_id );
					}
				}
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

		if ( ! $instance ) {
			$instance = new self();
			$instance->setup();
		}

		return $instance;
	}
}

