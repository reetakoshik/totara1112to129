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
 * @package totara_core
 */

define([], function() {

    /**
    * Class constructor for the SelectMulti selector.
    *
    * @class
    * @constructor
    */
    function SelectMulti() {
        if (!(this instanceof SelectMulti)) {
            return new SelectMulti();
        }
        this.activeClass = 'tw-selectMulti__link_active';
        this.activeSelector = 'data-tw-selector-active';
        this.clearSelector = 'data-tw-selectorgroup-clear';
        this.hideClass = 'tw-selectMulti__hidden';
        this.key = '';
        this.widget = '';
    }

    SelectMulti.prototype = {
        constructor: SelectMulti,

        /**
        * Add active selector
        *
        * @param {node} selector node
        */
        add: function(selector) {
            var selectorKey = selector.getAttribute('data-tw-selectMulti-optionKey'),
                iconTarget = selector.querySelector('[data-tw-selectMulti-close]');

            // Update selector UI
            selector.classList.add(this.activeClass);
            selector.setAttribute(this.activeSelector, true);
            selector.setAttribute('aria-selected', true);
            iconTarget.classList.remove(this.hideClass);

            // Inform parent widget of this change
            this.triggerEvent('add', {
                groupValues: this.getAllSelectedValues(),
                key: this.key,
                val: selectorKey
            });
        },

        /**
        * Clear all active selectors
        *
        */
        clear: function() {
            var nodeList = this.widget.querySelectorAll('[' + this.activeSelector + ']');

            for (var i = 0; i < nodeList.length; i++) {
                this.remove(nodeList[i]);
            }
        },

        /**
        * Add event listeners
        *
        */
        events: function() {
            var that = this;

            this.widget.addEventListener('click', function(e) {
                e.preventDefault();
                if (!e.target) {
                    return;
                }

                if (e.target.closest('[data-tw-selectMulti-optionKey]')) {
                    var selector = e.target.closest('[data-tw-selectMulti-optionKey]');

                    // toggle selector active state
                    if (selector.getAttribute(that.activeSelector)) {
                        that.remove(selector);
                    } else {
                        that.add(selector);
                    }

                    // Inform parent widget of this change
                    that.triggerEvent('changed', {});
                }
            });

            // Observe all selectors, to check when clear is complete.
            var observeClearBtn = new MutationObserver(function() {
                if (that.widget.getAttribute(that.clearSelector) === 'true') {
                    that.clear();
                    that.widget.setAttribute(that.clearSelector, false);
                }
            });

            // Start observing the selectors for changes to clear state
            observeClearBtn.observe(this.widget, {
                attributes: true,
                attributeFilter: [that.clearSelector],
                subtree: false
            });
        },

        /**
        * Get all selected values
        *
        * @returns {array} selectedValues
        */
        getAllSelectedValues: function() {
            var itemKey,
                nodeList = this.widget.querySelectorAll('[' + this.activeSelector + ']'),
                selectedValues = [];

            for (var i = 0; i < nodeList.length; i++) {
                itemKey = nodeList[i].getAttribute('data-tw-selectMulti-optionKey');
                selectedValues.push(itemKey);
            }
            return selectedValues;
        },

        /**
        * Inform parents of preset values
        *
        */
        preset: function() {
            var nodeList = this.widget.querySelectorAll('[' + this.activeSelector + ']');

            for (var i = 0; i < nodeList.length; i++) {
                this.triggerEvent('add', {
                    groupValues: this.getAllSelectedValues(),
                    key: this.key,
                    val: nodeList[i].getAttribute('data-tw-selectMulti-optionKey')
                });
            }
        },

        /**
        * Remove active selector
        *
        * @param {node} selector node
        */
        remove: function(selector) {
            var selectorKey = selector.getAttribute('data-tw-selectMulti-optionKey'),
                iconTarget = selector.querySelector('[data-tw-selectMulti-close]');

            // Update selector UI
            selector.removeAttribute(this.activeSelector);
            selector.setAttribute('aria-selected', false);
            selector.classList.remove(this.activeClass);
            iconTarget.classList.add(this.hideClass);

            this.triggerEvent('remove', {
                groupValues: this.getAllSelectedValues(),
                key: this.key,
                val: selectorKey
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
        * Set widget key
        *
        */
        setWidgetKey: function() {
            this.key = this.widget.getAttribute('data-tw-selectMulti-key');
        },

        /**
        * Trigger event
        *
        * @param {string} eventName
        * @param {object} data
        */
        triggerEvent: function(eventName, data) {
            var propagateEvent = new CustomEvent('totara_core/select_multi:' + eventName, {
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
            var wgt = new SelectMulti();
            wgt.setParent(parent);
            wgt.setWidgetKey();
            wgt.preset();
            wgt.events();
            resolve(wgt);
        });
    };

    return {
        init: init
    };
 });