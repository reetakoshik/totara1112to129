/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralms.com>
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 */

/**
 * Javascript file containing JQuery bindings for processing changes to instant filters
 */
define(['jquery', 'core/config', 'core/templates'], function ($, mdlcfg, templates) {
    var instantfilter = {

        xhr: null,

        /**
         * module initialisation method called by php js_init_call()
         *
         * @param string    args supplied in JSON format
         */
        init: function() {

            // ignoredirty needs to be avoided as it is the password hack to stop browsers populating password fields
            $('.rb-sidebar input:not(.ignoredirty), .rb-sidebar select').change(function (event) {
                // Force form dependency before processing (as it will enable fields that we might need to send).
                try {
                    var formid = $(this).parents('form').attr('id');
                    M.form.updateFormState(formid);
                } catch (err) {
                    // Cannot update form state: do nothing.
                }

                // Abort any call to instantreport that is already active.
                if (instantfilter.xhr) {
                    instantfilter.xhr.abort();
                }
                // Add the wait icon if it is not already attached to the clicked item.
                if ($(event.target).siblings('.instantfilterwait').length === 0) {
                    templates.renderIcon('loading', 'loading', 'instantfilterwait').done(function (html) {
                        $(html).insertAfter($(event.target).parent().children().last());
                    });
                }
                instantfilter.xhr = instantfilter.refresh_results();
            });
        },

        split_search: function (search) {
            var ar = {};
            if (search === undefined || search.length < 1) { return ar;}
            var pairs = search.slice(1).split('&');
            for (var i = 0; i < pairs.length; i++) {
                var key_val = pairs[i].split('=');
                // Replace '+' (alt space) char explicitly since decode does not.
                var arkey = decodeURIComponent(key_val[0]).replace(/\+/g,' ');
                var arval = decodeURIComponent(key_val[1]).replace(/\+/g,' ');
                if (ar[arkey] === undefined) {
                    ar[arkey] = [];
                }
                ar[arkey].push(arval);
            }
            return ar;
        },

        remove_param: function (ar, key) {
            if (ar[key]) {
                delete ar[key];
            }
            return ar;
        },

        build_search: function (ar) {
            var search = "?";
            for (var key in ar) {
                for (var i = 0; i < ar[key].length; i++) {
                    search += search == "?" ? "" : "&";
                    search += encodeURIComponent(key) + "=" + encodeURIComponent(ar[key][i]);
                }
            }
            return search;
        },

        /**
         * refresh results panel, used by change handler on sidebar search
         * as well as callbacks from reportbuilder sources that change
         * report entries
         *
         * @param callback    function to run once data is refreshed
         */
        refresh_results: function (callback) {
            // Get the current page params and strip off those we don't want to pass on.
            var pageparams = window.location.search;
            var paramsarray = instantfilter.split_search(pageparams);
            paramsarray = instantfilter.remove_param(paramsarray, 'spage');
            paramsarray = instantfilter.remove_param(paramsarray, 'ssort');
            paramsarray = instantfilter.remove_param(paramsarray, 'sid');
            paramsarray = instantfilter.remove_param(paramsarray, 'clearfilters');
            pageparams = instantfilter.build_search(paramsarray);

            // Make the ajax call.
            return $.post(
                mdlcfg.wwwroot + '/totara/reportbuilder/ajax/instantreport.php' + pageparams,
                $('.rb-sidebar').serialize()
            ).done( function (data) {
                // Clear all waiting icons.
                var instantfilter = $('.instantfilterwait');
                instantfilter.siblings('.sr-only').remove();
                instantfilter.remove();
                // Replace contents.
                $('.rb-display-table-container').replaceWith($(data).find('.rb-display-table-container'));
                $('.rb-record-count').replaceWith($(data).find('.rb-record-count'));
                // All browsers, except MSIE 6-7-8.
                $('.rb-report-svggraph').replaceWith($(data).find('.rb-report-svggraph'));
                // Support MSIE 6-7-8.
                $('.rb-report-pdfgraph').replaceWith($(data).find('.rb-report-pdfgraph'));
                // Update sidebar filter counts.
                $(data).find('.rb-sidebar.mform label').each(function (ind, elem) {
                    var $elem = $(elem);
                    if ($elem.attr('for')) {
                        $('label[for="'+$elem.attr('for')+'"]').html($elem.html());
                    }
                });
                if (callback) {
                    callback();
                }
            });
        }
    };

    return instantfilter;
});
