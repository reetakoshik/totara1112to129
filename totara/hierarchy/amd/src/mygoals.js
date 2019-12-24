/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Brian Barnes <brian.barnes@totaralms.com>
 * @package totara_hierarchy
 */

/**
 * This file defines the functionality of the mygoals page
 */
define(['jquery', 'core/config'], function ($, mdlconfig) {
    var goals = {
        /**
         * sets up to handle altering company goals
         *
         * @param userid int The id of the user to update
         * @param companyscope int goal::SCOPE_COMPANY
         */
        init_company: function(userid, companyscope) {
            $('#companygoals').on('change', '.company_scalevalue_selector', function () {
                goals._update_goal(userid, companyscope, $(this).data('goalid'), $(this).val());
            });
        },

        /**
         * sets up to handle altering company goals
         *
         * @param userid int The id of the user to update
         * @param personalscope int goal::SCOPE_PERSONAL
         */
        init_personal: function(userid, personalscope) {
            $('#personalgoals').on('change', '.personal_scalevalue_selector', function () {
                goals._update_goal(userid, personalscope, $(this).data('goalid'), $(this).val());
            });
        },

        /**
         * Set up to handle altering a single goal
         *
         * @param userid the user id you're working with
         * @param personalscope int goal::SCOPE_PERSONAL
         */
        init_single_personal: function(userid, personalscope) {
            $('#page-totara-hierarchy-prefix-goal-item-view .personal_scalevalue_selector').change( function () {
                goals._update_goal(userid, personalscope, $(this).data('goalid'), $(this).val());
            });
        },

        /**
         * Updates the goal within the scope required
         *
         * @param userid int the id of the user to update
         * @param scope int whether this is the personal or company scopre to update
         */
        _update_goal: function (userid, scope, goalid, value) {
            var params = {
                scope: scope,
                sesskey: mdlconfig.sesskey,
                goalitemid: goalid,
                userid: userid,
                scalevalueid: value
            };
            $.get(mdlconfig.wwwroot + '/totara/hierarchy/prefix/goal/update-scalevalue.php', params);
        }
    };

    return goals;
});