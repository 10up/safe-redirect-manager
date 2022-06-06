( function( $ ) {
	$( function() {
		const publishBtn = $('#publish');
		const fromRule = $('#srm_redirect_rule_from');

		fromRule.change(function(el) {
			publishBtn.prop('disabled', true);
			$.get(
				window.ajaxurl,
				{
					action: 'srm_validate_from_url',
					from: fromRule.val(),
					_wpnonce: $('#srm_redirect_nonce').val()
				}
			).done(function( data ) {
				if ( '1' === data ) {
					$('#message').html( '' ).hide();
					publishBtn.prop('disabled', false);
				} else if ( '0' === data ) {
					$('#message').html( `<p>${redirectValidation.urlError}</p>` ).show();
				} else {
					$('#message').html( `<p>${redirectValidation.fail.replace( '%s', data )}</p>` ).show();
				}
			});
		});
	} );
}( jQuery ) );