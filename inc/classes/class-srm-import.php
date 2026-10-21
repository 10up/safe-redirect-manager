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
		add_action( 'manage_posts_extra_tablenav', array( $this, 'add_import_button' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_footer', array( $this, 'render_import_form' ) );
		add_action( 'admin_notices', array( $this, 'import_notice' ) );
		add_filter( 'removable_query_args', array( $this, 'removable_query_args' ) );
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
	 * Loads the importer script and styles on the redirect list, including
	 * when it is empty (SRM_Post_Type::load_resources() needs a post).
	 *
	 * @since 2.4.0
	 * @return void
	 */
	public function enqueue_assets() {
		if ( ! $this->is_redirect_list() ) {
			return;
		}

		wp_enqueue_style( 'redirectjs', SRM_PLUGIN_URL . 'assets/css/redirect.css', array(), SRM_VERSION );
		wp_enqueue_script( 'srm-import', SRM_PLUGIN_URL . 'assets/js/import.js', array( 'jquery' ), SRM_VERSION, true );
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
	 * assets/js/import.js moves it below the page title, toggles it, reads
	 * the picked or dropped CSV and sends it to ajax_import() in batches.
	 * Printed in the footer because the list table is already inside a form.
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
			<button type="button" class="srm-import__close">
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
	 * Imports one batch of CSV rows (the header line plus up to a few hundred
	 * rows) sent by assets/js/import.js, so no single request runs long.
	 *
	 * @since 2.4.0
	 * @return void
	 */
	public function ajax_import() {
		check_ajax_referer( 'srm_import' );

		if ( ! current_user_can( apply_filters( 'srm_restrict_to_capability', 'srm_manage_redirects' ) ) ) {
			wp_send_json_error( null, 403 );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Raw CSV; srm_create_redirect() sanitizes every field it stores.
		$csv = isset( $_POST['csv'] ) && is_string( $_POST['csv'] ) ? wp_unslash( $_POST['csv'] ) : '';

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- In-memory stream, not the filesystem.
		$handle = '' !== $csv ? fopen( 'php://temp', 'r+' ) : false;

		if ( ! $handle ) {
			wp_send_json_error( null, 400 );
		}

		// phpcs:disable WordPress.WP.AlternativeFunctions, WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fwrite -- In-memory stream, not the filesystem.
		fwrite( $handle, $csv );
		rewind( $handle );

		$result = srm_import_file(
			$handle,
			array(
				'source' => 'redirect_from',
				'target' => 'redirect_to',
				'regex'  => 'enable_regex',
				'code'   => 'status_code',
				'order'  => 'menu_order',
				'notes'  => 'notes',
			)
		);

		fclose( $handle );
		// phpcs:enable

		wp_send_json_success( $result );
	}

	/**
	 * Shows the result of the last import.
	 *
	 * @since 2.4.0
	 * @return void
	 */
	public function import_notice() {
		if ( ! $this->is_redirect_list() ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display-only result args set by assets/js/import.js.
		if ( ! isset( $_GET['srm_imported'], $_GET['srm_skipped'] ) ) {
			return;
		}

		$message = sprintf(
			/* translators: 1: number of imported redirects, 2: number of skipped rows */
			__( 'Import complete. Imported: %1$d. Skipped: %2$d.', 'safe-redirect-manager' ),
			absint( $_GET['srm_imported'] ),
			absint( $_GET['srm_skipped'] )
		);
		// phpcs:enable

		wp_admin_notice(
			esc_html( $message ),
			array(
				'type'        => 'success',
				'dismissible' => true,
			)
		);

		if ( (int) wp_count_posts( 'redirect_rule' )->publish > srm_get_max_redirects() ) {
			wp_admin_notice(
				esc_html(
					sprintf(
						/* translators: %d: maximum number of redirects */
						__( 'There are now more published redirects than the maximum of %d. Redirects beyond the maximum are not used.', 'safe-redirect-manager' ),
						srm_get_max_redirects()
					)
				),
				array( 'type' => 'warning' )
			);
		}
	}

	/**
	 * Removes the import result args from the address bar once shown.
	 *
	 * @since 2.4.0
	 * @param string[] $args Query args WordPress removes from admin URLs.
	 * @return string[]
	 */
	public function removable_query_args( $args ) {
		return array_merge( $args, array( 'srm_imported', 'srm_skipped' ) );
	}
}
