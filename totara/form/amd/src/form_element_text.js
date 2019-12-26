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
 * @module  totara_form/form_element_text
 * @class   TextElement
 * @author  Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'totara_form/form'], function($, Form) {

    /**
     * Text Element class.
     *
     * @class
     * @constructor
     * @augments Form.Element
     * @param {HTMLElement} parent
     * @param {string} type
     * @param {string} id
     * @param {HTMLElement} node
     * @returns {TextElement}
     */
    function TextElement(parent, type, id, node) {

        if (!(this instanceof TextElement)) {
            return new TextElement(parent, type, id, node);
        }

        Form.Element.apply(this, arguments);

        this.input = null;
    }

    TextElement.prototype = Object.create(Form.Element.prototype);
    TextElement.prototype.constructor = TextElement;

    /**
     * Returns a string describing this object.
     * @returns {string}
     */
    TextElement.prototype.toString = function() {
        return '[object TextElement]';
    };

    /**
     * Initialises a new instance of this element.
     * @param {Function} done
     */
    TextElement.prototype.init = function(done) {
        this.input = $('#' + this.id);
        // Call the changed method when this element is changed.
        this.input.change($.proxy(this.changed, this));

        done();
    };

    TextElement.prototype.getValue = function() {
        return this.input.val();
    };

    TextElement.prototype.isEmpty = function() {
        var value = this.getValue();
        if (value.toString().trim() === '') {
            return true;
        }
        if (value.hasOwnProperty('isEmpty')) {
            return value.isEmpty();
        }
        return false;
    };

    TextElement.prototype.compare = function(operator) {
        var value = this.getValue(),
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
                return (value.toString().trim() === expected.toString().trim());

            case Form.Operators.Empty:
                // Value is empty e.g. null, '', 0, array(), {}.
                return this.isEmpty();

            case Form.Operators.Filled:
                // Value has been provided, e.g. Value !== null &&  Value !== ''
                // False and 0 pass.
                value = value.toString().trim();
                return (value !== '' && value.toLowerCase() !== 'false' && !(/^[0]*$/.test(value)));

            case Form.Operators.NotEquals:
                if (arguments.length !== 2) {
                    Form.debug('Compare NotEquals expects 2 arguments, ' + arguments.length + ' given.', this, Form.LOGLEVEL.warn);
                    return null;
                }
                expected = arguments[1];
                // Value !== Expected.
                return !this.compare(Form.Operators.Equals, expected);

            case Form.Operators.NotEmpty:
                // Value is not empty.
                return !this.isEmpty();

            case Form.Operators.NotFilled:
                // Value === Null.
                return !this.compare(Form.Operators.Filled);

            default:
                Form.debug('Element does not implement all comparisons: "' + operator + '", asking the Form',
                    this, Form.LOGLEVEL.warn);
                var args = [value].concat(arguments);
                return Form.Element.prototype.compare.apply(this, args);

        }
    };

    return TextElement;

});
