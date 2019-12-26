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
 * @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
 * @package totara_core
 */

define(['core/templates'], function(templates) {

    /**
     * Class constructor for the Nav.
     *
     * @class
     * @constructor
     */
    function Nav() {
        if (!(this instanceof Nav)) {
            return new Nav();
        }
        this.widget = '';
        this.itemExpandedClass = 'totaraNav--list_item_expanded';
    }

    Nav.prototype = {
        constructor: Nav,

        events: function() {
            var that = this;

            this.widget.addEventListener('click', function(e) {
                if (!e.target) {
                    return;
                }

                if (e.target.closest('[data-tw-totaraNav-toggle]') || e.target.closest('[data-tw-totaranav-list-close]')) {
                    that.widget.querySelector('[data-tw-totaraNav-list]').classList.toggle('totaraNav_prim--list_showMobile');
                    that.widget.querySelector('[data-tw-totaraNav-list]').classList.toggle('totaraNav_prim--list_hideMobile');
                    if (e.target.closest('[data-tw-totaraNav-toggle]')) {
                        that.widget.querySelector('[data-tw-totaranav-list-close]').focus();
                    }
                    return;
                }

                var list = e.target.closest('li');

                // In a list item & the closest list item has children
                if (list && list.hasAttribute('data-tw-totaraNav-hasChildren')) {
                    e.preventDefault();

                    var listExpanded = list.classList.contains(that.itemExpandedClass);

                    if (listExpanded) {
                        that.toggleExpandedList(list);
                        return;
                    }

                    var hasMultiListLayers = list.parentNode.closest('[data-tw-totaraNav-hasChildren]'),
                        keepListOpen = hasMultiListLayers ? hasMultiListLayers : '';

                    that.closeAllExpandedLists(keepListOpen);
                    that.toggleExpandedList(list);
                }
            });

            var topLevelItemList = this.widget.querySelectorAll('[data-tw-totaranav-toplevelitem]');
            for (var s = 0; s < topLevelItemList.length; s++) {
                topLevelItemList[s].addEventListener('mouseenter', function() {
                    var activeNode = document.activeElement;
                    // If focus is on a navigation item and not in a expanded list
                    if (activeNode.classList.contains('totaraNav_prim--list_item_link')
                    && !activeNode.parentNode.classList.contains(that.itemExpandedClass)) {
                        activeNode.blur();
                    }
                });
            }

            this.widget.addEventListener('mouseleave', function() {
                var activeNode = document.activeElement;
                // If focus is on a navigation item
                if (activeNode.closest('[data-tw-totaranav-item]')) {
                    that.closeAllExpandedLists();
                }
            });

            // If on desktop
            if (window.innerWidth > 991) {

                this.widget.addEventListener('keydown', function(e) {
                    if (!e.target) {
                        return;
                    }

                    // If on a nav item
                    if (e.target.closest('[data-tw-totaraNav-item]')) {
                        var currentListItem = e.target.closest('[data-tw-totaraNav-item]'),
                            inExpandedList = e.target.closest('[data-tw-navExpand-list]');

                        switch (e.key) {
                            case 'ArrowDown':
                            case 'Down':
                                e.preventDefault();
                                that.listItemKeyDown(currentListItem, inExpandedList);
                            break;
                            case 'ArrowLeft':
                            case 'Left':
                                e.preventDefault();
                                that.listItemKeyLeft(currentListItem, inExpandedList);
                            break;
                            case 'ArrowRight':
                            case 'Right':
                                e.preventDefault();
                                that.listItemKeyRight(currentListItem, inExpandedList);
                            break;
                            case 'ArrowUp':
                            case 'Up':
                                e.preventDefault();
                                that.listItemKeyUp(currentListItem, inExpandedList);
                            break;
                            case 'Escape':
                            case 'Esc':
                                that.listItemKeyEsc(currentListItem, inExpandedList);
                            break;
                            case 'Tab':
                                if (e.shiftKey) {
                                    that.listItemKeyTabBack(currentListItem);
                                } else {
                                    that.listItemKeyTab(currentListItem, inExpandedList);
                                }
                            break;
                            case ' ':
                            case 'Spacebar':
                                e.preventDefault();
                                that.listItemKeySpace(currentListItem);
                            break;
                        }
                    }
                });

                window.addEventListener('click', function(e) {
                    if (!e.target.closest('[data-tw-totaranav-item]')) {
                        that.closeAllExpandedLists();
                    }
                });
            } else {
                //On mobile, if someone clicks somewhere other than the menu, we want to close it
                window.addEventListener('click', function(e) {
                    if (!e.target.closest('[data-tw-totaraNav-list]') && !e.target.closest('[data-tw-totaraNav-toggle]')) {
                        that.widget.querySelector('[data-tw-totaraNav-list]').classList.remove('totaraNav_prim--list_showMobile');
                        that.widget.querySelector('[data-tw-totaraNav-list]').classList.add('totaraNav_prim--list_hideMobile');
                    }
                });
            }

            // Mobile / Desktop resize changes
            window.addEventListener('resize', function() {
                var resizefunction = function() {
                    resizeTimeout = null;

                    var nav = that.widget.querySelector('[data-tw-totaraNav-list]'),
                        chevron = that.widget.querySelectorAll('.totaraNav--icon_chevron');

                    // Rerender chevron icons
                    for (var i = 0; i < chevron.length; i++) {
                        chevron[i].remove();
                    }

                    if (window.innerWidth > 991) {
                        that.addExpandableIcons();
                        nav.classList.remove('totaraNav_prim--list_hideMobile', 'totaraNav_prim--list_showMobile');
                    } else {
                        that.addExpandableIcons();
                        if (!nav.classList.contains('totaraNav_prim--list_showMobile')) {
                            nav.classList.add('totaraNav_prim--list_hideMobile');
                        }
                    }
                };
                if (M.cfg.behatsiterunning) {
                    // Behat is more predictable without setTimeout.
                    resizefunction();
                    return;
                }
                var resizeTimeout;
                if (!resizeTimeout) {
                    // Execute at a rate of 15fps
                    resizeTimeout = setTimeout(resizefunction, 66);
                }
            });
        },

        /**
        * Add expandable icon
        *
        * @param {node} node, parent link
        */
        addExpandableIcons: function() {
            var expandableItems = this.widget.querySelectorAll('[data-tw-totaraNav-chevron]');
            for (var i = 0; i < expandableItems.length; i++) {
                this.toggleExpandableIcon(expandableItems[i].parentNode);
            }
        },

        /**
        * Close expanded list
        *
        * @param {node} node
        */
        closeExpandedList: function(node) {
            var listLink = node.querySelector('[aria-expanded]');

            // Close expanded nav
            node.classList.remove(this.itemExpandedClass);
            listLink.setAttribute('aria-expanded', false);
            // Toggle Icon
            this.toggleExpandableIcon(listLink);
        },

        /**
        * Close all expandable lists
        *
        * @param {node} exclude, used to keep current list parent open
        */
        closeAllExpandedLists: function(exclude) {
            var expandableItems = this.widget.querySelectorAll('.' + this.itemExpandedClass);
            for (var i = 0; i < expandableItems.length; i++) {
                if (exclude !== expandableItems[i]) {
                    this.closeExpandedList(expandableItems[i]);
                }
            }
        },

        /**
        * Key press down on list item
        *
        * @param {node} currentListItem
        * @param {node} inExpandedList
        */
        listItemKeyDown: function(currentListItem, inExpandedList) {
            // On a list parent which has been expanded
            if (currentListItem.classList.contains(this.itemExpandedClass)) {
                var childList = currentListItem.querySelectorAll('[data-tw-navExpand-list]')[0];
                childList.querySelectorAll('a')[0].focus();

            // In expanded list with next item
            } else if (inExpandedList && currentListItem.nextElementSibling) {
                currentListItem.nextElementSibling.querySelector('a').focus();
            }
        },

        /**
        * Key press escape on list item
        *
        * @param {node} currentListItem
        * @param {node} inExpandedList
        */
        listItemKeyEsc: function(currentListItem, inExpandedList) {
            // If not in an expanded list, abort
            if (!inExpandedList) {
                return;
            }

            var currentList = currentListItem.closest('.' + this.itemExpandedClass);
            this.closeExpandedList(currentList);
            currentList.querySelector('a').focus();
        },

        /**
        * Key press left on list item
        *
        * @param {node} currentListItem
        * @param {node} inExpandedList
        */
        listItemKeyLeft: function(currentListItem, inExpandedList) {
            // In expanded list or no previous item, abort
            if (inExpandedList || !currentListItem.previousElementSibling) {
                return;
            }

            this.closeAllExpandedLists();
            currentListItem.previousElementSibling.querySelector('a').focus();
        },

        /**
        * Key press right on list item
        *
        * @param {node} currentListItem
        * @param {node} inExpandedList
        */
        listItemKeyRight: function(currentListItem, inExpandedList) {
            // In expanded list or no next item, abort
            if (inExpandedList || !currentListItem.nextElementSibling) {
                return;
            }

            this.closeAllExpandedLists();
            currentListItem.nextElementSibling.querySelector('a').focus();
        },

        /**
        * Key press space on list item
        *
        * @param {node} currentListItem
        */
        listItemKeySpace: function(currentListItem) {
            // On a list item & the closest list item has children
            if (currentListItem.hasAttribute('data-tw-totaraNav-hasChildren')) {
                this.toggleExpandedList(currentListItem.closest('[data-tw-totaraNav-hasChildren]'));
            }
        },

        /**
        * Key press tab on list item
        *
        * @param {node} currentListItem
        * @param {node} inExpandedList
        */
        listItemKeyTab: function(currentListItem, inExpandedList) {
            // If not in an expanded list, or not last item, or item is expanded, abort
            if (!inExpandedList || currentListItem.nextElementSibling || currentListItem.classList.contains(this.itemExpandedClass)) {
                return;
            }

            var parentList = currentListItem.parentNode.closest('[data-tw-totaranav-item]'),
                parentHasSibling = parentList.nextElementSibling,
                parentIsTopLevel = !parentList.parentNode.closest('[data-tw-totaranav-item]'),
                topList = currentListItem.parentNode.closest('[data-tw-totaranav-toplevelitem]');

            // If parent is last item or parent is top level close all lists
            if (!parentHasSibling || parentIsTopLevel) {
                this.closeAllExpandedLists('');

            // If parent has sibling, close the closest list
            } else if (parentHasSibling) {
                this.closeAllExpandedLists(topList);
            }
        },

        /**
        * Key press shift tab on list item
        *
        * @param {node} currentListItem
        * @param {node} inExpandedList
        */
        listItemKeyTabBack: function(currentListItem) {
            var isExpanded = currentListItem.classList.contains(this.itemExpandedClass);
            if (isExpanded) {
                this.closeExpandedList(currentListItem);
            }
        },

        /**
        * Key press up on list item
        *
        * @param {node} currentListItem
        * @param {node} inExpandedList
        */
        listItemKeyUp: function(currentListItem, inExpandedList) {
            // In expanded list with previous item
            if (inExpandedList && currentListItem.previousElementSibling) {
                currentListItem.previousElementSibling.querySelector('a').focus();

            // First item in expanded list
            } else if (inExpandedList && !currentListItem.previousElementSibling) {
                inExpandedList.previousElementSibling.focus();
                this.closeExpandedList(inExpandedList.parentNode);
            }
        },

        /**
        * Toggle expanded list
        *
        * @param {node} list
        */
        toggleExpandedList: function(list) {
            // Display expanded menu
            list.classList.toggle(this.itemExpandedClass);

            // Set aria expanded to correct value
            var expanded = list.classList.contains(this.itemExpandedClass) ? true : false,
                listLink = list.querySelector('[aria-expanded]');

            listLink.setAttribute('aria-expanded', expanded);
            listLink.focus();
            this.toggleExpandableIcon(listLink);
        },

        /**
        * Set widget parent
        *
        * @param {node} widgetParent
        */
        setParent: function(widgetParent) {
            this.widget = widgetParent;
        },

        /**
        * Toggle / Add expandable icon
        *
        * @param {node} node, parent link
        */
        toggleExpandableIcon: function(node) {
            var iconArea = node.querySelector('[data-tw-totaraNav-chevron]'),
                iconType,
                primaryNav = node.closest('[data-tw-totaraNav-topLevelItem]'),
                topLevelItem = node.parentNode.hasAttribute('data-tw-totaraNav-topLevelItem');

            // Has icon, so toggle it
            if (iconArea.hasChildNodes()) {
                iconType = iconArea.querySelector('[data-flex-icon]').getAttribute('data-flex-icon');
            }
            // on top level and desktop or not primary nav
            if ((topLevelItem && window.innerWidth > 991) || !primaryNav) {
                if (!iconType) {
                    iconType = 'nav-down';
                } else {
                    return;
                }
            } else {
                iconType = iconType === 'nav-expand' ? 'nav-expanded' : 'nav-expand';
            }

            // Add icon to node
            templates.renderIcon(iconType, '', 'totaraNav--icon_chevron').done(function(data) {
                iconArea.innerHTML = data;
            });
        },

    };

    /**
    * nav initialisation method
    *
    * @param {node} parent node
    */
    var init = function(parent) {
        // Create an instance of nav
        var wgt = new Nav();
        wgt.setParent(parent);
        wgt.events();
        wgt.addExpandableIcons();
    };

    return {
        init: init
    };
});