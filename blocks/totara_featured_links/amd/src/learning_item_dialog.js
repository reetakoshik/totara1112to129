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
    'core/str',
    'core/config'
], function($, mdlstr, config) {

    /* global totaraSingleSelectDialog */

    var selectedHtml = '';
    var learningItem = '';
    var strings = {
        ok: '',
        cancel: '',
        title: ''
    };

    var makeDialog = function() {

        var url = config.wwwroot + '/blocks/totara_featured_links/' + learningItem + '_dialog.php?';

        totaraSingleSelectDialog(learningItem,
            strings.title + selectedHtml,
            url,
            learningItem + '_name_id',
            learningItem + '-name'
        );

        $('input[name="' + learningItem + '_name"]').attr('readonly', 'readonly');
    };

    return {
        init: function(selected, learningItemType) {
            learningItem = learningItemType;
            selectedHtml = selected;

            var requiredStrings = [];
            requiredStrings.push({key: 'cancel', component: 'moodle'});
            requiredStrings.push({key: learningItem + '_select', component: 'block_totara_featured_links'});

            mdlstr.get_strings(requiredStrings).done(function(stringResults) {
                strings = {
                    cancel: stringResults[0],
                    title: stringResults[1]
                };

                if (window.dialogsInited) {
                    makeDialog();
                } else {
                    // Queue it up.
                    if (!$.isArray(window.dialoginits)) {
                        window.dialoginits = [];
                    }
                    window.dialoginits.push(makeDialog);
                }
            });
        }
    };
});