/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @package totara_catalog
 */

/**
 * @module  totara_catalog/form_element_matrix
 * @class   Matrix
 * @author  Brian Barnes <brian.barnes@totaralearning.com>
 */
define(['totara_form/form', 'core/templates'], function(Form, templates) {

    /**
     * Matrix element
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
    function Matrix(parent, type, id, node) {
        if (!(this instanceof Matrix)) {
            return new Matrix(parent, type, id, node);
        }

        Form.Element.apply(this, arguments);

        this.input = document.getElementById(id);
        this.id = id;
        this.parent = parent;
        this.node = node;
        this.potentialfilters = {};
        this.name = this.input.getAttribute('data-name');
    }

    Matrix.prototype = Object.create(Form.Element.prototype);
    Matrix.prototype.constructor = Matrix;

    /**
     * Returns a string describing this object.
     * @returns {string}
     */
    Matrix.prototype.toString = function() {
        return '[object Matrix]';
    };

    /**
     * Initialises a new instance of this element
     * @param {Function} done function to call once initialisation is complete
     */
    Matrix.prototype.init = function(done) {
        var options = this.input.getElementsByClassName('totara_catalog-matrix__addfilter')[0].options;
        var option = 0;
        // Skip first option
        for (option = 1; option < options.length; option++) {
            this.potentialfilters[options[option].value] = options[option].label;

            if (this.input.querySelectorAll('[data-id=' + options[option].value + ']').length > 0) {
                options[option].classList.add("totara_form_option_invisible");
                options[option].disabled = true;
            }
        }

        this.input.getElementsByClassName('totara_catalog-matrix__addfilter')[0].addEventListener('change', this._addItem.bind(this));
        this.input.addEventListener('click', this._changeItem.bind(this));
        this.input.addEventListener('change', this.changed.bind(this));

        done();
    };

    /**
     * Returns the value of this matrix
     *
     * @returns {Object} the value of the matrix
     */
    Matrix.prototype.getValue = function() {
        var rows = this.input.getElementsByTagName('tbody')[0].getElementsByTagName('tr'),
            row,
            result = [];

        for (row = 0; row < rows.length; row++) {
            result.push({
                id: rows[row].getAttribute('data-id'),
                name: rows[row].getElementsByTagName('input')[0].value
            });
        }

        return result;
    };

    Matrix.prototype._addItem = function(e) {
        if (e.target.value === "") {
            // Ignore if the value is empty
            return;
        }

        var self = this;
        var context = {
            id: e.target.value,
            filtername: this.potentialfilters[e.target.value],
            heading: this.potentialfilters[e.target.value],
            name: this.name
        };
        var select = e.target;

        templates.render('totara_catalog/element_matrix__row', context)
            .done(function(htmlString) {
                self.input.querySelector('#' + self.id + '_table tbody').insertAdjacentHTML('beforeend', htmlString);

                select.options[select.selectedIndex].classList.add("totara_form_option_invisible");
                select.options[select.selectedIndex].disabled = true;
                select.selectedIndex = 0;

                self.changed();
            });
    };

    Matrix.prototype._changeItem = function(e) {
        var row = e.target.closest('tr'),
            button = e.target.closest('[data-action]');

        if (!button) {
            // We don't care if there was no action provided
            return;
        }
        e.preventDefault();

        switch (button.getAttribute('data-action')) {
            case 'delete':
                var select = this.input.getElementsByClassName('totara_catalog-matrix__addfilter')[0],
                    option;

                option = select.querySelector('option[value=' + row.getAttribute('data-id') + ']');
                option.classList.remove("totara_form_option_invisible");
                option.disabled = false;

                row.remove();
                break;
            case 'move-up':
                if (row.previousElementSibling !== null) {
                    row.parentElement.insertBefore(row, row.previousElementSibling);
                }
                break;
            case 'move-down':
                if (row.nextElementSibling !== null) {
                    row.nextElementSibling.insertAdjacentElement('afterend', row);
                }
                break;

        }

        this.changed();
    };

    return Matrix;
});