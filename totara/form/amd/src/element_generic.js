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
 * @author  Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @package totara_form
 */

/**
 * Totara Generic Element object.
 *
 * @module  totara_form/element_generic
 * @class   GenericElement
 * @author  Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['totara_form/form'], function(Form) {

    /**
     * Generic Element class
     *
     * @class
     * @constructor
     * @augments Form.Element
     *
     * @param {(Form|Group)} parent
     * @param {string} type
     * @param {string} id
     * @param {HTMLElement} node
     * @returns {GenericElement}
     */
    function GenericElement(parent, type, id, node) {

        if (!(this instanceof GenericElement)) {
            return new GenericElement(parent, type, id, node);
        }

        Form.Element.apply(this, arguments);

    }

    GenericElement.prototype = Object.create(Form.Element.prototype);
    GenericElement.prototype.constructor = GenericElement;

    /**
     * Returns a string describing this object.
     * @returns {string}
     */
    GenericElement.prototype.toString = function() {
        return '[object GenericElement]';
    };

    /**
     * Initialises the element.
     * @param {Function} done
     */
    GenericElement.prototype.init = function(done) {
        // Nothing to do here, but all element must override this function.
        done();
    };

    /**
     * Returns the value of this element.
     *
     * No guessing!
     *
     * @returns {null}
     */
    GenericElement.prototype.getValue = function() {
        // Null should always be returned if we do not know the value.
        // This is a generic element, as such we can't be sure what the value of the thing is.
        // Rather than guess (we should never guess) we will return null.
        // The form will have to check with the server to see what the value is.
        return null;
    };

    /**
     * Shows the loading icon for the form control
     *
     * Usually hide will not be called as a reload wipes and rebuilds the form
     */
    GenericElement.prototype.showLoading = function() {};

    /**
     * Hides the loading icon for the form control
     *
     * This wont normally be called as most of the functionality will wipe the form
     */
    GenericElement.prototype.hideLoading = function() {};

    return GenericElement;

});