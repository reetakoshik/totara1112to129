/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author    Brian Barnes <brian.barnes@totaralearning.com>
 * @copyright 2017 onwards Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   core
 */

 define(['jquery', 'core/str', 'core/templates'], function($, strlib, templateslib) {

    var timeleftinsec = 0;
    var maintenancenode = $('.maintenancewarning');

    /**
     * Decrement time left and update display text.
     *
     * @method updatetimer
     */
    var updatetimer = function() {
        timeleftinsec -= 1;
        var stringcode = '';
        var a = {};
        if (timeleftinsec <= 0) {
            stringcode = 'sitemaintenance';
        } else {
            a.sec = timeleftinsec % 60;
            a.min = Math.floor(timeleftinsec / 60) % 60;
            a.hour = Math.floor(timeleftinsec / 3600);
            if (a.hour > 0) {
                stringcode = 'maintenancemodeisscheduledlong';
            } else {
                stringcode = 'maintenancemodeisscheduled';
            }
        }

        strlib.get_string(stringcode, 'admin', a).then(function(string) {
            var template = 'notification_warning';
            // Set error class to highlight the importance.
            if (timeleftinsec < 30) {
                template = 'notification_error';
            }
            return templateslib.render('core/' + template, {message: string, extraclasses: 'maintenancewarning'});
        }).then(function(html) {
            maintenancenode = $('.maintenancewarning');
            if (maintenancenode.length > 0) {
                maintenancenode.replaceWith(html);
                // Node has already been replaced so aria needs to be reset.
                $('.maintenancewarning').attr('aria-live', 'polite');
            }
        });
    };

    return {
        /**
         * Initialise timer if maintenancemode set.
         *
         * @method init
         * @param {integer} timeleft The number of seconds before maintenance mode is activiated.
         */
        init: function(timeleft) {
            if (maintenancenode) {
                timeleftinsec = timeleft;
                maintenancenode.attr('aria-live', 'polite');
                setInterval(updatetimer, 1000);
            }
        }
    };
 });