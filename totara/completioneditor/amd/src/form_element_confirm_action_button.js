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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_completioneditor
 */

/**
 * @module  totara_completioneditor/form_element_confirm_action_button
 * @class   ConfirmActionButtonElement
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'totara_form/form', 'core/notification'], function($, Form, Notification) {

    /**
     * Confirm action button Element class.
     *
     * @module totara_completioneditor/form_element_confirm_action_button
     * @constructor
     * @augments Form.Element
     * @param {HTMLElement} parent
     * @param {string} type
     * @param {string} id
     * @param {HTMLElement} node
     * @returns {ConfirmActionButtonElement}
     */
    function ConfirmActionButtonElement(parent, type, id, node) {

        if (!(this instanceof ConfirmActionButtonElement)) {
            return new ConfirmActionButtonElement(parent, type, id, node);
        }

        Form.Element.apply(this, arguments);

        this.input = null;
    }

    ConfirmActionButtonElement.prototype = Object.create(Form.Element.prototype);
    ConfirmActionButtonElement.prototype.constructor = ConfirmActionButtonElement;

    /**
     * Returns a string describing this object.
     * @returns {string}
     */
    ConfirmActionButtonElement.prototype.toString = function() {
        return '[object ConfirmActionButtonElement]';
    };

    /**
     * Initialises a new instance of this element.
     * @param {Function} done
     */
    ConfirmActionButtonElement.prototype.init = function(done) {
        var showconfirm = true,
            button = $('#' + this.id);

        button.on('click', function (e) {
            if (showconfirm !== true) {
                return;
            }

            e.preventDefault();

            Notification.confirm(
                button.data("dialogtitle"),
                button.data("dialogmessage"),
                button.data("yesbuttonlabel"),
                button.data("nobuttonlabel"),
                function() {
                    showconfirm = false;
                    button.click();
                },
                function() {
                    showconfirm = true;
                }
            );
        });

        done();
    };

    return ConfirmActionButtonElement;

});
