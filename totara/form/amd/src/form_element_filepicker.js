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
 * @module  totara_form/form_element_filepicker
 * @class   FilePickerElement
 * @author  Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/yui', 'totara_form/form'], function($, Y, Form) {

    /**
     * File picker element
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
    function FilePickerElement(parent, type, id, node) {

        if (!(this instanceof FilePickerElement)) {
            return new FilePickerElement(parent, type, id, node);
        }

        Form.Element.apply(this, arguments);

        this.input = null;

    }

    FilePickerElement.prototype = Object.create(Form.Element.prototype);
    FilePickerElement.prototype.constructor = FilePickerElement;

    /**
     * Returns a string describing this object.
     * @returns {string}
     */
    FilePickerElement.prototype.toString = function() {
        return '[object FilePickerElement]';
    };

    /**
     * Initialises a new instance of this element.
     * @param {Function} done
     */
    FilePickerElement.prototype.init = function(done) {
        this.input = $('#' + this.id);
        var options = {};

        try {
            options = this.input.data('fpoptions');
        } catch (ex) {
            Form.debug('Failed to pick up FilePicker options from data attribute.', this, Form.LOGLEVEL.error);
            throw ex;
        }

        if (options === '') {
            // The data attribute was empty, this only happens when the file picker was frozen.
            Form.debug('FilePicker initialisation skipped as it was frozen.', this, Form.LOGLEVEL.error);
            done();
            return;
        }

        require(['totara_form/element_filepicker'], function(fm) {
            fm.init_filepicker(options);
            done();
        });
    };

    return FilePickerElement;

});
