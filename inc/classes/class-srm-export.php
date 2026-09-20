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
		add_action( 'wp_ajax_srm_export', array( $this, 'handle_export' ) );
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
					'action'        => 'srm_export',
					'export_format' => $format,
				),
				admin_url( 'admin-ajax.php' )
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
		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'srm_export' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'safe-redirect-manager' ) );
		}

		if ( ! current_user_can( apply_filters( 'srm_restrict_to_capability', 'srm_manage_redirects' ) ) ) {
			wp_die( esc_html__( 'You do not have permission to export redirects.', 'safe-redirect-manager' ) );
		}

		$export_format = sanitize_key( wp_unslash( $_GET['export_format'] ?? 'csv' ) );
		if ( ! in_array( $export_format, $this->supported_formats, true ) ) {
			$export_format = 'csv';
		}

		// Large redirect sets can take a while to stream; avoid the default 30s FastCGI cutoff.
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( apply_filters( 'srm_export_time_limit', 5 * MINUTE_IN_SECONDS ) );
		}

		if ( ! srm_query_redirect_page( 1 )->have_posts() ) {
			wp_die( esc_html__( 'There are no redirects to export.', 'safe-redirect-manager' ) );
		}

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

		srm_each_export_redirect(
			function ( $row ) use ( $handle ) {
				fputcsv( $handle, array_map( 'srm_escape_csv', $row ), ',', '"', '\\' );
			}
		);
		// phpcs:enable

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $handle );
	}

	/**
	 * Builds the full redirect list (paged internally to bound memory) and
	 * outputs it as a single JSON array.
	 *
	 * @since 2.2.3
	 * @return void
	 */
	protected function export_json() {
		$results = array();

		srm_each_export_redirect(
			function ( $row ) use ( &$results ) {
				$results[] = $row;
			}
		);

		$json = wp_json_encode( $results );

		echo false === $json ? '[]' : $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Raw file download stream, not HTML output.
	}
}
