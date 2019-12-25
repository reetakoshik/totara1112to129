/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Brian Barnes <brian.barnes@totaralms.com>
 * @package core_blocks
  */

define(['jquery', 'core/templates'], function ($, templates) {
    var blockhider = {

        init: function(config) {
            var block = $('#' + config.id);
            var title = block.find('.title');
            var action = block.find('.block_action');
            var collapsible = action.data('collapsible');

            function handleClick(e) {
                e.preventDefault();
                var hide = !block.hasClass('hidden');
                M.util.set_user_preference(config.preference, hide);
                if (hide) {
                    block.addClass('hidden');
                    block.find('.block-hider-show').focus();
                } else {
                    block.removeClass('hidden');
                    block.find('.block-hider-hide').focus();
                }
            }

            function handlekeypress(e) {
                e.preventDefault();
                if (e.keyCode == 13) { //allow hide/show via enter key
                    handleClick(this);
                }
            }

            if (title && action && collapsible) {
                templates.renderIcon('block-hide', config.tooltipVisible).done(function (html) {
                    var hideicon = $('<a href="#" title="' + config.tooltipVisible + '" class="block-hider-hide">' + html + '</a>');

                    hideicon.click(handleClick);
                    hideicon.keypress(handlekeypress);
                    action.append(hideicon);
                });
                templates.renderIcon('block-show', config.tooltipHidden).done(function (html) {
                    var showicon = $('<a href="#" title="' + config.tooltipHidden + '" class="block-hider-show">' + html + '</a>');

                    showicon.click(handleClick);
                    showicon.keypress(handlekeypress);
                    action.append(showicon);
                });
            }

        }
    };

    return blockhider;
});
