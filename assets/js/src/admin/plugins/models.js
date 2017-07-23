/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/
(function ($) {
    "use strict";

    var models = {
        field: function (args) {
            return $.extend(true, {}, {
                type: 'text',
                id: '',
                id_prefix: '',
                name: '',
                label: null,
                placeholder: '',
                desc: null,
                dynamic_desc: null,
                size: 'regular',
                classes: [],
                dependencies: "",
                value: null,
                select2: false,
                multiple: false,
                as_array: false,
                options: [],
                object_type: null,
                object_key: null,
                std: null,
                min: 0,
                max: 50,
                step: 1,
                unit: 'px',
                units: {},
                required: false,
                meta: {}
            }, args);
        }
    };

    // Import this module.
    window.PUM_Admin = window.PUM_Admin || {};
    window.PUM_Admin.models = models;
}(jQuery));