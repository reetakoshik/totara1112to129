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
define(['jquery', 'core/templates', 'block_totara_featured_links/slick'], function($, templates) {

    return {
        init: function(interval, id, transition, order, controls, autoplay, repeat, pauseonhover) {
            var leftArrow = templates.renderIcon('caret-left', 'Previous', 'slick-prev');
            var rightArrow = templates.renderIcon('caret-right', 'Next', 'slick-next');

            var fade = transition === 'fade';
            var dots = controls.indexOf('position_indicator') !== -1;
            var arrows = controls.indexOf('arrows') !== -1;

            $.when(leftArrow, rightArrow).then(function(leftArrowMarkup, rightArrowMarkup) {
                var element = $('#' + id);
                element.slick({
                    autoplay: autoplay === '1',
                    autoplaySpeed: interval,
                    prevArrow: leftArrowMarkup,
                    nextArrow: rightArrowMarkup,
                    fade: fade,
                    dots: dots,
                    rtl: document.dir === "rtl",
                    arrows: arrows,
                    infinite: repeat === '1',
                    pauseOnHover: pauseonhover === '1'
                });

                // Randomise the order.
                if (order === 'random' && autoplay === '1') {
                    var numSlides = element.slick('getSlick').slideCount;
                    var currentItem = 0;
                    element.slick('pause');
                    window.setInterval(function(){
                        var nextSlideIndex = Math.floor(Math.random() * (numSlides - 1));
                        if (nextSlideIndex >= currentItem) {
                            nextSlideIndex += 1;
                        }
                        element.slick('slickGoTo', nextSlideIndex, false);
                        currentItem = nextSlideIndex;
                    }, interval);
                }
            });
        }
    };
});
