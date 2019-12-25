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
 * Totara onchange ajax submit client action.
 *
 * @since Totara 9.10, 10
 * @module  totara_form/form_clientaction_onchange_ajaxsubmit
 * @class OnChangeAjaxSubmitAction
 * @author  Brian Barnes <brian.barnes@totaralms.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'jquery',
    'totara_form/form',
    'totara_form/clientaction_base',
    'core/notification',
    'core/str'
], function($, Form, ClientAction, Notification, Str) {

    /* global M */

    var MODULE = 'totara_form/form_clientaction_onchange_ajaxsubmit';

    var debug = function(message) {
        var level = (typeof arguments[1] !== 'undefined') ? arguments[1] : Form.LOGLEVEL.debug;
        Form.debug(message, OnChangeAjaxSubmitAction, level);
    };

    /**
     * Onchange ajax submit client action.
     *
     * Submits the form via AJAX and redisplays it when a given value has changed.
     *
     * @class
     * @constructor
     * @augments ClientAction
     */
    var OnChangeAjaxSubmitAction = function() {

        ClientAction.apply(this, arguments);

        this.initialValues = [];
        this.ajaxSubmitTimeoutID = 0;
        this.ajaxSubmitFormCallback = $.proxy(this.ajaxSubmitForm, this);
        this.ignoredValues = arguments[0].ignoredvalues || [];
        this.submitHandler = arguments[0].submithandler || false;

    };

    OnChangeAjaxSubmitAction = Form.extend(ClientAction, OnChangeAjaxSubmitAction, {

        init: function() {
            var element = this.form.getElementById(this.actionconfig.target);
            if (typeof element === 'undefined') {
                debug('Unable to retrieve the element with id ' + this.actionconfig.target, Form.LOGLEVEL.error);
                return;
            }
            this.getWatchedIds().push(this.actionconfig.target);

            this.initialValues[this.actionconfig.target] = element.getValue();
            debug('Registered onchange_ajaxsubmit client action for ' + this.actionconfig.target);
        },

        checkState: function() {
            var ajaxSubmit = false;

            if (this.ajaxSubmitTimeoutID) {
                this.cancelAjaxSubmitForm();
            }

            this.getWatchedIds().forEach(function(elementid) {
                if (ajaxSubmit === true) {
                    // We only need one to be true and the form will be submit by ajax.
                    return;
                }
                debug('Checking ' + elementid + ' for onchange_ajaxsubmit');
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

                    // Check if it is an ignored value. If so we don't submit by ajax.
                    if (this.ignoredValues.indexOf(value.toString()) === -1) {
                        element.showLoading();
                        ajaxSubmit = true;
                    }
                }
            }, this);

            if (ajaxSubmit) {
                debug('Onchange submit by ajax client action triggered for ' + this.actionconfig.target);
                this.form.showLoading();
                // Tell JS and things like behat that we are now waiting for something to happen.
                M.util.js_pending(MODULE);
                this.ajaxSubmitTimeoutID = setTimeout(this.ajaxSubmitFormCallback, this.actionconfig.delay);
            }
        },

        /**
         * @private
         */
        cancelAjaxSubmitForm: function() {
            debug('Clearing onchange submit by ajax client action timeout for ' + this.actionconfig.target);
            clearTimeout(this.ajaxSubmitTimeoutID);
            this.ajaxSubmitTimeoutID = 0;
            M.util.js_complete(MODULE);
        },

        /**
         * @private
         */
        ajaxSubmitForm: function() {
            debug('Onchange submit by ajax client action occurring for ' + this.actionconfig.target);
            var deferred = this.form.ajaxSubmit();
            if (this.submitHandler) {
                this.submitHandler.apply(null, [this, deferred]);
            } else {
                deferred.done(
                    $.proxy(
                        function(status, data) {
                            if (status === 'submitted') {
                                debug('Reloading the form to get an updated version after ajaxsubmit for ' + this.actionconfig.target);
                                this.form.reload();
                            } else {
                                Str.get_strings([
                                    {key: 'error', component: 'error'},
                                    {key: 'ok', component: 'core'}
                                ]).done(function(strings) {
                                    Notification.alert(strings[0], strings[0], strings[1]);
                                });
                            }
                        },
                        this
                    )
                );
            }
            deferred.done(function(){
                M.util.js_complete(MODULE);
            });
            this.ajaxSubmitTimeoutID = 0;
        },

        /**
         * Returns a string describing this object.
         * @returns {string}
         */
        toString: function() {
            return '[object OnChangeAjaxSubmitAction]';
        }
    });

    return {
        /**
         * Initialises a new onchange submit by ajax client action.
         *
         * Calls done() passing it an {OnChangeAjaxSubmitAction} instance, having already called init() on it.
         *
         * @param {Object} actionconfig
         * @param {Form} totaraform
         * @param {Function} done
         */
        init: function(actionconfig, totaraform, done) {
            var action = new OnChangeAjaxSubmitAction(actionconfig, totaraform);
            action.init();
            done(action);
        }
    };

});