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
 * @module  totara_form/form_element_filemanager
 * @class   FileManagerElement
 * @author  Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/yui', 'totara_form/form'], function($, Y, Form) {

    /**
     * File manager element
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
    function FileManagerElement(parent, type, id, node) {

        if (!(this instanceof FileManagerElement)) {
            return new FileManagerElement(parent, type, id, node);
        }

        Form.Element.apply(this, arguments);

        this.input = null;
    }

    FileManagerElement.prototype = Object.create(Form.Element.prototype);
    FileManagerElement.prototype.constructor = FileManagerElement;

    /**
     * Returns a string describing this object.
     * @returns {string}
     */
    FileManagerElement.prototype.toString = function() {
        return '[object FileManagerElement]';
    };

    /**
     * Initialises a new instance of this element.
     * @param {Function} done
     */
    FileManagerElement.prototype.init = function(done) {
        this.input = $('#' + this.id);
        var options = {};

        try {
            options = this.input.data('fmoptions');
        } catch (ex) {
            Form.debug('Failed to pick up FileManager options from data attribute.', this, Form.LOGLEVEL.error);
            throw ex;
        }

        if (options === '') {
            // The data attribute was empty, this only happens when the file manager was frozen.
            Form.debug('FileManager could not initialise, no options present.', this, Form.LOGLEVEL.error);
            done();
            return;
        }

        require(['totara_form/element_filemanager'], function(fp) {
            fp.init_filemanager(options);
            done();
        });
    };

    return FileManagerElement;

});
