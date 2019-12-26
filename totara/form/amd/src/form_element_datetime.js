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
 * @module  totara_form/form_element_datetime
 * @class   DateTimeElement
 * @author  Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'totara_form/form'], function($, Form) {

    /**
     * DateTime element
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
    function DateTimeElement(parent, type, id, node) {

        if (!(this instanceof DateTimeElement)) {
            return new DateTimeElement(parent, type, id, node);
        }

        Form.Element.apply(this, arguments);

        this.input = null;

    }

    DateTimeElement.prototype = Object.create(Form.Element.prototype);
    DateTimeElement.prototype.constructor = DateTimeElement;

    /**
     * Returns a string describing this object.
     * @returns {string}
     */
    DateTimeElement.prototype.toString = function() {
        return '[object DateTimeElement]';
    };

    /**
     * Initialises a new instance of this element.
     * @param {Function} done
     */
    DateTimeElement.prototype.init = function(done) {
        var id = this.id,
            deferreds = [];
        this.input = $('#' + id);
        // Call the changed method when this element is changed.
        this.input.change($.proxy(this.changed, this));

        if (!(/Android|iPhone|iPad|iPod/i.test(navigator.userAgent))) {
            this.input.attr('type', 'text');
            var dateDeferred = $.Deferred();
            deferreds.push(dateDeferred);
            // Polyfill the date/time functionality.
            require(['totara_form/polyfill_date-lazy'], function(date) {
                date.init(id, true).then(function() {
                    dateDeferred.resolve();
                });
            });
        }

        $.when.apply($, deferreds).done(done);
    };

    /**
     * Returns the datetime elements value.
     * @returns {string}
     */
    DateTimeElement.prototype.getValue = function() {
        return this.input.val();
    };

    return DateTimeElement;

});