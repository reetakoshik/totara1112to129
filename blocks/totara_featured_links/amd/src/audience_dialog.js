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
    'core/ajax',
    'block_totara_featured_links/visibility_form',
    'core/str',
    'core/config',
    'core/templates',
    'core/flex_icon'
], function($, ajax, VisForm, mdlstr, config, templates, flex) {

    /* global totaraDialog
              totaraDialog_handler_treeview_multiselect */

    var tileid = '';
    var configDialog = {};
    var handler;
    var strings = {
        ok: '',
        cancel: '',
        title: ''
    };
    // Copied stuff

    var makeDialog = function() {
        // Create handler for the dialog.
        var totaraDialog_handler_restrictcohorts = function() {
            this.baseurl = '';
        };

        totaraDialog_handler_restrictcohorts.prototype = new totaraDialog_handler_treeview_multiselect();

        totaraDialog_handler_restrictcohorts.prototype.every_load = function() {
            $('#audience_visible_table').find('[cohortid]').each(function() {
                var cohortid = $(this).attr('cohortid');
                ehandler._toggle_items('item_' + cohortid, false);
            });
            this._make_selectable($('.treeview', this._container));
            this._make_deletable($('.selected', this._container));
        };

        tileid = configDialog.instanceid;

        var ehandler = new totaraDialog_handler_restrictcohorts();
        handler = ehandler;

        var dbuttons = {};
        dbuttons[strings.ok] = function() {
            ehandler._update();
        };
        dbuttons[strings.cancel] = function() {
            ehandler._cancel();
        };

        var url = config.wwwroot + '/totara/cohort/dialog/';

        new totaraDialog(
            'course-cohorts-visible-dialogue',
            'add_audience_id',
            {
                buttons: dbuttons,
                title: strings.title
            },
            url + 'cohort.php?selected=' + configDialog.visibleselected
            + '&instancetype=' + configDialog.instancetype
            + '&instanceid=' + configDialog.instanceid
            + '&sesskey=' + configDialog.sesskey,
            ehandler
        );

        /**
         * Add a row to a list on the visibility form page
         * Also hides the dialog and any no item notice
         */
        totaraDialog_handler_restrictcohorts.prototype._update = function() {
            var elements = $('.selected [id^=item]', this._container);
            elements.each(function() {

                var itemid = $(this).attr('id').split('_'),
                    name = $(this).find('a').text();
                itemid = itemid[itemid.length - 1];  // The last item is the actual id.
                itemid = parseInt(itemid);

                // Check if list contains.
                if (VisForm.audience_list_contains(itemid)) {
                    return;
                }

                mdlstr.get_string('delete_audience_rule', 'block_totara_featured_links', name).then(function(str) {
                    return flex.getIconData('delete', 'core', {alt: str});
                }).then(function(icon) {
                    // This will call the function to load and render our template.
                    var context = {cohortid: itemid, name: name, deleteicon: icon};
                    return templates.render('block_totara_featured_links/element_audience_list_item', context);
                }).then(function(html) {
                    VisForm.add_to_audience_list(html);
                    VisForm.add_to_audience_id(itemid);
                    VisForm.add_audience_table_listeners();
                });
            });
            ehandler._dialog.hide();
        };
    };

    return {
        init: function(instancetype, instanceid, sesskey) {
            configDialog.instancetype = instancetype;
            configDialog.instanceid = instanceid;
            configDialog.sesskey = sesskey;

            var requiredStrings = [];
            requiredStrings.push({key: 'ok', component: 'moodle'});
            requiredStrings.push({key: 'cancel', component: 'moodle'});
            requiredStrings.push({key: 'audience_add', component: 'block_totara_featured_links'});

            mdlstr.get_strings(requiredStrings).done(function(stringResults) {
                strings = {
                    ok: stringResults[0],
                    cancel: stringResults[1],
                    title: stringResults[2]
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