// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Template renderer for Moodle. Load and render Moodle templates with Mustache.
 *
 * @module     core/templates
 * @package    core
 * @class      templates
 * @copyright  2015 Damyon Wiese <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      2.9
 */

define([ 'core/mustache',
        'jquery',
        'core/ajax',
        'core/str',
        'core/notification',
        'core/url',
        'core/config',
        'core/localstorage',
        'core/event',
        'core/flex_icon',
        'core/log',
        'core/user_date',
        'core/truncate'
    ],
    function(mustache, $, ajax, str, notification, coreurl, config, storage, event, flexicon, log, UserDate, Truncate) {

        // IE 9 (at minimum) doesn't support startsWith so add a polyfill ...
        if (!String.prototype.startsWith) {
            // eslint-disable-next-line
            String.prototype.startsWith = function(searchString, position) {
                position = position || 0;
                return this.substr(position, searchString.length) === searchString;
            };
        }

        // Private variables and functions.

        /** @var {string[]} templateCache - Cache of already loaded templates */
        var templateCache = {};

        /** @var {string[]} templateRequestCache - Cache of already loaded templates */
        var templateRequestCache = {};

        /**
         * Render a template and then call the callback with the result.
         *
         * @method doRender
         * @private
         * @param {string} templateSource The mustache template to render.
         * @param {Object} context Simple types used as the context for the template.
         * @param {String} themeName ignored!
         * @return {Promise} object
         */
        var doRender = function(templateSource, context, themeName) {
            var deferred = $.Deferred(),
                js = [],
                promises = [],
                partialPromises = [],
                result = '',
                requiredStrings = [],
                requiredDates = [],
                templatepartialsloaded = true,
                firstLoad = true;

            /**
             * Load a partial from the cache or ajax.
             *
             * @method partialHelper
             * @private
             * @param {string} name The partial name to load.
             * @return {string}
             */
            var partialHelper = function(name) {
                var template = '';
                var promise = getTemplate(name);

                promise.done(
                    function(source) {
                        template = source;
                    }
                ).fail(notification.exception);

                // If the template is already in the cache, the promise will be resolved immediately
                // This detects if it has been resolved. If it hasn't, store the promise for future reference
                if (template === '') {
                    partialPromises.push(promise);
                    templatepartialsloaded = false;
                }

                return template;
            };

            /**
             * Escape helper used to ensure there is no recursion inside other helpers
             *
             * @method escapeHelper
             * @private
             * @param {string} sectionText The text to parse the arguments from.
             * @param {function} helper Used to render subsections of the text.
             * @return {string}
             */
            var escapeHelper = function(sectionText, helper) {
                // This is the current context view! hella hurrah!
                if (sectionText.indexOf('{{') === -1 || sectionText.indexOf('}}') === -1) {
                    // Nothing to do.
                    return sectionText;
                }
                if (sectionText.match(/^\{{2,3}\s*([a-zA-Z0-9_]+)\s*\}{2,3}$/)) {
                    var key = sectionText.replace(/^\{{2,3}\s*([a-zA-Z0-9_]+)\s*\}{2,3}$/, '$1');
                    if (this.hasOwnProperty(key)) {
                        return this[key];
                    }
                }
                // It's not a straight up variable but contained mustache processing tags. Don't trust it!
                log.debug('Escaped content contains unexpected mustache processing queues. It will be lost.');
                return '';
            };
            /**
             * String helper used to render {{#str}}abd component { a : 'fish'}{{/str}}
             * into a get_string call.
             *
             * @method stringHelper
             * @private
             * @param {string} sectionText The text to parse the arguments from.
             * @param {function} helper Used to render subsections of the text.
             * @return {string}
             */
            var stringHelper = function(sectionText, helper) {
                var bits = sectionText.split(','),
                    finalise = function(key, component, a_key, a_component) {
                        var param = '',
                            a_index = 0,
                            index = 0;

                        if (a_key !== undefined && a_key !== '') {
                            a_index = requiredStrings.length;
                            requiredStrings.push({key: a_key, component: a_component});
                            param = '[[_s' + a_index + ']]';
                        }

                        index = requiredStrings.length;
                        requiredStrings.push({key: key, component: component, param: param});

                        // The placeholder must not use {{}} as those can be misinterpreted by the engine.
                        return '[[_s' + index + ']]';
                    },
                    get = function(params) {
                        var val = params.shift();
                        if (val === undefined) {
                            val = '';
                        }
                        return val.trim();
                    },
                    expand = function(str) {
                        if (str.indexOf('{{') === 0) {
                            // Expand the custom data, allowing it to come from context data.
                            str = helper('{{#esc}}' + str + '{{/esc}}', this);
                        }
                        return str;
                    },
                    get_and_expand = function(params) {
                        var string = get(params);
                        string = expand(string);
                        return string;
                    },
                    is_legacy_api_parameters = function(params) {
                        var first = params[0].trim(),
                            last = params[params.length - 1].trim();
                        if (first.search('{') !== 0 && last.substr(last.length - 1) !== '}') {
                            return false;
                        }
                        return true;
                    },
                    process_legacy_string = function(c_identifier, c_component, bits) {
                        var rawjson = bits.join(',').trim(),
                            params = {};
                        if ((rawjson.indexOf('{') === 0) && (rawjson.indexOf('{{') !== 0)) {
                            rawjson = expand(rawjson);
                            params = JSON.parse(rawjson);
                        } else {
                            params = expand(rawjson);
                            if ((params.indexOf('{') === 0) && (params.indexOf('{{') !== 0)) {
                                params = JSON.parse(params);
                            }
                        }
                        var index = requiredStrings.length;
                        requiredStrings.push({key: c_identifier, component: c_component, param: params});
                        return '[[_s' + index + ']]';
                    },
                    identifier = get_and_expand(bits),
                    component = get_and_expand(bits),
                    a_identifier = '',
                    a_component = '';

                if (!identifier.match(/^[a-zA-Z][a-zA-Z0-9\.:\/_\-]*$/)) {
                    log.debug('Invalid string identifier in "' + sectionText + '"');
                    // Sorry, we can't trust this, it won't resolve to a string.
                    return '';
                }

                if (component === '') {
                    component = 'core';
                } else if (!component.match(/^[a-z]+(_[a-z][a-z0-9_]*)?[a-z0-9]+$/) || component.search('__') > 0) {
                    log.debug('invalid component:' + component);
                    // Don't trust the component, on the server this resovles to core, copy that logic here.
                    component = 'core';
                }

                if (bits.length === 0) {
                    // no $a here
                    return finalise(identifier, component);
                }

                if (is_legacy_api_parameters(bits)) {
                    log.debug('Legacy string helper API in use for "' + sectionText + '"');
                    return process_legacy_string(identifier, component, bits);
                }

                // new API
                a_identifier = get_and_expand(bits);
                a_component = get_and_expand(bits);

                if (!a_identifier.match(/^[a-zA-Z][a-zA-Z0-9\.:\/_\-]*$/)) {
                    log.debug('Invalid string identifier in "' + sectionText + '"');
                    a_identifier = '';
                }

                if (a_component === '') {
                    a_component = '';
                } else if (!a_component.match(/^[a-z]+(_[a-z][a-z0-9_]*)?[a-z0-9]+$/) || a_component.search('__') > 0) {
                    log.debug('Invalid component:' + a_component);
                    a_component = '';
                }

                return finalise(identifier, component, a_identifier, a_component);
            };


            /**
             * Render flexible icons.
             *
             * @method flexIconHelper
             * @private
             * @param {String} sectionText
             * @param {function} helper Used to render subsections of the text.
             * @return {String}
             */
            var flexIconHelper = function(sectionText, helper) {
                var bits = sectionText.split(','),
                    render = function(identifier, alt, classes) {
                        var index = promises.length;
                        identifier = expand(identifier);
                        promises.push(renderIcon(identifier, alt, classes));
                        return '<_p' + index + '>';
                    },
                    get = function(params) {
                        var val = params.shift();
                        if (val === undefined) {
                            val = '';
                        }
                        return val.trim();
                    },
                    expand = function(str) {
                        if (str.indexOf('{{') === 0) {
                            // Expand the custom data, allowing it to come from context data.
                            str = helper('{{#esc}}' + str + '{{/esc}}', this);
                        }
                        return str;
                    },
                    get_and_expand = function(params) {
                        var string = get(params);
                        string = expand(string);
                        return string;
                    },
                    identifier = expand(bits.shift().trim()),
                    is_legacy_api_bits = function(params) {
                        if (params.length === 0) {
                            return false;
                        }
                        var first = params[0].trim(),
                            last = params[params.length - 1].trim();
                        if (first.search('{') !== 0 && last.substr(last.length - 1) !== '}') {
                            return false;
                        }
                        if (first.search('{{') === 0 && last.substr(last.length - 2) === '}}') {
                            return false;
                        }
                        // Now we need to know if it looks like JSON.
                        if (first[0] !== '{') {
                            return false;
                        }
                        if (last[last.length - 1] !== '}') {
                            return false;
                        }
                        return true;
                    },
                    from_legacy_api = function(identifier, params) {
                        var data = params.join(',').trim(),
                            alt = '',
                            classes = '';
                        if (data !== '') {
                            if (data.match(/^\{{2}.*?\}{2}$/)) {
                                data = expand(data);
                            }
                            try {
                                data = JSON.parse(data);
                            } catch (err) {
                                log.error('Unable to parse customdata for flex icon helper. Not valid JSON.');
                                data = {};
                            }
                            if (typeof data.alt !== 'undefined') {
                                alt = expand(data.alt);
                            }

                            if (data.classes) {
                                classes = expand(data.classes);
                            }
                        }
                        return render(identifier, alt, classes);
                    },
                    alt_identifier = '',
                    alt_component = '',
                    alt = '',
                    classes = '';

                if (is_legacy_api_bits(bits)) {
                    log.debug('Legacy flex icon helper API in use for "' + sectionText + '"');
                    return from_legacy_api(identifier, bits);
                }

                alt_identifier = get_and_expand(bits);
                if (alt_identifier !== '' && !alt_identifier.match(/^[a-zA-Z][a-zA-Z0-9\.:\/_\-]*$/)) {
                    log.debug('Invalid alt identifier for flex icon "' + alt_identifier + '", it must be a string identifier.');
                }

                alt_component = get_and_expand(bits);
                if (alt_component !== '' && (!alt_component.match(/^[a-z]+(_[a-z][a-z0-9_]*)?[a-z0-9]+$/) || alt_component.search('__') > 0)) {
                    log.debug('Invalid alt identifier for flex icon "' + alt_component + '", it must be a component identifier.');
                }

                if (alt_identifier !== '') {
                    var index = requiredStrings.length;

                    requiredStrings.push({key: alt_identifier, component: alt_component});
                    // The placeholder must not use {{}} as those can be misinterpreted by the engine.
                    alt = '[[_s' + index + ']]';
                } else {
                    alt = '';
                }

                classes = get(bits);

                return render(identifier, alt, classes);
            };

            /**
             * Render image icons.
             *
             * API usage:
             *  - {{#pix}}identifier{{/pix}}
             *  - {{#pix}}identifier, component{{/pix}}
             *  - {{#pix}}identifier, component, alt_identifier{{/pix}}
             *  - {{#pix}}identifier, component, alt_identifier, alt_component{{/pix}}
             *
             * Legacy API usage: (Deprecated to be removed after Totara 12.
             *  - {{#pix}}t/edit,component,Anything else is alt text{{/pix}}
             *  - {{#pix}}t/edit,component,{{#str}}edit{{/str}}{{/pix}}
             *
             * The args are comma separated and only the first is required.
             *
             * @method pixHelper
             * @private
             * @param {string} sectionText The text to parse arguments from.
             * @param {function} helper Used to render the alt attribute of the text.
             * @return {string}
             */
            var pixHelper = function(sectionText, helper) {
                var bits = sectionText.split(','),
                    render = function(identifier, component, alt, classes) {
                        var index = promises.length;
                        if (component === '') {
                            component = 'core';
                        }
                        identifier = expand(identifier);
                        promises.push(renderIcon(component + '|' + identifier, alt, classes));
                        return '<_p' + index + '>';
                    },
                    get = function(bits) {
                        var val = bits.shift();
                        if (val === undefined) {
                            val = '';
                        }
                        return val.trim();
                    },
                    expand = function(str) {
                        if (str.indexOf('{{') === 0) {
                            // Expand the custom data, allowing it to come from context data.
                            str = helper('{{#esc}}' + str + '{{/esc}}', this);
                        }
                        return str;
                    },
                    get_and_expand = function(bits, helper) {
                        var string = get(bits);
                        string = expand(string, helper);
                        return string;
                    },
                    is_legacy_api_customdata = function(parameters) {
                        if (parameters.length === 0) {
                            return false;
                        }
                        var first = parameters[0].trim();
                        var last = parameters[parameters.length - 1].trim();
                        if (first.search('{') !== 0 && last.substr(last.length - 1) !== '}') {
                            // It's not a variable, or json.
                            return false;
                        }
                        if (first.search('{{') === 0 && last.search('}}') !== last.length - 1) {
                            // It's a variable.
                            return false;
                        }
                        // OK, its JSON.
                        return true;
                    },
                    from_legacy_api = function(identifier, component, text) {
                        var i, attributes;
                        try {
                            attributes = JSON.parse(text);
                        } catch (e) {
                            attributes = {alt: text};
                        }
                        for (i in attributes) {
                            if (attributes.hasOwnProperty(i)) {
                                attributes[i] = expand(attributes[i]);
                            }
                        }
                        return attributes;
                    },
                    identifier = '',
                    component = '',
                    alt_identifier = '',
                    alt_component = '',
                    alt = '',
                    next = '',
                    classes = '',
                    data = {};

                identifier = get_and_expand(bits);
                component = get_and_expand(bits);

                if (!bits.length) {
                    return render(identifier, component);
                }

                next = get(bits);
                // Check if it looks like a string identifier. It's a guess, sorry.
                // Intentionally favour the new API.
                if (next.match(/^[a-zA-Z][a-zA-Z0-9\.:\/_\-]*$/)) {
                    // Looks like valid string id.
                    alt_identifier = next.trim();
                    alt_component = get(bits);
                    if (alt_component !== '' && (!alt_component.match(/^[a-z]+(_[a-z][a-z0-9_]*)?[a-z0-9]+$/) || alt_component.search('__') > 0)) {
                        // almost matches PARAM_COMPONENT
                        log.debug('invalid component:' + alt_component);
                    }
                    var index = requiredStrings.length;
                    requiredStrings.push({key: alt_identifier, component: alt_component});
                    // The placeholder must not use {{}} as those can be misinterpreted by the engine.
                    alt = '[[_s' + index + ']]';
                    classes = get(bits);
                } else {
                    log.debug('Legacy pix API in use for string:"' + sectionText + '" - Please use the pix icon template or new API');
                    bits.unshift(next);
                    if (is_legacy_api_customdata(bits)) {
                        data = from_legacy_api(identifier, component, bits.join(','));
                        if (data.hasOwnProperty('alt')) {
                            alt = data.alt;
                        }
                        if (data.hasOwnProperty('classes')) {
                            classes = data.classes;
                        }
                    } else {
                        // OK, the alt is raw content.
                        // Rejoin the bits into a single item and expand it, which will put it through the escape helper and get rid
                        // of any nasties.
                        alt = expand(bits.join(','));
                    }
                }

                if (component === 'flexicon') {
                    return flexIconHelper.apply(this, [identifier + ',' + alt, helper]);
                }

                return render(identifier, component, alt, classes);

            };

            /**
             * Render blocks of javascript and save them in an array.
             *
             * @method jsHelper
             * @private
             * @param {string} sectionText The text to save as a js block.
             * @param {function} helper Used to render the block.
             * @return {string}
             */
            var jsHelper = function(sectionText, helper) {
                js.push(helper(sectionText, this));
                return '';
            };

            /**
             * Quote helper used to wrap content in quotes, and escape all quotes present in the content.
             *
             * @method quoteHelper
             * @private
             * @param {string} sectionText The text to parse the arguments from.
             * @param {function} helper Used to render subsections of the text.
             * @return {string}
             */
            var quoteHelper = function(sectionText, helper) {
                var content = helper(sectionText.trim(), this);

                // Escape the {{ and the ".
                // This involves wrapping {{, and }} in change delimeter tags.
                content = content
                    .replace('"', '\\"')
                    .replace(/([\{\}]{2,3})/g, '{{=<% %>=}}$1<%={{ }}=%>')
                ;
                return '"' + content + '"';
            };

            /**
             * User date helper to render user dates from timestamps.
             *
             * @method userDateHelper
             * @private
             * @param {object} context The current mustache context.
             * @param {string} sectionText The text to parse the arguments from.
             * @param {function} helper Used to render subsections of the text.
             * @return {string}
             */
            var userDateHelper = function(sectionText, helper) {
                // Non-greedy split on comma to grab the timestamp and format.
                var regex = /(.*?),(.*)/;
                var parts = sectionText.match(regex);
                var timestamp = helper('{{#esc}}' + parts[1].trim() + '{{/esc}}', this);
                var format = helper(parts[2].trim(), this);
                var index = requiredDates.length;

                requiredDates.push({
                    timestamp: parseInt(timestamp, 10),
                    format: format
                });

                return '[[_t_' + index + ']]';
            };

            var shortenTextHelper = function(sectionText, helper) {
                // Non-greedy split on comma to grab section text into the length and
                // text parts.
                var regex = /(.*?),(.*)/;
                var parts = sectionText.match(regex);
                // The length is the part matched in the first set of parethesis.
                var length = parseInt(parts[1].trim(), 10);
                // The length is the part matched in the second set of parethesis.
                var text = parts[2].trim();
                var content = helper('{{#esc}}' + text + '{{/esc}}', this);

                return Truncate.truncate(content, {
                    length: length,
                    words: true,
                    ellipsis: '...'
                });
            };

            context.str = function() { return stringHelper; };
            context.pix = function() { return pixHelper; };
            context.flex_icon = function() { return flexIconHelper; };
            context.js = function() { return jsHelper; };
            context.quote = function() { return quoteHelper; };
            context.userdate = function () {return userDateHelper; };
            context.esc = function() { return escapeHelper; };
            context.shortentext = function() {return shortenTextHelper; };
            context.globals = { config : config };

            var partialsComplete = $.Deferred();

            // Try rendering the template. If there is any partials that weren't in the cache,
            // wait until they have been loaded and try again.
            var loadPartials = function() {
                // If partialPromises is an empty array, this will resolve immediately.
                $.when.apply($, partialPromises).done(function () {
                    if (templatepartialsloaded && !firstLoad) {
                        // All partials have been loaded, so continue execution.
                        partialsComplete.resolve();
                    } else {
                        // Re initialise variables to reduce memory usage
                        firstLoad = false;
                        js = [];
                        promises = [];
                        partialPromises = [];
                        result = '';
                        requiredStrings = [];
                        requiredDates = [];
                        templatepartialsloaded = true;
                        try {
                            // Try rendering.
                            result = mustache.render(templateSource, context, partialHelper);
                            loadPartials();
                        } catch (ex) {
                            deferred.reject(ex);
                        }
                    }
                });
            };
            loadPartials();

            partialsComplete.done(function () {
                // Resolve template promises.
                for (var i = 0; i < promises.length; i++) {
                    // A closure is needed here otherwise i is highly likely to be promises.length
                    /* eslint-disable no-loop-func */
                    promises[i].done((function(index) { return function(html, templatejs) {
                        js.push(templatejs);
                        result = result.replace('<_p' + index + '>', html);
                    }; })(i));
                    /* eslint-enable no-loop-func */
                }

                $.when.apply($, promises).then(function () {
                    var stringsLoaded = $.Deferred();
                    js = js.join(';\n');

                    if (requiredStrings.length > 0) {
                        str.get_strings(requiredStrings).done(function(strings) {
                            var i,
                                count = 0;

                            // Why do we not do another call the render here?
                            //
                            // Because that would expose DOS holes. E.g.
                            // I create an assignment called "{{fish" which
                            // would get inserted in the template in the first pass
                            // and cause the template to die on the second pass (unbalanced).
                            //
                            // NOTE: the explanation above might not be accurate because MDL-56341 switched to [[_s from {{_s.
                            // Placed in while as strings may be nested to 2 levels. Limited as in some cases the string comes later
                            while ((result.indexOf('[[_s') !== -1 || js.indexOf('[[_s') !== -1) && count < 2) {
                                for (i = 0; i < strings.length; i++) {
                                    // Why the use of while?
                                    // Because replace doesn't replace all.
                                    while (result.indexOf('[[_s' + i + ']]') !== -1) {
                                        result = result.replace('[[_s' + i + ']]', strings[i]);
                                    }
                                    while (js.indexOf('[[_s' + i + ']]') !== -1) {
                                        js = js.replace('[[_s' + i + ']]', strings[i]);
                                    }
                                }
                                count++;
                            }

                            if (requiredDates.length > 0) {
                                requiredDates.forEach(function(date) {
                                    date.format = date.format.replace(/\[\[_s\d+\]\]/, function(match) {
                                        var index = parseInt(match.match(/\d+/), 10);
                                        return strings[index];
                                    });
                                    return date;
                                });
                            }
                            stringsLoaded.resolve();
                        });
                    } else {
                        stringsLoaded.resolve();
                    }

                    return stringsLoaded;
                }).then(function() {
                    if (requiredDates.length > 0) {
                        log.debug('userdate mustache helper in use - This has a potential XSS security issue');
                        return UserDate.get(requiredDates).then(function(dates) {
                            dates.forEach(function(date, index) {
                                var key = '\\[\\[_t_' + index + '\\]\\]';
                                var re = new RegExp(key, 'g');
                                result = result.replace(re, date);
                                js = js.replace(re, date);
                            });
                        });
                    } else {
                        return $.Deferred().resolve().promise();
                    }
                }).then(function() {
                    deferred.resolve(result.trim(), js);
                }, function(ex) {
                    deferred.reject(ex);
                });
            });
            return deferred.promise();
        };

        /**
         * Load a template from the cache or local storage or ajax request.
         *
         * @method getTemplate
         * @private
         * @param {string} templateName - should consist of the component and the name of the template like this:
         *                              core/menu (lib/templates/menu.mustache) or
         *                              tool_bananas/yellow (admin/tool/bananas/templates/yellow.mustache)
         * @return {Promise} JQuery promise object resolved when the template has been fetched.
         */
        var getTemplate = function(templateName, async) {
            var deferred = $.Deferred();
            var parts = templateName.split('/');
            var component = parts.shift();
            var name = parts.shift();

            var searchKey = config.theme + '/' + templateName;

            // First try request variables.
            if (searchKey in templateCache) {
                deferred.resolve(templateCache[searchKey]);
                return deferred.promise();
            }

            // Now try local storage.
            var cached = storage.get('core_template/' + searchKey);

            if (cached) {
                deferred.resolve(cached);
                templateCache[searchKey] = cached;
                return deferred.promise();
            }

            // It is not in localstorage or already loaded
            if (searchKey in templateRequestCache) {
                // But it has been requested
                return templateRequestCache[searchKey].promise();
            } else {
                // If not, cache the promise so it doesn't hit the server again
                templateRequestCache[searchKey] = deferred;
            }

            // Oh well - load via ajax.
            var promises = ajax.call([{
                methodname: 'core_output_load_template',
                args:{
                    component: component,
                    template: name,
                    themename: config.theme
                }
            }], async, false);

            promises[0].done(
                function (templateSource) {
                    storage.set('core_template/' + searchKey, templateSource);
                    templateCache[searchKey] = templateSource;
                    deferred.resolve(templateSource);
                }
            ).fail(
                function (ex) {
                    deferred.reject(ex);
                }
            );
            return deferred.promise();
        };

        /**
         * Execute a block of JS returned from a template.
         * Call this AFTER adding the template HTML into the DOM so the nodes can be found.
         *
         * @method runTemplateJS
         * @param {string} source - A block of javascript.
         */
        var runTemplateJS = function(source) {
            if (source !== undefined && source.trim() !== '') {
                var newscript = $('<script>').attr('type','text/javascript').html(source);
                $('head').append(newscript);
            }

            // Totara: Initialise all amd modules via data-core-autoinitialise,
            //         make sure behat waits for all to complete.
            M.util.js_pending('core-autoinitialise');
            require(['core/autoinitialise'], function(ai) {
                ai.scan().then(function() {
                    M.util.js_complete('core-autoinitialise');
                });
            });
        };

        /**
         * Do some DOM replacement and trigger correct events and fire javascript.
         *
         * @method domReplace
         * @private
         * @param {JQuery} element - Element or selector to replace.
         * @param {String} newHTML - HTML to insert / replace.
         * @param {String} newJS - Javascript to run after the insertion.
         * @param {Boolean} replaceChildNodes - Replace only the childnodes, alternative is to replace the entire node.
         */
        var domReplace = function(element, newHTML, newJS, replaceChildNodes) {
            var replaceNode = $(element);
            if (replaceNode.length) {
                // First create the dom nodes so we have a reference to them.
                var newNodes = $(newHTML);
                // Do the replacement in the page.
                if (replaceChildNodes) {
                    replaceNode.empty();
                    replaceNode.append(newNodes);
                } else {
                    replaceNode.replaceWith(newNodes);
                }
                // Run any javascript associated with the new HTML.
                runTemplateJS(newJS);
                // Notify all filters about the new content.
                event.notifyFilterContentUpdated(newNodes);
            }
        };

        /**
         * Render an icon. All icons should be passed through here.
         *
         * Note that this method may also be used in the form:
         * renderIcon(iconName, customData);
         *
         * @method renderIcon
         * @public
         * @param {String} iconName - The icon to render. This can either be in the traditional Moodle format or a flex icon name.
         * @param {Mixed} alt - As a String: for screen readers to read (either as an alt tag, or as hidden text). As an {Object}:
         *                      options which may contain alt, and any other custom data. Keys in this Object take precendence over
         *                      following arguments.
         * @param {String} cssclasses - any additional CSS classes that are needed for the icon.
         * @param {Object} customData - Optional. Custom data to be passed to the [Flex Icon] template.
         * @return {String} An HTML string containing the rendered icon.
         */
        var renderIcon = function (iconName, alt, cssclasses, customData) {

            var renderData = {};
            renderData.classes = (cssclasses || '');
            renderData = $.extend(renderData, customData);

            // It is possible to pass all args and more in an options object as
            // second param or all arguments one by one.
            /* eslint-disable no-extra-boolean-cast */
            if (Boolean(alt)) {
                if ((alt).toString() === '[object Object]') {
                    renderData = $.extend(renderData, alt);
                } else {
                    renderData.alt = alt;
                }
            }
            /* eslint-enable no-extra-boolean-cast */

            var iconhtml = $.Deferred();

            flexicon.getFlexTemplateData(iconName).done(function (completetemplate) {

                if (typeof completetemplate.data === 'undefined') {
                    completetemplate.data = {};
                }

                completetemplate.data.customdata = renderData;

                if (typeof completetemplate.data.customdata.title === 'undefined' &&
                    typeof completetemplate.data.customdata.alt !== 'undefined') {
                    completetemplate.data.customdata.title = completetemplate.data.customdata.alt;
                }

                templates.render(completetemplate.template, completetemplate.data)
                    .done(function (html) {iconhtml.resolve(html);});
            }).fail(function () {
                // Fallback to the traditional icon
                var parts = iconName.split('|');

                if (parts.length === 1) {
                    parts.unshift('core');
                }

                var url = coreurl.imageUrl(parts[1], parts[0]);
                var attributes = [
                    { name: 'src', value: url},
                    { name: 'alt', value: renderData.alt},
                    { name: 'title', value: renderData.alt}
                ];
                var classes = renderData.classes;
                delete(renderData.alt);
                delete(renderData.classes);

                for (var key in renderData) {
                    attributes.push({name: key, value: renderData[key]});
                }

                templates.render('core/pix_icon', {attributes:attributes, extraclasses: classes}).done(iconhtml.resolve);
            });

            return iconhtml.promise();
        };

        /**
         * Prepend some HTML to a node and trigger events and fire javascript.
         *
         * @method domPrepend
         * @private
         * @param {jQuery|String} element - Element or selector to prepend HTML to
         * @param {String} html - HTML to prepend
         * @param {String} js - Javascript to run after we prepend the html
         */
        var domPrepend = function(element, html, js) {
            var node = $(element);
            if (node.length) {
                // Prepend the html.
                node.prepend(html);
                // Run any javascript associated with the new HTML.
                runTemplateJS(js);
                // Notify all filters about the new content.
                event.notifyFilterContentUpdated(node);
            }
        };

        /**
         * Append some HTML to a node and trigger events and fire javascript.
         *
         * @method domAppend
         * @private
         * @param {jQuery|String} element - Element or selector to append HTML to
         * @param {String} html - HTML to append
         * @param {String} js - Javascript to run after we append the html
         */
        var domAppend = function(element, html, js) {
            var node = $(element);
            if (node.length) {
                // Append the html.
                node.append(html);
                // Run any javascript associated with the new HTML.
                runTemplateJS(js);
                // Notify all filters about the new content.
                event.notifyFilterContentUpdated(node);
            }
        };

        var templates = /** @alias module:core/templates */ {
            // Public variables and functions.
            /**
             * Load a template and call doRender on it.
             *
             * @method render
             * @private
             * @param {string} templateName - should consist of the component and the name of the template like this:
             *                              core/menu (lib/templates/menu.mustache) or
             *                              tool_bananas/yellow (admin/tool/bananas/templates/yellow.mustache)
             * @param {Object} context - Could be array, string or simple value for the context of the template.
             * @param {string} themeName - ignored!
             * @return {Promise} JQuery promise object resolved when the template has been rendered.
             */
            render: function(templateName, context, themename) {
                var deferred = $.Deferred();
                var ctx = $.extend({}, context); // context is being altered somewhere - this ensures it is saved

                var loadTemplate = getTemplate(templateName, true);

                loadTemplate.done(function (templateSource) {
                    var renderPromise = doRender(templateSource, ctx);

                    renderPromise.done(
                        function(result, js) {
                            deferred.resolve(result, js);
                        }
                    ).fail(
                        function(ex) {
                            deferred.reject(ex);
                        }
                    );
                }).fail(
                    function(ex) {
                        deferred.reject(ex);
                    }
                );

                return deferred;
            },

            /**
             * Execute a block of JS returned from a template.
             * Call this AFTER adding the template HTML into the DOM so the nodes can be found.
             *
             * @method runTemplateJS
             * @param {string} source - A block of javascript.
             */
            runTemplateJS: runTemplateJS,

            /**
             * Replace a node in the page with some new HTML and run the JS.
             *
             * @method replaceNodeContents
             * @param {JQuery} element - Element or selector to replace.
             * @param {String} newHTML - HTML to insert / replace.
             * @param {String} newJS - Javascript to run after the insertion.
             */
            replaceNodeContents: function(element, newHTML, newJS) {
                return domReplace(element, newHTML, newJS, true);
            },

            /**
             * Insert a node in the page with some new HTML and run the JS.
             *
             * @method replaceNode
             * @param {JQuery} element - Element or selector to replace.
             * @param {String} newHTML - HTML to insert / replace.
             * @param {String} newJS - Javascript to run after the insertion.
             */
            replaceNode: function(element, newHTML, newJS) {
                return domReplace(element, newHTML, newJS, false);
            },

            /**
             * Prepend some HTML to a node and trigger events and fire javascript.
             *
             * @method prependNodeContents
             * @param {jQuery|String} element - Element or selector to prepend HTML to
             * @param {String} html - HTML to prepend
             * @param {String} js - Javascript to run after we prepend the html
             */
            prependNodeContents: function(element, html, js) {
                domPrepend(element, html, js);
            },

            /**
             * Append some HTML to a node and trigger events and fire javascript.
             *
             * @method appendNodeContents
             * @param {jQuery|String} element - Element or selector to append HTML to
             * @param {String} html - HTML to append
             * @param {String} js - Javascript to run after we append the html
             */
            appendNodeContents: function(element, html, js) {
                domAppend(element, html, js);
            },

            renderIcon: renderIcon,

            /**
             * Renderers an icon - maintained for Moodle compatibility
             *
             * @param {String} key the icon key (eg. i/delete)
             * @param {String} component the component the icon belongs to (eg. core)
             * @param {String} title The alt text for the icon
             * @returns {$.Deferred} jquery Deferred object that resolves with the image HTML
             */
            renderPix: function(key, component, title) {
                return renderIcon(component + '|' + key, title);
            }
        };

        return templates;
    });
