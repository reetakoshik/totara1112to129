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
    * Class constructor for search text select.
    *
    * @class
    * @constructor
    */
    function SelectSearchText() {
        if (!(this instanceof SelectSearchText)) {
            return new SelectSearchText();
        }
        this.activeSelector = 'data-tw-selector-active';
        this.clearSelector = 'data-tw-selectorgroup-clear';
        this.hideClass = 'tw-selectSearchText__hidden';
        this.searchTerm = '';
        this.widget = '';
    }

    SelectSearchText.prototype = {
        constructor: SelectSearchText,

        /**
        * Add value
        *
        * @param {string} value
        */
        add: function(value) {
            var inputNode = this.widget.querySelector('[data-tw-selectSearchText-urlkey]'),
                searchKey = inputNode.getAttribute('data-tw-selectSearchText-urlkey'),
                iconTarget = inputNode.parentNode.querySelector('[data-tw-selectSearchText-clear]');

            // Update search state
            this.setSearchTerm(value);
            inputNode.setAttribute(this.activeSelector, true);

            // Update UI
            iconTarget.classList.remove(this.hideClass);

            // Inform parent of this change
            this.triggerEvent('add', {
                key: searchKey,
                val: value
            });
        },

        /**
        * Clear value
        *
        */
        clear: function() {
            var inputNode = this.widget.querySelector('[data-tw-selectSearchText-urlkey]'),
                searchKey = inputNode.getAttribute('data-tw-selectSearchText-urlkey'),
                iconTarget = inputNode.parentNode.querySelector('[data-tw-selectSearchText-clear]');

            // Update search state
            this.setSearchTerm('');
            inputNode.removeAttribute(this.activeSelector);

            // Update UI
            iconTarget.classList.add(this.hideClass);
            inputNode.value = '';

            this.triggerEvent('remove', {
                key: searchKey,
                val: ''
            });
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

                // If submit btn clicked
                if (e.target.closest('[data-tw-selectSearchText-trigger]')) {
                    that.valueCheck();

                } else if (e.target.closest('[data-tw-selectSearchText-clear]')) {
                    that.clear();
                    that.triggerEvent('changed', {});
                }
            });

            this.widget.addEventListener('keydown', function(e) {
                if (e.target.closest('[data-tw-selectSearchText-urlkey]')) {
                    // Behat only supplies e.KeyCode & e.charCode which is deprecated in favour of e.key
                    if (e.key === 'Enter' || e.which === 13 || e.keyCode === 13) {
                        e.preventDefault();
                        that.valueCheck();
                    }
                }
            });

            // Create an observer instance with a callback function for clearing active
            var observeClearBtn = new MutationObserver(function() {
                if (that.widget.getAttribute(that.clearSelector) === 'true') {
                    that.clear();
                    that.widget.setAttribute(that.clearSelector, false);
                }
            });

            // Start observing for selectGroup clear attribute mutations
            observeClearBtn.observe(this.widget, {
                attributes: true,
                attributeFilter: [that.clearSelector],
                subtree: false
            });
        },

        /**
        * Inform parent of preset values
        *
        */
        preset: function() {
            var input = this.widget.querySelector('[data-tw-selectSearchText-urlkey]'),
                inputVal = input.value;

            // Edge case,value provided in template but user has cleared input & refreshed page
            if (inputVal === '' && input.getAttribute(this.activeSelector) === true) {
                this.clear();
                this.triggerEvent('changed', {});
            } else if (inputVal === '') {
                return;
            } else {
                this.setSearchTerm(inputVal);
                this.add(inputVal);
            }
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
        * Set search term
        *
        * @param {string} searchTerm
        */
        setSearchTerm: function(searchTerm) {
            this.searchTerm = searchTerm;
        },

        /**
        * Trigger event
        *
        * @param {string} eventName
        * @param {object} data
        */
        triggerEvent: function(eventName, data) {
            var propagateEvent = new CustomEvent('totara_core/select_search_text:' + eventName, {
                bubbles: true,
                detail: data
            });
            this.widget.dispatchEvent(propagateEvent);
        },

        /**
        * check search value
        *
        */
        valueCheck: function() {
            var inputVal = this.widget.querySelector('[data-tw-selectSearchText-urlkey]').value;

            if (this.searchTerm === inputVal) {
                return;
            } else if (inputVal === '') {
                this.clear();
            } else {
                this.add(inputVal);
            }

            this.triggerEvent('changed', {});
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
            var wgt = new SelectSearchText();
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