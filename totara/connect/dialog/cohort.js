/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_connect
 */

/**
 * This file contains the Javascript for the dialog that lets you
 * add cohorts to a Totara Connect clients.
 */

M.totara_connect_cohort = M.totara_connect_cohort || {
    Y: null,
    selected: null,
    instanceid: null,

    /**
     * Module initialisation method called by php js_init_call()
     *
     * @param Y          object YUI instance
     * @param selected   string selected
     * @param instanceid int    instanceid
     */
    init: function(Y, selected, instanceid) {
        // Check jQuery dependency is available.
        if (typeof $ === 'undefined') {
            throw new Error('M.totara_connect_cohort.init()-> jQuery dependency required for this module.');
        }

        this.Y = Y;
        this.selected = selected;
        this.instanceid = instanceid;

        this.init_dialogs();
    },


    init_dialogs: function() {

        // Init the dialogs.
        var url = M.cfg.wwwroot + '/totara/connect/dialog/';

        // Enrolled cohorts.
        var ehandler = new totaraDialog_handler_connectcohorts();
        ehandler.baseurl = url;

        var dbuttons = {};
        dbuttons[M.util.get_string('ok', 'moodle')] = function() { ehandler._update() };
        dbuttons[M.util.get_string('cancel', 'moodle')] = function() { ehandler._cancel() };

        totaraDialogs['id_cohortsadd'] = new totaraDialog(
            'totara-connect-cohorts-dialog',
            'id_cohortsadd',
            {
                buttons: dbuttons,
                title: '<h2>' + M.util.get_string('cohorts', 'totara_connect') + '</h2>'
            },
            url + 'cohort.php?selected=' + this.selected
                + '&instanceid=' + this.instanceid
                + '&sesskey=' + M.cfg.sesskey,
            ehandler
        );
    }
};


/**
 * Create handler for the dialog
 */
totaraDialog_handler_connectcohorts = function() {
    // Base url.
    this.baseurl = '';
    this.cohort_items = $('input:hidden[name="cohorts"]').val();
    this.cohort_items = (this.cohort_items && this.cohort_items.length > 0) ? this.cohort_items.split(',') : [];
    this.cohort_table = $('#totara-connect-cohorts-table');

    this.add_cohort_delete_event_handlers();

    this.check_table_hidden_status();
};

totaraDialog_handler_connectcohorts.prototype = new totaraDialog_handler_treeview_multiselect();

/**
 * Add a row to a table on the calling page
 * Also hides the dialog and any no item notice
 *
 * @return void
 */
totaraDialog_handler_connectcohorts.prototype._update = function() {

    var self = this;
    var elements = $('.selected > div > span', this._container);
    var selected_str = this._get_ids(elements).join(',');
    var url = this._dialog.default_url.split("selected=");
    var params = url[1].slice(url[1].indexOf('&'));
    this._dialog.default_url = url[0] + 'selected=' + selected_str + params;

    var newids = new Array();

    // Loop through the selected elements.
    elements.each(function() {

        // Get id
        var itemid = $(this).attr('id').split('_');
        itemid = itemid[itemid.length-1];  // The last item is the actual id.
        itemid = parseInt(itemid);

        if (!self.cohort_item_exists(itemid)) {
            newids.push(itemid);
            self.add_cohort_item(itemid);
        }
    });

    if (newids.length > 0) {
        this._dialog.showLoading();

        var ajax_url = M.cfg.wwwroot + '/totara/connect/dialog/cohort_item.php?itemid=' + newids.join(',') + params;
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
 * Checks if the item id exists in this category
 */
totaraDialog_handler_connectcohorts.prototype.cohort_item_exists = function(itemid) {
    for (x in this.cohort_items) {
        if (this.cohort_items[x] == itemid) {
            return true;
        }
    }
    return false;
};

totaraDialog_handler_connectcohorts.prototype.check_table_hidden_status = function() {

    if (this.cohort_items.length == 0) {
        $(this.cohort_table).hide();
    } else {
        $(this.cohort_table).show();
    }
};

totaraDialog_handler_connectcohorts.prototype.add_cohort_delete_event_handlers = function() {
    // Remove previous click event handlers.
    $('.connectcohortdeletelink', this.cohort_table).unbind('click');

    // Add fresh event handlers.
    var self = this;
    this.cohort_table.on('click', '.connectcohortdeletelink', function(event) {
        event.preventDefault();
        self.remove_cohort_item(this);
    });
};

/**
 * Adds an item
 */
totaraDialog_handler_connectcohorts.prototype.add_cohort_item = function(itemid) {
    this.cohort_items.push(itemid);

    $('input:hidden[name="cohorts"]').val(this.cohort_items.join(','));

    this.check_table_hidden_status();
};

/**
 * Creates an element and then adds it
 */
totaraDialog_handler_connectcohorts.prototype.create_item = function(html) {
    var element = $(html);

    // Add the item element to the table.
    this.cohort_table.append(element);
};

totaraDialog_handler_connectcohorts.prototype.remove_cohort_item = function(item) {
    var itemid = $(item).closest('div').attr('id').match(/[\d]+$/);  // Get the id part from e.g 'cohort-item-1'.
    var row = $(item).closest('tr');

    // Remove the item from the array of items.
    this.cohort_items = $.grep(this.cohort_items, function (element, x) {
        return (element == itemid);
    }, true);

    // Remove item from interface.
    row.remove();

    this.check_table_hidden_status();

    $('input:hidden[name="cohorts"]').val(this.cohort_items.join(','));

    var url = this._dialog.default_url.split("selected=");
    var params = url[1].slice(url[1].indexOf('&'));
    this._dialog.default_url = url[0] + 'selected=' + this.cohort_items.join(',') + params;
};
