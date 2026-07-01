<?php
/**
 * Export functionality for Safe Redirect Manager.
 *
 * @package safe-redirect-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Export class.
 */
class SRM_Export {

	/**
	 * Supported export formats.
	 *
	 * @since 2.2.3
	 * @var string[]
	 */
	protected $supported_formats = array( 'csv', 'json' );

	/**
	 * Sets up export hooks.
	 *
	 * @since 2.2.3
	 * @return void
	 */
	public function setup() {
		add_action( 'admin_init', array( $this, 'handle_export' ) );
		add_action( 'manage_posts_extra_tablenav', array( $this, 'add_export_button' ) );
	}

	/**
	 * Factory method.
	 *
	 * @since 2.2.3
	 * @return self
	 */
	public static function factory() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
			$instance->setup();
		}

		return $instance;
	}

	/**
	 * Renders the export format dropdown after the filter bar in the redirect list table.
	 *
	 * @since 2.2.3
	 * @param string $which Position in the table ('top' or 'bottom').
	 * @return void
	 */
	public function add_export_button( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'edit-redirect_rule' !== $screen->id ) {
			return;
		}

		if ( ! current_user_can( apply_filters( 'srm_restrict_to_capability', 'srm_manage_redirects' ) ) ) {
			return;
		}

		if ( ! array_sum( (array) wp_count_posts( 'redirect_rule' ) ) ) {
			return;
		}
		?>
		<div class="alignleft actions">
			<label for="srm-export-format" class="screen-reader-text">
				<?php esc_html_e( 'Export format', 'safe-redirect-manager' ); ?>
			</label>
			<select id="srm-export-format">
				<?php foreach ( $this->supported_formats as $format ) : ?>
					<option value="<?php echo esc_attr( $this->get_export_url( $format ) ); ?>">
						<?php echo esc_html( strtoupper( $format ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<a href="<?php echo esc_url( $this->get_export_url( $this->supported_formats[0] ) ); ?>"
				id="srm-export-btn" class="button">
				<?php esc_html_e( 'Export', 'safe-redirect-manager' ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Builds a signed export URL for the given format.
	 *
	 * @since 2.2.3
	 * @param string $format Export format key (e.g. 'csv', 'json').
	 * @return string
	 */
	protected function get_export_url( $format ) {
		return wp_nonce_url(
			add_query_arg(
				array(
					'post_type'     => 'redirect_rule',
					'action'        => 'srm_export',
					'export_format' => $format,
				),
				admin_url( 'edit.php' )
			),
			'srm_export'
		);
	}

	/**
	 * Handles the export download request, validates auth, and dispatches to the
	 * appropriate format handler.
	 *
	 * @since 2.2.3
	 * @return void
	 */
	public function handle_export() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Nonce verified below.
		$action        = sanitize_key( wp_unslash( $_GET['action'] ?? '' ) );
		$post_type     = sanitize_key( wp_unslash( $_GET['post_type'] ?? '' ) );
		$nonce         = sanitize_key( wp_unslash( $_GET['_wpnonce'] ?? '' ) );
		$export_format = sanitize_key( wp_unslash( $_GET['export_format'] ?? 'csv' ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( 'srm_export' !== $action || 'redirect_rule' !== $post_type ) {
			return;
		}

		if ( ! wp_verify_nonce( $nonce, 'srm_export' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'safe-redirect-manager' ) );
		}

		if ( ! current_user_can( apply_filters( 'srm_restrict_to_capability', 'srm_manage_redirects' ) ) ) {
			wp_die( esc_html__( 'You do not have permission to export redirects.', 'safe-redirect-manager' ) );
		}

		if ( ! in_array( $export_format, $this->supported_formats, true ) ) {
			$export_format = 'csv';
		}

		// Large redirect sets can take a while to stream; avoid the default 30s FastCGI cutoff.
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( apply_filters( 'srm_export_time_limit', 5 * MINUTE_IN_SECONDS ) );
		}

		if ( ! $this->query_redirects_page( 1 )->have_posts() ) {
			wp_die( esc_html__( 'There are no redirects to export.', 'safe-redirect-manager' ) );
		}

		while ( ob_get_level() ) {
			ob_end_clean();
		}

		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		switch ( $export_format ) {
			case 'json':
				header( 'Content-Type: application/json; charset=utf-8' );
				header( 'Content-Disposition: attachment; filename="safe-redirect-manager-export-' . gmdate( 'Y-m-d' ) . '.json"' );
				$this->export_json();
				break;
			default:
				header( 'Content-Type: text/csv; charset=utf-8' );
				header( 'Content-Disposition: attachment; filename="safe-redirect-manager-export-' . gmdate( 'Y-m-d' ) . '.csv"' );
				$this->export_csv();
		}

		exit;
	}

	/**
	 * Queries a single page of redirect IDs and bulk-primes the post/meta caches.
	 *
	 * @since 2.2.3
	 * @param int $paged Page number to query.
	 * @return WP_Query
	 */
	protected function query_redirects_page( $paged ) {
		$query = new WP_Query(
			array(
				'post_type'         => 'redirect_rule',
				'post_status'       => 'any',
				'posts_per_page'    => 100,
				'paged'             => $paged,
				'fields'            => 'ids',
				'orderby'           => 'menu_order ID',
				'order'             => 'ASC',
				'update_term_cache' => false,
				'no_found_rows'     => true,
			)
		);

		_prime_post_caches( $query->posts, false, true );

		return $query;
	}

	/**
	 * Iterates over all redirects in pages, passing each page's IDs to the callback.
	 * Flushes the runtime cache after each page to keep memory bounded.
	 *
	 * @since 2.2.3
	 * @param callable $callback Receives a page's array of redirect IDs.
	 * @return void
	 */
	protected function each_redirect_page( $callback ) {
		$paged = 1;

		while ( true ) {
			$query = $this->query_redirects_page( $paged );

			if ( ! $query->have_posts() ) {
				break;
			}

			$callback( $query->posts );

			wp_cache_flush_runtime();

			++$paged;
		}
	}

	/**
	 * Returns a normalized array for a single redirect.
	 *
	 * @since 2.2.3
	 * @param array $redirect Redirect data from srm_get_redirect_data().
	 * @return array
	 */
	protected function normalize_redirect( $redirect ) {
		$normalized = array();

		foreach ( srm_get_export_fields() as $field ) {
			$normalized[ $field ] = $redirect[ $field ] ?? '';
		}

		return $normalized;
	}

	/**
	 * Streams redirects as CSV rows to php://output, one page at a time.
	 *
	 * @since 2.2.3
	 * @return void
	 */
	protected function export_csv() {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$handle = fopen( 'php://output', 'w' );

		if ( ! $handle ) {
			return;
		}

		// phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fputcsv -- Writing to php://output stream, not the filesystem.
		fputcsv( $handle, srm_get_export_fields(), ',', '"', '\\' );

		$this->each_redirect_page(
			function ( $redirect_ids ) use ( $handle ) {
				foreach ( $redirect_ids as $redirect_id ) {
					$redirect = srm_get_redirect_data( $redirect_id, true );
					fputcsv( $handle, array_map( 'srm_escape_csv', $this->normalize_redirect( $redirect ) ), ',', '"', '\\' );
				}
			}
		);
		// phpcs:enable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fputcsv

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $handle );
	}

	/**
	 * Streams redirects as a JSON array to php://output, one page at a time.
	 *
	 * @since 2.2.3
	 * @return void
	 */
	protected function export_json() {
		echo "[\n";

		$first = true;

		$this->each_redirect_page(
			function ( $redirect_ids ) use ( &$first ) {
				foreach ( $redirect_ids as $redirect_id ) {
					$redirect = srm_get_redirect_data( $redirect_id, true );
					// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
					$row = wp_json_encode( $this->normalize_redirect( $redirect ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
					$row = false === $row ? '{}' : $row;

					echo ( $first ? '' : ",\n" ) . preg_replace( '/^/m', '    ', $row ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Raw file download stream, not HTML output.
					$first = false;
				}
			}
		);

		echo "\n]\n";
	}
}
