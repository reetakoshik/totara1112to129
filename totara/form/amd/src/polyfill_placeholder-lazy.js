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
 * @author Brian Barnes <brian.barnes@totaralms.com>
 * @package totara_form
 */

/* eslint-disable */

define(['jquery'], function($) {
    return {
        /**
         * initialisation function
         *
         * @param fieldid string the html id that requires the placeholder
         */
        init: function(fieldid) {
            var $placeholder = $('#' + fieldid);

            function blur() {
                if ($placeholder.val() === '') {
                    $placeholder.val($placeholder.attr('placeholder'));
                    $placeholder.addClass('placeholder');
                }
            }

            $placeholder.blur(blur);
            $placeholder.focus(function () {
                if ($placeholder.hasClass('placeholder')) {
                    $placeholder.val('');
                    $placeholder.removeClass('placeholder');
                }
            });

            $placeholder.closest('form').find("input[type='submit']").click(function () {
                if ($placeholder.hasClass('placeholder')) {
                    $placeholder.val('');
                }
            });

            blur();
        }
    };
});