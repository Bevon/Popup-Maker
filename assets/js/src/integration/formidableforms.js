/***********************************
 * Copyright (c) 2020, Popup Maker
 **********************************/

{
	const formProvider = "formidableforms";
	const $ = window.jQuery;

	$(document).on("frmFormComplete", function(event, form, response) {
		const $form = $(form);
		const formId = $form.find('input[name="form_id"]').val();

		// All the magic happens here.
		window.PUM.integrations.formSubmission($form, {
			formProvider,
			formId,
			extras: {
				response
			}
		});
	});
}
