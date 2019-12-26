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
 * @subpackage manage_btns
 */

define([], function() {

    /**
     * Class constructor for the ManageBtns.
     *
     * @class
     * @constructor
     */
    function ManageBtns() {
        if (!(this instanceof ManageBtns)) {
            return new ManageBtns();
        }
        this.widget = '';
    }

    ManageBtns.prototype = {

        /**
         * Add event listeners
         *
         */
        events: function() {
            // Click handler
            this.widget.addEventListener('click', function(e) {
                if (!e.target) {
                    return;
                }

                if (e.target.closest('[data-tw-catalogManageBtnsGroup]')) {
                    if (!e.target.closest('.tw-catalogManageBtns__group_options')) {
                        e.preventDefault();
                    }
                    var list = e.target.closest('[data-tw-catalogManageBtnsGroup]');
                    list.classList.toggle('tw-catalog__activePopover');
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

    };

    /**
     * Initialisation method
     *
     * @param {node} parent
     * @returns {Object} promise
     */
    var init = function(parent) {
        return new Promise(function(resolve) {
            var wgt = new ManageBtns();
            wgt.setParent(parent);
            wgt.events();
            resolve(wgt);
        });
    };

    return {
        init: init
    };
 });