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
 * @module  totara_form/form_element_select
 * @class   SelectElement
 * @author  Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'totara_form/form'], function($, Form) {

    var ERROR_CONTAINER_CLASS = 'totara_form-error-container',
        ERROR_CONTAINER_SELECTOR = '.'+ERROR_CONTAINER_CLASS;

    /**
     * Select element
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
    function SelectElement(parent, type, id, node) {

        if (!(this instanceof SelectElement)) {
            return new SelectElement(parent, type, id, node);
        }

        Form.Element.apply(this, arguments);

        this.input = null;
        this.validationerroradded = false;
    }

    SelectElement.prototype = Object.create(Form.Element.prototype);
    SelectElement.prototype.constructor = SelectElement;

    /**
     * Returns a string describing this object.
     * @returns {string}
     */
    SelectElement.prototype.toString = function() {
        return '[object SelectElement]';
    };

    /**
     * Initialises a new instance of this element.
     * @param {Function} done
     */
    SelectElement.prototype.init = function(done) {
        this.input =  $('#' + this.id);
        // Call the changed method when this element is changed.
        this.input.change($.proxy(this.changed, this));

        done();
    };

    SelectElement.prototype.polyFillValidate = function(e) {
        var valid = false,
            input = this.input;

        input.find('option').each(function(index, option) {
            if ($(option).prop('selected') && $(option).val() !== '') {
                valid = true;
            }
        });
        if (valid) {
            this.validationerroradded = false;
            input.closest('.tf_element').find(ERROR_CONTAINER_SELECTOR).remove();
        } else {
            e.preventDefault();
            if (!this.validationerroradded) {
                this.validationerroradded = true;
                require(['core/templates', 'core/str', 'core/config'], function (templates, mdlstrings, mdlconfig) {
                    mdlstrings.get_string('required','core').done(function (requiredstring) {
                        var context = {
                            errors_has_items: true,
                            errors: [{message: requiredstring}]
                        };
                        templates.render('totara_form/validation_errors', context, mdlconfig.theme).done(function (template) {
                            input.parent().prepend(template);
                        });
                    });
                });
            }
        }
    };

    SelectElement.prototype.getValue = function() {
        return this.input.val();
    };

    SelectElement.prototype.showLoading = function() {
        this.input.siblings('.tf_loading').show();
    };

    SelectElement.prototype.hideLoading = function () {
        this.siblings('.tf_loading').hide();
    };

    return SelectElement;

});