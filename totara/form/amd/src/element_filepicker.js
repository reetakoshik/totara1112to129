/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_form
 */

/**
 * File picker JS stuff.
 *
 * NOTE: this file is based on lib/form/filepicker.js code
 *
 * @module     totara_form/element_filepicker
 * @class      element_filepicker
 * @author     Petr Skoda <petr.skoda@totaralms.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/yui'], function(Y) {
    M.totara_form = M.totara_form || {};
    M.totara_form.element_filepicker = M.totara_form.element_filepicker || {};
    M.totara_form.element_filepicker.instances = M.totara_form.element_filepicker.instances || [];

    M.totara_form.element_filepicker.callback = function (params) {
        var html = '<div class="filepicker-container"><a href="' + params.url + '">' + params.file + '</a></div>';
        html += '<div class="dndupload-progressbars"></div>';
        M.totara_form.element_filepicker.Y.one('#file_info_' + params.client_id + ' .filepicker-filename').setContent(html);
        // When file is added then set status of global variable to true.
        var elementname = M.core_filepicker.instances[params.client_id].options.elementname;
        M.totara_form.element_filepicker.instances[elementname].fileadded = true;
    };

    /**
     * This function is called for each file picker on page.
     */
    M.totara_form.element_filepicker.init = function (Y, options) {
        M.totara_form.element_filepicker.Y = Y;
        // For client side validation, initialize file status for this filepicker.
        M.totara_form.element_filepicker.instances[options.elementname] = {};
        M.totara_form.element_filepicker.instances[options.elementname].fileadded = false;

        // Set filepicker callback.
        options.formcallback = M.totara_form.element_filepicker.callback;

        if (!M.core_filepicker.instances[options.client_id]) {
            M.core_filepicker.init(Y, options);
        }
        Y.on('click', function (e, client_id) {
            e.preventDefault();
            if (this.ancestor('.fitem.disabled') === null) {
                M.core_filepicker.instances[client_id].show();
            }
        }, '#filepicker-button-' + options.client_id, null, options.client_id);

        var item = document.getElementById('filepicker-wrapper-' + options.client_id);
        if (item) {
            item.style.display = '';
        }

        var dndoptions = {
            clientid: options.client_id,
            acceptedtypes: options.accepted_types,
            author: options.author,
            maxfiles: -1,
            maxbytes: options.maxbytes,
            itemid: options.itemid,
            repositories: options.repositories,
            formcallback: options.formcallback,
            containerprefix: '#file_info_',
            containerid: 'file_info_' + options.client_id,
            contextid: options.context.id
        };
        M.form_dndupload.init(Y, dndoptions);
    };

    return {
        init_filepicker : function(options) {
            Y.use(['core_filepicker', 'node', 'node-event-simulate', 'core_dndupload'], function (Y) {
                M.core_filepicker.set_templates(Y, options.fptemplates);
                options.fptemplates = null;
                M.totara_form.element_filepicker.init(Y, options);
            });
        }
    };
});
