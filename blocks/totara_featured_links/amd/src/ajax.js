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

/**
 * contains the functions that do the ajax calls to add and remove tiles
 */

define(['jquery', 'core/ajax', 'core/str'], function ($, ajax, mdlstr) {
    return {
        block_totara_featured_links_remove_tile: function () {
            var required_strings = [];
            required_strings.push({key: 'delete', component: 'core'});
            required_strings.push({key: 'cancel', component: 'core'});
            required_strings.push({key: 'confirm', component: 'block_totara_featured_links'});

            mdlstr.get_strings(required_strings).done(function (strings) {
                var delete_button = $('a[type="remove"]');
                delete_button.off('click');
                delete_button.click(function (event) {
                    event.preventDefault();
                    var confirm = new M.core.confirm({
                        question: strings[2],
                        modal: true,
                        yesLabel: strings[0],
                        noLabel: strings[1],
                        title: '&nbsp;',
                        zIndex: 500 // Set the z_index so the tile does not get on top of the dialog.
                    });
                    confirm.on('complete-yes', function () {
                        var parent_a = $(event.target).closest('a');
                        var tileid = parent_a.attr('tileid');

                        var promises = ajax.call([
                            {
                                methodname: 'block_totara_featured_links_external_remove_tile',
                                args: {
                                    tileid: tileid
                                }
                            }
                        ]);
                        promises[0].done(function (response) {
                            if (response === true) {
                                $(event.target).closest('.block-totara-featured-links-layout > div').hide();
                            }
                        });
                    }, this);
                });
            });
        }
    };
});