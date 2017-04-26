/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/
(function ($) {
    "use strict";

    $.fn.popmake.cookies = $.fn.popmake.cookies || {};

    $.extend($.fn.popmake.cookies, {
        gforms_form_success: function (settings) {
            var $popup = PUM.getPopup(this);
            $popup.on('pum_gforms.success', function () {
                $popup.popmake('setCookie', settings);
            });
        }
    });

    $(document).ready(function () {
        $('.pum .gform_wrapper > form').each(function () {
            var $form = $(this),
                form_id = $form.attr('id').replace('gform_', ''),
                $settings = $form.find('meta[name="gforms-pum"]'),
                settings = $settings.length ? JSON.parse($settings.attr('content')) : false,
                $popup = $form.parents('.pum');

            if (!settings) {
                return;
            }

            settings = $.extend({
                openpopup: false,
                openpopup_id: 0,
                closepopup: false,
                closedelay: 0
            }, settings);

            $popup.attr('data-gform-id', form_id).data('gform-id', form_id);
            $popup.attr('data-gform-settings', JSON.stringify(settings)).data('gform-settings', settings);
        });
    });

    $(document).on('gform_confirmation_loaded', function (event, form_id) {
        var $popup = $('.pum[data-gform-id="' + form_id + '"]'),
            settings = $popup.data('gform-settings');

        console.log($popup, settings);

        if ( $popup.length ) {
            $popup.trigger('pum_gforms.success');
        }

        if ($popup.length && settings.closepopup) {

            setTimeout(function () {
                $popup.popmake('close');

                // Trigger another if set up.
                if (settings.openpopup && PUM.getPopup(settings.openpopup_id).length) {
                    PUM.open(settings.openpopup_id);
                }
            }, parseInt(settings.closedelay));
        } else if (settings.openpopup) {
            $popup = PUM.getPopup(settings.openpopup_id);

            if ($popup.length) {
                $popup.popmake('open');
            }
        }

    });
}(jQuery));