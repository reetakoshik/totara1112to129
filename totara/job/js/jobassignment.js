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
 * @package totara
 * @subpackage totara_core
 */
M.totara_jobassignment = M.totara_jobassignment || {

    Y: null,
    // optional php params and defaults defined here, args passed to init method
    // below will override these values
    config: {
        disablecreateempty:false // whether to show "create empty job assignment" option
    },

    /**
     * module initialisation method called by php js_init_call()
     *
     * @param object    YUI instance
     * @param string    args supplied in JSON format
     */
    init: function(Y, args){
        // save a reference to the Y instance (all of its dependencies included)
        this.Y = Y;

        // if defined, parse args into this module's config object
        if (args) {
            var jargs = Y.JSON.parse(args);
            for (var a in jargs) {
                if (Y.Object.owns(jargs, a)) {
                    this.config[a] = jargs[a];
                }
            }
        }

        // check jQuery dependency is available
        if (typeof $ === 'undefined') {
            throw new Error('M.totara_jobassignment.init()-> jQuery dependency required for this module to function.');
        }

        ///
        /// Position dialog
        ///
        (function() {
            var url = M.cfg.wwwroot+'/totara/hierarchy/prefix/position/assign/';
            totaraSingleSelectDialog(
                'position',
                M.util.get_string('chooseposition', 'totara_job') + M.totara_jobassignment.config.dialog_display_position,
                url+'position.php?',
                'positionid',
                'positiontitle',
                undefined,
                M.totara_jobassignment.config.can_edit           //Make selection deletable
            );
        })();

        ///
        /// Organisation dialog
        ///
        (function() {
            var url = M.cfg.wwwroot+'/totara/hierarchy/prefix/organisation/assign/';
            totaraSingleSelectDialog(
                'organisation',
                M.util.get_string('chooseorganisation', 'totara_job') + M.totara_jobassignment.config.dialog_display_organisation,
                url+'find.php?',
                'organisationid',
                'organisationtitle',
                undefined,
                M.totara_jobassignment.config.can_edit            // Make selection deletable
            );
        })();

        ///
        /// Manager dialog
        ///
        (function() {
            var url = M.cfg.wwwroot+'/totara/job/dialog/assign_manager_html.php';

            totaraAssignManagerDialog(
                'manager',
                M.util.get_string('choosemanager', 'totara_job') + M.totara_jobassignment.config.dialog_display_manager,
                url + '?userid='+M.totara_jobassignment.config.userid + '&disablecreateempty='+M.totara_jobassignment.config.disablecreateempty,
                'managerid',
                'managerjaid',
                'managertitle',
                M.totara_jobassignment.config.can_edit,
                'manageridjaid'
            );
        })();

        ///
        /// Temporary manager dialog
        ///
        (function() {
            var url = M.cfg.wwwroot+'/totara/job/dialog/assign_tempmanager_html.php';
            var usualmanagerid = $('input[name="managerid"]').val();

            totaraAssignManagerDialog(
                'tempmanager',
                M.util.get_string('choosetempmanager', 'totara_job') + M.totara_jobassignment.config.dialog_display_tempmanager,
                url+'?userid='+M.totara_jobassignment.config.userid+'&usualmgrid=' + usualmanagerid,
                'tempmanagerid',
                'tempmanagerjaid',
                'tempmanagertitle',
                M.totara_jobassignment.config.can_edit_tempmanager,
                'tempmanageridjaid'
            );
        })();

        ///
        /// Appraiser dialog
        ///
        (function() {
            var url = M.cfg.wwwroot+'/totara/job/dialog/assign_appraiser_html.php';

            totaraSingleSelectDialog(
                'appraiser',
                M.util.get_string('chooseappraiser', 'totara_job') + M.totara_jobassignment.config.dialog_display_appraiser,
                url+'?userid='+M.totara_jobassignment.config.userid,
                'appraiserid',
                'appraisertitle',
                undefined,
                M.totara_jobassignment.config.can_edit            // Make selection deletable
            );
        })();

    }
};

totaraAssignManagerDialog = function(name, titleString, findUrl, useridKey, jaidKey, textElementId, deletable, selectedKey) {
    var assignManagerhandlerExtra = function() {
        var self = this;
        var selected_val = $('#treeview_selected_val_'+self._title).val();
        var item = $('.treeview span.unclickable#item_'+selected_val, self._container);
        var customdata = item.data();
        $('input[name="' + useridKey + '"]').val(customdata.userid);
        $('input[name="' + jaidKey + '"]').val(customdata.jaid);

        var text_element = $('#'+self.text_element_id);
        text_element.html(customdata.displaystring);
        if (self.deletable) {
            self.setup_delete();
            // setup_delete() covers clearing the manageridjaid on delete, but we also need to ensure
            // the more useful managerid and managerjaid will be cleared on deletion.
            var deletebutton = text_element.find('.dialog-singleselect-deletable').first();
            deletebutton.click(function() {
                $('input[name="' + useridKey + '"]').val('');
                $('input[name="' + jaidKey + '"]').val('');
                if ($("#id_tempmanagerexpirydate_enabled").is(":checked")) {
                    $("#id_tempmanagerexpirydate_enabled").trigger("click");
                }
            });
        }
    };

    var handler = new totaraDialog_handler_treeview_singleselect(selectedKey, textElementId);
    var buttonObj = {};
    if (deletable) {
        handler.setup_delete();
        // setup_delete() covers clearing the manageridjaid on delete, but we also need to ensure
        // the more useful managerid and managerjaid will be cleared on deletion.
        var text_element = $('#'+handler.text_element_id);
        var deletebutton = text_element.find('.dialog-singleselect-deletable').first();
        if (deletebutton) {
            deletebutton.click(function () {
                $('input[name="' + useridKey + '"]').val('');
                $('input[name="' + jaidKey + '"]').val('');
                if ($("#id_tempmanagerexpirydate_enabled").is(":checked")) {
                    $("#id_tempmanagerexpirydate_enabled").trigger("click");
                }
            });
        }
    }
    handler.external_function = assignManagerhandlerExtra;
    handler.get_selected_title = function(clone) {
        var customdata = clone.data();
        return customdata.displaystring;
    };

    buttonObj[M.util.get_string('ok', 'moodle')] = function() { handler._save() };
    buttonObj[M.util.get_string('cancel', 'moodle')] = function() { handler._cancel() };

    totaraDialogs[name] = new totaraDialog(
        name,
        'show-'+name+'-dialog',
        {
            buttons: buttonObj,
            title: '<h2>'+titleString+'</h2>'
        },
        findUrl,
        handler
    );
};
