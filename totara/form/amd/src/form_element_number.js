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
 * @module  totara_form/form_element_number
 * @class   NumberInputElementElement
 * @author  Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'totara_form/form', 'totara_form/modernizr'], function($, Form, Modernizr) {

    /**
     * Number element
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
    function NumberInputElement(parent, type, id, node) {

        if (!(this instanceof NumberInputElement)) {
            return new NumberInputElement(parent, type, id, node);
        }

        this.input = null;

        Form.Element.apply(this, arguments);
    }

    NumberInputElement.prototype = Object.create(Form.Element.prototype);
    NumberInputElement.prototype.constructor = NumberInputElement;

    /**
     * Returns a string describing this object.
     * @returns {string}
     */
    NumberInputElement.prototype.toString = function() {
        return '[object NumberInputElement]';
    };

    /**
     * Initialises a new instance of this element.
     * @param {Function} done
     */
    NumberInputElement.prototype.init = function(done) {
        var id = this.id;
        var deferreds = [];
        this.input = $('#' + id);
        // Call the changed method when this element is changed.
        this.input.change($.proxy(this.changed, this));

        if (!Modernizr.inputtypes.number) {
            var numberdeferred = $.Deferred();
            deferreds.push(numberdeferred);

            require(['totara_form/polyfill_number-lazy'], function(number) {
                number.init(id);
                numberdeferred.resolve();
            });
        }
        if (this.input.attr('required') && !Modernizr.input.required) {
            var requireddeferred = $.Deferred();
            deferreds.push(requireddeferred);

            require(['totara_form/polyfill_required-lazy'], function (poly) {
                poly.init(id);
                requireddeferred.resolve();
            });
        }
        if (this.input.attr('placeholder') && !Modernizr.input.placeholder) {
            var placeholderdeferred = $.Deferred();
            deferreds.push(placeholderdeferred);

            require(['totara_form/polyfill_placeholder-lazy'], function (poly) {
                poly.init(id);
                placeholderdeferred.resolve();
            });
        }
        $.when.apply($, deferreds).done(done);
    };

    NumberInputElement.prototype.getValue = function() {
        return this.input.val();
    };

    return NumberInputElement;

});
