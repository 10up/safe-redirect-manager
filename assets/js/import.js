( function ($) {
	var BATCH_SIZE = 100;

	var $wrap   = $( '#srm-import' );
	var $button = $( '#srm-import-btn' );
	var $file   = $( '#srm-import-file' );
	var $status = $wrap.find( '.srm-import__status' );
	var $text   = $status.find( '.srm-import__status-text' );
	var $bar    = $status.find( '.srm-import__progress div' );

	function toggle( show ) {
		$wrap.prop( 'hidden', ! show );
		$button.attr( 'aria-expanded', String( show ) );
	}

	function showError( message ) {
		$wrap.removeClass( 'is-uploading' ).addClass( 'has-error' );
		$text.text( message );
	}

	function showProgress( done, total ) {
		$text.text(
			$status.data( 'importing' )
				.replace( '%1$s', done.toLocaleString() )
				.replace( '%2$s', total.toLocaleString() )
		);
		$bar.css( 'width', Math.round( done / total * 100 ) + '%' );
	}

	/**
	 * Splits CSV text into records the way fgetcsv() reads them: a quote only
	 * opens a quoted field at the start of a field, "" and \" inside quotes
	 * are literal, and line breaks inside quotes stay in the record. Blank
	 * lines are dropped.
	 */
	function splitRecords( text ) {
		var records  = [];
		var start    = 0;
		var inQuotes = false;
		var i;

		text = text.replace( /^\uFEFF/, '' );

		for ( i = 0; i < text.length; i++ ) {
			if ( inQuotes ) {
				if ( '\\' === text[ i ] || ( '"' === text[ i ] && '"' === text[ i + 1 ] ) ) {
					i++;
				} else if ( '"' === text[ i ] ) {
					inQuotes = false;
				}
			} else if ( '"' === text[ i ] && ( i === start || ',' === text[ i - 1 ] ) ) {
				inQuotes = true;
			} else if ( '\n' === text[ i ] ) {
				records.push( text.slice( start, i ) );
				start = i + 1;
			}
		}
		records.push( text.slice( start ) );

		return records.map( function ( record ) {
			return record.replace( /\r$/, '' );
		} ).filter( function ( record ) {
			return '' !== record.trim();
		} );
	}

	// Sends the rows in batches so no request runs into a server timeout.
	function importRows( header, rows ) {
		var created = 0;
		var skipped = 0;
		var done    = 0;

		function next() {
			var batch = rows.slice( done, done + BATCH_SIZE );

			if ( ! batch.length ) {
				window.location.href = $wrap.data( 'done-url' ) + '&srm_imported=' + created + '&srm_skipped=' + skipped;
				return;
			}

			$.post( $wrap.data( 'ajax-url' ), {
				action: 'srm_import',
				_ajax_nonce: $wrap.data( 'nonce' ),
				csv: header + '\n' + batch.join( '\n' ) + '\n'
			} ).done( function ( response ) {
				if ( ! response || ! response.success ) {
					showError( $status.data( 'failed' ) );
					return;
				}

				created += response.data.created;
				skipped += response.data.skipped;
				done    += batch.length;
				showProgress( done, rows.length );
				next();
			} ).fail( function () {
				showError( $status.data( 'failed' ) );
			} );
		}

		showProgress( 0, rows.length );
		next();
	}

	function upload( files ) {
		var file   = files && files[0];
		var reader = new FileReader();

		if ( ! file || $wrap.hasClass( 'is-uploading' ) ) {
			return;
		}

		if ( ! /\.csv$/i.test( file.name ) ) {
			showError( $status.data( 'invalid' ) );
			return;
		}

		$wrap.removeClass( 'has-error' ).addClass( 'is-uploading' );
		$status.find( '.srm-import__filename' ).text( file.name );

		reader.onload = function () {
			var records = splitRecords( String( reader.result ) );
			var columns = ( records.shift() || '' ).split( ',' ).map( function ( column ) {
				return column.trim().replace( /^"|"$/g, '' );
			} );

			if ( -1 === columns.indexOf( 'redirect_from' ) || -1 === columns.indexOf( 'redirect_to' ) || ! records.length ) {
				showError( $status.data( 'invalid' ) );
				return;
			}

			// Send the cleaned-up header so PHP matches the same column names.
			importRows( columns.join( ',' ), records );
		};
		reader.onerror = function () {
			showError( $status.data( 'failed' ) );
		};
		reader.readAsText( file );
	}

	// Show the uploader below the page title, like Media > Add New.
	$wrap.insertAfter( '.wp-header-end' );

	$button.on( 'click', function () {
		toggle( $wrap.prop( 'hidden' ) );
	} );

	$wrap.find( '.srm-import__close' ).on( 'click', function () {
		toggle( false );
		$button.trigger( 'focus' );
	} );

	$wrap.find( '.srm-import__select' ).on( 'click', function () {
		$file.trigger( 'click' );
	} );

	$file.on( 'change', function () {
		upload( this.files );
		this.value = '';
	} );

	// Like the Media uploader, a file dropped outside the zone must not open in the browser.
	$( document ).on( 'dragover drop', function ( event ) {
		if ( ! $wrap.prop( 'hidden' ) ) {
			event.preventDefault();
		}
	} );

	$wrap.on( 'dragover', function ( event ) {
		event.preventDefault();
		$wrap.addClass( 'drag-over' );
	} ).on( 'dragleave drop', function () {
		$wrap.removeClass( 'drag-over' );
	} ).on( 'drop', function ( event ) {
		event.preventDefault();
		upload( event.originalEvent.dataTransfer.files );
	} );
}(jQuery) );
