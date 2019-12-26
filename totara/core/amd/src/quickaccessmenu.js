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
 * @author Carl Anderson <carl.anderson@totaralearning.com>
 * @package totara_core
 */
define(['core/str', 'core/popover_region_controller'], function(str, PopoverRegionController) {

    /**
     * Internal class to extend popover functionality
     * @constructor
     * @param {string|$} element
     */
    function QuickAccessPopoverController(element) {
        // Initialise base class.
        PopoverRegionController.call(this, element);
        this.element = element;
    }

    /**
     * Clone the parent prototype
     */
    QuickAccessPopoverController.prototype = Object.create(PopoverRegionController.prototype);

    /**
     * Change language string when closing and opening the menu
     */
    QuickAccessPopoverController.prototype.updateButtonAriaLabel = function() {
        if (this.isMenuOpen()) {
            str.get_string('quickaccessmenu:hidemenuwindow', 'totara_core').done(function(string) {
                // Double up the check to make sure that the result hasn't changed during the async function.
                if (this.isMenuOpen()) {
                    this.menuToggle.attr('aria-label', string);
                }
            }.bind(this));
        } else {
            str.get_string('quickaccessmenu:showmenuwindow', 'totara_core').done(function(string) {
                if (this.isMenuOpen() === false) {
                    this.menuToggle.attr('aria-label', string);
                }
            }.bind(this));
        }
    };

    /**
     * Setup events listeners for this widget
     */
    QuickAccessPopoverController.prototype.setupEvents = function() {
        var self = this;
        this.element.querySelector('[data-quickaccessmenu-close-menu]').addEventListener('click', function(e) {
            e.preventDefault();
            self.closeMenu();

            return false;
        });

        if (document.readyState === 'complete') {
            self.checkLocation();
        } else {
            window.addEventListener('load', self.checkLocation.bind(self));
        }
    };

    QuickAccessPopoverController.prototype.checkLocation = function() {
        var popover = this.element.querySelector('#quickaccess-popover-content');
        var boundingRect = popover.getBoundingClientRect();

        // If popover is off-screen, i.e. on RTL
        if (boundingRect.left < 0 || boundingRect.right > window.innerWidth) {
            popover.classList.add('totara_core__QuickAccess_menu--large');
        } else {
            popover.classList.remove('totara_core__QuickAccess_menu--large');
        }
    };

    /**
     * Initialise our widget.
     * @param {string|$} element
     * @return {Promise}
     */
    function init(element) {
        return new Promise(function(resolve) {
            var controller = new QuickAccessPopoverController(element);
            controller.setupEvents();
            resolve(controller);
        });
    }

    return {
        init: init
    };
});
