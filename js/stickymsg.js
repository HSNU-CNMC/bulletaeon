jQuery(document).ready(function($) {
	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	$('input[type="radio"][name="sticky"]').click(function () {
		var data = {
			action: 'update_sticky_msg',
			set_sticky: $(this).val()
		};
		$.post(ajaxurl, data, function(response) {
			alert(response);
		});
	});
});
