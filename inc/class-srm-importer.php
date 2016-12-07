<?php

class SRM_Importer {

	/**
	 * Uploaded file ID.
	 *
	 * @since 1.7.6
	 *
	 * @access private
	 * @var int
	 */
	private $_file_id;

	/**
	 * The file handle.
	 *
	 * @since 1.7.6
	 *
	 * @access private
	 * @var resource
	 */
	private $_file_handle;

	/**
	 * CSV delimiter character.
	 *
	 * @since 1.7.6
	 *
	 * @access private
	 * @var string
	 */
	private $_csv_delimiter = ',';

	/**
	 * CSV enclosure character.
	 *
	 * @since 1.7.6
	 *
	 * @access private
	 * @var string
	 */
	private $_csv_enclouse = '"';

	/**
	 * Array of CSV headers.
	 *
	 * @since 1.7.6
	 *
	 * @access private
	 * @var array
	 */
	private $_csv_headers;

	/**
	 * Processes importer page.
	 *
	 * @since 1.7.6
	 *
	 * @access public
	 */
	public function process() {
		$this->_header();

		$step = filter_input( INPUT_GET, 'step', FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 0, 'default' => 0 ) ) );
		switch ( $step ) {
			case 0:
				$this->_render_upload_page();
				break;
			case 1:
				check_admin_referer( 'import-upload' );
				if ( $this->_handle_upload() ) {
					$this->_handle_options();
					fclose( $this->_file_handle );
				}
				break;
			case 2:
				check_admin_referer( 'import-redirects' );
				$this->_import_file();
				break;
		}

		$this->_footer();
	}

	/**
	 * Renders upload page.
	 *
	 * @since 1.7.6
	 *
	 * @access private
	 */
	private function _render_upload_page() {
		/**
		 * @see wp_import_upload_form()
		 */
		$bytes = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
		$size = size_format( $bytes );
		$upload_dir = wp_upload_dir();
		
		echo '<div class="narrow">';
			echo '<p>', __( 'Howdy! Upload your CSV file and we&#8217;ll import redirects into this site.', 'safe-redirect-manager' ), '</p>';
			echo '<p>', __( 'Choose a CSV (.csv) file to upload, then click Upload file and import.', 'safe-redirect-manager' ), '</p>';

			if ( ! empty( $upload_dir['error'] ) ) :
				echo '<div class="error">'; 
					echo '<p>', __( 'Before you can upload your import file, you will need to fix the following error:' ), '</p>';
					echo '<p><strong>', $upload_dir['error'], '</strong></p>';
				echo '</div>';
			else :
				echo '<form enctype="multipart/form-data" id="import-upload-form" method="post" class="wp-upload-form" action="', esc_url( add_query_arg( 'step', 1 ) ), '">';
					wp_nonce_field( 'import-upload' );
					echo '<input type="hidden" name="action" value="save">';
					echo '<input type="hidden" name="max_file_size" value="', esc_attr( $bytes ), '">';
					
					echo '<p>';
						echo '<label for="upload">', __( 'Choose a file from your computer:' ), '</label> (', sprintf( __( 'Maximum size: %s' ), $size ), ') ';
						echo '<input type="file" id="upload" name="import" size="25">';
					echo '</p>';
					
					echo '<p>';
						echo '<label for="delimiter">', __( 'Enter field delimiter:', 'safe-redirect-manager' ), '</label> ';
						echo '<input type="text" id="delimiter" name="delimiter" size="1" maxlength="1" value=",">';
					echo '</p>';

					echo '<p>';
						echo '<label for="enclosure">', __( 'Enter field enclosure:', 'safe-redirect-manager' ), '</label> ';
						echo '<input type="text" id="enclosure" name="enclosure" size="1" maxlength="1" value="&quot;">';
					echo '</p>';

					submit_button( __( 'Upload file and import' ), 'button' );
				echo '</form>';
			endif;
		echo '</div>';
	}

	/**
	 * Handles the upload and initial parsing of the file to prepare for displaying column mapping options.
	 *
	 * @since 1.7.6
	 *
	 * @access private
	 * @return bool FALSE if error uploading or invalid file, otherwise TRUE.
	 */
	private function _handle_upload() {
		// handle uploaded file
		$file = wp_import_handle_upload();
		if ( isset( $file['error'] ) ) {
			$this->_display_error( esc_html( $file['error'] ) );
			return false;
		} elseif ( ! file_exists( $file['file'] ) || ! is_readable( $file['file'] ) ) {
			$this->_display_error( sprintf(
				esc_html__( 'The export file could not be found or can not be read at %s. It is likely that this was caused by a permissions problem.', 'safe-redirect-manager' ),
				'<code>' . esc_html( $file['file'] ) . '</code>'
			) );
			return false;
		}

		// enable line endings auto detection
		@ini_set( 'auto_detect_line_endings', true );

		// fetch import info
		$this->_file_id = (int) $file['id'];
		$this->_file_handle = fopen( $file['file'], 'rb' );
		$this->_csv_delimiter = isset( $_POST['delimiter'] ) ? substr( stripslashes( $_POST['delimiter'] ), 0, 1 ) : ',';
		$this->_csv_enclouse = isset( $_POST['enclosure'] ) ? substr( stripslashes( $_POST['enclosure'] ), 0, 1 ) : '"';
		$this->_csv_headers = fgetcsv( $this->_file_handle, 0, $this->_csv_delimiter, $this->_csv_enclouse );

		return true;
	}

	/**
	 * Display pre-import options to map columns.
	 *
	 * @since 1.7.6
	 *
	 * @access private
	 */
	private function _handle_options() {
		$column_index = 0;
		$columns = array(
			'source' => __( 'Source URL column', 'safe-redirect-manager' ),
			'target' => __( 'Target URL column', 'safe-redirect-manager' ),
			'regex'  => __( 'Regex flag column', 'safe-redirect-manager' ),
			'code'   => __( 'HTTP Code column', 'safe-redirect-manager' ),
		);

		echo '<form action="', add_query_arg( 'step', 2 ), '" method="post">';
			wp_nonce_field( 'import-redirects' );
			echo '<input type="hidden" name="import_id" value="', esc_attr( $this->_file_id ), '">';
			echo '<input type="hidden" name="import_delimiter" value="', esc_attr( $this->_csv_delimiter ), '">';
			echo '<input type="hidden" name="import_enclosure" value="', esc_attr( $this->_csv_enclouse ), '">';

			echo '<h3>', esc_html__( 'Assign Columns', 'safe-redirect-manager' ), '</h3>';
			echo '<p>', esc_html__( 'To properly import redirect rules, you may want to remap the columns of the imported file.', 'safe-redirect-manager' ), '</p>';

			echo '<ol id="authors">';
				foreach ( $columns as $column_id => $column_label ) :
					echo '<li>';
						echo '<strong>', esc_html( $column_label ), ':</strong> ';
						echo '<select name="import_column[', esc_attr( $column_id ), ']">';
							foreach ( $this->_csv_headers as $header_index => $header_value ) :
								echo '<option ', selected( $header_index, $column_index, false ), '>', esc_html( $header_value ), '</option>';
							endforeach;
						echo '</select>';
					echo '</li>';
					$column_index++;
				endforeach;
			echo '</ol>';

			echo '<p class="submit"><input type="submit" class="button" value="', esc_attr__( 'Submit', 'safe-redirect-manager' ), '"></p>';
		echo '</form>';
	}

	/**
	 * Imports file.
	 *
	 * @since 1.7.6
	 *
	 * @access private
	 * @global SRM_Safe_Redirect_Manager $safe_redirect_manager The plugin instance.
	 */
	private function _import_file() {
		global $safe_redirect_manager;

		$this->_file_id = filter_input( INPUT_POST, 'import_id', FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1, 'default' => 0 ) ) );
		$file = get_attached_file( $this->_file_id );
		if ( ! is_file( $file ) ) {
			$this->_display_error( esc_html__( 'The file does not exist, please try again.', 'safe-redirect-manager' ) );
			return;
		}

		set_time_limit( 0 );

		$args = isset( $_POST['import_column'] ) ? (array) $_POST['import_column'] : array();
		$args['delimiter'] = filter_input( INPUT_POST, 'import_delimiter', FILTER_DEFAULT );
		$args['enclosure'] = filter_input( INPUT_POST, 'import_enclosure', FILTER_DEFAULT );

		$imported = $safe_redirect_manager->import_file( $file, $args );
		if ( ! $imported ) {
			$this->_display_error( esc_html__( 'There was an error during redirects importing.', 'safe-redirect-manager' ) );
			return;
		}

		echo '<p>';
			printf( 
				esc_html__( 'All done. %d created, %d skipped.', 'safe-redirect-manager' ),
				$imported['created'],
				$imported['skipped']
			);
			echo ' <a href="', esc_url( admin_url( 'edit.php?post_type=redirect_rule' ) ), '">', __( 'Have fun!', 'safe-redirect-manager' ), '</a>';
		echo '</p>';
	}

	/**
	 * Renders error message.
	 *
	 * @since 1.7.6
	 *
	 * @access private
	 * @param string $message The error message.
	 */
	private function _display_error( $message ) {
		echo '<p>';
			echo '<strong>', __( 'Sorry, there has been an error.', 'safe-redirect-manager' ), '</strong><br>', $message;
		echo '</p>';
	}

	/**
	 * Renders page header.
	 *
	 * @since 1.7.6
	 *
	 * @access private
	 */
	private function _header() {
		echo '<div class="wrap">';
			echo '<h2>' . __( 'Import Redirects', 'safe-redirect-manager' ) . '</h2>';
	}

	/**
	 * Renders page footer.
	 *
	 * @since 1.7.6
	 *
	 * @access private
	 */
	private function _footer() {
		echo '</div>';
	}

}