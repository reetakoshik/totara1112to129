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
/**
 * This is a hack because font size cannot be set relative to parent container yet.
 */
define([
    'jquery'
], function($) {

    var resize = function(id) {
        var icons = $('#block-totara-featured-links-tile-' + id + ' .block-totara-featured-links-icon');
        icons.each(function(index, iconWrapper) {
            $(icon).attr('style', '');
            var icon = $(iconWrapper).find('span.flex-icon');
            var iconAspectRatio = icon.height() / icon.width();

            var height = $(iconWrapper).parent('div').height();
            var width = $(iconWrapper).parent('div').width();
            var smallestDimension = height > width ? width : height;

            var multiplier = 1; // Large and any that are not defined.
            if ($(iconWrapper).hasClass('block-totara-featured-links-icon-small')) {
                multiplier = 0.5;
            }
            if ($(iconWrapper).hasClass('block-totara-featured-links-icon-medium')) {
                multiplier = 0.8;
            }

            if (iconAspectRatio < 1) {
                $(icon).attr(
                    'style',
                    'font-size: ' + (Math.floor(iconAspectRatio * smallestDimension) * multiplier) + 'px !important'
                );
            } else {
                $(icon).attr(
                    'style',
                    'font-size: ' + (Math.floor((1 / iconAspectRatio) * smallestDimension) * multiplier) + 'px !important'
                );
            }
            // Size the icon
            $(icon).css('height', (smallestDimension * multiplier) + 'px');
            $(icon).css('line-height', (smallestDimension * multiplier) + 'px');
        });
    };

    return {
        resize: resize
    };
});