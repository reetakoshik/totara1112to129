/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package totara
 * @subpackage cohorts
 */

/**
 * This file contains the Javascript for the dialog that lets you add cohorts
 * to a course
 */

M.totara_restrictcohort = M.totara_restrictcohort || {

    Y: null,

    config: {},

    /**
     * module initialisation method called by php js_init_call()
     *
     * @param object    YUI instance
     * @param string    args supplied in JSON format
     */
    init: function(Y, args) {
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
            throw new Error('M.totara_restrictcohort.init()-> jQuery dependency required for this module.');
        }

        this.config['sesskey'] = M.cfg.sesskey;

        // Category instance type: coming from COHORT_ASSN_ITEMTYPE_CATEGORY.
        if (this.config.instancetype == 40) {
            // Because the user can set the category in the form
            // we need to make sure the instanceid reflects the value
            // in the form, not the original value passed in when the
            // page loaded.
            var select = $('select[name="category"] option:selected'),
                categoryid = (select) ? select.val() : false ;
            if (categoryid !== undefined && categoryid != this.config.instanceid) {
                this.config.instanceid = categoryid;
            }
        }

        $('select[name="audiencevisible"]').change(M.totara_restrictcohort.checkcohortaddvisibility);

        this.init_dialogs();
    },

    init_dialogs: function() {
        var url = M.cfg.wwwroot + '/totara/cohort/dialog/';

        var ehandler = new totaraDialog_handler_restrictcohorts();
        ehandler.baseurl = url;

        var dbuttons = {};
        dbuttons[M.util.get_string('ok', 'moodle')] = function() { ehandler._update() }
        dbuttons[M.util.get_string('cancel', 'moodle')] = function() { ehandler._cancel() }

        totaraDialogs['id_cohortsaddvisible'] = new totaraDialog(
            'course-cohorts-visible-dialog',
            'id_cohortsaddvisible',
            {
                buttons: dbuttons,
                title: '<h2>' + M.util.get_string(this.config.type + 'cohortsvisible', 'totara_cohort') + '</h2>'
            },
            url+'cohort.php?selected=' + this.config.visibleselected
                    + '&instancetype=' + this.config.instancetype
                    + '&instanceid=' + this.config.instanceid
                    + '&sesskey=' + this.config.sesskey,
            ehandler
        );
    },
}

// Create handler for the dialog.
totaraDialog_handler_restrictcohorts = function() {
    this.baseurl = '';
    this.cohort_items = $('input:hidden[name="cohortsvisible"]').val();
    this.cohort_items = (this.cohort_items && this.cohort_items.length > 0) ? this.cohort_items.split(',') : [];
    this.cohort_table = $('#course-cohorts-table-visible');

    this.add_cohort_delete_event_handlers();

    this.check_table_hidden_status();
}

totaraDialog_handler_restrictcohorts.prototype = new totaraDialog_handler_treeview_multiselect();

/**
 * Add a row to a table on the calling page
 * Also hides the dialog and any no item notice
 *
 * @param string    HTML response
 * @return void
 */
totaraDialog_handler_restrictcohorts.prototype._update = function(response) {

    var self = this;
    var elements = $('.selected > div > span', this._container);
    var selected_str = this._get_ids(elements).join(',');
    var url = this._dialog.default_url.split("selected=");
    var params = url[1].slice(url[1].indexOf('&'));
    this._dialog.default_url = url[0] + 'selected=' + selected_str + params;
    var newids = new Array();

    // Loop through the selected elements.
    elements.each(function() {

        // Get id.
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

        var ajax_url = M.cfg.wwwroot + '/totara/cohort/dialog/cohort_item.php?module=course&itemid=' + newids.join(',') + params;
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
}

/*
 * Checks if the item id exists in this category.
 */
totaraDialog_handler_restrictcohorts.prototype.cohort_item_exists = function(itemid) {
    for (x in this.cohort_items) {
        if (this.cohort_items[x] == itemid) {
            return true;
        }
    }
    return false;
}

totaraDialog_handler_restrictcohorts.prototype.check_table_hidden_status = function() {
    if (this.cohort_items.length == 0) {
        $(this.cohort_table).hide();
    } else {
        $(this.cohort_table).show();
    }
}

totaraDialog_handler_restrictcohorts.prototype.add_cohort_delete_event_handlers = function() {
    // Remove previous click event handlers.
    $('.coursecohortdeletelink', this.cohort_table).unbind('click');

    // Add fresh event handlers.
    var self = this;
    this.cohort_table.on('click', '.coursecohortdeletelink', function(event) {
        event.preventDefault();
        self.remove_cohort_item(this);
    });
}

/*
 * Adds an item
 */
totaraDialog_handler_restrictcohorts.prototype.add_cohort_item = function(itemid) {
    this.cohort_items.push(itemid);

    $('input:hidden[name="cohortsvisible"]').val(this.cohort_items.join(','));

    this.check_table_hidden_status();
}

/*
 * Creates an element and then adds it
 */
totaraDialog_handler_restrictcohorts.prototype.create_item = function(html) {
    var element = $(html);

    // Add the item element to the table.
    this.cohort_table.append(element);
}

totaraDialog_handler_restrictcohorts.prototype.remove_cohort_item = function(item) {
    var itemid = $(item).closest('div').attr('id').match(/[\d]+$/);
    var row = $(item).closest('tr');

    // Remove the item from the array of items.
    this.cohort_items = $.grep(this.cohort_items, function (element, x) {
        return (element == itemid);
    }, true);

    // Remove item from interface.
    row.remove()

    this.check_table_hidden_status();

    $('input:hidden[name="cohortsvisible"]').val(this.cohort_items.join(','));

    var url = this._dialog.default_url.split("selected=");
    var params = url[1].slice(url[1].indexOf('&'));
    this._dialog.default_url = url[0] + 'selected=' + this.cohort_items.join(',') + params;
}
