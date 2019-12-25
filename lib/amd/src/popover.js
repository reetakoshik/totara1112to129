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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @package core_output
 */

/**
 * Popover component JS
 *
 * @module  core_output
 * @class   Popover
 * @author  Brian Barnes <brian.barnes@totaralearning.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'theme_roots/bootstrap'], function($) {

    /**
     * Scans and initalises popover components that are yet to be initialised
     */
    function scan() {
        $('[data-component="/core/output/popover"][data-enhanced="false"]').each(function() {
            var that = this;
            var popover = $(this);
            var parent = popover.parent();
            var placement = this.getAttribute('data-arrow_placement') ? this.getAttribute('data-arrow_placement') : 'bottom';
            var triggerType = this.getAttribute('data-trigger') ? this.getAttribute('data-trigger') : 'focus';

            // Base placement on popup max size required and availble viewport space
            if (placement === 'smartPosition') {
                var pagePlacementTop = false;

                placement = function(context, source) {
                    var domRect = source.getBoundingClientRect(),
                        popoverMaxHeight = that.getAttribute('data-placement_max_height') ? that.getAttribute('data-placement_max_height') : 300,
                        popoverMaxWidth = that.getAttribute('data-placement_max_width') ? that.getAttribute('data-placement_max_width') : 300;

                    // Account for arrow spacing
                    popoverMaxHeight += 10;
                    popoverMaxWidth += 10;

                    if (window.innerHeight - domRect.bottom > popoverMaxHeight) {
                        pagePlacementTop = true;
                        return 'bottom';
                    } else if (domRect.top > popoverMaxHeight) {
                        pagePlacementTop = false;
                        return 'top';
                    } else if (domRect.left > popoverMaxWidth) {
                        return 'left';
                    } else if (window.innerWidth - domRect.right > popoverMaxWidth) {
                        return 'right';
                    } else {
                        pagePlacementTop = false;
                        return 'top';
                    }
                };

                // On mobile popover display
                if (window.innerWidth < 992) {
                    parent.on('shown.bs.popover', function() {
                        // Place mobile popover at top or bottom of view
                        that.parentNode.scrollIntoView(pagePlacementTop);
                    });
                }
            }

            parent.popover({
                    title: popover.data('title') ? popover.data('title') : '',
                    content: $('.popover__content', this).html(),
                    placement: placement,
                    trigger: triggerType,
                    html: true
                });

            if (triggerType === 'manual') {
                that.parentNode.addEventListener('core/popover:show', function() {
                    parent.popover('show');
                });
                that.parentNode.addEventListener('core/popover:hide', function() {
                    parent.popover('hide');
                });
            } else {
                parent.attr('tabindex', 0)
                    .css('cursor', 'pointer');
            }

            popover.attr('data-enhanced', "true");
        });

        // If we want to close popover when clicking outside of popup area
        var popoverList = document.querySelectorAll('[data-component="/core/output/popover"][data-close_on_focus_out]');
        if (popoverList) {
            // Incase scan was called more than once
            document.removeEventListener('click', focusOutClosePopover, false);
            document.removeEventListener('touchstart', focusOutClosePopover, false);

            document.addEventListener('click', focusOutClosePopover, false);
            document.addEventListener('touchstart', focusOutClosePopover, false);
        }

        /**
        * Close popover if clicked/touched outside of self
        *
        * @param {object} e
        */
        function focusOutClosePopover(e) {
            for (var i = 0; i < popoverList.length; i++) {
                var node = popoverList[i],
                    parent = $(node.parentNode),
                    popover = $('.popover');

                if (!parent.is(e.target)
                    && parent.has(e.target).length === 0
                    && popover.has(e.target).length === 0) {
                    parent.popover('hide').data('bs.popover');
                }
            }
        }
    }

    return {
        scan: scan
    };
});