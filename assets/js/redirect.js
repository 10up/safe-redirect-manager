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

		// Disable the 'Redirect To:' field if a 4xx status code is set.
		const statusSelect = $('#srm_redirect_rule_status_code');
		const toRule = $('#srm_redirect_rule_to');
		const disabledMessage = $('#srm_to_disabled_message');

		statusSelect.change(maybeDisableToRule);
		maybeDisableToRule();

		function maybeDisableToRule() {
			const status = Number.parseInt(statusSelect.val());
			if ([403, 404, 410].includes(status)) {
				toRule.prop('disabled', 'disabled');
				disabledMessage.show();
			} else {
				toRule.prop('disabled', '');
				disabledMessage.hide();
			}
		}

		// Disable and hide the 'Message' field unless 403 or 410 is selected.
		const messageContainer = $('#srm_redirect_rule_message_container');
		const message = $('#srm_redirect_rule_message');

		statusSelect.change(maybeHideMessage);
		maybeHideMessage();

		function maybeHideMessage() {
			const status = Number.parseInt(statusSelect.val());
			if ([403, 410].includes(status)) {
				message.prop('disabled', '');
				messageContainer.show();
			} else {
				message.prop('disabled', 'disabled');
				messageContainer.hide();
			}
		}

	} );
}( jQuery ) );