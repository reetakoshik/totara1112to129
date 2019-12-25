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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package tmod_quiz
 */

/**
 * This file defines the functionality of mod/quiz/edit
 */
define(['jquery', 'core/config'], function($, mdlconfig) {
    var totara_random_category_selectors;

    var warn_randomquestions = {
        /**
         * Setup for displaying warnings for random questions without enough questions in the bank
         */
        init: function() {
        },

        set_totara_random_category_selectors: function(selectors) {
            totara_random_category_selectors = selectors;
            warn_randomquestions.toggle_random_usage_warning();
            warn_randomquestions.toggle_question_icons();
        },

        toggle_random_usage_warning: function() {
            if (undefined == totara_random_category_selectors ||
                totara_random_category_selectors['notenough'].length === 0) {
                $('.randomnotification').hide();
            } else {
                $('.randomnotification').show();
            }
        },

        toggle_question_icons: function() {
            // Each relevant icon has a class "randomwarning_{category}_{recurse).
            // The totara_random_category_selectors arrays contain lists of these class names that need to be shown or hidden.
            $('.flex-icon.fa-warning').filter(totara_random_category_selectors['hasenough']).hide();
            $('.flex-icon.fa-warning').filter(totara_random_category_selectors['notenough']).show();
        }
    };

    return warn_randomquestions;
});
