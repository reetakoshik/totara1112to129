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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package block_totara_featured_links
 */

define(['jquery'], function($) {

    var init = function() {
        $('[name="type"]').change(function(event) {
            var url = window.location.href;
            if (url.match(/type=[^&;]+/)) {
                url = url.replace(/type=[^&;]+/, 'type=' + encodeURI($(event.target).val()));
            } else {
                url += '&type=' + encodeURI($(event.target).val());
            }
            window.location = url;
        });
    };

    return {
        init: init
    };
});