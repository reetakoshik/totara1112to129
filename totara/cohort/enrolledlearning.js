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
 * @package totara
 * @subpackage totara_cohort
 */
M.totara_cohortenrolledlearning = M.totara_cohortenrolledlearning || {

    Y: null,
    // public handler reference for the dialog
    totaraDialog_handler_preRequisite: null,
    datesDialogue: null,

    /**
     * module initialisation method called by php js_init_call()
     *
     * @param object    YUI instance
     */
    init: function(Y) {
        // save a reference to the Y instance (all of its dependencies included)
        var module = this;
        this.Y = Y;

        // Check jQuery dependency is available.
        if (typeof $ === 'undefined') {
            throw new Error('M.totara_positionuser.init()-> jQuery dependency required for this module to function.');
        }

        // On click event for "View dates" links.
        $('.assignment-duedates').on('click', function(){
            $.get($(this).attr('href'), function(result){
                M.totara_cohortenrolledlearning.datesDialogue = new M.core.dialogue({
                    headerContent: null,
                    bodyContent  : result,
                    width        : 500,
                    centered     : true,
                    modal        : true,
                    render       : true
                });
                M.totara_cohortenrolledlearning.datesDialogue.show();
            });
            return false;
        });

        // On click events for column sorting and paging inside "View dates" popup.
        $('#page-admin-totara-cohort-enrolledlearning').on('click',
            '.moodle-dialogue-content #program_assignment_duedates a, ' +
            '.moodle-dialogue-content #cert_assignment_duedates a', function(event){
            if (!event.target.closest('td') || !$(event.target.closest('td')).hasClass('cell')) {
                $.get($(event.target).attr('href'), function(result){
                    M.totara_cohortenrolledlearning.datesDialogue.bodyNode.setHTML(result);
                });
            } else {
                window.open($(event.target).attr('href'));
            }
            return false;
        });
    }
};
