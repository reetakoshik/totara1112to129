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
    * Class constructor for the SelectRegionPanel.
    *
    * @class
    * @constructor
    */
    function SelectRegionPanel() {
        if (!(this instanceof SelectRegionPanel)) {
            return new SelectRegionPanel();
        }

        this.activeSelector = 'data-tw-selector-active';
        this.clearNode = '';
        this.clearSelector = 'data-tw-selectorgroup-clear';
        this.hideClass = 'tw-selectRegionPanel__content_hidden';
        this.widget = '';
    }

    SelectRegionPanel.prototype = {

        // Listen for propagated events
        bubbledEventsListener: function() {
            var selectMultiEvents = 'totara_core/select_multi',
                selectSearchTextEvents = 'totara_core/select_search_text',
                selectTreeEvents = 'totara_core/select_tree',
                that = this;

            this.widget.addEventListener(selectMultiEvents + ':add', function(e) {
                that.triggerEvent('add', e.detail);
            });
            this.widget.addEventListener(selectSearchTextEvents + ':add', function(e) {
                that.triggerEvent('add', e.detail);
            });
            this.widget.addEventListener(selectTreeEvents + ':add', function(e) {
                that.triggerEvent('add', e.detail);
            });

            this.widget.addEventListener(selectMultiEvents + ':changed', function() {
                that.regionChange();
            });
            this.widget.addEventListener(selectSearchTextEvents + ':changed', function() {
                that.regionChange();
            });
            this.widget.addEventListener(selectTreeEvents + ':changed', function() {
                that.regionChange();
            });

            this.widget.addEventListener(selectMultiEvents + ':remove', function(e) {
                that.triggerEvent('remove', e.detail);
            });
            this.widget.addEventListener(selectSearchTextEvents + ':remove', function(e) {
                that.triggerEvent('remove', e.detail);
            });
            this.widget.addEventListener(selectTreeEvents + ':remove', function(e) {
                that.triggerEvent('remove', e.detail);
            });
        },

        /**
        * Clear active
        *
        */
        clear: function() {
            // Inform selectors with active options to clear by adding a data attribute
            var nodeList = this.widget.querySelectorAll('[' + this.activeSelector + ']'),
                that = this;

            if (!nodeList) {
                return;
            }

            // Observe all selectors, to check when clear is complete.
            var observeClearable = new MutationObserver(function() {
                var pendingClearGroups = that.widget.querySelectorAll('[' + that.clearSelector + '=true]').length;
                // If no groups waiting to be cleared, clear has completed.
                if (!pendingClearGroups) {
                    this.disconnect();
                    that.regionChange();
                }
            });

            // Start observing the selectors for changes to clear state
            observeClearable.observe(this.widget, {
                attributes: true,
                attributeFilter: [that.clearSelector],
                subtree: true
            });

            for (var i = 0; i < nodeList.length; i++) {
                var group = nodeList[i].closest('[data-tw-selectorgroup]'),
                    clearState = group.getAttribute(this.clearSelector);

                if (!clearState || clearState === 'false') {
                    group.setAttribute(this.clearSelector, true);
                }
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

                // Listen for selector reset (clears all selectors)
                if (e.target.closest('[data-tw-selectRegionPanel-clear]')) {
                    that.clear();
                }
            });
        },

        /**
        * Region has a change event
        *
        */
        regionChange: function() {
            this.updateActiveCount();
            this.triggerEvent('changed', {});
        },

        /**
        * Update active count
        *
        */
        updateActiveCount: function() {
            var activeCount = this.widget.querySelectorAll('[' + this.activeSelector + ']').length,
                clearBtnNode = this.widget.querySelector('[data-tw-selectRegionPanel-clear]'),
                countTextNode = this.widget.querySelector('[data-tw-selectRegionPanel-count]');

            // If we have a clear button
            if (clearBtnNode) {
                if (activeCount > 0) {
                    clearBtnNode.classList.remove(this.hideClass);
                } else {
                    clearBtnNode.classList.add(this.hideClass);
                }
            }

            // If we have an active count
            if (countTextNode) {
                countTextNode.innerHTML = activeCount;

                if (activeCount > 0) {
                    countTextNode.parentNode.classList.remove(this.hideClass);
                } else {
                    countTextNode.parentNode.classList.add(this.hideClass);
                }
            }
        },

        /**
        * Set widget parent
        *
        * @param {node} widgetParent
        */
        setParent: function(widgetParent) {
            this.widget = widgetParent;
        },

        /**
        * Trigger event
        *
        * @param {string} eventName
        * @param {object} data
        */
        triggerEvent: function(eventName, data) {
            var propagateEvent = new CustomEvent('totara_core/select_region_panel:' + eventName, {
                bubbles: true,
                detail: data
            });
            this.widget.dispatchEvent(propagateEvent);
        }
    };

    /**
    * widget initialisation method
    *
    * @param {node} widgetParent
    * @returns {Object} promise
    */
    var init = function(widgetParent) {
        return new Promise(function(resolve) {
            // Create an instance of widget
            var wgt = new SelectRegionPanel();
            wgt.setParent(widgetParent);
            wgt.bubbledEventsListener();
            wgt.events();
            wgt.updateActiveCount();
            resolve(wgt);
        });
    };

    return {
        init: init
    };
 });