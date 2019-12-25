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
 * @package totara
 * @subpackage toggle_filter_panel
 */

define([], function() {

    /**
     * Class constructor for the ToggleFilterPanel.
     *
     * @class
     * @constructor
     */
    function ToggleFilterPanel() {
        if (!(this instanceof ToggleFilterPanel)) {
            return new ToggleFilterPanel();
        }

        this.activeClass = 'tw-toggleFilterPanel__active';
        this.fixedClass = 'tw-toggleFilterPanel__trigger_fixed';
        this.targetwidget = '.tw-selectRegionPanel';
        this.toggleClass = 'tw-selectRegionPanel__hiddenOnSmall_show';
        this.widget = '';
    }

    ToggleFilterPanel.prototype = {
        // Ensure instanceof knows this is a ToggleFilterPanel
        constructor: ToggleFilterPanel,

        /**
         * Add event listeners for toggle sibling widget
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

                if (e.target.closest('[data-tw-toggleFilterPanel-trigger]')) {
                    that.toggleWidget();
                }
            });

            // Scroll event, only enabled when conditions met
            this.setPositionFixedEvent = function() {
                var trigger = that.widget.querySelector('[data-tw-toggleFilterPanel-trigger]'),
                    parentNode = that.widget.parentNode,
                    parentOffset = parentNode.getBoundingClientRect().top,
                    widgetHeight = that.widget.offsetHeight,
                    maxRange = parentNode.offsetHeight - widgetHeight;

                if (parentOffset < 0 && parentOffset > -maxRange) {
                    trigger.classList.add(that.fixedClass);
                } else {
                    trigger.classList.remove(that.fixedClass);
                }
            };

            // Create an observer instance with a callback function for clearing active filters
            var observeAddLabelContent = new MutationObserver(function() {
                if (that.widget.getAttribute('data-tw-toggleFilterPanel-addLabelContent') !== 'false') {
                    var content = that.widget.getAttribute('data-tw-toggleFilterPanel-addLabelContent');
                    that.widget.querySelector('[data-tw-toggleFilterPanel-extraContent]').innerHTML = content;
                    that.widget.setAttribute('data-tw-toggleFilterPanel-addLabelContent', false);
                }
            });

            // Start observing the widget for filtergroup clear attribute mutations
            observeAddLabelContent.observe(this.widget, {
                attributes: true,
                attributeFilter: ['data-tw-togglefilterpanel-addlabelcontent'],
                subtree: false
            });
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
         * Set position fixed
         *
         */
        setPositionFixed: function() {
            var trigger = this.widget.querySelector('[data-tw-toggleFilterPanel-trigger]');

            // Add fixed class if toggle is visible
            if (this.widget.classList.contains(this.activeClass)) {
                window.addEventListener('scroll', this.setPositionFixedEvent, true);
            } else {
                window.removeEventListener('scroll', this.setPositionFixedEvent, true);
                trigger.classList.remove(this.fixedClass);
            }
        },

        /**
         * Toggle widget
         *
         */
        toggleWidget: function() {
            this.widget.classList.toggle(this.activeClass);
            this.setPositionFixed();

            // Inform parent widget of this change
            this.triggerEvent('changed', {
                targetwidget: this.targetwidget,
                toggleClass: this.toggleClass
            });
        },

        /**
         * Trigger event
         *
         * @param {string} eventName
         * @param {object} data
         */
        triggerEvent: function(eventName, data) {
            var propagateEvent = new CustomEvent('totara_catalog/toggle_filter_panel:' + eventName, {
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
            // Create an instance of widget.
            var wgt = new ToggleFilterPanel();
            wgt.setParent(widgetParent);
            wgt.events();
            resolve(wgt);
        });
    };

    return {
        init: init
    };
 });