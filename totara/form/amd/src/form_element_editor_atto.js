/**
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
 * @author  Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @package totara_form
 */

/**
 * @module  totara_form/form_element_editor_atto
 * @class   AttoElement
 * @author  Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery','core/yui', 'core/str', 'totara_form/form'], function($, Y, core_strings, Form) {

    /* global YUI_config */

    /**
     * Atto editor element
     *
     * @class
     * @constructor
     * @augments Form.Element
     *
     * @param {(Form|Group)} parent
     * @param {string} type
     * @param {string} id
     * @param {HTMLElement} node
     */
    function AttoElement(parent, type, id, node) {

        if (!(this instanceof AttoElement)) {
            return new AttoElement(parent, type, id, node);
        }

        Form.Element.apply(this, arguments);

        this.input = null;
        this.editor = null;

    }

    AttoElement.prototype = Object.create(Form.Element.prototype);
    AttoElement.constructor = AttoElement;

    /**
     * Returns a string describing this object.
     * @returns {string}
     */
    AttoElement.prototype.toString = function() {
        return '[object AttoElement]';
    };

    /**
     * Initialises a new instance of this element.
     * @param {Function} done
     */
    AttoElement.prototype.init = function(done) {
        M.util.js_pending('totara_form_element_editor_atto');
        var self = this;
        this.input = $('#' + this.id);

        // This is a bit of hackery to expose this editor object to the world.
        // This is required for the likes of behat where we must programatically interact with the editor.
        window.totara_form_editors = window.totara_form_editors || {};
        window.totara_form_editors[this.id] = this;

        var fptemplates = this.input.data('fptemplates'),
            jsmodules = this.input.data('jsmodules'),
            requiredstrings = this.input.data('requiredstrings'),
            yuimodules = this.input.data('yuimodules');

        Y.use(['core_filepicker', 'node', 'node-event-simulate', 'core_dndupload'], function (Y) {
            M.core_filepicker.set_templates(Y, fptemplates);
        });

        // Add modules to global Y instance.
        M.yui.add_module(jsmodules);
        // Apply to current Y instance too.
        Y.applyConfig(YUI_config);

        core_strings.get_strings(requiredstrings).done(function () {
            for (var i = 0; i < yuimodules.length; i++) {
                self.initYUIModules(yuimodules[i]);
            }
        });

        done();
    };

    /**
     * Initialises the required YUI modules.
     * @param {Object} yuimodule
     */
    AttoElement.prototype.initYUIModules = function(yuimodule) {
        var self = this;
        Y.use(yuimodule.modules, function () {
            // Note: Eval is not pretty here, but it seems to be the most robust way to replicate the PHP JS stuff.
            /* eslint-disable no-eval*/
            var result = eval(yuimodule.functionstr);
            if (result) {
                self.setEditor(result);
            }
            /* eslint-enable no-eval*/
        });
    };

    /**
     * Sets the editor for this element.
     * @param {mixed} editor
     */
    AttoElement.prototype.setEditor = function(editor) {
        this.editor = editor;
        editor.on('change', $.proxy(this.changed, this));
    };

    /**
     * Returns the value for this editor
     * @returns {string}
     */
    AttoElement.prototype.getValue = function() {
        if (this.editor) {
            this.editor.updateOriginal();
        }
        return this.input.val().toString();
    };

    /**
     * Sets the editor value, really useful for behat.
     */
    AttoElement.prototype.setValue = function(value) {
        this.input.val(value);
        if (this.editor) {
            this.editor.updateFromTextArea();
        }
        Form.debug('Atto editor value changed programmatically', this, Form.LOGLEVEL.info);
        this.changed({});
    };

    /**
     * Compare the value of this editor given a specific operator.
     *
     * @param {Object} operator
     * @returns {boolean|null}
     */
    AttoElement.prototype.compare = function(operator) {
        var value = this.getValue(),
            result = null,
            expected;

        switch (operator) {

            case Form.Operators.Equals:
                // Value === Expected.
                if (arguments.length !== 2) {
                    Form.debug('Compare Equals expects 2 arguments, ' + arguments.length + ' given.', this, Form.LOGLEVEL.warn);
                    return null;
                }
                expected = arguments[1];
                if (Array.isArray(expected)) {
                    return false;
                }
                result = (value.toString() === expected.toString() || this.stripValue(value) === expected.toString());
                break;

            case Form.Operators.Empty:
                // Value is empty e.g. null, '', 0, array(), {}.
                result = (
                    value === null ||
                    value === false ||
                    value.toString() === '' ||
                    value.toString() === '0' ||
                    this.stripValue(value) === ''
                );
                break;

            case Form.Operators.Filled:
                // Value has been provided, e.g. Value !== null &&  Value !== ''
                // False and 0 pass.
                result = (value !== null && value !== '' && this.stripValue(value) !== '');
                break;

            case Form.Operators.NotEquals:
                if (arguments.length !== 2) {
                    Form.debug('Compare NotEquals expects 2 arguments, ' + arguments.length + ' given.',
                        this, Form.LOGLEVEL.warn);
                    return null;
                }
                expected = arguments[1];
                // Value !== Expected.
                result = !this.compare(Form.Operators.Equals, expected);
                break;

            case Form.Operators.NotEmpty:
                // Value is not empty.
                result = !this.compare(Form.Operators.Empty);
                break;

            case Form.Operators.NotFilled:
                // Value === Null.
                result = !this.compare(Form.Operators.Filled);
                break;

            default:
                Form.debug('Element does not implement all comparisons: "' + operator + '", asking the Form',
                    this, Form.LOGLEVEL.warn);
                var args = [value].concat(Array.prototype.slice.call(arguments));
                result = Form.prototype.compare.apply(null, args);
                break;

        }

        return result;
    };

    /**
     * Strips out any expected content.
     *
     * @param {string} value
     * @returns {string}
     */
    AttoElement.prototype.stripValue = function(value) {
        return value.trim().replace(/^<p[^>]*>(.*)<\/p>$/g, '$1').replace(/<br ?\/?>/g, '').replace('&nbsp;', '').trim();
    };

    /**
     * Returns true if the editor value is empty.
     * @returns {boolean}
     */
    AttoElement.prototype.isEmpty = function() {
        var value = this.getValue();
        return (value === '' || value === '<p></p>');
    };

    /**
     * returns the class of the given object.
     * @param {Object} obj
     * @returns {string}
     */
    AttoElement.prototype.getObjectClass = function(obj) {
        if (obj && obj.constructor) {
            if (obj.constructor.name) {
                return obj.constructor.name;
            }
            if (obj.constructor.toString) {
                var arr = obj.constructor.toString().match(
                    /function\s*(\w + )/);

                if (arr && arr.length == 2) {
                    return arr[1];
                }
            }
        }

        return undefined;
    };

    return AttoElement;

});
