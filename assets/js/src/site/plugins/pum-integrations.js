/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/
(function ($) {
    "use strict";

	window.PUM = window.PUM || {};
	window.PUM.integrations = window.PUM.integrations || {};

	$.extend(window.PUM.integrations, {
		formSubmission: function (form, args) {
			var $popup = PUM.getPopup(form);

			args = $.extend({
				popup: $popup,
				formProvider: null,
				formID: null,
				formInstanceId: null,
				formKey: null
			}, args);

			// Generate unique formKey identifier.
			args.formKey = [args.formProvider, args.formID, args.formInstanceId].join('_');

			if ($popup.length) {
				// Should this be here. It is the only thing not replicated by a new form trigger & cookie.
				// $popup.trigger('pumFormSuccess');
			}

			window.PUM.hooks.doAction('pum.integration.form.success', form, args );
		}
	});

}(window.jQuery));
