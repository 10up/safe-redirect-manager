( function ($) {
	$( '#srm-export-format' ).on( 'change', function () {
		$( '#srm-export-btn' ).attr( 'href', $( this ).val() );
	} );
}(jQuery) );
