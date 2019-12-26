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
 * @module  totara_catalog/form_element_MultiSelect
 * @class   multiSelect
 * @author  Brian Barnes <brian.barnes@totaralearning.com>
 */
define(['totara_form/form', 'core/templates'], function(Form, templates) {

    /**
     * multiSelect element
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
    function MultiSelect(parent, type, id, node) {
        if (!(this instanceof MultiSelect)) {
            return new MultiSelect(parent, type, id, node);
        }

        Form.Element.apply(this, arguments);

        this.input = document.getElementById(id);
        this.id = id;
        this.parent = parent;
        this.node = node;
        this.addSelector = this.input.getElementsByClassName('totara_catalog-multiple_select__addicon')[0];
        this.name = this.input.getAttribute('data-name');
    }

    MultiSelect.prototype = Object.create(Form.Element.prototype);
    MultiSelect.prototype.constructor = MultiSelect;

    /**
     * Returns a string describing this object.
     * @returns {string}
     */
    MultiSelect.prototype.toString = function() {
        return '[object multiSelect]';
    };

    /**
     * Initialises a new instance of this element
     * @param {Function} done function to call once initialisation is complete
     */
    MultiSelect.prototype.init = function(done) {
        var options = this.addSelector.options;
        var option = 0;
        // Skip first option
        for (option = 1; option < options.length; option++) {
            if (this.input.querySelectorAll('[data-id=' + options[option].value + ']').length > 0) {
                options[option].classList.add("totara_form_option_invisible");
                options[option].disabled = true;
            }
        }

        this.addSelector.addEventListener('change', this._addItem.bind(this));
        this.input.addEventListener('click', this._changeItem.bind(this));
        this._checkempty();

        done();
    };

    /**
     * Returns the value of this multiSelect
     *
     * @returns {Object} the value of the multiSelect
     */
    MultiSelect.prototype.getValue = function() {
        var icons = this.input.getElementsByTagName('ol')[0].getElementsByTagName('li'),
            icon,
            result = [];

        for (icon = 0; icon < icons.length; icon++) {
            result.push(icons[icon].getAttribute('data-id'));
        }

        return result;
    };

    MultiSelect.prototype._addItem = function(e) {
        if (e.target.value === "") {
            // Ignore if the value is empty
            return;
        }

        var self = this,
            select = e.target,
            selectedIndex = select.selectedIndex,
            context = {
                id: select.value,
                iconname: select.options[selectedIndex].text
            };

        templates.render('totara_catalog/element_multiple_select__item', context)
            .done(function(htmlString) {
                self.input.querySelector('#' + self.id + '_list').insertAdjacentHTML('beforeend', htmlString);

                select.options[selectedIndex].classList.add("totara_form_option_invisible");
                select.options[selectedIndex].disabled = true;
                select.selectedIndex = 0;

                self._changed();
            });
    };

    MultiSelect.prototype._changeItem = function(e) {
        var listItem = e.target.closest('li'),
            button = e.target.closest('[data-action]');

        if (!button) {
            // We don't care if there was no action provided
            return;
        }
        e.preventDefault();

        switch (button.getAttribute('data-action')) {
            case 'delete':
                var select = this.input.getElementsByClassName('totara_catalog-multiple_select__addicon')[0],
                    option;

                option = select.querySelector('option[value=' + listItem.getAttribute('data-id') + ']');
                option.classList.remove("totara_form_option_invisible");
                option.disabled = false;

                listItem.remove();
                break;
            case 'move-up':
                if (listItem.previousElementSibling !== null) {
                    listItem.parentElement.insertBefore(listItem, listItem.previousElementSibling);
                }
                break;
            case 'move-down':
                if (listItem.nextElementSibling !== null) {
                    listItem.nextElementSibling.insertAdjacentElement('afterend', listItem);
                }
                break;

        }

        this._changed();
    };

    MultiSelect.prototype._changed = function() {
        this.node.querySelector('[type=hidden]').value = JSON.stringify(this.getValue());
        this._checkempty();

        this.changed();
    };

    MultiSelect.prototype._checkempty = function() {
        if (this.input.querySelector('.totara_catalog-multiple_select__selected li') === null) {
            this.input.classList.add('totara_catalog-multiple_select__noitems');
        } else {
            this.input.classList.remove('totara_catalog-multiple_select__noitems');
        }
    };

    return MultiSelect;
});