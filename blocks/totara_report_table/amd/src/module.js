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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @author Brian Quinn <brian@learningpool.com>
 * @author Finbar Tracey <finbar@learningpool.com>
 * @package block_totara_report_table
 */

/**
 * Javascript file containing JQuery bindings for processing changes to instant report
 */

define(['jquery', 'core/config', 'core/templates'], function ($, mdlconfig, templates) {
   var report_table = {
        /**
         * Change links behaviour to load report pages in ajax mode
         * @param uniqueid string
         * @param blockid int
         * @returns {undefined}
         */
        change_links: function (uniqueid, blockid) {
            var xhr = null;

            // Change links behaviour to load page using AJAX (pages and sorting).
            $('.block_totara_report_table.' + uniqueid + ' .content .rb-display-table-container').
                    find('div.paging a, th.header a').each(function() {
                $(this).click(function(e){
                    e.preventDefault();
                    var $link = $(this),
                        href = $link.attr('href'),
                        querystring = href.slice(href.indexOf('?') + 1);
                    // Add sesskey and blockid, other params are ignored.
                    querystring += '&sesskey=' + mdlconfig.sesskey + '&blockid=' + blockid;

                    // Abort any call to instantreport that is already active.
                    if (xhr) {
                        xhr.abort();
                    }

                    // Add the wait icon if it is not already attached to the clicked item.
                    if ($(e.target).siblings('.block_totara_report_table_instant_wait').length === 0) {
                        var $splash = $('<div class="block_totara_report_table_instant_wait"></div>'),
                            $content = $('.block_totara_report_table.' + uniqueid + ' .content .rb-display-table-container');

                        $content.addClass('block_totara_report_table_loading');
                        $content.append($splash);

                        templates.renderIcon('loading', '', 'ft-size-600').done(function(html) {
                            $splash.html(html);
                            var offset = $content.position();

                            offset.left += $content.width()/2 - $splash.width()/2;
                            offset.top += $content.height()/2 - $splash.height()/2;
                            $splash.css(offset);
                        });
                    }

                    xhr = report_table.refresh_results(uniqueid, querystring, function() {
                        report_table.change_links(uniqueid, blockid);
                    });
                });
            });
        },

        /**
         * Refresh results panel, used by change handler on sidebar search
         * as well as callbacks from reportbuilder sources that change
         * report entries
         *
         * @param string      uniqueid report table uniqueid
         * @param string      querystring get parameters string for report
         * @param callback    function to run once data is refreshed
         */
        refresh_results: function (uniqueid, querystring, callback) {
            // Make the ajax call.
            return $.get(
                mdlconfig.wwwroot + '/blocks/totara_report_table/ajax_instantreport.php?' + querystring
            ).done( function (data) {
                var $content = $('.block_totara_report_table.' + uniqueid + ' .content');
                // Clear all waiting icons.
                $content.find('.block_totara_report_table_instant_wait').remove();

                // Replace contents.
                $content.find('.rb-display-table-container').remove();
                $content.prepend($(data).find('.rb-display-table-container'));
                // Remove all forms from table view (otherwise we need to handle them as well, which is beyond this block).
                $content.find('form').remove();

                if (callback) {
                    callback();
                }
            });
        },

        /**
         * Initialize populate list
         *
         * Used from PHP.
         *
         * @param Y object    YUI instance
         */
        populatelist: function() {

            var savedListControl = $('#id_config_savedsearch');

            $('#id_config_reportid').change(function () {
                var reportId = $(this).val();

                if (reportId === '') {
                    return;
                }

                savedListControl.attr('disabled', 'disabled');

                M.util.js_pending('block_totara_report_table-populatelist');

                // Make an AJAX request to get the saved searches.
                $.ajax({
                    type: 'POST',
                    url: mdlconfig.wwwroot + '/blocks/totara_report_table/ajax_list_saved.php',
                    data: {
                        'reportid': reportId,
                        'sesskey' : M.cfg.sesskey
                    },
                    success: function(data) {
                        savedListControl.find('option').remove();

                        // Add the new options.
                        savedListControl.append('<option value="">' +
                            M.util.get_string('allavailabledata', 'block_totara_report_table') + '</option>');

                        for (var i in data) {
                            savedListControl.append('<option value="' + i + '">' + data[i] + '</option>');
                        }

                        // Enable the control.
                        savedListControl.removeAttr('disabled');

                        M.util.js_complete('block_totara_report_table-populatelist');
                    }
                });
            });
        }

    };

    return report_table;
});
