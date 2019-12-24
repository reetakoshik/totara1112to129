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

/*
 * This switches the images on the tiles with multiple images.
 */
define(['jquery'], function($) {
    return {
        init: function(interval, id) {
            var currentImg = 1;
            var maxImg = 0;
            maxImg = $('#' + id).children('div').length;
            currentImg = Math.floor(Math.random() * (maxImg)) + 1;
            $('#' + id + ' div:nth-of-type(' + currentImg + ')').show();
            if (maxImg <= 1) {
                return;
            }
            if (interval === 0) {
                return;
            }
            window.setInterval(function() {
                $('#' + id + ' div:nth-of-type(' + currentImg + ')').css({'z-index': '2'}).fadeOut('slow');

                var newcurrentImg = -1;
                do {
                    newcurrentImg = Math.floor(Math.random() * (maxImg)) + 1;
                } while (currentImg === newcurrentImg);
                currentImg = newcurrentImg;

                $('#' + id + ' div:nth-of-type(' + currentImg + ')').css({'z-index': '1'}).show();
            }, interval);
        }
    };
});
