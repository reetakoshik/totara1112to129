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
 * This file defines the functionality of addrandomform
 */
define(['jquery', 'core/config'], function($, mdlconfig) {
    var totara_category_counts;

    var warn_add_randomquestions = {
        /**
         * Setup for displaying warning if category doesn't contain enough questions
         */
        init: function() {
            warn_add_randomquestions.add_onchanges();
            if (undefined != totara_category_counts) {
                warn_add_randomquestions.toggle_warning();
            }
        },

        set_totara_category_counts: function(counts) {
            totara_category_counts = counts;
            warn_add_randomquestions.toggle_warning();
        },

        toggle_warning: function() {
            var showwarning = false;

            var key = $('#id_category').val();
            var includesubs = $('#id_includesubcategories').prop('checked');
            var numbertoadd = parseInt($('#id_numbertoadd').val(), 10);

            var counts = totara_category_counts[key];
            if (includesubs) {
                if (numbertoadd > counts['includedcount']) {
                    showwarning = true;
                }
            } else {
                if (numbertoadd > counts['questioncount']) {
                    showwarning = true;
                }
            }

            if (showwarning) {
                $('#fitem_id_warn_random').show();
            } else {
                $('#fitem_id_warn_random').hide();
            }
        },

        add_onchanges: function() {
            var selector = '#id_category, #id_includesubcategories, #id_numbertoadd';
            $(document).on('change', selector, function () {
                if (undefined == totara_category_counts) {
                    return;
                }

                warn_add_randomquestions.toggle_warning();
            });
        }
    };

    return warn_add_randomquestions;
});
