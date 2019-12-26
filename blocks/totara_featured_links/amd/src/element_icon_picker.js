/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package block_totara_featured_links
 */
define([
    'jquery',
    'core/templates',
    'core/config',
    'core/str',
    'core/modal_factory',
    'core/modal_events'
], function($,
            templates,
            config,
            mdlstr,
            ModalFactory,
            ModalEvents) {

    var init = function(tfElementId, tfElementName, iconData) {
        var id = tfElementId;
        var name = tfElementName;
        var saveElement = $($('input[name="' + name + '"]')[0]);

        var requiredStrings = [];
        requiredStrings.push({key: 'ok', component: 'moodle'});
        requiredStrings.push({key: 'cancel', component: 'moodle'});
        requiredStrings.push({key: 'icon_choose_title', component: 'block_totara_featured_links'});
        requiredStrings.push({key: 'icon_choose', component: 'block_totara_featured_links'});
        requiredStrings.push({key: 'icon_change', component: 'block_totara_featured_links'});

        var gettingStrings = mdlstr.get_strings(requiredStrings);

        var renderingIcons = templates.render('block_totara_featured_links/icon_picker_icons',
            {icons: iconData});

        $.when(gettingStrings, renderingIcons).done(function(stringResults, contentMarkup){
            var strings = {
                ok: stringResults[0],
                cancel: stringResults[1],
                title: stringResults[2],
                choose: stringResults[3],
                change: stringResults[4]
            };

            // Set up listener for removing the icon
            $('#' + id + '_delete').on('click', function() {
                $(this).hide();
                $('#' + id + '_icon').html('');
                $('input[name="' + name + '"]').val('');
                $('#show-iconPicker-dialog').html(strings.choose);
            });

            ModalFactory.create(
                {
                    type: 'CONFIRM',
                    title: strings.title,
                    body: contentMarkup[0]
                },
                $('#show-iconPicker-dialog'),
                {
                    yesstr: strings.ok,
                    nostr: strings.cancel
                }
            ).done(function(modal) {
                var root = modal.getRoot();
                root.find('.icon-picker-icons .icon-picker-item').on('click', function() {
                    $('.icon-picker-item.selected').removeClass('selected');
                    $(this).addClass('selected');
                    $('#icon-picker-selected-icon').html($(this).attr('data-flex-icon-identifier'));
                });
                root.on(ModalEvents.shown, function(e) {
                    $('.icon-picker-item.selected').removeClass('selected');
                    var selected = $('input[name="' + name + '"]').val();
                    $('[data-flex-icon-identifier="' + selected + '"]').addClass('selected');
                });
                root.on(ModalEvents.yes, function(e) {
                    var selectedIcon = $('.selected').attr('data-flex-icon-identifier');
                    if (!selectedIcon) {
                        return;
                    }
                    saveElement.val(selectedIcon);
                    templates.renderIcon(selectedIcon)
                        .done(function(html) {
                            $('#' + id + '_icon').html(html);
                            var deleteButton = $('#' + id + '_delete');
                            deleteButton.removeClass('hidden');
                            deleteButton.show();
                            $('#show-iconPicker-dialog').html(strings.change);
                        });
                    modal.hide();
                });
            });
        });
    };

    return {
        init: init
    };
});