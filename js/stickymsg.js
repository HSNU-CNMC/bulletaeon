jQuery(document).ready(function($) {
	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	$('input[type="radio"][name="sticky"]').click(function () {
		alert($(this).val());
		var data = {
			action: 'update_sticky_msg',
		};
		$.post(ajaxurl, data, function(response) {
			alert('Got this from the server: ' + response);
		});
	});
});
