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
 * @subpackage item_style_toggle
 */

define([], function() {

    /**
     * Class constructor for the ItemStyleToggle.
     *
     * @class
     * @constructor
     */
    function ItemStyleToggle() {
        if (!(this instanceof ItemStyleToggle)) {
            return new ItemStyleToggle();
        }
        this.activeClass = 'tw-catalogItemStyleToggle__btn_active';
        this.widget = '';
    }

    ItemStyleToggle.prototype = {

        /**
         * Add event listeners
         *
         */
        events: function() {
            var that = this;

            // Click handler
            this.widget.addEventListener('click', function(e) {
                e.preventDefault();
                if (!e.target) {
                    return;
                }

                if (e.target.closest('[data-tw-catalogItemStyleToggle_trigger]')) {
                    var trigger = e.target.closest('[data-tw-catalogItemStyleToggle_trigger]');

                    // If already active, abort.
                    if (trigger.classList.contains(that.activeClass)) {
                        return;
                    }

                    that.toggle(trigger);
                    that.triggerEvent('changed', {});
                }
            });
        },

        /**
        * Inform catalog of preset value
        *
        */
        preset: function() {

            var activeBtn = this.widget.querySelector('.' + this.activeClass);
            if (!activeBtn) {
                return;
            }

            // Inform parent
            this.triggerEvent('add', {
                key: 'itemstyle',
                val: activeBtn.getAttribute('data-tw-catalogItemStyleToggle_trigger')
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
         * Toggle item view
         *
         * @param {node} btn
         */
        toggle: function(btn) {
            var btnList = this.widget.querySelectorAll('[data-tw-catalogItemStyleToggle_trigger]');

            // Update UI
            for (var i = 0; i < btnList.length; i++) {
                btnList[i].classList.toggle(this.activeClass);
            }

            // Inform parent
            this.triggerEvent('add', {
                key: 'itemstyle',
                val: btn.getAttribute('data-tw-catalogItemStyleToggle_trigger')
            });
        },

        /**
         * Trigger event
         *
         * @param {string} eventName
         * @param {object} data
         */
        triggerEvent: function(eventName, data) {
            var propagateEvent = new CustomEvent('totara_catalog/item_style_toggle:' + eventName, {
                bubbles: true,
                detail: data
            });
            this.widget.dispatchEvent(propagateEvent);
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
            var wgt = new ItemStyleToggle();
            wgt.setParent(parent);
            wgt.preset();
            wgt.events();
            resolve(wgt);
        });
    };

    return {
        init: init
    };
 });