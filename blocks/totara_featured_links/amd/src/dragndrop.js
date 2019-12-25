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
define(['jquery', 'core/ajax', 'jqueryui'], function($, ajax) {
    // A Hack of the base code so it puts the place holder in the right place
    // See the @Update tags to see the parts that were changed.
    $.widget('custom.sortableGrid', $.ui.sortable, {
        /** @Update added this method
         *  This method checks whether then cursor is on the right side of the element that it is over
         * @param item
         * @param event
         * @returns boolean
         */
        _onRightSide: function(item, event) {
            return this._isOverAxis(
                event.pageX,
                item.left + (item.width / 2),
                item.width / 2
            );
        },
        _mouseDrag: function(event) {
            var i, item, itemElement, intersection,
                o = this.options,
                scrolled = false;

            // Compute the helpers position
            this.position = this._generatePosition(event);
            this.positionAbs = this._convertPositionTo("absolute");

            if (!this.lastPositionAbs) {
                this.lastPositionAbs = this.positionAbs;
            }

            // Do scrolling
            if (this.options.scroll) {
                if (this.scrollParent[0] !== this.document[0] &&
                    this.scrollParent[0].tagName !== "HTML") {

                    if ((this.overflowOffset.top + this.scrollParent[0].offsetHeight) -
                        event.pageY < o.scrollSensitivity) {
                        this.scrollParent[0].scrollTop =
                            scrolled = this.scrollParent[0].scrollTop + o.scrollSpeed;
                    } else if (event.pageY - this.overflowOffset.top < o.scrollSensitivity) {
                        this.scrollParent[0].scrollTop =
                            scrolled = this.scrollParent[0].scrollTop - o.scrollSpeed;
                    }

                    if ((this.overflowOffset.left + this.scrollParent[0].offsetWidth) -
                        event.pageX < o.scrollSensitivity) {
                        this.scrollParent[0].scrollLeft = scrolled =
                            this.scrollParent[0].scrollLeft + o.scrollSpeed;
                    } else if (event.pageX - this.overflowOffset.left < o.scrollSensitivity) {
                        this.scrollParent[0].scrollLeft = scrolled =
                            this.scrollParent[0].scrollLeft - o.scrollSpeed;
                    }

                } else {

                    if (event.pageY - this.document.scrollTop() < o.scrollSensitivity) {
                        scrolled = this.document.scrollTop(this.document.scrollTop() - o.scrollSpeed);
                    } else if (this.window.height() - (event.pageY - this.document.scrollTop()) <
                        o.scrollSensitivity) {
                        scrolled = this.document.scrollTop(this.document.scrollTop() + o.scrollSpeed);
                    }

                    if (event.pageX - this.document.scrollLeft() < o.scrollSensitivity) {
                        scrolled = this.document.scrollLeft(
                            this.document.scrollLeft() - o.scrollSpeed
                        );
                    } else if (this.window.width() - (event.pageX - this.document.scrollLeft()) <
                        o.scrollSensitivity) {
                        scrolled = this.document.scrollLeft(
                            this.document.scrollLeft() + o.scrollSpeed
                        );
                    }

                }

                if (scrolled !== false && $.ui.ddmanager && !o.dropBehaviour) {
                    $.ui.ddmanager.prepareOffsets(this, event);
                }
            }

            // Regenerate the absolute position used for position checks
            this.positionAbs = this._convertPositionTo("absolute");

            // Set the helper position
            if (!this.options.axis || this.options.axis !== "y") {
                this.helper[0].style.left = this.position.left + "px";
            }
            if (!this.options.axis || this.options.axis !== "x") {
                this.helper[0].style.top = this.position.top + "px";
            }

            // Rearrange
            for (i = this.items.length - 1; i >= 0; i--) {


                // Cache variables and intersection, continue if no intersection
                item = this.items[i];
                itemElement = item.item[0];
                intersection = this._intersectsWithPointer(item);
                if (!intersection) {
                    continue;
                }

                // @Update this part was changed
                // It makes sure that the place holder is changed as soon as the cursor is over or under 50% across an element
                this.direction = this._onRightSide(item, event) === true ? "right" : "left";
                this._rearrange(event, item);

                if (!this._intersectsWithSides(item)) {
                    break;
                }
                // end part that was changed.

                this._trigger("change", event, this._uiHash());
                break;
            }

            // Post events to containers.
            this._contactContainers(event);

            // Interconnect with droppables.
            if ($.ui.ddmanager) {
                $.ui.ddmanager.drag(this, event);
            }

            // Call callbacks.
            this._trigger("sort", event, this._uiHash());

            this.lastPositionAbs = this.positionAbs;
            return false;

        },
        _rearrange: function(event, i, a, hardRefresh) {
            if (a) {
                a[0].appendChild(this.placeholder[0]);
            } else {
                i.item[0].parentNode.insertBefore(
                    this.placeholder[0],
                    // @Update This part was changed.
                    // Moves the placeholder to the left or right of the element that it is over.
                    (this.direction === "left" ? i.item[0] : i.item[0].nextSibling)
                    // End changed part.
                );
            }

            // Various things done here to improve the performance:
            // 1. we create a setTimeout, that calls refreshPositions
            // 2. on the instance, we have a counter variable, that get's higher after every append
            // 3. on the local scope, we copy the counter variable, and check in the timeout,
            // if it's still the same
            // 4. this lets only the last addition to the timeout stack through
            this.counter = this.counter ? ++this.counter : 1;
            var counter = this.counter;

            this._delay(function() {
                if (counter === this.counter) {

                    // Precompute after each DOM insertion, NOT on mousemove
                    this.refreshPositions(!hardRefresh);
                }
            });

        }
    });

    return {
        init: function() {
            $('.block-totara-featured-links-layout').sortableGrid({
                cursor: 'move',
                helper: 'clone',
                placeholder: 'block-totara-featured-links-placeholder',
                tolerance: 'intersect',
                items: ' > [id^=block-totara-featured-links-tile-]',
                opacity: 0.5,
                update: function(event, ui) {
                    var sortedIDs = $(event.target).sortableGrid("toArray");
                    ajax.call([
                        {
                            methodname: 'block_totara_featured_links_external_reorder_tiles',
                            args: {
                                tiles: sortedIDs
                            }
                        }
                    ]);
                },
                start: function(event, ui) {
                    ui.item.show();
                    $('.block-totara-featured-links-layout').sortableGrid('option',
                        'cursorAt',
                        {left: ui.item.width() / 2, top: ui.item.height() / 2});
                }
            }).disableSelection();
        }
    };
});
