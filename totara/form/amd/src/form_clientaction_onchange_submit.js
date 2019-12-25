/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Brian Barnes <brian.barnes@totaralms.com>
 * @package totara_form
 */

/**
 * Totara onchange submit client action.
 *
 * @since Totara 9.10, 10
 * @module  totara_form/form_clientaction_onchange_submit
 * @class OnChangeSubmitAction
 * @author  Brian Barnes <brian.barnes@totaralms.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'jquery',
    'totara_form/form',
    'totara_form/clientaction_base'
], function($, Form, ClientAction) {

    /* global M */

    var MODULE = 'totara_form/form_clientaction_onchange_submit';

    var debug = function(message) {
        var level = (typeof arguments[1] !== 'undefined') ? arguments[1] : Form.LOGLEVEL.debug;
        Form.debug(message, OnChangeSubmitAction, level);
    };

    /**
     * Onchange submit client action.
     *
     * Submits the form when a given value has changed
     *
     * @class
     * @constructor
     * @augments ClientAction
     */
    var OnChangeSubmitAction = function() {

        ClientAction.apply(this, arguments);

        this.initialValues = [];
        this.submitTimeoutID = 0;
        this.submitFormCallback = $.proxy(this.submitForm, this);
        this.ignoredValues = arguments[0].ignoredvalues || [];

    };

    OnChangeSubmitAction = Form.extend(ClientAction, OnChangeSubmitAction, {

        init: function () {
            var element = this.form.getElementById(this.actionconfig.target);
            if (typeof element === 'undefined') {
                debug('Unable to retrieve the element with id ' + this.actionconfig.target, Form.LOGLEVEL.error);
                return;
            }
            this.getWatchedIds().push(this.actionconfig.target);

            this.initialValues[this.actionconfig.target] = element.getValue();
            debug('Registered onchange_submit client action for ' + this.actionconfig.target);
        },

        checkState: function () {
            var submit = false;

            if (this.submitTimeoutID) {
                this.cancelSubmitForm();
            }

            this.getWatchedIds().forEach(function (elementid) {
                if (submit === true) {
                    // We only need one to be true and the form will be submit.
                    return;
                }
                debug('Checking ' + elementid + ' for onchange_submit');
                var element = this.form.getElementById(elementid),
                    value;
                if (typeof element === 'undefined') {
                    debug('Unable to retrieve the element with id ' + this.actionconfig.target, Form.LOGLEVEL.error);
                    return;
                }
                value = element.getValue();

                var equal = (function(initial, newValue) {
                    var index;
                    // Array comparison
                    if (Array.isArray(initial) && Array.isArray(newValue)) {
                        if (initial.length !== newValue.length) {
                            return false;
                        }
                        for (index = 0; index < newValue.length; index++) {
                            if (initial[index] !== newValue[index]) {
                                return false;
                            }
                        }
                        return true;
                    }

                    // traditional string comparison
                    if (initial === newValue) {
                        return true;
                    } else {
                        return false;
                    }
                })(this.initialValues[elementid], value);

                if (!equal) {
                    this.initialValues[elementid] = value;

                    // Check if it is an ignored value. If so we don't submit.
                    if (this.ignoredValues.indexOf(value.toString()) === -1) {
                        element.showLoading();
                        submit = true;
                    }
                }
            }, this);

            if (submit) {
                debug('Onchange submit client action triggered for ' + this.actionconfig.target);
                this.form.showLoading();
                // Tell JS and things like behat that we are now waiting for something to happen.
                M.util.js_pending(MODULE);
                this.submitTimeoutID = setTimeout(this.submitFormCallback, this.actionconfig.delay);
                $(window).on('beforeunload', this.submitFormCallback);
            }
        },

        /**
         * @private
         */
        cancelSubmitForm: function () {
            debug('Clearing onchange submit client action timeout for ' + this.actionconfig.target);
            clearTimeout(this.submitTimeoutID);
            this.submitTimeoutID = 0;
            $(window).off('beforeunload', this.submitFormCallback);
            M.util.js_complete(MODULE);
        },

        /**
         * @private
         */
        submitForm: function () {
            debug('Onchange submit client action occurring for ' + this.actionconfig.target);
            this.form.submit();
            this.submitTimeoutID = 0;
            $(window).off('beforeunload', this.submitFormCallback);
            M.util.js_complete(MODULE);
        },

        /**
         * Returns a string describing this object.
         * @returns {string}
         */
        toString: function () {
            return '[object OnChangeSubmitAction]';
        }
    });

    return {
        /**
         * Initialises a new onchange submit client action.
         *
         * Calls done() passing it an {OnChangeSubmitAction} instance, having already called init() on it.
         *
         * @param {Object} actionconfig
         * @param {Form} totaraform
         * @param {Function} done
         */
        init: function(actionconfig, totaraform, done) {
            var action = new OnChangeSubmitAction(actionconfig, totaraform);
            action.init();
            done(action);
        }
    };

});