( function ($) {
	$( function () {
		var wpInlineEditor = inlineEditPost.edit;

		inlineEditPost.edit = function (id) {

			wpInlineEditor.apply(this, arguments);

			var postId = 0;
			if (typeof (id) == 'object') {
				postId = parseInt(this.getId(id));
			}

			if (postId != 0) {
				var editRow = $('#edit-' + postId);
				var postRow = $('#post-' + postId);

				var statusCode = $('.srm_redirect_rule_status_code', postRow).text();
				var forceHttps = $('.srm_redirect_rule_force_https', postRow).text();

				$('select[name="srm_redirect_rule_status_code"]', editRow).val(statusCode);
				if ('✓' === forceHttps) {
					$('input[name="srm_redirect_rule_force_https"]', editRow).prop('checked', true);
				}
			}
		}
	} );
}(jQuery) );