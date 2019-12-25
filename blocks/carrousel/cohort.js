/*
    Cohorts dialog for carrousel
    Based on totara dashboard dialog but with different permissions
*/

M.carrousel_cohort = M.carrousel_cohort || {

    Y: null,

    config: {},

    init: function(Y, args) {
        this.Y = Y;

        if (args) {
            var jargs = Y.JSON.parse(args);
            for (var a in jargs) {
                if (Y.Object.owns(jargs, a)) {
                    this.config[a] = jargs[a];
                }
            }
        }

        if (typeof $ === 'undefined') {
            throw new Error('M.carrousel_cohort.init()-> jQuery dependency required for this module.');
        }

        this.config['sesskey'] = $('input:hidden[name="sesskey"]').val();

        this.init_dialogs();
    },


    init_dialogs: function() {
        var url = M.cfg.wwwroot + '/blocks/carrousel/';

        var ehandler = new totaraDialog_handler_dashboardcohorts();
        ehandler.baseurl = url;

        var dbuttons = {};
        dbuttons[M.util.get_string('ok', 'moodle')] = function() { ehandler._update() }
        dbuttons[M.util.get_string('cancel', 'moodle')] = function() { ehandler._cancel() }

        totaraDialogs['id_cohortsbtn'] = new totaraDialog(
            'dashboard-cohorts-assigned-dialog',
            'id_cohortsbtn',
            {
                buttons: dbuttons,
                title: '<h2>' + M.util.get_string('assignedcohorts', 'totara_dashboard') + '</h2>'
            },
            url+'cohort.php?selected=' + this.config.selected
                    + '&sesskey=' + this.config.sesskey
                    + '&blockid=' + this.config.blockid,
            ehandler
        );
    }
};

totaraDialog_handler_dashboardcohorts = function() {
    this.baseurl = '';
    this.cohort_items = $('input:hidden[name="cohorts"]').val();
    this.cohort_items = (this.cohort_items && this.cohort_items.length > 0) ? this.cohort_items.split(',') : [];
    this.cohort_table = $('#dashboard-cohorts-table-assigned');

    this.add_cohort_delete_event_handlers();
    this.add_published_status_change_handlers();

    this.check_table_hidden_status();
};

totaraDialog_handler_dashboardcohorts.prototype = new totaraDialog_handler_treeview_multiselect();

totaraDialog_handler_dashboardcohorts.prototype._update = function(response) {

    var self = this;
    var elements = $('.selected > div > span', this._container);
    var selected_str = this._get_ids(elements).join(',');
    var url = this._dialog.default_url.split("selected=");
    var params = url[1].slice(url[1].indexOf('&'));
    this._dialog.default_url = url[0] + 'selected=' + selected_str + params;

    var newids = new Array();

    elements.each(function() {
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

        var ajax_url = M.cfg.wwwroot + '/blocks/carrousel/cohort_item.php?itemid=' + newids.join(',') + params;
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
        });
    } else {
        this._dialog.hide();
    }
};

totaraDialog_handler_dashboardcohorts.prototype.cohort_item_exists = function(itemid) {
    for (x in this.cohort_items) {
        if (this.cohort_items[x] == itemid) {
            return true;
        }
    }
    return false;
};

totaraDialog_handler_dashboardcohorts.prototype.check_table_hidden_status = function() {
    if (this.cohort_items.length == 0) {
        $(this.cohort_table).hide();
    } else {
        $(this.cohort_table).show();
    }
};

totaraDialog_handler_dashboardcohorts.prototype.add_cohort_delete_event_handlers = function() {
    // Remove previous click event handlers.
    $('.dashboardcohortdeletelink', this.cohort_table).unbind('click');

    // Add fresh event handlers.
    var self = this;
    this.cohort_table.on('click', '.dashboardcohortdeletelink', function(event) {
        event.preventDefault();
        self.remove_cohort_item(this);
    });
};

totaraDialog_handler_dashboardcohorts.prototype.add_published_status_change_handlers = function() {
    $('input[name="published[published]"]').change(function() {
        if($('input[name="published[published]"]:checked').val() == 1) {
            $('#dashboard-cohorts-table-assigned a.dashboardcohortdeletelink').show();
        } else {
            $('#dashboard-cohorts-table-assigned a.dashboardcohortdeletelink').hide();
        }
    });
};

totaraDialog_handler_dashboardcohorts.prototype.add_cohort_item = function(itemid) {
    this.cohort_items.push(itemid);

    $('input:hidden[name="cohorts"]').val(this.cohort_items.join(','));

    this.check_table_hidden_status();
};

totaraDialog_handler_dashboardcohorts.prototype.create_item = function(html) {
    var element = $(html);
    this.cohort_table.append(element);
};

totaraDialog_handler_dashboardcohorts.prototype.remove_cohort_item = function(item) {
    var itemid = $(item).closest('div').attr('id').match(/[\d]+$/);  // Get the id part from e.g 'cohort-item-1'.
    var row = $(item).closest('tr');

    this.cohort_items = $.grep(this.cohort_items, function (element, x) {
        return (element == itemid);
    }, true);

    row.remove();

    this.check_table_hidden_status();

    $('input:hidden[name="cohorts"]').val(this.cohort_items.join(','));

    var url = this._dialog.default_url.split("selected=");
    var params = url[1].slice(url[1].indexOf('&'));
    this._dialog.default_url = url[0] + 'selected=' + this.cohort_items.join(',') + params;
};
