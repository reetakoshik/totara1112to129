/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @package core_output
 */

/**
 * Calendar JS
 *
 * @module  core_calendar
 * @class   Calendar
 * @author  Brian Barnes <brian.barnes@totaralearning.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {
    /**
     * Close popover window
     *
     * @param {FocusEvent|MouseEvent} e Event signifying that the popover is to be closed
     */
    function closePopover(e) {
        var triggerElement = e.target.closest('[data-calendar-popover-shown="true"]');
        var nodes;
        var node = 0;

        nodes = document.querySelectorAll('[data-calendar-popover-shown="true"]');
        var event = new CustomEvent('core/popover:hide');
        for (node = 0; node < nodes.length; node++) {
            if (nodes[node] !== triggerElement) {
                nodes[node].removeAttribute('data-calendar-popover-shown');
                nodes[node].querySelector('[data-component="/core/output/popover"]').parentElement.dispatchEvent(event);
            }
        }
    }

    /**
     * Open the popover window
     *
     * @param {FocusEvent|MouseEvent} e Event signifying that the popover should be opened
     */
    function displayPopover(e) {
        var triggerElement = e.target.closest('.hasevent, .today');

        if (triggerElement && triggerElement.getAttribute('data-calendar-popover-shown') !== 'true') {
            var event = new CustomEvent('core/popover:show');
            e.preventDefault();
            triggerElement.setAttribute('data-calendar-popover-shown', true);
            triggerElement.querySelector('[data-component="/core/output/popover"]').parentElement.dispatchEvent(event);
        }
    }

    document.addEventListener('focusin', closePopover);

    return {
        init: function() {
            var calendars = document.querySelectorAll('.minicalendar');
            var calendar = 0;

            for (calendar = 0; calendar < calendars.length; calendar++) {
                calendars[calendar].addEventListener('mouseover', displayPopover);
                calendars[calendar].addEventListener('focusin', displayPopover);
                calendars[calendar].addEventListener('mouseout', closePopover);
            }
        }
    };
});