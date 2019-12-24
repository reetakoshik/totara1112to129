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
 * @author Joby Harding <joby.harding@totaralearning.com>
 * @package core_elementlibrary
 */
define(['jquery', 'core/log'], function($, logging) {

    // jQuery plugin for animating between page fragment URLs e.g. #top
    // Example usage apply to all links with hrefs starting with hash:
    // $('a[href^=#][href!=#]).animateScroll();
    // Example usage apply to single element:
    // $('#scroll_to_link').animateScroll();
    $.fn.animateScroll = function(options) {

        options = $.extend({}, $.fn.animateScroll.defaults, options);
        var $body = $('html, body');

        // Sanity check href attributes then set handlers.
        var $fragmentLinks = this.filter('a[href^="#"][href!="#"]');
        // Use jQuery namespacing for event handler.
        $fragmentLinks.on('click.animateScroll', function(event) {
            event.preventDefault();
            var href = event.target.getAttribute('href');
            var $scrollTarget = $(href);
            // Only if target exists.
            if ($scrollTarget.length < 1) {
                logging.debug('elementlibrary/pattern_library: Scroll target \'' + href + '\' not found in document');
                return;
            }
            var $scrollTo = $scrollTarget.first().offset().top + 'px';
            $body.stop().animate({'scroll-top': $scrollTo}, options.duration, options.easing);
        });

        return this;
    };

    // Pattern for blanket default overrides.
    $.fn.animateScroll.defaults = {
        duration: 500,
        easing: 'swing'
    };

});