<?php

class SRM_Loop_Detection {
	private static function get_directed_graph() {
		$redirects = srm_get_redirects();

		foreach ( $redirects as $redirect ) {
			$source      = $redirect['redirect_from'];
			$destination = $redirect['redirect_to'];
	
			// Add the source URL to the graph if not already present.
			if ( ! isset( $graph[ $source ] ) ) {
				$graph[ $source ] = array();
			}
	
			// Add the destination URL to the graph if not already present.
			if ( ! isset( $graph[ $destination ] ) ) {
				$graph[ $destination ] = array();
			}
	
			// Add an edge from the source URL to the destination URL.
			$graph[ $source ][] = $destination;
		}

		return $graph;
	}

	private static function has_cycle_recursive( $graph, $vertex, &$visited, &$current_path ) {
		$visited[ $vertex ]     = true;
		$current_path[ $vertex ] = true;
	
		if ( isset( $graph[ $vertex ] ) ) {
			foreach ( $graph[ $vertex ] as $neighbor ) {
				if ( ! isset( $visited[ $neighbor ] ) ) {
					if ( self::has_cycle_recursive( $graph, $neighbor, $visited, $current_path )) {
						return true;
					}
				} elseif ( isset( $current_path[ $neighbor ] ) ) {
					return true; // Cycle detected
				}
			}
		}
	
		unset( $current_path[ $vertex ] );
		return false;
	}

	public static function detect_redirect_loops() {
		$graph       = self::get_directed_graph();
		$visited     = array();
		$current_path = array();
	
		foreach ( $graph as $vertex => $destinations ) {
			if ( ! isset( $visited[ $vertex ] ) ) {
				if ( self::has_cycle_recursive( $graph, $vertex, $visited, $current_path ) ) {
					return true; // Cycle detected
				}
			}
		}
	
		return false;
	}
}
