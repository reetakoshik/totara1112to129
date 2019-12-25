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
 * @subpackage results_sort
 */

define([], function() {

    /**
     * Class constructor for the ResultsSort.
     *
     * @class
     * @constructor
     */
    function ResultsSort() {
        if (!(this instanceof ResultsSort)) {
            return new ResultsSort();
        }
        this.widget = '';
    }

    ResultsSort.prototype = {

        bubbledEventsListener: function() {
            var selectTreeEvents = 'totara_core/select_tree',
                that = this;

            this.widget.addEventListener(selectTreeEvents + ':add', function(e) {
                that.triggerEvent('add', e.detail);
            });
            this.widget.addEventListener(selectTreeEvents + ':changed', function() {
                that.triggerEvent('changed', {});
            });
            this.widget.addEventListener(selectTreeEvents + ':remove', function(e) {
                that.triggerEvent('remove', e.detail);
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
         * Trigger event
         *
         * @param {string} eventName
         * @param {object} data
         */
        triggerEvent: function(eventName, data) {
            var propagateEvent = new CustomEvent('totara_catalog/results_sort:' + eventName, {
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
            var wgt = new ResultsSort();
            wgt.setParent(parent);
            wgt.bubbledEventsListener();
            resolve(wgt);
        });
    };

    return {
        init: init
    };
 });