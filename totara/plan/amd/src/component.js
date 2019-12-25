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
 * @author Brian Barnes <brian.barnes@totaralms.com>
 * @package totara
 * @subpackage totara_core
 */
define(['jquery', 'core/config'], function($, mdlconfig) {

    /* global jQuery totaraDialog_handler_treeview_multiselect */

    var component = {
        // Optional php params and defaults defined here, args passed to init method
        // below will override these values.
        config: {},
        // Public handler reference for the dialog.
        totaraDialog_handler_preRequisite: null,

        /**
         * module initialisation method called by php js_init_call()
         *
         * @param string    args supplied in JSON format
         */
        init: function(args) {
            // If defined, parse args into this module's config object.
            if (args) {
                if (typeof args == 'string') {
                    args = $.parseJSON(args);
                }
                component.config = args;
            }

            component.add_handlers();

            // Create the dialog.
            component.totaraDialog_handler_preRequisite = function() {
                // Base url.
                var baseurl = '';
            };

            component.totaraDialog_handler_preRequisite.prototype = new totaraDialog_handler_treeview_multiselect();

            /**
             * Add a row to a table on the calling page
             * Also hides the dialog and any no item notice
             *
             * @param string    HTML response
             * @return void
             */
            component.totaraDialog_handler_preRequisite.prototype._update = function(response) {
                // Hide dialog.
                this._dialog.hide();
                // Update table
                component.totara_totara_plan_update(response);
            };
        },

        /**
         * Add change event handlers to input and select elements.
         */
        add_handlers : function() {
            // Add hooks to learning plan component form elements.
            // Update when form elements change.
            jQuery('table.dp-plan-component-items input, table.dp-plan-component-items select').change(function() {
                var data = {
                    updatesettings: "1",
                    ajax: "1",
                    sesskey: mdlconfig.sesskey,
                    page: component.config.page
                };

                // Get current value.
                data[$(this).attr('name')] = $(this).val();

                $.post(
                    mdlconfig.wwwroot + '/totara/plan/component.php?id=' + component.config.plan_id +
                        '&c=' + component.config.component_name + '&page=' + component.config.page,
                    data,
                    component.totara_totara_plan_update
                );
            });
        },

        /**
         * Update the table on the calling page, and remove/add no items notices
         *
         * @param   string  HTML response
         * @return  void
         */
        totara_totara_plan_update: function(response) {

            // Remove no item warning (if exists)
            $('.noitems-assign' + component.config.component_name).remove();

            // Split response into table and div.
            var new_table = $(response).find('table.dp-plan-component-items');
            var new_planbox = $(response).filter('.plan_box');
            var new_paging = $(response).filter('.paging')[0];

            // Grab table.
            var table = $('form#dp-component-update table.dp-plan-component-items');

            // Check for no items msg.
            var noitems = $(response).filter('span.noitems-assign' + component.config.component_name);

            // Define update setting button div.
            var updatesettings = $('div#dp-component-update-submit');

            if (noitems.length) {
                // If no items, just display message.
                $('form#dp-component-update div#dp-component-update-table').append(noitems);
                // Replace table with nothing.
                table.empty();
                // Hide update setting button when there are no items.
                updatesettings.hide();
            } else if (table.length) {
                // If table found.
                table.replaceWith(new_table);
                updatesettings.show();
            } else {
                // Add new table.
                $('form#dp-component-update div#dp-component-update-table').append(new_table);
                // Show update setting button there are now rows.
                updatesettings.show();
            }

            // Replace plan message box.
            $('div.plan_box').replaceWith(new_planbox);
            $('.paging').replaceWith(new_paging);

            // Reinit handlers.
            component.add_handlers();

            // Add duedate datepicker.
            require(['core/str'], function (mdlstr) {
                mdlstr.get_string('datepickerlongyeardisplayformat', 'totara_core').done(function (format) {
                    // This function does not use Y so no point in sending it.
                    M.totara_core.build_datepicker(null, "[id^=duedate_" + component.config.component_name + "]", format);
                });
            });
        }
    };

    return component;
});
