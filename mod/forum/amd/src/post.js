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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_forum
 * @module mod_forum/post
 */
define(['jquery', 'core/notification', 'core/str'], function($, Notification, Str) {
    var post = {
        // Show a confirmation popup to inform the user that submitting will unlock the discussion.
        confirm: function(e) {
            e.preventDefault();
            Str.get_strings([
                {
                    key:        'confirm',
                    component:  'moodle'
                },
                {
                    key:        'postingwillunlock',
                    component:  'mod_forum'
                },
                {
                    key:        'yes',
                    component:  'moodle'
                },
                {
                    key:        'no',
                    component:  'moodle'
                }
            ]).done(function(s) {
                Notification.confirm(s[0], s[1], s[2], s[3], $.proxy(function() {
                    window.onbeforeunload = null;
                    $('#mformforum').submit();
                }));
            });
        },

        setup: function() {
            $('body').delegate('#mformforum #id_submitbutton', 'click', post.confirm);
        }
    };

    return {
        setup: post.setup
    };
});
