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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @author Dave Wallace <dave.wallace@kineo.co.nz>
 * @package totara_hierarchy
 */

define(['jquery', 'core/config', 'core/str'], function ($, config, strings) {

    /* global totaraDialog
              totaraDialogs
              totaraMultiSelectDialog
              totaraDialog_handler_assignEvidence
              totaraDialog_handler_treeview
              totaraDialog_handler_compEvidence
              totaraDialog_handler_treeview_multiselect */

    var loadItemDialogs = function(id, competencyuseresourcelevelevidence) {
        // Add related competency dialog.
        strings.get_string('assignrelatedcompetencies', 'totara_hierarchy').done(function (assignrelatedcompetencies) {
            var url = config.wwwroot+'/totara/hierarchy/prefix/competency/related/';
            totaraMultiSelectDialog(
                'related',
                assignrelatedcompetencies,
                url + 'find.php?sesskey=' + config.sesskey + '&id=' + id,
                url + 'save.php?sesskey=' + config.sesskey + '&id=' + id + '&deleteexisting=1&add='
            );
        });

        if (typeof competencyuseresourcelevelevidence !== 'undefined' && competencyuseresourcelevelevidence) {

            // Create handler for the assign evidence dialog
            totaraDialog_handler_assignEvidence = function() {};

            totaraDialog_handler_assignEvidence.prototype = new totaraDialog_handler_treeview();

            totaraDialog_handler_assignEvidence.prototype._handle_update_hierarchy = function(list) {
                var handler = this;
                $('span', list).click(function() {
                    var par = $(this).parent();

                    // Get the id in format item_list_XX
                    var id = par.attr('id').substr(10);

                    // Check it's not a category
                    if (id.substr(0, 3) == 'cat') {
                        return;
                    }

                    handler._handle_course_click(id);
                });
            };

            totaraDialog_handler_assignEvidence.prototype._handle_course_click = function(courseid) {
                // Load course details
                var url = this.baseurl+'course.php?id=' + courseid + '&competency=' + id;

                // Indicate loading...
                this._dialog.showLoading();

                this._dialog._request(url, {object: this, method: '_display_evidence'});
            };

            /**
             * Display course evidence items
             *
             * @param string    HTML response
             */
            totaraDialog_handler_assignEvidence.prototype._display_evidence = function(response) {
                this._dialog.hideLoading();

                $('.selected', this._dialog.dialog).html(response);

                var handler = this;

                // Bind click event
                $('#available-evidence', this._dialog.dialog).find('.addbutton').click(function(e) {
                    e.preventDefault();
                    var type = $(this).parent().attr('type');
                    var instance = $(this).parent().attr('id');
                    var url = handler.baseurl + 'add.php?sesskey=' + config.sesskey + '&competency=' + id + '&type=' + type + '&instance=' + instance;
                    handler._dialog._request(url, {object: handler, method: '_update'});
                });

            };

        } else { // use course-level dialog

            // Create handler for the dialog
            totaraDialog_handler_compEvidence = function() {};

            totaraDialog_handler_compEvidence.prototype = new totaraDialog_handler_treeview_multiselect();

            /**
             * Add a row to a table on the calling page
             * Also hides the dialog and any no item notice
             *
             * @param string    HTML response
             * @return void
             */
            totaraDialog_handler_compEvidence.prototype._update = function(response) {

                // Hide dialog
                this._dialog.hide();

                // Remove no item warning (if exists)
                $('.noitems-' + this._title).remove();

                //Split response into table and div
                var new_table = $(response).find('#list-evidence');

                // Grab table
                var table = $('#list-evidence');

                // If table found
                if (table.length) {
                    table.replaceWith(new_table);
                }
                else {
                    // Add new table
                    $('div#evidence-list-container').append(new_table);
                }
            };

            var url = config.wwwroot + '/totara/hierarchy/prefix/competency/evidenceitem/';
            var saveurl = url + 'add.php?sesskey=' + config.sesskey + '&competency=' + id + '&type=coursecompletion&instance=0&deleteexisting=1&update=';
            var buttonsObj = {};
            var handler = new totaraDialog_handler_compEvidence();
            handler.baseurl = url;
            var requiredstrings = [];
            requiredstrings.push({key: 'save', component: 'totara_core'});
            requiredstrings.push({key: 'cancel', component: 'moodle'});
            requiredstrings.push({key: 'assigncoursecompletions', component: 'totara_hierarchy'});

            strings.get_strings(requiredstrings).done(function (translated) {
                var tstr = [];
                for (var i = 0; i < requiredstrings.length; i++) {
                    tstr[requiredstrings[i].key] = translated[i];
                }
                buttonsObj[tstr.save] = function() { handler._save(saveurl);};
                buttonsObj[tstr.cancel] = function() { handler._cancel();};

                totaraDialogs.evidence = new totaraDialog(
                    'evidence',
                    'show-evidence-dialog',
                    {
                         buttons: buttonsObj,
                         title: '<h2>' +  tstr.assigncoursecompletions + '</h2>'
                    },
                    url + 'edit.php?id=' + id,
                    handler
                );
            });
        }

        if (typeof competencyuseresourcelevelevidence !== 'undefined' && competencyuseresourcelevelevidence) {
            // Assign evidence item dialog (resource-level).
            var requiredstrings = [];
            requiredstrings.push({key: 'cancel', component: 'moodle'});
            requiredstrings.push({key: 'assignnewevidenceitem', component: 'totara_hierarchy'});

            strings.get_strings(requiredstrings).done(function (translated) {
                var tstr = [];
                for (var i = 0; i < requiredstrings.length; i++) {
                    tstr[requiredstrings[i].key] = translated[i];
                }
                // Assign evidence item dialog (resource-level).
                var url = config.wwwroot + '/totara/hierarchy/prefix/competency/evidenceitem/';
                var buttonsObj = {};
                var handler = new totaraDialog_handler_assignEvidence();
                handler.baseurl = url;

                buttonsObj[tstr.cancel] = function() { handler._cancel();};

                totaraDialogs.evidence = new totaraDialog(
                    'evidence',
                    'show-evidence-dialog',
                    {
                        buttons: buttonsObj,
                        title: '<h2>' + tstr.assignnewevidenceitem + '</h2>'
                    },
                    url + 'edit.php?id=' + id,
                    handler
                );
            });
        }
    };

    return {
        /**
         * module initialisation method called by php js_call_amd()
         *
         * @param integer the id of the item that is required
         */
        item: function(id, competencyuseresourcelevelevidence) {
            var iteminited = $.Deferred();
            iteminited.done(function () {
                loadItemDialogs(id, competencyuseresourcelevelevidence);
            });


            if (window.dialogsInited) {
                iteminited.resolve();
            } else {
                // Queue it up.
                if (!$.isArray(window.dialoginits)) {
                    window.dialoginits = [];
                }
                window.dialoginits.push(iteminited.resolve);
            }

        },

        template: function (id) {
            var templateinited = $.Deferred();

            templateinited.done(function () {
                var url = config.wwwroot+'/totara/hierarchy/prefix/competency/template/';

                strings.get_string('assignnewcompetency', 'competency').done(function (assignnewcompetency) {
                    totaraMultiSelectDialog(
                        'assignment',
                        '<h2>' + assignnewcompetency + '</h2>',
                        url + 'find_competency.php?templateid=' + id,
                        url + 'save_competency.php?templateid=' + id + '&deleteexisting=1&add='
                    );
                });
            });

            if (window.dialogsInited) {
                templateinited.resolve();
            } else {
                // Queue it up.
                if (!$.isArray(window.dialoginits)) {
                    window.dialoginits = [];
                }
                window.dialoginits.push(templateinited.resolve);
            }
        }
    };
});
