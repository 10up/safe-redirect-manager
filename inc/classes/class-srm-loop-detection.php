<?php
/**
 * Handle loop detection.
 *
 * @package safe-redirect-manager
 */

/**
 * Class for redirect loop detection.
 */
class SRM_Loop_Detection {
	/**
	 * Builds a directed graph from the data returned by `srm_get_redirects()`.
	 *
	 * @return array
	 */
	private static function get_directed_graph() {
		$transient = get_transient( '_srm_redirects_graph' );

		if ( false !== $transient ) {
			return $transient;
		}

		$redirects = srm_get_redirects();
		$graph     = array();

		foreach ( $redirects as $redirect ) {
			$source          = $redirect['redirect_from'];
			$destination     = $redirect['redirect_to'];
			$source_url      = '';
			$destination_url = '';

			if ( function_exists( 'wp_parse_url' ) ) {
				$current_url  = wp_parse_url( home_url() );
				$redirect_url = wp_parse_url( $destination );
			} else {
				$current_url  = parse_url( home_url() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
				$redirect_url = parse_url( $destination ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
			}

			$this_host     = ( is_array( $current_url ) && ! empty( $current_url['host'] ) ) ? $current_url['host'] : '';
			$redirect_host = ( is_array( $redirect_url ) && ! empty( $redirect_url['host'] ) ) ? $redirect_url['host'] : '';

			// check if we are redirecting locally
			if ( empty( $redirect_host ) || $redirect_host === $this_host ) {
				$source_url      = preg_replace( '/(http:\/\/|https:\/\/|www\.)/i', '', home_url() . $source );
				$destination_url = $destination;
				if ( ! preg_match( '/https?:\/\//i', $destination_url ) ) {
					$destination_url = $this_host . $destination_url;
				} else {
					$destination_url = preg_replace( '/(http:\/\/|https:\/\/|www\.)/i', '', $destination_url );
				}

				// Add the source URL to the graph if not already present.
				if ( ! isset( $graph[ $source_url ] ) ) {
					$graph[ $source_url ] = array();
				}

				// Add the destination URL to the graph if not already present.
				if ( ! isset( $graph[ $destination_url ] ) ) {
					$graph[ $destination_url ] = array();
				}

				// Add an edge from the source URL to the destination URL.
				$graph[ $source_url ][] = array(
					'id'          => $redirect['ID'],
					'destination' => $destination_url,
				);
			}
		}

		set_transient( '_srm_redirects_graph', $graph );

		return $graph;
	}

	/**
	 * Core function that detects a cycle in a directed graph.
	 *
	 * @param array  $graph        The directed graph in which a cycle/loop has to be detected.
	 * @param string $vertex       Node in the graph that holds URL of source or destination.
	 * @param array  $visited      Array of nodes visited during traversal.
	 * @param array  $current_path Array of paths traversed.
	 * @param array  $cycle_source Array of starting point of detected cycles.
	 *
	 * @return void
	 */
	private static function has_cycle_recursive( $graph, $vertex, &$visited, &$current_path, &$cycle_source ) {
		$visited[ $vertex ]      = true;
		$current_path[ $vertex ] = true;

		if ( isset( $graph[ $vertex ] ) ) {
			foreach ( $graph[ $vertex ] as $neighbor ) {
				if ( ! isset( $visited[ $neighbor['destination'] ] ) ) {
					self::has_cycle_recursive( $graph, $neighbor['destination'], $visited, $current_path, $cycle_source );
				} elseif ( isset( $current_path[ $neighbor['destination'] ] ) ) {
					$cycle_source[] = $neighbor;
				}
			}
		}

		unset( $current_path[ $vertex ] );
	}

	/**
	 * Initiates detection of a redirect loop in redirected URLs.
	 *
	 * @return boolean
	 */
	public static function detect_redirect_loops() {
		$graph        = self::get_directed_graph();
		$visited      = array();
		$current_path = array();
		$cycle_source = array();

		foreach ( $graph as $vertex => $destinations ) {
			if ( ! isset( $visited[ $vertex ] ) ) {
				if ( self::has_cycle_recursive( $graph, $vertex, $visited, $current_path, $cycle_source ) ) {
					return $cycle_source;
				}
			}
		}

		return $cycle_source;
	}

	/**
	 * Returns an array of "Redirect From" values that are the starting vertices of redirect loops.
	 *
	 * @param array $cycle_source Array of redirect loops.
	 *
	 * @return array
	 */
	public static function get_cycle_source( $cycle_source = array() ) {
		return array_map(
			function( $source ) {
				return array(
					'path' => wp_parse_url( esc_url( $source['destination'] ), PHP_URL_PATH ),
					'id'   => $source['id'],
				);
			},
			$cycle_source
		);
	}
}
