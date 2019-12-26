/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
 * @package totara_catalog
 * @subpackage Grid
 */

define([], function() {

    /**
     * Class constructor for the Grid.
     *
     * @class
     * @constructor
     */
    function Grid() {
        if (!(this instanceof Grid)) {
            return new Grid();
        }
        this.activeClass = 'tw-grid__item_active';
        this.widget = '';
    }

    Grid.prototype = {
        // Ensure instanceof knows this is a Grid
        constructor: Grid,

        /**
         * Add event listeners for Grids
         *
         */
        events: function() {
            var that = this;

            this.widget.addEventListener('click', function(e) {
                if (!e.target) {
                    return;
                }

                if (e.target.closest('[data-tw-grid-item-toggle]')) {
                    e.preventDefault();
                    var tile = e.target.closest('[data-tw-grid-item]');
                    var isActive = tile.classList.contains(that.activeClass);

                    // Clear active tiles
                    that.unsetActive();

                    // If active tile was toggled
                    if (isActive) {
                        that.triggerEvent('remove', {});
                        return;
                    }

                    // Set tile active
                    that.setActive(tile);
                }
            });
        },

        /**
         * Set parent
         *
         * @param {node} parent
         */
        setParent: function(parent) {
            this.widget = parent;
        },

        /**
         * Set tile active
         *
         * @param {node} tile
         */
        setActive: function(tile) {
            var tileID = tile.getAttribute('data-tw-grid-item-ID');

            tile.setAttribute('data-tw-grid-item-active', '');
            tile.classList.add(this.activeClass);
            this.setVisibleView();
            this.triggerEvent('add', {
                key: 'id',
                val: tileID
            });
        },

        /**
         * Set viewport to active tile
         *
         */
        setVisibleView: function() {
            var activeTile = this.widget.querySelector('[data-tw-grid-item-active]');
            if (activeTile) {
                activeTile.scrollIntoView(false);
            }
        },

        /**
         * Trigger event
         *
         * @param {string} eventName
         * @param {object} data
         */
        triggerEvent: function(eventName, data) {
            var propagateEvent = new CustomEvent('totara_core/grid:' + eventName, {
                bubbles: true,
                detail: data
            });
            this.widget.dispatchEvent(propagateEvent);
        },

        /**
         * Unset active tiles
         *
         */
        unsetActive: function() {
            var node,
                nodeList = this.widget.querySelectorAll('[data-tw-grid-item-active]');
            for (var i = 0; i < nodeList.length; i++) {
                node = nodeList[i];
                node.removeAttribute('data-tw-grid-item-active');
                node.classList.remove(this.activeClass);
            }
        }
    };

    /**
     * Initialisation method
     *
     * @param {node} parent
     * @returns {Object} promise
     */
    var init = function(parent) {
        return new Promise(function(resolve) {
            var wgt = new Grid();
            wgt.setParent(parent);
            wgt.events();
            resolve(wgt);
        });
    };

    return {
        init: init
    };
 });