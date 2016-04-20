/**
 * Initialize Popup Maker.
 * Version 1.4.0
 */
(function ($, document, undefined) {
    "use strict";
    // Defines the current version.
    $.fn.popmake.version = 1.4;

    // Stores the last open popup.
    $.fn.popmake.last_open_popup = null;

    $(document).ready(function () {
        $('.popmake').popmake();
    });
}(jQuery));