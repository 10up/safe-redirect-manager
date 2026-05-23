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
	 * Renders the "Export CSV" button after the filter bar in the redirect list table.
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

		$url = wp_nonce_url(
			add_query_arg(
				array(
					'post_type' => 'redirect_rule',
					'action'    => 'srm_export',
				),
				admin_url( 'edit.php' )
			),
			'srm_export'
		);
		?>
		<div class="alignleft actions">
			<a href="<?php echo esc_url( $url ); ?>" class="button">
				<?php esc_html_e( 'Export CSV', 'safe-redirect-manager' ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Handles the CSV export download request and streams the file.
	 *
	 * @since 2.2.3
	 * @return void
	 */
	public function handle_export() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Nonce verified below.
		$action    = sanitize_key( wp_unslash( $_GET['action'] ?? '' ) );
		$post_type = sanitize_key( wp_unslash( $_GET['post_type'] ?? '' ) );
		$nonce     = sanitize_key( wp_unslash( $_GET['_wpnonce'] ?? '' ) );
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

		$redirects = srm_get_redirects( array( 'post_status' => 'any' ), true );

		// Clear any existing output buffers to prevent content from corrupting the download.
		while ( ob_get_level() ) {
			ob_end_clean();
		}

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="safe-redirect-manager-export-' . gmdate( 'Y-m-d' ) . '.csv"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$handle = fopen( 'php://output', 'w' );

		// phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fputcsv -- Writing to php://output stream, not the filesystem.
		fputcsv( $handle, array( 'ID', 'Redirect From', 'Redirect To', 'HTTP Status Code', 'Enable Regex', 'Force HTTPS', 'Message', 'Status' ) );

		foreach ( $redirects as $redirect ) {
			fputcsv(
				$handle,
				array(
					$redirect['ID'],
					$this->escape_csv( $redirect['redirect_from'] ),
					$this->escape_csv( $redirect['redirect_to'] ),
					$redirect['status_code'],
					$redirect['enable_regex'] ? '1' : '0',
					$redirect['force_https'] ? '1' : '0',
					$this->escape_csv( $redirect['message'] ),
					$redirect['post_status'],
				)
			);
		}
		// phpcs:enable WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fputcsv

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $handle );
		exit;
	}

	/**
	 * Escapes a CSV field value against formula injection.
	 *
	 * @since 2.2.3
	 * @param string $value Field value.
	 * @return string
	 */
	private function escape_csv( $value ) {
		$value = (string) $value;
		if ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			$value = "'" . $value;
		}
		return $value;
	}
}
