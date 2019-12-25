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
 * @author Ryan Lafferty <ryanl@learningpool.com>
 * @author James Robinson <jamesr@learningpool.com>
 * @package totara
 * @subpackage goal
 */

/**
 * This file contains the Javascript for the dialog that lets you add audiences
 * to a personal goal type
 */

M.totara_goalcohort = M.totara_goalcohort || {

    Y: null,

    // Optional php params and defaults defined here, arguments
    // passed to init method below will override these values.
    config: {},

    /**
     * Module initialisation method called by php js_init_call().
     *
     * @param Y object YUI instance.
     * @param selected array List of selected audiences.
     */
    init: function(Y, selected) {
        // Save a reference to the Y instance (all of its dependencies included).
        this.Y = Y;
        this.config['selected'] = selected;

        // Check jQuery dependency is available.
        if (typeof $ === 'undefined') {
            throw new Error('M.totara_cohortlearning.init()-> jQuery dependency required for this module.');
        }

        this.config['sesskey'] = M.cfg.sesskey;

        // Hide the cohort/audience dialogue if goal type availability is 'all users' when the page loads.
        if ($('#id_audience').val() == 0) {
            $('#goal-cohort-assignments').hide();
            $('#fitem_id_cohortsaddenrolled').hide();
        }

        // Show / hide the cohort / audience dialogue depending on goal type availability when it's changed.
        $(document).on('change', '#id_audience', function() {
            if ($(this).val() == 0) {
                $('#goal-cohort-assignments').hide();
                $('#fitem_id_cohortsaddenrolled').hide();
            } else {
                $('#goal-cohort-assignments').show();
                $('#fitem_id_cohortsaddenrolled').show();
            }
        });

        this.init_dialogs();
    },

    /**
     * Initialise the audiences dialogue.
     */
    init_dialogs: function() {

        // Init the dialogs.
        var url = M.cfg.wwwroot + '/totara/cohort/dialog/';
        var ehandler = new totaraDialog_handler_goalcohorts();
        ehandler.baseurl = url;

        var dbuttons = {};
        dbuttons[M.util.get_string('ok', 'moodle')] = function() { ehandler._update() };
        dbuttons[M.util.get_string('cancel', 'moodle')] = function() { ehandler._cancel() };

        totaraDialogs['id_cohortsaddenrolled'] = new totaraDialog(
            'goal-cohorts-enrolled-dialog',
            'id_cohortsaddenrolled',
            {
                buttons: dbuttons,
                title: '<h2>' + M.util.get_string('choosecohort', 'totara_hierarchy') + '</h2>'
            },
            url + 'cohort_goal.php?selected=' + this.config.selected
                    + '&instancetype=' + this.config.instancetype
                    + '&instanceid=' + this.config.instanceid
                    + '&sesskey=' + this.config.sesskey,
            ehandler
        );
    }
};


// Create handler for the dialog.
totaraDialog_handler_goalcohorts = function() {
    this.baseurl = '';
    this.cohort_items = $('input:hidden[name="cohortsenrolled"]').val();
    this.cohort_items = (this.cohort_items && this.cohort_items.length > 0) ? this.cohort_items.split(',') : [];
    this.cohort_table = $('#goal-cohorts-table-enrolled');

    this.add_cohort_delete_event_handlers();
    this.check_table_hidden_status();

    // Remove the empty tr that the html_writer inserts for an empty table.
    $('#goal-cohorts-table-enrolled tbody tr').each(function () {
        $(this).find('td').each(function () {
            if ($(this).text().trim() == "") {
                $(this).closest("tr").remove();
            };
        });
    });

};

totaraDialog_handler_goalcohorts.prototype = new totaraDialog_handler_treeview_multiselect();

/**
 * Add a row to a table on the calling page
 * Also hides the dialog and any no item notice
 *
 * @param response string HTML
 */
totaraDialog_handler_goalcohorts.prototype._update = function(response) {

    var self = this;
    var elements = $('.selected > div > span', this._container);
    var selected_str = this._get_ids(elements).join(',');
    var url = this._dialog.default_url.split("selected=");
    var params = url[1].slice(url[1].indexOf('&'));
    this._dialog.default_url = url[0] + 'selected=' + selected_str + params;

    var newids = new Array();

    // Loop through the selected elements
    elements.each(function() {

        // Get id
        var itemid = $(this).attr('id').split('_');
        itemid = itemid[itemid.length-1];  // The last item is the actual id
        itemid = parseInt(itemid);

        if (!self.cohort_item_exists(itemid)) {
            newids.push(itemid);
            self.add_cohort_item(itemid);
        }
    });

    if (newids.length > 0) {
        this._dialog.showLoading();
        var ajax_url = M.cfg.wwwroot + '/totara/cohort/dialog/cohort_item.php?itemid=' + newids.join(',') + params;
        $.getJSON(ajax_url, function(data) {
            if (data.error) {
                self._dialog.hide();
                alert(data.error);
                return;
            }
            $.each(data['rows'], function(index, html) {
                self.create_item(html);
            });

            self._dialog.hide();
        })
    } else {
        this._dialog.hide();
    }
};

/**
 * Checks if the audiance / chort item already exists.
 *
 * @param itemid integer Id of and audiance / cohort to check.
 * @return boolean If the item already exists.
 */
totaraDialog_handler_goalcohorts.prototype.cohort_item_exists = function(itemid) {
    for (var x in this.cohort_items) {
        if (this.cohort_items[x] == itemid) {
            return true;
        }
    }
    return false;
}

/**
 * Manage hide / showing the table containing the list fo audiences / cohorts.
 */
totaraDialog_handler_goalcohorts.prototype.check_table_hidden_status = function() {
    if (this.cohort_items.length == 0) {
        $(this.cohort_table).hide();
    } else {
        $(this.cohort_table).show();
    }
};

/**
 * Add an event to allow the audience / cohort to be deleted.
 */
totaraDialog_handler_goalcohorts.prototype.add_cohort_delete_event_handlers = function() {
    // Remove previous click event handlers.
    $('.goalcohortdeletelink', this.cohort_table).unbind('click');

    // Add fresh event handlers.
    var self = this;
    this.cohort_table.on('click', '.goalcohortdeletelink', function(event) {
        event.preventDefault();
        self.remove_cohort_item(this);
    });
};

/**
 * Adds an item to the list and table of audiences / cohorts.
 *
 * @param itemid integer The audience / cohort to add.
 */
totaraDialog_handler_goalcohorts.prototype.add_cohort_item = function(itemid) {
    this.cohort_items.push(itemid);

    $('input:hidden[name="cohortsenrolled"]').val(this.cohort_items.join(','));

    this.check_table_hidden_status();
};

/**
 * Add a new row to the table of audiances / cohorts.
 *
 * @param html string HTML for the row to add to the table.
 */
totaraDialog_handler_goalcohorts.prototype.create_item = function(html) {
    var element = $(html);

    // Add the item element to the table
    this.cohort_table.append(element);
};

/**
 * Remove an audience / cohort from the list.
 *
 * @param item object The audiance / cohort to remove.
 */
totaraDialog_handler_goalcohorts.prototype.remove_cohort_item = function(item) {
    // Get the id part from e.g 'cohort-item-1'.
    var itemid = $(item).closest('div').attr('id').match(/[\d]+$/);
    var row = $(item).closest('tr');

    // Remove the item from the array of items
    this.cohort_items = $.grep(this.cohort_items, function (element, x) {
        return (element == itemid);
    }, true);

    // Remove item from interface
    row.remove()

    this.check_table_hidden_status();

    $('input:hidden[name="cohortsenrolled"]').val(this.cohort_items.join(','));

    var url = this._dialog.default_url.split("selected=");
    var params = url[1].slice(url[1].indexOf('&'));
    this._dialog.default_url = url[0] + 'selected=' + this.cohort_items.join(',') + params;
};
