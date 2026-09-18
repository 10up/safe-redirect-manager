( function ($) {
	$( '#srm-export-format' ).on( 'change', function () {
		var urls = ( window.srmExport && window.srmExport.urls ) ? window.srmExport.urls : {};
		var url  = urls[ $( this ).val() ];

		if ( url ) {
			$( '#srm-export-btn' ).attr( 'href', url );
		}
	} );
}(jQuery) );
