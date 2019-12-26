/**
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
 * @author  Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_form
 */

/**
 * @module  totara_form/form_element_utc10date
 * @class   UTC10Date
 * @author  Petr Skoda <petr.skoda@totaralearning.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'totara_form/form'], function($, Form) {

    /**
     * UTC10Date element
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
    function UTC10Date(parent, type, id, node) {

        if (!(this instanceof UTC10Date)) {
            return new UTC10Date(parent, type, id, node);
        }

        Form.Element.apply(this, arguments);

        this.input = null;

    }

    UTC10Date.prototype = Object.create(Form.Element.prototype);
    UTC10Date.prototype.constructor = UTC10Date;

    /**
     * Returns a string describing this object.
     * @returns {string}
     */
    UTC10Date.prototype.toString = function() {
        return '[object UTC10Date]';
    };

    /**
     * Initialises a new instance of this element.
     * @param {Function} done
     */
    UTC10Date.prototype.init = function(done) {
        var id = this.id,
            deferreds = [];
        this.input = $('#' + id);
        // Call the changed method when this element is changed.
        this.input.change($.proxy(this.changed, this));

        if (!(/Android|iPhone|iPad|iPod/i.test(navigator.userAgent))) {
            var dateDeferred = $.Deferred();
            this.input.attr('type', 'text');
            deferreds.push(dateDeferred);
            // Polyfill the date/time functionality.
            require(['totara_form/polyfill_date-lazy'], function(date) {
                date.init(id, false).done(function() {
                    dateDeferred.resolve();
                });
            });
        }

        $.when.apply($, deferreds).done(done);
    };

    /**
     * Returns the utc10date elements value.
     * @returns {string}
     */
    UTC10Date.prototype.getValue = function() {
        return this.input.val();
    };

    return UTC10Date;

});