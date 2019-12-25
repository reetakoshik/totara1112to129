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
 * @subpackage share
 */

define([], function() {

    /**
     * Class constructor for the ResultsShare.
     *
     * @class
     * @constructor
     */
    function ResultsShare() {
        if (!(this instanceof ResultsShare)) {
            return new ResultsShare();
        }
        this.activeClass = 'tw-catalog__activePopover';
        this.widget = '';
    }

    ResultsShare.prototype = {

        /**
         * Copy URL to clipboard
         *
         */
        copyToClipboard: function() {
            var input = this.widget.querySelector('[data-tw-catalogResultsShare_url]');
            input.select();
            document.execCommand('copy');
        },

        /**
         * Display URL in input bpx
         *
         */
        displayURL: function() {
            var input = this.widget.querySelector('[data-tw-catalogResultsShare_url]'),
                url = window.location.href;
            input.setAttribute('value', url);
        },

        /**
         * Add event listeners
         *
         */
        events: function() {
            var that = this;

            // Click handler
            this.widget.addEventListener('click', function(e) {
                if (!e.target || e.target.closest('label')) {
                    return;
                }
                e.preventDefault();

                if (e.target.closest('[data-tw-catalogResultsShare_btn]')) {
                    that.displayURL();
                    that.toggleUI();

                    // Move expanded panel into view
                    var expandedNode = that.widget.querySelector('[data-tw-catalogresultsshare_expanded]');
                    if (expandedNode.getBoundingClientRect().top < 0) {
                        expandedNode.scrollIntoView({block: 'start'});
                    }
                } else if (e.target.closest('[data-tw-catalogresultsshare_close]')) {
                    that.toggleUI();
                } else if (e.target.closest('[data-tw-catalogResultsShare_expanded_btn]')) {
                    that.copyToClipboard();
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
         * Toggle expanded UI
         *
         */
        toggleUI: function() {
            this.widget.classList.toggle(this.activeClass);
        },

        /**
         * Trigger event
         *
         * @param {string} eventName
         * @param {object} data
         */
        triggerEvent: function(eventName, data) {
            var propagateEvent = new CustomEvent('catalog/results_share:' + eventName, {
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
            var wgt = new ResultsShare();
            wgt.setParent(parent);
            wgt.events();
            resolve(wgt);
        });
    };

    return {
        init: init
    };
 });