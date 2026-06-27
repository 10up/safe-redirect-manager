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
	 * Supported export formats. Add new format keys here as they are implemented.
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

		// Hide the button when there are no redirects to export. wp_count_posts() is cached.
		if ( ! array_sum( (array) wp_count_posts( 'redirect_rule' ) ) ) {
			return;
		}

		$format_labels = array(
			'csv'  => __( 'CSV', 'safe-redirect-manager' ),
			'json' => __( 'JSON', 'safe-redirect-manager' ),
		);
		?>
		<div class="alignleft actions">
			<label for="srm-export-format" class="screen-reader-text">
				<?php esc_html_e( 'Export format', 'safe-redirect-manager' ); ?>
			</label>
			<select id="srm-export-format">
				<?php foreach ( $this->supported_formats as $format ) : ?>
					<option value="<?php echo esc_attr( $this->get_export_url( $format ) ); ?>">
						<?php echo esc_html( $format_labels[ $format ] ?? strtoupper( $format ) ); ?>
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

		// Validate format against whitelist; fall back to csv for unknown values.
		if ( ! in_array( $export_format, $this->supported_formats, true ) ) {
			$export_format = 'csv';
		}

		// Raise the redirect cap so the export includes every record. Filterable via srm_export_max_redirects.
		$export_limit = apply_filters( 'srm_export_max_redirects', PHP_INT_MAX );
		add_filter( 'srm_max_redirects', fn() => $export_limit );
		$redirects = srm_get_redirects( array( 'post_status' => 'any' ), true );

		delete_transient( '_srm_redirects_' . $export_limit );

		if ( empty( $redirects ) ) {
			wp_die( esc_html__( 'There are no redirects to export.', 'safe-redirect-manager' ) );
		}

		// Clear any existing output buffers to prevent content from corrupting the download.
		while ( ob_get_level() ) {
			ob_end_clean();
		}

		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		switch ( $export_format ) {
			case 'json':
				header( 'Content-Type: application/json; charset=utf-8' );
				header( 'Content-Disposition: attachment; filename="safe-redirect-manager-export-' . gmdate( 'Y-m-d' ) . '.json"' );
				$this->export_json( $redirects );
				break;
			default:
				header( 'Content-Type: text/csv; charset=utf-8' );
				header( 'Content-Disposition: attachment; filename="safe-redirect-manager-export-' . gmdate( 'Y-m-d' ) . '.csv"' );
				$this->export_csv( $redirects );
		}

		exit;
	}

	/**
	 * Returns a normalized array for a single redirect.
	 *
	 * @since 2.2.3
	 * @param array $redirect Redirect data from srm_get_redirects().
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
	 * Outputs redirects as CSV rows to php://output.
	 *
	 * @since 2.2.3
	 * @param array[] $redirects Redirect data from srm_get_redirects().
	 * @return void
	 */
	protected function export_csv( $redirects ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$handle = fopen( 'php://output', 'w' );

		// phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fputcsv -- Writing to php://output stream, not the filesystem.
		fputcsv( $handle, srm_get_export_fields() );

		foreach ( $redirects as $redirect ) {
			fputcsv( $handle, array_map( 'srm_escape_csv', $this->normalize_redirect( $redirect ) ) );
		}
		// phpcs:enable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fputcsv

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $handle );
	}

	/**
	 * Outputs redirects as a JSON array to php://output.
	 *
	 * @since 2.2.3
	 * @param array[] $redirects Redirect data from srm_get_redirects().
	 * @return void
	 */
	protected function export_json( $redirects ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
		echo wp_json_encode( array_map( array( $this, 'normalize_redirect' ), $redirects ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}
}
