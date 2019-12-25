/*
 * This file is part of Totara LMS
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
 * @author Carl Anderson <carl.anderson@totaralearning.com>
 * @package totara_core
 */
define(['core/str'], function(str) {

    /**
     * @param {string} element
     * @constructor
     */
    function InlineEditController(element) {
        var self = this;
        this.element = element;

        // Settings
        this.allowEmpty = this.element.getAttribute('data-inline-edit-allow-empty') === "true";
        this.maxLength = this.element.getAttribute('data-inline-edit-max-length') || 0; //0 = infinite

        this.controller = this.element.querySelector('[data-inline-edit-control]');
        this.target = this.element.querySelector('[data-inline-edit]');

        // Find the input to swap with, or create one if none exists
        var input = this.element.querySelector('[data-inline-edit-input]');
        if (!input) {
            var classes = this.target.getAttribute('class') || '';
            this.target.insertAdjacentHTML('afterend', '<input class="' + classes + '" data-inline-edit-input type="text">');
            input = this.element.querySelector('[data-inline-edit-input]'); // Fetch the new element
        }
        this.input = input;
        this.input.style.display = 'none';

        // Find the tooltip to show, or create one if none exists
        var tooltip = this.element.querySelector('[data-inline-edit-tooltip]');
        if (!tooltip) {
            this.target.insertAdjacentHTML('afterend', '<span data-inline-edit-tooltip class="totara_core__InlineEdit--tooltip"></span>');
            tooltip = this.element.querySelector('[data-inline-edit-tooltip]'); // Fetch the new element

            // Get the help text, and add it to the element
            str.get_string('inlineedit:instructions', 'totara_core').done(function(string) {
                tooltip.textContent = string;
                self.tooltipText = string;

                // Update the aria description of the input with the help text
                input.setAttribute('aria-label', string);
            });
        } else {
            // Update the aria description of the input with the help text
            input.setAttribute('aria-label', tooltip.textContent);
        }
        this.tooltip = tooltip;
        this.tooltipText = tooltip.textContent;

        this.tooltip.style.display = 'none';

        this.editing = false;
    }

    /**
     * Create event and key listeners
     */
    InlineEditController.prototype.events = function() {
        var self = this;

        this.controller.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            self.edit();
            self.hideError();
            return false;
        });

        this.input.addEventListener('keyup', function(e) {
            switch (e.key) {
                case "Enter":
                    self.save();
                    break;
                case "Esc": // TL-20522: IE11 puts interprets escape at "Esc"
                case "Escape":
                    self.cancel();
                    break;
                case "Space":
                    // Space is a "click" event for some elements, so prevent it from propagating to parents
                    e.stopPropagation();
                    break;
                default:
                    break;
            }

            if (!self.editing) { return; }

            var length = self.input.value.trim().length;
            if (!self.allowEmpty && length === 0) {
                self.showError('noempty');
            } else if (self.maxLength > 0 && length >= self.maxLength) {
                self.showError('maxlength');
            } else {
                self.hideError();
            }
        });

        this.input.addEventListener('focusout', function(e) {
            self.cancel();
        });

        // Don't propagate click events on the input
        this.input.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    };

    /**
     * Swap the element to editing mode
     */
    InlineEditController.prototype.edit = function() {
        if (this.editing) {
            return;
        }

        this.input.value = this.target.textContent.trim();

        this.target.style.display = 'none';
        // TL-20522: IE11 needs an empty string to clear style attributes instead of null
        this.input.style.display = '';
        this.tooltip.style.display = '';

        // Send focus to the input element
        this.input.focus();

        this.controller.style.display = 'none';
        this.editing = true;

        this.element.dispatchEvent(new CustomEvent('totara_core/inline-edit:edit', {
            bubbles: true,
            detail: {elem: this.element}
        }));
    };

    /**
     * Save the input to the original element, and displays it
     * Fires the totara_core/inline-edit:save event
     */
    InlineEditController.prototype.save = function() {
        if (!this.editing) {
            return;
        }

        var text = this.input.value.trim();

        if (text === "" && !this.allowEmpty) {
            // If they try to save an empty string, we should cancel instead of save
            this.cancel();
            return;
        }

        if (text.length > this.maxLength && this.maxLength > 0) {
            this.cancel();
            return;
        }

        this.target.textContent = text;

        // TL-20522: IE11 needs an empty string to clear style attributes instead of null
        this.target.style.display = '';
        this.input.style.display = 'none';
        this.tooltip.style.display = 'none';

        this.controller.style.display = '';
        this.editing = false;

        this.element.dispatchEvent(new CustomEvent('totara_core/inline-edit:save', {
            bubbles: true,
            detail: {text: text, elem: this.element}
        }));
    };

    /**
     * Cancels the input, displaying the original element
     */
    InlineEditController.prototype.cancel = function() {
        if (!this.editing) {
            return;
        }

        this.element.dispatchEvent(new CustomEvent('totara_core/inline-edit:cancel', {
            bubbles: true,
            detail: {elem: this.element}
        }));

        this.input.value = '';

        this.target.style.display = '';
        this.input.style.display = 'none';
        this.tooltip.style.display = 'none';

        this.controller.style.display = '';
        this.editing = false;
    };

    /**
     * Show an error message
     */
    InlineEditController.prototype.showError = function(string) {
        var self = this;
        this.error = string; // locking variable for the string
        str.get_string('inlineedit:' + string, 'totara_core').done(function(inlinestring) {
            if (self.error === string) {
                self.tooltip.textContent = inlinestring;
                self.tooltip.classList.add('totara_core__InlineEdit--tooltip--error');

                // Update the aria description of the input with the help text
                self.input.setAttribute('aria-label', inlinestring);
            }
        });
    };

    /**
     * Hides an error message
     */
    InlineEditController.prototype.hideError = function() {
        this.error = ''; // clear error
        this.tooltip.textContent = this.tooltipText;
        this.tooltip.classList.remove('totara_core__InlineEdit--tooltip--error');
    };

    return {
        init: function(element) {
            return new Promise(function(resolve) {
                var controller = new InlineEditController(element);
                controller.events();
                resolve(controller);
            });
        }
    };
});