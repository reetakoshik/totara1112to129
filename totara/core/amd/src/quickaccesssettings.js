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
define(['core/templates', 'core/str', 'core/ajax'], function(template, str, ajax) {

    /**
     * @param {string|$} element
     * @constructor
     */
    function QuickAccessSettings(element) {
        this.element = element;
    }

    /**
     * Register event listeners for QuickAccessSettings
     */
    QuickAccessSettings.prototype.events = function() {
        var self = this;
        var items; // temp variable for looping through NodeLists -- don't trust it's value
        var i;

        this.element.addEventListener('click', function(e) {
            var group = e.target.closest('[data-quickaccesssettings-group-key]'),
                item = e.target.closest('[data-quickaccesssettings-item-key]'),
                action = '';

            if (item) {
                action = e.target.closest('[data-quickaccesssettings-item-action]');
                if (action !== null) {
                    action = action.getAttribute('data-quickaccesssettings-item-action');
                    switch (action) {
                        case 'moveup':
                            swapItems(item.previousElementSibling, item);
                            e.preventDefault();
                            e.stopPropagation();
                            break;
                        case 'movedown':
                            swapItems(item, item.nextElementSibling);
                            e.preventDefault();
                            e.stopPropagation();
                            break;
                        case 'delete':
                            e.preventDefault();
                            e.stopPropagation();

                            self.save('totara_core_quickaccessmenu_remove_item',
                                {key: item.getAttribute('data-quickaccesssettings-item-key')})
                                .done(function() {
                                    item.remove();
                                })
                                .fail(function() {
                                    window.location.reload();
                                });
                            break;
                        default:
                    }
                }

                return;
            }

            if (group) {
                action = e.target.closest('[data-quickaccesssettings-group-action]');

                if (action !== null) {

                    // Close the accordion
                    group.dispatchEvent(new CustomEvent('totara_core/accordion:close', {
                        bubbles: true
                    }));

                    action = action.getAttribute('data-quickaccesssettings-group-action');
                    switch (action) {
                        case 'moveup':
                            swapGroups(group.previousElementSibling, group);
                            e.preventDefault();
                            e.stopPropagation();
                            break;
                        case 'movedown':
                            swapGroups(group, group.nextElementSibling);
                            e.preventDefault();
                            e.stopPropagation();
                            break;
                        case 'delete':
                            e.preventDefault();
                            self.save('totara_core_quickaccessmenu_remove_group',
                                {groupkey: group.getAttribute('data-quickaccesssettings-group-key')})
                                .done(function() {
                                    group.remove();
                                })
                                .fail(function() {
                                    window.location.reload();
                                });

                            break;
                        default:

                    }
                }
                return;
            }
        }, true); //capture event

        /**
         * Inline-edit event to rename item and groups
         * @param {Event} e
         */
        function saveEdit(e) {
            var elem = e.detail.elem;
            var text = e.detail.text;

            if (elem.closest('[data-quickaccesssettings-item-key]')) {
                self.save('totara_core_quickaccessmenu_rename_item', {
                    key: elem.closest('[data-quickaccesssettings-item-key]').getAttribute('data-quickaccesssettings-item-key'),
                    label: text
                }).fail(function() {
                    window.location.reload();
                });

                str.get_string('quickaccesssettings:itemlabel', 'totara_core').done(function(string) {
                    elem.setAttribute('aria-label', string + ' ' + text);
                });
            } else if (elem.closest('[data-quickaccesssettings-group-key]')) {
                self.save('totara_core_quickaccessmenu_rename_group', {
                    groupkey: elem.closest('[data-quickaccesssettings-group-key]').getAttribute('data-quickaccesssettings-group-key'),
                    groupname: text
                }).fail(function() {
                    window.location.reload();
                });
            }
        }
        this.element.addEventListener('totara_core/inline-edit:save', saveEdit);

        /**
         * Select_tree event to add items
         *
         * @param {Event} event
         */
        function addItem(event) {
            var data = event.detail;

            var key = data.val;
            var groupElem = event.target.closest('.totara_core__QuickAccessSettings__group');
            var group = groupElem.getAttribute('data-quickaccesssettings-group-key');

            self.save('totara_core_quickaccessmenu_add_item', {
                key: key,
                group: group
            }).done(function(res) {

                template.render('totara_core/quickaccesssettings_item', res).done(function(item) {
                    //Search for duplicate items, and remove them (external api behaviour)
                    var duplicate = self.element.querySelector('[data-quickaccesssettings-item-key="' + key + '"]');
                    if (duplicate) {
                        duplicate.closest('.totara_core__QuickAccessSettings__item').remove();
                    }
                    var itemList = groupElem.querySelector('.totara_core__QuickAccessSettings__item-list');
                    itemList.insertAdjacentHTML('beforeend', item);
                    template.runTemplateJS();
                });
            }).fail(function(res) {
                window.location.reload();
            });

            // Unset active items from select_tree
            var customEvent = new CustomEvent('totara_core/select_tree:set-active', {
                bubbles: false
            });
            data.widget.dispatchEvent(customEvent);
            data.widget.setAttribute('data-tw-selectorgroup-clear', true);
        }
        this.element.addEventListener('totara_core/select_tree:add', addItem);

        /**
         * Adds a group and then reloads.
         */
        function addGroup() {
            self.save('totara_core_quickaccessmenu_add_group', {
                groupname: ''
            }).done(function(res) {
                template.render('totara_core/quickaccesssettings_group', res).done(function(item) {
                    var groupList = self.element.querySelector('.totara_core__QuickAccessSettings__group-list');
                    groupList.insertAdjacentHTML('beforeend', item);
                    template.runTemplateJS();
                });
            }).fail(function() {
                window.location.reload();
            });
        }
        items = this.element.querySelectorAll('[data-quickaccesssettings-addgroup]');
        for (i = 0; i < items.length; i++) {
            items.item(i).addEventListener('click', addGroup);
        }

        /**
         * Swaps 2 groups
         *
         * @param {Node} prev The group to move down
         * @param {Node} group The group to move up
         */
        var swapGroups = function(prev, group) {
            if (!prev || !group) {
                // no point in swapping if one is null
                return;
            }
            var groupkey = group.getAttribute('data-quickaccesssettings-group-key');
            var prevkey = prev.getAttribute('data-quickaccesssettings-group-key');

            self.save('totara_core_quickaccessmenu_move_group_before', {
                key: groupkey,
                beforekey: prevkey
            }).done(function() {
                group.classList.add('totara_core__QuickAccessSettings__item-swap-up');
                prev.classList.add('totara_core__QuickAccessSettings__item-swap-down');

                M.util.js_pending('totara_core-quickaccesssettings-reorder-item-swap-up');
                setTimeout(function() {
                    group.classList.remove('totara_core__QuickAccessSettings__item-swap-up');
                    prev.classList.remove('totara_core__QuickAccessSettings__item-swap-down');

                    prev.parentNode.insertBefore(group, prev);

                    M.util.js_complete('totara_core-quickaccesssettings-reorder-item-swap-up');
                }, 200);
            }).fail(function() {
                window.location.reload();
            });
        };

        /**
         * Swaps 2 menu items
         *
         * @param {Node} prev The menu item to move down
         * @param {Node} item The menu item to move up
         */
        var swapItems = function(prev, item) {
            if (!prev || !item) {
                // no point in swapping if one is null
                return;
            }
            var itemkey = item.getAttribute('data-quickaccesssettings-item-key');
            var prevkey = prev.getAttribute('data-quickaccesssettings-item-key');

            self.save('totara_core_quickaccessmenu_move_item_before', {
                key: itemkey,
                beforekey: prevkey
            }).done(function() {
                item.classList.add('totara_core__QuickAccessSettings__item-swap-up');
                prev.classList.add('totara_core__QuickAccessSettings__item-swap-down');

                M.util.js_pending('totara_core-quickaccesssettings-reorder-item-up');
                setTimeout(function() {
                    item.classList.remove('totara_core__QuickAccessSettings__item-swap-up');
                    prev.classList.remove('totara_core__QuickAccessSettings__item-swap-down');

                    prev.parentNode.insertBefore(item, prev);

                    M.util.js_complete('totara_core-quickaccesssettings-reorder-item-up');
                }, 200);
            }).fail(function() {
                window.location.reload();
            });
        };
    };

    /**
     * Saves change via ajax
     *
     * @param {string} method
     * @param {object} args
     */
    QuickAccessSettings.prototype.save = function(method, args) {
        args.userid = 0;
        var promise = ajax.call(
            [{
                methodname: method,
                args: args
            }]
        )[0];

        M.util.js_pending('totara_core-quickaccesssettings-save');

        promise.always(function() {
            M.util.js_complete('totara_core-quickaccesssettings-save');
        });
        return promise;
    };

    /**
     * Initialise our widget
     * @param {string|$} element
     * @returns {Promise}
     */
    function init(element) {
        return new Promise(function(resolve) {
            var controller = new QuickAccessSettings(element);
            controller.events();
            resolve(controller);
        });
    }

    return {
        init: init
    };
});
