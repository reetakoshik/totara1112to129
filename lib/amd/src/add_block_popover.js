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
 * @package add_block_popover
 */

define(['jquery', 'core/templates', 'core/str', 'core/flex_icon'], function($, templates, mdlstr, flex) {

    /**
     * Class constructor for the AddBlockPopover.
     *
     * @class
     * @constructor
     */
    function AddBlockPopover() {
        if (!(this instanceof AddBlockPopover)) {
            return new AddBlockPopover();
        }
    }

    AddBlockPopover.prototype = {
        constructor: AddBlockPopover,

        /**
        * Add 'add block popover' to page
        *
        * @param {Object} itemList The template context for rendering this modal body.
        * @param {string} region The region in which we are adding popover.
        */
        addToPage: function(itemList, region) {
            var that = this;
            if (document.querySelector('[data-addblock="' + region + '"]') === null) {
                // No more blocks can be here - no point in doing any work
                return;
            }

            M.util.js_pending('core_add_block');
            mdlstr.get_strings([
                {key: 'closebuttontitle', component: 'core'},
                {key: 'search', component: 'core'}

            ]).then(function(strings) {
                return $.when(
                    flex.getIconData('delete', 'core', {alt: strings[0]}),
                    flex.getIconData('search', 'core', {alt: strings[1]})
                );
            }).then(function(closeIcon, searchIcon) {
                var data = {
                    'contenttemplate': 'core/add_block_popover_content',
                    'contenttemplatecontext': {
                        'close_icon': closeIcon,
                        'has_focus': true,
                        'item_list': itemList,
                        'placement_max_height': 600,
                        'placement_max_width': 600,
                        'search_icon': searchIcon
                    },
                    'arrow_placement': 'smartPosition',
                    'close_on_focus_out': true,
                    'trigger': 'click'
                };
                return templates.render('core/popover', data);

            }).then(function(htmlString, js) {
                var html,
                    parent = document.querySelector('[data-addblock="' + region + '"]'),
                    range = document.createRange(),
                    block = parent.parentNode;
                range.selectNode(parent);

                // Add modal markup to page
                html = range.createContextualFragment(htmlString);
                parent.appendChild(html);

                // Run popup scan
                templates.runTemplateJS(js);

                // Add events to popover
                that.events(block, parent);
                M.util.js_complete('core_add_block');
            });
        },

        /**
        * Change focus on list items
        *
        * @param {event} e
        */
        changeListItemFocus: function(e) {
            var node = e.target.closest('.addBlockPopover--results_list_item'),
                scrollArea = e.target.closest('.addBlockPopover--results');

            // Set focus to previous
            if (e.keyCode == '38') {
                while (node.previousElementSibling) {
                    node = node.previousElementSibling;
                    if (!node.classList.contains('hide')) {
                        break;
                    }
                }

            // Set focus to next
            } else if (e.keyCode == '40') {
                while (node.nextElementSibling) {
                    node = node.nextElementSibling;
                    if (!node.classList.contains('hide')) {
                        break;
                    }
                }
            }

            // If there is no node to change to, abort
            if (node.classList.contains('hide')) {
                return;
            }

            node.querySelector('a').focus();

            // Make sure focused item is visible
            scrollArea.scrollTop = node.offsetTop;
        },

        /**
        * Close Add block popover
        *
        * @param {node} parent
        */
        closeAddBlockPopover: function(parent) {
            $(parent).popover('hide');
        },

        /**
        * Add block popover event listeners
        *
        * @param {node} block
        * @param {node} parent
        */
        events: function(block, parent) {
            var that = this;

            block.addEventListener('click', function(e) {
                e.preventDefault();
                if (!e.target) {
                    return;
                }

                if (e.target.closest('[data-addblockpopover-close]')) {
                    that.closeAddBlockPopover(parent);
                }

                if (e.target.closest('.addBlockPopover--results_list_item')) {
                    that.submitAddBlock(this, e.target.closest('.addBlockPopover--results_list_item'));
                }
            });

            block.addEventListener('keydown', function(e) {
                if (!e.target) {
                    return;
                }

                if (e.target.closest('.addBlockPopover--results_list_item') && ((e.keyCode == '38' || e.keyCode == '40'))) {
                    e.preventDefault();
                    that.changeListItemFocus(e);

                } else if (e.target.closest('#addBlockPopover--search_query') && e.shiftKey && e.keyCode == 9) {
                    that.closeAddBlockPopover(parent);
                }
            });

            block.addEventListener('keyup', function(e) {
                if (!e.target) {
                    return;
                }

                // If escape key pressed
                if (e.keyCode == '27') {
                    that.closeAddBlockPopover(parent);
                }

                if (e.target.closest('#addBlockPopover--search_query')) {
                    if (e.keyCode == '13') {
                        // If enter key hit
                        that.searchListEnter(block);

                    } else {
                        // If text added to input field
                        that.searchListItems(block, e);
                    }
                }
            });

            // When focus out on add block popover
            block.addEventListener('focusout', function(e) {
                if (!e.target || !e.relatedTarget) {
                    return;
                }

                if (e.target.closest('.addBlockPopover--close') && !e.relatedTarget.closest('.popover')) {
                    that.closeAddBlockPopover(parent);
                }
            });

            // Set focus to input when popover displayed
            $(parent).on('shown.bs.popover', function() {
                that.setFocusToInput(block);
            });
        },

        /**
        * Enter key pressed on input
        *
        * @param {node} block
        */
        searchListEnter: function(block) {
            var visibleNodeItems = block.querySelectorAll('.popover .addBlockPopover--results_list_item:not(.hide)');
            if (visibleNodeItems.length === 1) {
                this.submitAddBlock(block, visibleNodeItems[0]);

            } else if (visibleNodeItems.length >= 2) {
                visibleNodeItems[0].querySelector('a').focus();
            }
        },

        /**
        * Search list items
        *
        * @param {node} block
        * @param {event} e
        */
        searchListItems: function(block, e) {
            var value = e.target.value.toLowerCase();
            var nodeItems = block.querySelectorAll('.popover .addBlockPopover--results_list_item');

            for (var i = 0; i < nodeItems.length; i++) {
                var node = nodeItems[i],
                    nodeString = node.getAttribute('data-addblockpopover-blocktitle');

                // If title doesn't contain substring hide it
                if (nodeString.toLowerCase().indexOf(value) === -1) {
                    node.classList.add('hide');

                } else {
                    node.classList.remove('hide');
                    // Wrap first instance of substring in bold tags
                    var target = node.querySelector('a');
                    target.innerHTML = nodeString.replace(new RegExp(value, 'i'), '<b>$&</b>');
                }
            }
        },

        /**
        * Set focus to filter input
        *
        * @param {node} block
        */
        setFocusToInput: function(block) {
            block.querySelector('.popover #addBlockPopover--search_query').focus();
        },

        /**
        * Submit add block
        *
        * @param {node} parent
        * @param {node} item
        */
        submitAddBlock: function(parent, item) {
            // Add loading overlay
            item.closest('.addBlockPopover').classList.add('addBlockPopover--overlay');

            var selectedVal = item.getAttribute('data-addblockpopover-blockname');
            parent.querySelector('input[name=bui_addblock]').setAttribute('value', selectedVal);
            parent.querySelector('form').submit();
        },
    };

    /**
    * add block popover initialisation method
    *
    * @param {Object} itemList The template context for rendering this modal body.
    * @param {string} region The region in which we are adding popover.
    */
    var init = function(itemList, region) {
        // Create an instance of add block popover
        var wgt = new AddBlockPopover();
        wgt.addToPage(itemList, region);
    };

    return {
        init: init
    };
});
