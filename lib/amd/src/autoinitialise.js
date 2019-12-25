/*
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
 */

/**
 * Automatic AMD module initialisation via data-core-autoinitialise attribute.
 *
 * This module is supposed to be executed at initial page load and later
 * when new nodes are added to the DOM, such as when injecting rendered
 * template.
 *
 * @package core
 * @author  Brian Barnes <brian.barnes@totaralearning.com>
 * @module  core/autoinitialise
 */
define(['core/log'], function(log) {
    var INITIALISATION_DATA = 'data-core-autoinitialise';
    var AMD_DATA = 'data-core-autoinitialise-amd';
    var STATE_NOTINITIALISED = "true";
    var STATE_INITIALISING = "initialising";
    var STATE_INITIALISED = "done";
    var STATE_INITIALISATIONFAILED = "error";

    /**
     * Scan's for nodes that contain autoinitialise attributes, initialising them when possible
     *
     * @returns {Promise} ES6 promise which resolves when all DOM elements are initialised
     */
    var autoScan = function() {
        var initingObjects = [];
        var elements = document.querySelectorAll('[' + INITIALISATION_DATA + '=' + STATE_NOTINITIALISED + ']');

        /**
         * Call when AMD init fails.
         *
         * @param {Node} element DOM node that contains data-core-autoinitialise and data-core-autoinitialise-amd attributes
         * @param {String} reason Reason to reject the promise
         */
        var initFailed = function(element, reason) {
            log.error(reason);
            element.setAttribute(INITIALISATION_DATA, STATE_INITIALISATIONFAILED);
        };

        /**
         * Creates a function suitable for adding as an argument to the Promise constructor
         *
         * @param {Node} element DOM node to be enhanced
         * @param {String} amdModule AMD module name
         * @returns {Function} A function suitable to be set as the argument to the promise constructor
         */
        var initialise = function(element, amdModule) {
            /**
             * Promise function
             *
             * @param {Function} resolve called when the module finishes initialisation, both success and failure
             */
            var promiseFunc = function(resolve) {

                // NOTE: Mark as resolved even if failed, the reason is that we do not care about failures here,
                //       we just want to wait until all modules finish initialisation.

                require([amdModule], function(js) {
                    if (typeof js.init === 'undefined') {
                        initFailed(element, 'AMD module "' + amdModule + '" cannot be autoinitialised because init method is missing');
                        resolve();
                        return;
                    }
                    try {
                        var result = js.init(element);
                        if (result && (typeof result.then === 'function')) {
                            // We may need to wait a bit longer for init to finish.
                            result.then(function() {
                                element.setAttribute(INITIALISATION_DATA, STATE_INITIALISED);
                                resolve();
                            }).catch(function(reason) {
                                initFailed(element, reason);
                                resolve();
                            });
                        } else {
                            // AMD module is not returning a promise, so just mark as done.
                            element.setAttribute(INITIALISATION_DATA, STATE_INITIALISED);
                            resolve();
                        }
                    } catch (e) {
                        initFailed(element, 'AMD module "' + amdModule + '" failed to autoinitialise');
                        resolve();
                    }
                }, function() {
                    initFailed(element, 'Failed to load AMD module "' + amdModule + '"');
                    resolve();
                });
            };

            return promiseFunc;
        };

        for (var el = 0; el < elements.length; el++) {
            var element = elements[el];
            var amdModule = element.getAttribute(AMD_DATA);

            // Make sure the AMD module data attribute is present and contains PARAM_SAFEPATH compatible value,
            // see lib/requirejs.php code why.
            if (amdModule === null) {
                initFailed(element, 'AMD module cannot be autoinitialised because "' + AMD_DATA + '" attribute is missing');
                continue;
            }
            if (!amdModule.match(/^[a-zA-Z0-9\/_-]+$/g)) {
                initFailed(element, 'AMD module cannot be autoinitialised because "' + AMD_DATA + '" attribute is invalid');
                continue;
            }

            // Function promiseFunc is executed asynchronously later, this means
            // that the element attribute will not change while this loop is still running.
            element.setAttribute(INITIALISATION_DATA, STATE_INITIALISING);

            var promiseFunc = initialise(element, amdModule);
            var initingPromise = new Promise(promiseFunc);
            initingObjects.push(initingPromise);
        }

        return Promise.all(initingObjects);
    };

    return {
        scan: autoScan
    };
});