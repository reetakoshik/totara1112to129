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
define(['core/templates'], function(templates) {

    /**
     * Initialises Accordion, and opens the first element if alwaysOpen = true
     * @constructor
     * @param {string} element
     */
    function AccordionController(element) {
        this.element = element;

        this.alwaysOpen = this.element.getAttribute('data-always-open') !== "false"; // default true
        this.allowMultiple = this.element.getAttribute('data-allow-multiple') === "true"; // default false

        // If we're always open, and nothing is open, open the first element
        if (this.alwaysOpen) {
            var openChildren = this.getOpenChildren();
            if (openChildren.length === 0) {
                this.toggleItem(this.getChildren()[0]);
            }
        }
    }

    /**
     * Sets up click events for accordion widget
     */
    AccordionController.prototype.events = function() {
        var self = this;

        var toggleHeader = function(e) {
            // If this is inside a no-op section, don't open/close the accordion
            if (e.target.closest('[data-accordion-noop]')) { return; }

            var item = e.target.closest('.totara_core__Accordion__item');
            if (item) {
                self.toggleItem(item);
            }
        };

        this.element.addEventListener('click', toggleHeader);

        // Open the accordion
        this.element.addEventListener('totara_core/accordion:open', function(e) {
            var item = e.target.closest('.totara_core__Accordion__item');
            var event = e.originalEvent || e;

            self.toggleItem(item, true, !!event.suppress);
        });

        // Close the accordion
        this.element.addEventListener('totara_core/accordion:close', function(e) {
            var item = e.target.closest('.totara_core__Accordion__item');
            var event = e.originalEvent || e;

            self.toggleItem(item, false, !!event.suppress);
        });
    };

    /**
     * gets all Accordion Item children
     * @returns {NodeList}
     */
    AccordionController.prototype.getChildren = function() {
        return this.element.querySelectorAll('.totara_core__Accordion__item');
    };

    /**
     * Gets all open Accordion Item children
     * @returns {NodeList}
     */
    AccordionController.prototype.getOpenChildren = function() {
        return this.element.querySelectorAll('.totara_core__Accordion__item:not(.collapsed)');
    };

    /**
     * Toggle the selected item open and closed
     * @param {string} elem HTMLElement Accordion item to toggle open or closed
     * @param {boolean} toggle (optional) State to toggle to, true = open
     * @param {boolean} silent (optional) Suppress event propogation
     */
    AccordionController.prototype.toggleItem = function(elem, toggle, silent) {
        var isOpen = !elem.classList.contains('collapsed'),
            newState = toggle === undefined ? !isOpen : toggle;

        // Don't toggle if we have alwaysOpen and this is the last open item
        if (this.alwaysOpen && this.getOpenChildren().length === 1 && isOpen) {
            return;
        }

        // Close all other items if we don't allow multiple
        if (newState && !this.allowMultiple) {
            this.closeAllItems();
        }

        // TL-20522: IE11 ignores the second parameter of classList.toggle, so we have to do this explicitly
        if (newState) {
            elem.classList.remove('collapsed');
        } else {
            elem.classList.add('collapsed');
        }

        elem.querySelector('.totara_core__Accordion__item__header').setAttribute('aria-expanded', newState);

        var icon = newState ? 'totara_core|accordion-expanded' : 'totara_core|accordion-collapsed';

        templates.renderIcon(icon).then(function(html) {
            elem.querySelector('.totara_core__Accordion__item__header__icon').innerHTML = html;
        });

        if (!silent) {
            if (newState) {
                elem.dispatchEvent(new CustomEvent('totara_core/accordion:opened', {
                    bubbles: true,
                    detail: {elem: elem}
                }));
            } else {
                elem.dispatchEvent(new CustomEvent('totara_core/accordion:closed', {
                    bubbles: true,
                    detail: {elem: elem}
                }));
            }
        }
    };

    /**
     * Closes all children
     */
    AccordionController.prototype.closeAllItems = function() {
        var children = this.getChildren();

        for (var i = 0; i < children.length; i++) {
            var child = children.item(i);

            child.classList.add('collapsed');
            child.querySelector('.totara_core__Accordion__item__header').setAttribute('aria-expanded', false);
        }

        //Run this loop seperately so icon loading doesn't delay collapse
        templates.renderIcon('totara_core|accordion-collapsed').then(function(html) {
            for (var i = 0; i < children.length; i++) {
                var child = children.item(i);
                if (child.classList.contains('collapsed')) {
                    child.querySelector('.totara_core__Accordion__item__header__icon').innerHTML = html;
                }
            }
        });
    };

    /**
     * Initialise our widget
     * @param {string} element
     * @returns {Promise}
     */
    function init(element) {
        return new Promise(function(resolve) {
            var controller = new AccordionController(element);
            controller.events();
            resolve(controller);
        });
    }

    return {
        init: init
    };
});