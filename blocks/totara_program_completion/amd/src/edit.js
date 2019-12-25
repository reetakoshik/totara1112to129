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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @author Brian Barnes <brian.barnes@totaralms.com>
 * @package block
 * @subpackage totara_program_completion
 */

define(['jquery', 'core/config', 'core/str'], function ($, mdlconfig, mdlstrings) {

    /* global totaraDialog totaraDialogs totaraDialog_handler_treeview_multiselect */

    var edit = {

        // Optional php params and defaults defined here, args passed to init method
        // below will override these values.
        blockid: null,
        programsselected: null,

        /**
         * Module initialisation method called by php js_init_call().
         *
         * @param string    args supplied in JSON format
         */
        init: function(blockid, programsselected) {
            var dialogsReady = $.Deferred();
            var self = this;

            // If defined, parse args into this module's config object.
            edit.blockid = blockid;
            edit.programsselected = programsselected;

            if (window.dialogsInited) {
                dialogsReady.resolve();
            } else {

                if (!(window.dialoginits instanceof Array)) {
                    window.dialoginits = [];
                }

                window.dialoginits.push(function() {
                    dialogsReady.resolve();
                });
            }

            var requiredstrings = [];
            requiredstrings.push({key: 'save', component: 'totara_core'});
            requiredstrings.push({key: 'cancel', component: 'moodle'});
            requiredstrings.push({key: 'addprograms', component: 'block_totara_program_completion'});

            $.when(mdlstrings.get_strings(requiredstrings), dialogsReady).done(function(strings) {
                var tstr = [];
                for (var i = 0; i < requiredstrings.length; i++) {
                    tstr[requiredstrings[i].key] = strings[i];
                }

                var url = mdlconfig.wwwroot + '/blocks/totara_program_completion/';
                var TotaraDialog_handler_blockprograms = self._createDialog();

                // Init programs dialog.
                var phandler = new TotaraDialog_handler_blockprograms();
                phandler.baseurl = url;
                var pbuttons = {};
                pbuttons[tstr.save] = function() { phandler._update(); };
                pbuttons[tstr.cancel] = function() { phandler._cancel(); };

                totaraDialogs.addblockprograms = new totaraDialog(
                    'addblockprograms',
                    'add-block-programs-dialog',
                    {
                        buttons: pbuttons,
                        title: '<h2>' + tstr.addprograms + '</h2>'
                    },
                    url+'findprograms.php?selected=' + edit.programsselected
                            + '&blockid=' + edit.blockid
                            + '&sesskey=' + mdlconfig.sesskey,
                    phandler
                );

            });

        },

        _createDialog: function() {
            // Create handler for the dialog.
            var TotaraDialog_handler_blockprograms = function() {
                // Base url
                this.baseurl = '';
                this.program_items = $('input:hidden[name="config_programids"]').val();
                this.program_items = (this.program_items && this.program_items.length > 0) ? this.program_items.split(',') : [];
                this.program_table = $('#block-programs-table');

                this.add_program_delete_event_handlers();

                this.check_table_hidden_status();

            };

            TotaraDialog_handler_blockprograms.prototype = new totaraDialog_handler_treeview_multiselect();

            /**
             * Add a row to a table on the calling page.
             * Also hides the dialog and any no item notice.
             *
             * @param string    HTML response
             * @return void
             */
            TotaraDialog_handler_blockprograms.prototype._update = function(response) {

                var self = this;
                var elements = $('.selected > div > span', this._container);
                var selected = this._get_ids(elements);
                var selected_str = selected.join(',');
                var url = this._dialog.default_url.split("selected=");
                var params = url[1].slice(url[1].indexOf('&'));
                this._dialog.default_url = url[0] + 'selected=' + selected_str + params;

                var newids = [];

                // Loop through the selected elements.
                $(selected).each(function(_, itemid) {
                    if (!self.program_item_exists(itemid)) {
                        newids.push(itemid);
                        self.add_program_item(itemid);
                    }
                });

                if (newids.length > 0) {
                    this._dialog.showLoading();

                    var ajax_url = mdlconfig.wwwroot + '/blocks/totara_program_completion/program_item.php?itemid=' + newids.join(',') + params;
                    $.getJSON(ajax_url, function(data) {
                        if (data.error) {
                            self._dialog.hide();
                            alert(data.error);
                            return;
                        }
                        $.each(data['items'], function(index, html) {
                            self.create_item(html);
                        });

                        self._dialog.hide();
                    });
                } else {
                    this._dialog.hide();
                }
            };

            /**
             * Checks if the item id exists.
             */
            TotaraDialog_handler_blockprograms.prototype.program_item_exists = function(itemid) {
                for (var x in this.program_items) {
                    if (this.program_items[x] == itemid) {
                        return true;
                    }
                }
                return false;
            };

            TotaraDialog_handler_blockprograms.prototype.check_table_hidden_status = function() {
                if (this.program_items.length == 0) {
                    $(this.program_table).hide();
                } else {
                    $(this.program_table).show();
                }
            };

            TotaraDialog_handler_blockprograms.prototype.add_program_delete_event_handlers = function() {
                // Remove previous click event handlers.
                $('.blockprogramdeletelink', this.program_table).unbind('click');

                // Add fresh event handlers.
                var self = this;
                this.program_table.on('click', '.blockprogramdeletelink', function(event) {
                    event.preventDefault();
                    self.remove_program_item(this);
                });
            };

            /**
             * Adds an item.
             */
            TotaraDialog_handler_blockprograms.prototype.add_program_item = function(itemid) {
                this.program_items.push(itemid);

                $('input:hidden[name="config_programids"]').val(this.program_items.join(','));

                this.check_table_hidden_status();
            };

            /**
            * Creates an element and then adds it.
            */
            TotaraDialog_handler_blockprograms.prototype.create_item = function(html) {
                var element = $(html);

                // Add the item element to the table.
                this.program_table.append(element);
            };

            TotaraDialog_handler_blockprograms.prototype.remove_program_item = function(item) {
                var row = $(item).closest('li');
                var itemid = row.data('progid');

                // Remove the item from the array of items.
                this.program_items = $.grep(this.program_items, function (element, x) {
                    return (element == itemid);
                }, true);

                // Remove item from interface.
                row.remove();

                this.check_table_hidden_status();

                $('input:hidden[name="config_programids"]').val(this.program_items.join(','));

                var url = this._dialog.default_url.split("selected=");
                var params = url[1].slice(url[1].indexOf('&'));
                this._dialog.default_url = url[0] + 'selected=' + this.program_items.join(',') + params;
            };

            return TotaraDialog_handler_blockprograms;
        }
    };

    return edit;
});
