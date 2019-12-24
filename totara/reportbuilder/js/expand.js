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
 * @package totara
 * @subpackage reportbuilder
 */

/**
 * Javascript file containing JQuery bindings for processing expanding information
 */

M.totara_reportbuilder_expand = M.totara_reportbuilder_expand || {
    /** Selectors. */
    SELECTORS: {
        ENROLMENTBUTTONS:     '.expandenrol'
    },

    init: function(Y, args) {
        $('body').on('click', '.rb-display-expand', M.totara_reportbuilder_expand.displayExpand);
        $('body').on('click', 'input' + this.SELECTORS.ENROLMENTBUTTONS, M.totara_reportbuilder_expand.clickEnrol);
        $('body').on('click', '.rb-display-expand-link', function(event) { event.stopPropagation(); });
    },

    /*
     * Inserts the expanded contents after the clicked row.
     * Keeps track of whether a _link_ was clicked so that if it is clicked again then it will clear the expanded contents instead.
     */
    displayExpand: function(event) {
        var that = this;
        if ($(this).attr('clicked')) {
            // We reclicked a link, so remove the expanded contents, unmark as clicked and return.
            $('.rb-expand-row').remove();
            $(this).attr({clicked: null});
            return;
        }
        var id = $('.rb-display-table-container').attr('id');
        var url = M.cfg.wwwroot + '/totara/reportbuilder/ajax/expand.php?id=' + id +
            '&expandname=' + $(this).data('name') +
            '&sesskey=' + M.cfg.sesskey;
        if ($(this).data('param')) {
            url = url + '&' + $(this).data('param');
        }
        $.post(url).done(function(data) {
            // Remove any existing expanded contents.
            $('.rb-expand-row').remove();
            // Unmark any links as clicked.
            $('.rb-display-table-container div').attr({clicked: null});
            // Insert the content in the following row. We calculate colspan using the clicked row.
            var content = $(data).find('.rb-expand-row');
            var colspan = $(that).closest('tr').find('td').length;
            content.find('td.rb-expand-cell').attr({ colspan: colspan});
            content.insertAfter($(that).closest('tr'));
            // Mark the link as clicked.
            $(that).attr({clicked: true});
        });
    },

    /*
     * When clicking an enrol button in a course expander
     * Post the form values with expand data, render the result, redirect if told to do so.
     */
    clickEnrol: function(event) {
        event.stopPropagation();
        var button=$(event.target);

        var courseid = $('input[type="hidden"][name="courseid"]').attr('value');
        var id = $('.rb-display-table-container').attr('id');
        var url = M.cfg.wwwroot + '/totara/reportbuilder/ajax/expand.php';

        var form = $(button).parents('form')[0];
        var formdata = $(form).serialize();

        formdata += '&expandname=course_details';
        formdata += '&expandcourseid=' + courseid;
        formdata += '&id=' + id;
        formdata += '&instancesubmitted=' + button.attr('name');

        var that = $('[data-param="expandcourseid=' + courseid + '"]');

        $.post(url, formdata).done(function(data) {
            if (data.hasOwnProperty('error')) {
                // Oh nuts. Its an exception, lets try to handle it.
                // If debugdeveloper is off we will not actually do anything here.
                if (M.cfg.developerdebug) {
                    // Developer debug has been enabled. Lets display the exception nicely so that developers can hear something
                    // has gone wrong.
                    YUI().use('moodle-core-notification-ajaxexception', function () {
                        new M.core.ajaxException(data);
                    });
                } else {
                    // No developer debug. Lets just throw up an alert so that the user at least knows that something went wrong
                    // and can talk to their superior about it.
                    YUI().use('moodle-core-notification-alert', function() {
                        // Tiny reliance issue here, we don't actually load the error string into JS. It is already loaded by
                        // some other code and we just use it,
                        new M.core.alert({message: data.error, title: M.util.get_string('error', 'moodle')});
                    });
                }
            }
            var redirect = $(data).find('input[type="hidden"][name="redirect"]');
            if (redirect.length) {
                window.location = redirect.attr('value');
                return;
            }
            if (data === 'success') {
                $('.rb-expand-row').remove();
                // Close and re-expand to refresh contents.
                $('.rb-display-table-container div').attr({clicked: null});
                that.trigger( "click" );
            }

            // Remove any existing expanded contents.
            $('.rb-expand-row').remove();
            // Unmark any links as clicked.
            $('.rb-display-table-container div').attr({clicked: null});
            // Insert the content in the following row. We calculate colspan using the clicked row.
            var content = $(data).find('.rb-expand-row');
            var colspan = $(that).closest('tr').find('td').length;
            content.find('td.rb-expand-cell').attr({ colspan: colspan});
            content.insertAfter($(that).closest('tr'));
            // Mark the link as clicked.
            that.attr({clicked: true});
        });
    }
};
