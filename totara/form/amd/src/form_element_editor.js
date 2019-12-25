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
 * @module  totara_form/form_element_editor
 * @class   Editor
 * @author  Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'totara_form/form'], function($, Form) {

    /**
     * Editor element
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
    function Editor(parent, type, id, node) {

        if (!(this instanceof Editor)) {
            return new Editor(parent, type, id, node);
        }

        Form.Element.apply(this, arguments);

        this.input = null;

    }

    Editor.prototype = Object.create(Form.Element.prototype);
    Editor.constructor = Editor;

    /**
     * Returns a string describing this object.
     * @returns {string}
     */
    Editor.prototype.toString = function() {
        return '[object Editor]';
    };

    /**
     * Initialises a new instance of this element.
     * @param {Function} done
     */
    Editor.prototype.init = function(done) {
        this.input = $('#' + this.id);
        this.input.change($.proxy(this.changed, this));

        // This is a bit of hackery to expose this editor object to the world.
        // This is required for the likes of behat where we must programatically interact with the editor.
        window.totara_form_editors = window.totara_form_editors || {};
        window.totara_form_editors[this.id] = this;

        done();
    };

    /**
     * Sets the editor value, really useful for behat.
     */
    Editor.prototype.setValue = function(value) {
        this.input.val(value);
        this.changed({});
    };

    /**
     * Returns the value of the editor.
     * @returns {string}
     */
    Editor.prototype.getValue = function() {
        return this.input.val();
    };

    return Editor;
});