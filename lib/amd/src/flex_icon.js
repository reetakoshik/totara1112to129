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
 * @author    Brian Barnes <brian.barnes@totaralearning.com>
 * @copyright 2016 onwards Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   core
 */

define(['jquery', 'core/config', 'core/localstorage', 'core/ajax'], function($, config, storage, ajax) {
    var initstarted = false;
    var iconsdata = false;
    var templatesDeferred = null;

    /**
     * Loads the icons cache
     *
     * @method init
     * @private
     */
    var init = function() {
        var STORAGEKEY = 'core_flex_icon/' + config.theme + '/cache',
            deferred = $.Deferred();
        initstarted = true;
        templatesDeferred = deferred.promise();

        var cachesrc = storage.get(STORAGEKEY);
        if (cachesrc) {
            // We've got it in storage, get it and resolve the promise immediately.
            iconsdata = JSON.parse(cachesrc);
            deferred.resolve();
        } else {
            // Load it via ajax, store it, and resolve the promise.
            $.when.apply(this, ajax.call([{
                methodname: 'core_output_get_flex_icons',
                args: {
                    themename: config.theme
                }
            }], true, false)).done(function(iconsCacheSource) {
                storage.set(STORAGEKEY, JSON.stringify(iconsCacheSource));
                iconsdata = iconsCacheSource;
                deferred.resolve();
            });
        }
    };

    var cache = /** @alias module:core/flex_icon */{

        /**
         * Gets the template and data for the given flex icon
         *
         * @method getFlexTemplateData
         * @public
         * @param {String} identifier The requested flex icon
         * @return {Promise} Resolves with an object that contains the
         *                   template name and base data for the template
         */
        getFlexTemplateData: function(identifier) {
            var deferred = $.Deferred(),
                promise = deferred.promise();

            var resolvetemplate = function() {
                if (!iconsdata.icons.hasOwnProperty(identifier)) {
                    deferred.reject();
                    return;
                }

                var iconindexs = iconsdata.icons[identifier];
                var icondata = {
                    data: $.extend({}, iconsdata.datas[iconindexs[1]]),
                    template: iconsdata.templates[iconindexs[0]]
                };
                icondata.data.identifier = identifier;

                deferred.resolve(icondata);
            };

            templatesDeferred.done(resolvetemplate);

            return promise;
        },

        /**
         * Loads context and template for a pix or flex icon
         *
         * @param {string} identifier this could either be a flex or pix icon identifier
         * @param {string} component which component the icon is part of
         * @param {object} data Data for the template
         * @returns {Promise} jQuery Deferred - resolved with a object containing the context data and the template
         *                          for the icon
         */
        getIconData: function(identifier, component, data) {
            var deferred = $.Deferred(),
                prom = deferred.promise(),
                that = this,
                iconidentifier;

            if (typeof data === 'undefined') {
                data = {};
            }

            if (!component || component === '' || component === 'moodle' || component === 'core') {
                iconidentifier = identifier;
                component = 'core';
            } else {
                iconidentifier = component + '|' + identifier;
            }

            if (typeof data.title === 'undefined' && typeof data.alt !== 'undefined') {
                data.title = data.alt;
            }

            var loadflexicon = function(icondata) {
                // A flex icon
                icondata.data.customdata = data;
                deferred.resolve({
                    'context': icondata.data,
                    'template': icondata.template
                });
            };

            var loadpixicon = function() {
                // attempt to use the pix icon
                var attributes = [],
                    extraclasses = '';

                for (var name in data) {
                    if (name === 'class' && data.hasOwnProperty(name)) {
                        extraclasses = data[name];
                    } else if (data.hasOwnProperty(name)) {
                        attributes.push({name: name, value: data[name]});
                    }
                }

                var url = config.wwwroot + '/theme/image.php';
                if (config.themerev > 0 && config.slasharguments == 1) {
                    url += '/' + config.theme + '/' + component + '/' + config.themerev + '/' + identifier;
                } else {
                    url += '?theme=' + config.theme + '&component=' + component + '&rev=' + config.themerev + '&image=' + identifier;
                }

                attributes.push({name: 'src', value: url});

                deferred.resolve({
                    'template': 'core/pix_icon',
                    'context': {
                        'attributes': attributes,
                        'extraclasses': extraclasses
                    }
                });
            };

            this.getFlexTemplateData(iconidentifier)
                .then(loadflexicon)
                .fail(function() {
                    if (iconidentifier === component + '|' + identifier) {
                        loadpixicon();
                    } else {
                        iconidentifier = component + '|' + identifier;
                        that.getFlexTemplateData(iconidentifier)
                            .then(loadflexicon)
                            .fail(loadpixicon);
                    }
                });

            return prom;
        }
    };

    if (!initstarted) {
        init();
    }
    return cache;
});
