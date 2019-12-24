/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @package totara_form
 */

/**
 * Totara Hidden If client action.
 *
 * @module  totara_form/form_clientaction_hidden_if
 * @class   HiddenIf
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['totara_form/form', 'totara_form/clientaction_base'], function(Form, ClientAction) {

    /**
     * Hidden If client action.
     *
     * Hides the target element when specific conditions are met.
     *
     * @class
     * @constructor
     * @augments ClientAction
     */
    var HiddenIf = function() {

        /**
         * An array of comparison objects
         * @protected
         * @type {Object[]}
         */
        this.comparisons = [];

        ClientAction.apply(this, arguments);

    };
    HiddenIf = Form.extend(ClientAction, HiddenIf, {

        /**
         * Initialises this Hidden If client action.
         */
        init: function() {
            var i,
                comparison;

            if (this.actionconfig.comparisons) {
                for (i in this.actionconfig.comparisons) {
                    if (this.actionconfig.comparisons.hasOwnProperty(i)) {
                        comparison = this.actionconfig.comparisons[i];
                        this.watchedIds.push(comparison.element);
                        comparison.element = this.form.getElementById(comparison.element);
                        this.comparisons.push(comparison);
                    }
                }
            }
            Form.debug('Registered HiddenIf with ' + this.comparisons.length + ' comparisons', HiddenIf, Form.LOGLEVEL.debug);
        },

        /**
         * Enforce the hidden if action on the client.
         *
         * @protected
         */
        enforce: function() {
            Form.debug('Enforcing HiddenIf rule on #' + this.target.getId(), this, Form.LOGLEVEL.info);
            this.target.setHidden(true);
        },

        /**
         * Cease enforcement of the hidden if on the client.
         *
         * @protected
         */
        cease: function() {
            Form.debug('Ceasing enforcement of HiddenIf rule on #' + this.target.getId(), this, Form.LOGLEVEL.info);
            this.target.setHidden(false);
        },

        /**
         * Checks the state of this hidden if client action.
         */
        checkState: function() {
            var i,
                comparison,
                current = this.getCurrentState(),
                args,
                apply = false,
                coparisonResult;

            for (i in this.comparisons) {
                if (this.comparisons.hasOwnProperty(i)) {
                    comparison = this.comparisons[i];
                    args = [comparison.operator].concat(comparison.options);
                    coparisonResult = comparison.element.compare.apply(comparison.element, args);
                    if (coparisonResult === true) {
                        apply = true;
                        break;
                    }
                }
            }

            if (apply) {
                if (!current) {
                    this.enforce();
                }
            } else {
                if (current) {
                    // We need to cease applying the action. Its no longer needed.
                    this.cease();
                }
            }
        },

        /**
         * Returns true if this action should be enforced, and false if it should be ceased.
         *
         * @returns {boolean}
         */
        getCurrentState: function() {
            return this.target.isHidden();
        }
    });

    return {
        /**
         * Initialises a new HiddenIf client action.
         *
         * When the action has been initialised {@see done()} must be called.
         *
         * @param {Object} actionconfig
         * @param {Form} totaraform
         * @param {Function} done
         * @returns {HiddenIf}
         */
        init: function(actionconfig, totaraform, done) {
            var action = new HiddenIf(actionconfig, totaraform);
            action.init();
            done(action);
        }
    };

});
