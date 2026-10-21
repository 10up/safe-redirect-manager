<?php
/**
 * Import functionality for Safe Redirect Manager.
 *
 * @package safe-redirect-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Import class.
 */
class SRM_Import {

	/**
	 * Factory method.
	 *
	 * @since 2.4.0
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
	 * Sets up import hooks.
	 *
	 * @since 2.4.0
	 * @return void
	 */
	public function setup() {
		add_action( 'wp_ajax_srm_import', array( $this, 'ajax_import' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'manage_posts_extra_tablenav', array( $this, 'add_import_button' ) );
		add_action( 'admin_footer', array( $this, 'render_import_form' ) );
		add_action( 'admin_notices', array( $this, 'import_notice' ) );
	}

	/**
	 * Whether the current screen is the redirect list and the user may manage redirects.
	 *
	 * @since 2.4.0
	 * @return bool
	 */
	protected function is_redirect_list() {
		$screen = get_current_screen();

		return $screen && 'edit-redirect_rule' === $screen->id && current_user_can( apply_filters( 'srm_restrict_to_capability', 'srm_manage_redirects' ) );
	}

	/**
	 * Loads the importer assets on the redirect list, including when it is empty.
	 *
	 * @since 2.4.0
	 * @return void
	 */
	public function enqueue_assets() {
		if ( $this->is_redirect_list() ) {
			wp_enqueue_style( 'redirectjs', SRM_PLUGIN_URL . 'assets/css/redirect.css', array(), SRM_VERSION );
			wp_enqueue_script( 'srm-import', SRM_PLUGIN_URL . 'assets/js/import.js', array( 'jquery' ), SRM_VERSION, true );
		}
	}

	/**
	 * Renders the import button after the filter bar in the redirect list table.
	 *
	 * @since 2.4.0
	 * @param string $which Position in the table ('top' or 'bottom').
	 * @return void
	 */
	public function add_import_button( $which ) {
		if ( 'top' !== $which || ! $this->is_redirect_list() ) {
			return;
		}
		?>
		<div class="alignleft actions">
			<button type="button" id="srm-import-btn" class="button" aria-expanded="false" aria-controls="srm-import">
				<?php esc_html_e( 'Import', 'safe-redirect-manager' ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Renders the hidden drop zone, styled like the Media Library uploader.
	 * Printed in the footer because the list table is already inside a form;
	 * assets/js/import.js moves it below the page title.
	 *
	 * @since 2.4.0
	 * @return void
	 */
	public function render_import_form() {
		if ( ! $this->is_redirect_list() ) {
			return;
		}
		?>
		<div id="srm-import" class="srm-import" hidden
			data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'srm_import' ) ); ?>"
			data-done-url="<?php echo esc_url( admin_url( 'edit.php?post_type=redirect_rule' ) ); ?>">
			<button type="button" class="srm-import__close dashicons">
				<span class="screen-reader-text"><?php esc_html_e( 'Close importer', 'safe-redirect-manager' ); ?></span>
			</button>
			<input type="file" id="srm-import-file" accept=".csv,text/csv" class="screen-reader-text" />
			<div class="srm-import__ui">
				<h2><?php esc_html_e( 'Drop a CSV file to import', 'safe-redirect-manager' ); ?></h2>
				<p><?php echo esc_html_x( 'or', 'Uploader: Drop a file here - or - Select File', 'safe-redirect-manager' ); ?></p>
				<button type="button" class="button button-hero srm-import__select"><?php esc_html_e( 'Select File', 'safe-redirect-manager' ); ?></button>
			</div>
			<div class="srm-import__status"
				<?php /* translators: 1: number of rows imported so far, 2: total number of rows */ ?>
				data-importing="<?php esc_attr_e( 'Importing %1$s of %2$s…', 'safe-redirect-manager' ); ?>"
				data-invalid="<?php esc_attr_e( 'Please choose a CSV file exported from Safe Redirect Manager.', 'safe-redirect-manager' ); ?>"
				data-failed="<?php esc_attr_e( 'The import did not finish. Please try again; rows already imported are updated, not duplicated.', 'safe-redirect-manager' ); ?>">
				<h2 class="srm-import__status-text" role="status"></h2>
				<p class="srm-import__filename"></p>
				<div class="srm-import__progress"><div></div></div>
			</div>
			<p class="srm-import__help"><?php esc_html_e( 'Use a CSV file exported from Safe Redirect Manager.', 'safe-redirect-manager' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Imports one batch of CSV rows sent by assets/js/import.js.
	 *
	 * @since 2.4.0
	 * @return void
	 */
	public function ajax_import() {
		check_ajax_referer( 'srm_import' );

		if ( ! current_user_can( apply_filters( 'srm_restrict_to_capability', 'srm_manage_redirects' ) ) ) {
			wp_send_json_error( null, 403 );
		}

		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Raw CSV; srm_create_redirect() sanitizes every field it stores.
		$header = isset( $_POST['header'] ) && is_string( $_POST['header'] ) ? str_getcsv( wp_unslash( $_POST['header'] ), ',', '"', '\\' ) : array();
		$rows   = isset( $_POST['rows'] ) ? array_filter( (array) wp_unslash( $_POST['rows'] ), 'is_string' ) : array();
		// phpcs:enable

		wp_send_json_success( $this->import_rows( $header, $rows ) );
	}

	/**
	 * Creates or updates a redirect for each CSV row in the export format,
	 * keeping its status and regex source.
	 *
	 * @since 2.4.0
	 * @param string[] $header Column names.
	 * @param string[] $rows   CSV rows.
	 * @return array Counts of 'created' (created or updated) and 'skipped' rows.
	 */
	public function import_rows( $header, $rows ) {
		$created = 0;
		$skipped = 0;

		foreach ( $rows as $row ) {
			$row  = str_getcsv( $row, ',', '"', '\\' );
			$rule = count( $row ) === count( $header ) ? array_combine( $header, $row ) : array();
			$to   = $rule['redirect_to'] ?? '';
			$code = absint( $rule['status_code'] ?? 0 ) ? absint( $rule['status_code'] ) : 302;

			// 403, 404 and 410 rules have no destination.
			if ( empty( $rule['redirect_from'] ) || ( '' === $to && ! in_array( $code, array( 403, 404, 410 ), true ) ) ) {
				++$skipped;
				continue;
			}

			$regex  = filter_var( $rule['enable_regex'] ?? false, FILTER_VALIDATE_BOOLEAN );
			$status = in_array( $rule['post_status'] ?? '', array( 'publish', 'draft', 'pending', 'private' ), true ) ? $rule['post_status'] : 'publish';
			$id     = srm_create_redirect( $rule['redirect_from'], $to, $code, $regex, $status, 0, $rule['notes'] ?? '', get_current_user_id(), true );

			if ( is_wp_error( $id ) ) {
				++$skipped;
				continue;
			}

			// srm_create_redirect() stores '/' for an empty destination.
			if ( '' === $to ) {
				delete_post_meta( $id, '_redirect_rule_to' );
				srm_flush_cache();
			}

			++$created;
		}

		return array(
			'created' => $created,
			'skipped' => $skipped,
		);
	}

	/**
	 * Shows the result of the last import.
	 *
	 * @since 2.4.0
	 * @return void
	 */
	public function import_notice() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display-only counts set by assets/js/import.js.
		if ( ! $this->is_redirect_list() || ! isset( $_GET['srm_imported'], $_GET['srm_skipped'] ) ) {
			return;
		}

		wp_admin_notice(
			esc_html(
				sprintf(
					/* translators: 1: number of imported redirects, 2: number of skipped rows */
					__( 'Import complete. Imported: %1$d. Skipped: %2$d.', 'safe-redirect-manager' ),
					absint( $_GET['srm_imported'] ),
					absint( $_GET['srm_skipped'] )
				)
			),
			array( 'type' => 'success' )
		);
		// phpcs:enable
	}
}
