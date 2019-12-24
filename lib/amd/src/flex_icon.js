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

define(['jquery', 'core/config', 'core/localstorage', 'core/ajax'], function ($, config, storage, ajax) {
    var initstarted = false;
    var iconsdata = false;
    var templatesDeferred = [];

    /**
     * Loads the icons cache
     *
     * @method init
     * @private
     */
    var init = function () {
        var STORAGEKEY = 'core_flex_icon/' + config.theme + '/cache';
        initstarted = true;

        var cachesrc = storage.get(STORAGEKEY);
        if (cachesrc) {
            iconsdata = JSON.parse(cachesrc);
        } else {
            var promises = ajax.call([{
                methodname: 'core_output_get_flex_icons',
                args: {
                    themename: config.theme
                }
            }], true, false);

            promises[0].done(function (iconsCacheSource) {
                storage.set(STORAGEKEY, JSON.stringify(iconsCacheSource));
                iconsdata = iconsCacheSource;
                for (var index = 0; index < templatesDeferred.length; index++) {
                    templatesDeferred[index].resolve();
                }
            });
        }
    };

    var cache = /** @alias module:core/flex_icon */{
        loadingflex: [],
        loadingtranslation: [],

        /**
         * Gets the template and data for the given flex icon
         *
         * @method getFlexTemplateData
         * @public
         * @param {String} identifier The requested flex icon
         * @return {Promise} Resolves with an object that contains the
         *                   template name and base data for the template
         */
        getFlexTemplateData: function (identifier) {
            var templatepromise = $.Deferred();

            /**
             * Resolve template data once the cache has been loaded.
             *
             * @method resolvetemplate
             * @private
             */
            var resolvetemplate = function () {
                if (typeof iconsdata.icons[identifier] === 'undefined') {
                    templatepromise.reject();
                    return;
                }

                var iconindexs = iconsdata.icons[identifier];
                // Perform clone to prevent modifying of original data. template is a pure string so no need.
                var icondata = {
                    data: JSON.parse(JSON.stringify(iconsdata.datas[iconindexs[1]])),
                    template: iconsdata.templates[iconindexs[0]]
                };
                icondata.data.identifier = identifier;

                templatepromise.resolve(icondata);
            };

            if (iconsdata) {
                // Cache is loaded - resolve immediately
                resolvetemplate();
            } else {
                // Cache hasn't been loaded, resolve once it has been.
                var response = $.Deferred();

                response.done(function () {
                    resolvetemplate();
                });
                templatesDeferred.push(response);
            }

            return templatepromise.promise();
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
            var promise = $.Deferred();
            var that = this;

            if (!component || component !== '' || component == 'moodle' || component == 'core') {
                component = 'core';
            }

            if (typeof data.title === 'undefined' && typeof data.alt !== 'undefined') {
                data.title = data.alt;
            }

            this.getFlexTemplateData(component + '|' + identifier).then(function(icondata) {
                // A flex icon
                var flexdata = {
                    template: icondata.template,
                    context: icondata.data
                };

                flexdata.context.customdata = data;

                promise.resolve(flexdata);
            }).fail(function() {
                that.getFlexTemplateData(identifier).then(function(icondata) {
                    // A flex icon with no component
                    var flexdata = {
                        template: icondata.template,
                        context: icondata.data
                    };

                    flexdata.context.customdata = data;

                    promise.resolve(flexdata);
                }).fail(function() {
                    // attempt to use the pix icon
                    var pixData = {
                        template: 'core/pix_icon',
                        context: {}
                    };
                    var attributes = [];

                    for (var name in data) {
                        if (data.hasOwnProperty(name)) {
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

                    pixData.context.attributes = attributes;
                    promise.resolve(pixData);
                });
            });
            return promise.promise();
        }
    };

    if (!initstarted) {
        init();
    }
    return cache;
});