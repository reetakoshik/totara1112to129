/**
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
 * @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
 * @package totara_core
 */

define([], function() {

    /**
    * Class constructor for the tree select.
    *
    * @class
    * @constructor
    */
    function SelectTree() {
        if (!(this instanceof SelectTree)) {
            return new SelectTree();
        }
        this.activeClass = 'tw-selectTree__active';
        this.activeSelector = 'data-tw-selector-active';
        this.clearSelector = 'data-tw-selectorgroup-clear';
        this.hideClass = 'tw-selectTree__hidden';
        this.key = '';
        this.keyboardClass = 'tw-selectTree__keyboard';
        this.repositionClass = 'tw-selectTree__reposition';
        this.widget = '';
        this.visibility = false;
    }

    SelectTree.prototype = {
        constructor: SelectTree,

        /**
        * Add value
        *
        * @param {node} activeNode
        */
        add: function(activeNode) {
            var value = activeNode.getAttribute('data-tw-selectTree-urlVal'),
                label = activeNode.getAttribute('data-tw-selectTree-label'),
                parent = activeNode.parentNode,
                defaultNode = parent.hasAttribute('data-tw-selectTree-default');

            // Update UI
            this.removeActive();
            this.closeLists();
            this.setCurrentLabel(label);
            parent.classList.add(this.activeClass);
            if (!defaultNode) {
                parent.setAttribute(this.activeSelector, '');
            }
            this.expandActiveLists(parent);
            this.setEventType(value);
        },

        /**
        * Close all tree lists
        *
        */
        closeLists: function() {
            var lists = this.widget.querySelectorAll('[data-tw-selectTree-list]');

            for (var i = 0; i < lists.length; i++) {
                if (!lists[i].classList.contains(this.hideClass)) {
                    var prevNode = lists[i].previousElementSibling,
                        toggle = prevNode.querySelector('[data-tw-selectTree-toggle]');
                    this.toggleTreeList(lists[i], toggle);
                }
            }
        },

        /**
        * Add event listeners
        *
        */
        events: function() {
            var that = this;

            this.widget.addEventListener('click', function(e) {
                e.preventDefault();
                if (!e.target) {
                    return;
                }

                // If show tree triggered
                if (e.target.closest('[data-tw-selectTree-trigger]')) {
                    that.toggleTree();

                // If list toggle clicked
                } else if (e.target.closest('[data-tw-selectTree-toggle]')) {
                    var toggleNode = e.target.closest('[data-tw-selectTree-toggle]'),
                        toggleList = toggleNode.parentNode.nextElementSibling;

                    if (toggleNode.hasAttribute('data-tw-selecttree-label')) {
                        toggleNode = toggleNode.previousElementSibling;
                    }

                    that.toggleTreeList(toggleList, toggleNode);

                // If tree item clicked
                } else if (e.target.closest('[data-tw-selectTree-urlVal]')) {
                    var node = e.target.closest('[data-tw-selectTree-urlVal]');

                    if (node.parentNode.classList.contains(that.activeClass)) {
                        return;
                    }

                    that.add(node);
                    that.toggleTree();
                    that.triggerEvent('changed', {});
                }
            });

            var clickclear = function(e) {
                var tree = that.widget.querySelector('[data-tw-selectTree-tree]');
                if (!that.widget.contains(e.target) && !tree.classList.contains(that.hideClass)) {
                    that.toggleTree();
                }
            };

            // Add a click event for focus events not correctly and Safari doesn't focus on <a>
            document.addEventListener('click', clickclear);
            document.addEventListener('touchstart', clickclear);

            this.widget.addEventListener('keydown', function(e) {
                if (!e.target) {
                    return;
                }
                var activeNodeIndex = 0,
                    nodeList = that.widget.querySelectorAll('.tw-selectTree__list_row'),
                    found = false;

                for (var i = 0; i < nodeList.length; ++i) {
                    if (nodeList[i] == document.activeElement.closest('.tw-selectTree__list_row')) {
                        activeNodeIndex = i;
                        found = true;
                        break;
                    }
                }

                if (!found) {
                    // Just gone into a single entry list
                    for (var s = 0; s < nodeList.length; ++s) {
                        if (nodeList[s].classList.contains('tw-selectTree__active')) {
                            activeNodeIndex = s;
                            break;
                        }
                    }
                }
                // If keyboard key pressed on select tree, add keyboard styling
                that.widget.classList.add(that.keyboardClass);

                switch (e.key) {
                    case 'Enter':
                        e.preventDefault();
                        e.target.click();
                        nodeList[activeNodeIndex].getElementsByClassName('tw-selectTree__list_row_link')[0].focus();
                    break;
                    case 'Tab':
                        if (activeNodeIndex === nodeList.length - 1 && that.visibility) {
                            that.toggleTree();
                            return;
                        }

                        var selectLastElement = true;

                        // Find next visible node
                        for (var t = activeNodeIndex + 1; t < nodeList.length; ++t) {
                            var nodeT = nodeList[t].getElementsByClassName('tw-selectTree__list_row_link')[0];
                            if (nodeT && nodeT.offsetHeight !== 0) {
                                e.preventDefault();
                                selectLastElement = false;
                                nodeT.focus();
                                return;
                            }
                        }

                        // Close the select tree if it's visiable
                        if (selectLastElement && that.visibility) {
                            that.toggleTree();
                        }
                    break;
                    case 'ArrowUp':
                    case 'Up':
                        e.preventDefault();
                        if (activeNodeIndex === 0) {
                            return;
                        }

                        // Find prev visible node
                        var prevNode = activeNodeIndex - 1;
                        for (var b = prevNode; b < nodeList.length; ++b) {
                            var node = nodeList[prevNode].getElementsByClassName('tw-selectTree__list_row_link')[0];
                            if (node && node.offsetHeight !== 0) {
                                node.focus();
                                return;
                            }
                            prevNode--;
                        }
                    break;
                    case 'ArrowDown':
                    case 'Down':
                        e.preventDefault();
                        if (activeNodeIndex === nodeList.length - 1) {
                            return;
                        }

                        // Find next visible node
                        for (var a = activeNodeIndex + 1; a < nodeList.length; ++a) {
                            var nodeA = nodeList[a].getElementsByClassName('tw-selectTree__list_row_link')[0];
                            if (nodeA && nodeA.offsetHeight !== 0) {
                                nodeA.focus();
                                return;
                            }
                        }
                    break;
                    case 'ArrowLeft':
                    case 'Left':
                        e.preventDefault();
                        var nodeB = nodeList[activeNodeIndex].getElementsByClassName('tw-selectTree__list_row_icon_expand')[0];
                        if (nodeB && nodeB.classList.contains('tw-selectTree__hidden')) {
                            nodeList[activeNodeIndex].querySelector('[data-tw-selecttree-toggle]').click();
                        }
                    break;
                    case 'ArrowRight':
                    case 'Right':
                        e.preventDefault();
                        var nodeC = nodeList[activeNodeIndex].getElementsByClassName('tw-selectTree__list_row_icon_expanded')[0];
                        if (nodeC && nodeC.classList.contains('tw-selectTree__hidden')) {
                            nodeList[activeNodeIndex].querySelector('[data-tw-selecttree-toggle]').click();
                        }
                    break;
                }
            });

            // Create an observer instance with a callback function for clearing active items
            var observeClearBtn = new MutationObserver(function() {
                if (that.widget.getAttribute(that.clearSelector) === 'true') {
                    that.setToDefault();
                    that.triggerEvent('changed', {});
                    that.widget.setAttribute(that.clearSelector, false);
                }
            });

            // Start observing the widget for selectGroup clear attribute mutations
            observeClearBtn.observe(this.widget, {
                attributes: true,
                attributeFilter: [that.clearSelector],
                subtree: false
            });
        },

        /**
        * Expand lists with active child
        *
        * @param {node} activeNode
        */
        expandActiveLists: function(activeNode) {
            var parentList,
                parentListsExpanded = false;

            while (!parentListsExpanded) {
                parentList = activeNode.closest('[data-tw-selectTree-list].' + this.hideClass);
                if (parentList) {
                    var prevNode = parentList.previousElementSibling,
                        toggle = prevNode.querySelector('[data-tw-selectTree-toggle]');
                    this.toggleTreeList(parentList, toggle);
                } else {
                    parentListsExpanded = true;
                }
            }
        },

        /**
        * Inform parent widget of preset values
        *
        */
        preset: function() {
            var activeNode = this.widget.querySelector('[' + this.activeSelector + ']');

            if (!activeNode) {
                var defaultNode = this.widget.querySelector('[data-tw-selectTree-default] [data-tw-selectTree-urlVal]');
                if (defaultNode) {
                    var defaultNodeValue = defaultNode.getAttribute('data-tw-selectTree-urlVal');
                    if (defaultNodeValue) {
                        this.setEventType(defaultNodeValue);
                    }
                }
                return;
            }

            var item = activeNode.querySelector('[data-tw-selectTree-urlVal]'),
                value = item.getAttribute('data-tw-selectTree-urlVal');

            this.expandActiveLists(activeNode);
            this.setEventType(value);
        },

        /**
        * Remove active
        *
        */
        removeActive: function() {
            var list = this.widget.querySelectorAll('[data-tw-selectTree-tree] [' + this.activeSelector + ']'),
                def = this.widget.querySelector('[data-tw-selectTree-default]');

            if (def) {
                def.classList.remove(this.activeClass);
            }
            for (var i = 0; i < list.length; i++) {
                list[i].classList.remove(this.activeClass);
                list[i].removeAttribute(this.activeSelector);
            }
        },

        /**
        * Set current label
        *
        * @param {string} label
        */
        setCurrentLabel: function(label) {
            var current = this.widget.querySelector('[data-tw-selectTree-current]');
            current.innerHTML = label;
        },

        /**
        * Set event type
        *
        * @param {node} value
        */
        setEventType: function(value) {
            if (!value) {
                this.triggerEvent('remove', {
                    key: this.key
                });

            } else {
                this.triggerEvent('add', {
                    key: this.key,
                    val: value,
                    widget: this.widget
                });
            }
        },

        /**
        * Set URL key
        *
        * @param {string} key
        */
        setKey: function(key) {
            this.key = key;
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
        * Set to default value
        *
        */
        setToDefault: function() {
            var defaultNode = this.widget.querySelector('[data-tw-selectTree-default] [data-tw-selectTree-urlVal]');

            // Update UI
            this.removeActive();
            this.closeLists();

            if (defaultNode) {
                var label = defaultNode.getAttribute('data-tw-selectTree-label'),
                    value = defaultNode.getAttribute('data-tw-selectTree-urlVal'),
                    parent = defaultNode.parentNode;

                this.expandActiveLists(parent);
                this.setCurrentLabel(label);
                parent.classList.add(this.activeClass);
                this.setEventType(value);
            } else {
                var fallbackLabel = this.widget.querySelector('[data-tw-selecttree-callToActionLabel]');
                if (fallbackLabel) {
                    this.setCurrentLabel(fallbackLabel.getAttribute('data-tw-selecttree-callToActionLabel'));
                }
            }
        },

        /**
        * Toggles visibility of expand/expanded icons
        *
        * @param {node} toggleNode
        */
        toggleExpandIcons: function(toggleNode) {
            var toggleIcons = toggleNode.childNodes;
            toggleIcons[0].classList.toggle(this.hideClass);
            toggleIcons[1].classList.toggle(this.hideClass);
        },

        /**
        * Toggles sublist of tree
        *
        * @param {node} list
        * @param {node} toggleNode
        */
        toggleTreeList: function(list, toggleNode) {
            // Update UI
            list.classList.toggle(this.hideClass);
            this.toggleExpandIcons(toggleNode);

            // aria update
            var ariaState = list.classList.contains(this.hideClass) ? 'false' : 'true';
            toggleNode.setAttribute('aria-expanded', ariaState);
        },

        /**
        * Toggles visibility of options tree
        *
        */
        toggleTree: function() {
            // visibility should be changed in the fucntion toggleTree only
            this.visibility = !this.visibility;

            var tree = this.widget.querySelector('[data-tw-selectTree-tree]'),
                triggerNode = this.widget.querySelector('[data-tw-selectTree-trigger]');

            tree.classList.remove(this.repositionClass);
            triggerNode.classList.toggle(this.activeClass);
            tree.classList.toggle(this.hideClass);

            var endPosFromLeft = tree.getBoundingClientRect().right,
                windowWidth = document.documentElement.clientWidth;

            // If panel doesn't fit in view
            if (windowWidth < endPosFromLeft) {
                tree.classList.toggle(this.repositionClass);
            }

            // If list isn't expanded
            if (tree.classList.contains(this.hideClass)) {
                tree.setAttribute('aria-hidden', 'true');
                this.widget.classList.remove(this.keyboardClass);
            } else {
                tree.setAttribute('aria-hidden', 'false');

                var activeItem = tree.querySelector('[' + this.activeSelector + '] [data-tw-selectTree-label]'),
                    currentItem = activeItem ? activeItem : tree.querySelector('[data-tw-selectTree-default]');
                if (currentItem) {
                    this.expandActiveLists(currentItem);
                    currentItem.focus();
                }
            }
        },

        /**
        * Trigger event
        *
        * @param {string} eventName
        * @param {object} data
        */
        triggerEvent: function(eventName, data) {
            var propagateEvent = new CustomEvent('totara_core/select_tree:' + eventName, {
                bubbles: true,
                detail: data
            });
            this.widget.dispatchEvent(propagateEvent);
        }
    };

    /**
    * widget initialisation method
    *
    * @param {node} widgetParent
    * @returns {Object} promise
    */
    var init = function(widgetParent) {
        return new Promise(function(resolve) {
            // Create an instance of widget
            var wgt = new SelectTree();
            wgt.setParent(widgetParent);
            wgt.setKey(widgetParent.getAttribute('data-tw-selectTree-urlkey'));
            wgt.preset();
            wgt.events();
            resolve(wgt);
        });
    };

    return {
        init: init
    };
 });