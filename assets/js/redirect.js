( function( $ ) {
	$( function() {
		const publishBtn = $('#publish');
		const fromRule = $('#srm_redirect_rule_from');
		const toRule = $('#srm_redirect_rule_to');
		let timer = 0;
		let currentRequest = null;

		fromRule.on('input',function(el) {
			publishBtn.prop('disabled', true); // Disable submit button.
			fromRule.addClass('ui-autocomplete-loading'); // Add loader.
			if (timer) {
				clearTimeout(timer); // Clear the time after function execution.
			}
			timer = setTimeout( validateUrl, 850); // Wait for 0.85 seconds.
		});

		// Show autocomplete for the 'Redirect To:' field.
		toRule.autocomplete({
			minLength: 2,
			classes: {
				"ui-autocomplete": "srm-autocomplete"
			},
			source: function(request, response) {
				$.ajax({
					dataType: 'json',
					url: redirectValidation.ajax_url,
					data: {
						term: request.term,
						action: 'srm_autocomplete',
						security: redirectValidation.ajax_nonce
					},
					success: function(data) {
						response(data);
					}
				});
			},
			select: function( event, ui ) {
				toRule.val( ui.item.relative_url );
				return false;
			}
		})
		.autocomplete("instance")._renderItem = function (ul, item) {
			return $(`<li class="srm-autocomplete__item">`)
				.append(`
					<div class="srm-autocomplete__item-title">${item.post_title}</div>
					<div class="srm-autocomplete__item-url">${item.relative_url}</div>
					<div class="srm-autocomplete__item-type">${item.post_type}</div>
				`)
				.appendTo(ul);
		};

		// Disable the 'Redirect To:' field if a 4xx status code is set.
		const statusSelect = $('#srm_redirect_rule_status_code');
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

		/**
		 * Validate URL for 'Redirect From' field.
		 */
		function validateUrl() {
			currentRequest = $.ajax({
				url: window.ajaxurl,
				method : 'GET',
				data : {
					action: 'srm_validate_from_url',
					from: fromRule.val(),
					_wpnonce: $('#srm_redirect_nonce').val()
				},
				beforeSend : function() {
					if ( currentRequest !== null ) {
						currentRequest.abort();
					}
				},
				success: function( data ) {

					// Remove loader.
					fromRule.removeClass( 'ui-autocomplete-loading' );

					if ( '1' === data ) {
						$('#message').html( '' ).hide();
						publishBtn.prop('disabled', false);
					} else if ( '0' === data ) {
						$('#message').html( `<p>${redirectValidation.urlError}</p>` ).show();
					} else {
						$('#message').html( `<p>${redirectValidation.fail.replace( '%s', data )}</p>` ).show();
					}
				}
			});
		}

	} );
}( jQuery ) );
