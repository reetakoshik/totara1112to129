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
 * @author David Curry <david.curry@totaralms.com>
 * @package mod_facetoface
 */


M.facetoface_approver = M.facetoface_approver || {

    Y: null,
    // Optional php params and defaults defined here, args passed to init method.
    // These values will be overrided below.
    config: {
        id:0,
        sesskey:0
    },

    /**
     * module initialisation method called by php js_init_call()
     *
     * @param object    YUI instance
     * @param int       course          The course id
     * @param string    sesskey         The sesskey
     * @param array     existing        The existing approvers for the facetoface
     */
    init: function(Y, course, sesskey, existing){

        // Save a reference to the Y instance (all of its dependencies included)
        this.Y = Y;

        // Parse args into this module's config object
        this.config.cid = course;
        this.config.sesskey = sesskey;

        // check jQuery dependency is available
        if (typeof $ === 'undefined') {
            throw new Error('M.facetoface_approver.init()-> jQuery dependency required for this module to function.');
        }

        // Select users to be activity level approvers, javascript dialog.
        (function() {
            var url = M.cfg.wwwroot+ '/mod/facetoface/approver';
            var saveurl = url + '/update.php';
            var findurl = url + '/find.php';
            var cid = M.facetoface_approver.config.cid;
            var sesskey = M.facetoface_approver.config.sesskey;
            facetofaceApproverDialog(
                'addapprover',
                M.util.get_string('chooseapprovers', 'mod_facetoface'),
                findurl + '?cid=' + cid + '&sesskey=' + sesskey,
                saveurl + '?cid=' + cid + '&sesskey=' + sesskey
            );
        })();

        // Hijack the activity approver delete buttons.
        $(document).on('click', '.activity_approver_del', function (event) {
            event.preventDefault();

            var userid = this.id;
            var sysnew = $('input[name="selectedapprovers"]');
            var user_record = document.getElementById('facetoface_approver_' + userid);

            // Remove the user from displaying on the screen.
            user_record.parentNode.removeChild(user_record);

            // Remove the user from the hidden div used to add them.
            var sysval = sysnew.val().split(',');

            var newval = [];
            for (var i = 0; i < sysval.length; i++) {
                if (sysval[i] != userid) {
                    newval.push(sysval[i]);
                }
            }

            sysnew.val(newval.join(','));
        });
    }
}

/**
 * Setup multi-select treeview dialog that calls a save page, and
 * prints the html response to an underlying table
 *
 * @param string dialog name
 * @param string dialog title
 * @param string find page url
 * @param string save page url
 * @return void
 */
facetofaceApproverDialog = function(name, title, find_url, save_url) {
    var handler = new facetoface_handler();
    var saveurl = save_url;
    var findurl = find_url;

    var buttonObj = {};
    buttonObj[M.util.get_string('save', 'totara_core')] = function() { handler._save(save_url) };
    buttonObj[M.util.get_string('cancel', 'moodle')] = function() { handler._cancel() };

    handler.responsegoeshere = $('#activityapproverbox.activity_approvers');

    totaraDialogs[name] = new totaraDialog(
        name,
        'show-'+name+'-dialog',
        {
            buttons: buttonObj,
            title: '<h2>'+title+'</h2>'
        },
        find_url + '&selected=' + $('input[name="selectedapprovers"]').val(),
        handler
    );

    totaraDialogs[name].saveurl = save_url;
    totaraDialogs[name].findurl = find_url;

    /**
     * Open dialog and load external page
     * @return  void
     */
    totaraDialogs[name].open = function() {
        // Open default url in dialog
        var method = 'GET';

        var selected = $('input[name="selectedapprovers"]');
        this.default_url = this.findurl + '&selected=' + $('input[name="selectedapprovers"]').val();
        this.dialog.html('');
        this.dialog.dialog('open');

        // Get dialog parent
        var par = this.dialog.parent();

        // Set dialog body height (the 20px is the margins above and below the content)
        var height = par.height() - $('div.ui-dialog-titlebar', par).height() - $('div.ui-dialog-buttonpane', par).height() - 36;
        this.dialog.height(height);

        // Run dialog open hook
        if (this.handler._open != undefined) {
            this.handler._open();
        }

        this.load(this.default_url);
    }

}

// A function to handle the responses generated by handlers
var facetoface_handler_responsefunc = function(response) {
    if (response.substr(0,4) == 'DONE') {
        // Get all root elements in response
        var els = $(response.substr(4));

        // Update the assignments table.
        $('#activityapproverbox.activity_approvers').replaceWith(els);
        els.effect('pulsate', { times: 3 }, 2000);

        this.responsegoeshere.show();

        // Close dialog
        this._dialog.hide();
    } else {
        this._dialog.render(response);
    }
}

facetoface_handler = function() {};
facetoface_handler.prototype = new totaraDialog_handler_treeview_multiselect();

/*
 * Serialize dropped items and send to url,
 * update table with result
 *
 * @param string URL to send dropped items to
 * @return void
 */
facetoface_handler.prototype._save = function() {
    // Serialize data

    var elements = $('.selected > div > span', this._container);
    var selected = this._get_ids(elements);

    // If they're trying to create a new rule but haven't selected anything, just exit.
    // (If they are updating an existing rule, we'll want to delete the selected ones.)
    if (!selected.length) {
        if (this.responsetype == 'new') {
            this._cancel();
            return;
        } else if (this.responsetype == 'update') {
            // Trigger the "delete" link, closing this dialog if it's successful
            $('a.group-delete', this.responsegoeshere).trigger('click', {object: this, method: '_cancel'});
            return;
        }
    }

    $('#activityapproverbox.activity_approvers').show();

    // Add userids to the selectedapprovers hidden field.
    var selected_str = selected.join(','); // Anything new.
    $('input[name="selectedapprovers"]').val(selected_str);

    // Add to url
    var url = this._dialog.saveurl;

    if (selected_str != null && selected_str.length > 0) {
        url += '&users=' + selected_str;
    }

    // Update the find url for next time.
    this._dialog.default_url = this._dialog.findurl + '&selected=' + selected_str;

    // Send to server
    this._dialog._request(url, {object: this, method: '_update'});
}

facetoface_handler.prototype._update = facetoface_handler_responsefunc;
