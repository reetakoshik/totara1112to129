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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_job
 * @module totara_job/job_management_listing
 */
define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {

    function ListManager(userid) {

        this.items = [];
        this.userid = userid;

        var self = this;
        this.container = $('[data-enhance="job-management-listing"][data-enhanced="false"][data-userid="'+userid+'"]');
        if (!this.container) {
            return;
        }
        this.container.attr('data-enhanced', 'true');
        this.container.find('li a.editjoblink[data-id]').each(function() {
            var item = $(this),
                id = item.data('id'),
                sortorder = item.data('sortorder');
            self.register_item(id, sortorder, item);
        });
        this.container.delegate('a[data-action]', 'click', function(ev) {
            ev.preventDefault();
            ev.stopPropagation();
            var item = $(ev.currentTarget),
                action = item.data('action'),
                id = item.data('id');
            if (action === 'up') {
                self.move_item_up(id);
            } else if (action === 'down') {
                self.move_item_down(id);
            } else if (action === 'delete') {
                self.confirm_delete(id);
            }
        });
    }

    /**
     * The id of the user this list belongs to.
     * @type {Integer}
     */
    ListManager.prototype.userid = null;

    /**
     * An array of job assignment list items.
     * @type {Array}
     */
    ListManager.prototype.items = [];
    ListManager.prototype.container = null;
    ListManager.prototype.register_item = function(id, sortorder, node) {
        var self;
        // Set the ID on all data actions to make our life easier.
        $(node).parent('li').find('a[data-action]').each(function(){
            $(this).attr('data-id', id);
        });
        this.items.push({
            'id': id,
            'sortorder': sortorder
        });
    };
    ListManager.prototype.confirm_delete = function(itemid) {
        var node = this.container.find('a.editjoblink[data-id="'+itemid+'"]'),
            self = this,
            params = [];

        // Get staff users that will be affected for this decision.
        var promise = $.ajax({
            url: M.cfg.wwwroot + '/totara/job/dialog/get_deletion_notification.php',
            type: "GET",
            data: ({
                sesskey: M.cfg.sesskey,
                userid: this.userid,
                jobassignmentid: itemid,
                jobassignmenttext: node.text()
            })
        });

        promise.done(function(data) {
            params['jobassignment'] = node.text();
            var  deferred = Str.get_strings([
                { key: 'deletejobassignment', component: 'totara_job', param: null, lang: null },
                { key: 'confirmdeletejobassignment', component: 'totara_job', param: params, lang: null },
                { key: 'yesdelete', component: 'totara_core', param: null, lang: null },
                { key: 'cancel', component: 'core', param: null, lang: null }
            ]);
            deferred.done(function(results){
                if (data) {
                    results[1] = data;
                }
                results.push(function() {
                    M.util.js_pending('totara_job-delete');
                    var deferred = self.delete_job_assignment(itemid);
                    deferred.done(function(){
                        M.util.js_complete('totara_job-delete');
                    });
                });
                Notification.confirm.apply(Notification.confirm, results);
            });
        }).fail(Notification.exception);
    };
    ListManager.prototype.delete_job_assignment = function(itemid) {
        var ajaxrequests = [{
                methodname: 'totara_job_external_delete_job_assignment',
                args: {
                    userid: this.userid,
                    jobassignmentid: itemid
                }
            }],
            deferred = $.Deferred(),
            self = this;

        var deferreds = Ajax.call(ajaxrequests, true, true);
        $.when.apply(null, deferreds).done(
            function() {
                // Turn the list of arguments (unknown length) into a real array.
                self.update_list(arguments[0]);
                deferred.resolve();
            }
        ).fail(
            function(ex) {
                deferred.reject(ex);
            }
        );
        return deferred.promise();
    };
    ListManager.prototype.move_item_up = function(itemid) {
        var i = 0,
            currentSort = 0,
            switchItem = false,
            targetItem = this.get_item_by_id(itemid),
            ul = this.container.find('ul');
        if (targetItem === false) {
            // Can't find it.
            return false;
        }
        for (i in this.items) {
            if (this.items.hasOwnProperty(i) && this.items[i].sortorder >= currentSort && this.items[i].sortorder < targetItem.sortorder) {
                switchItem = this.items[i];
                currentSort = this.items[i].sortorder;
            }
        }
        if (!switchItem) {
            // Can't find the next item.
            return false;
        }
        M.util.js_pending('totara_job-move_item_up');
        var deferred = this.switch_items(switchItem, targetItem);
        deferred.done(function() {
            var li = $(ul.find('a.editjoblink[data-id="'+targetItem.id+'"]').parents('li')[0]);
            li.fadeOut(50, function() {
                li.css({backgroundColor: '#fefdb2'});
            }).fadeIn(500, function(){
                li.css({backgroundColor: 'initial'});
            });
            M.util.js_complete('totara_job-move_item_up');
        });
    };
    ListManager.prototype.move_item_down = function(itemid) {
        var i = 0,
            currentSort = 2147483646,
            switchItem = false,
            targetItem = this.get_item_by_id(itemid),
            ul = this.container.find('ul');
        if (targetItem === false) {
            // Can't find it.
            return false;
        }
        for (i in this.items) {
            if (this.items.hasOwnProperty(i) && this.items[i].sortorder <= currentSort && this.items[i].sortorder > targetItem.sortorder) {
                switchItem = this.items[i];
                currentSort = this.items[i].sortorder;
            }
        }
        if (!switchItem) {
            // Can't find the next item.
            return false;
        }
        M.util.js_pending('totara_job-move_item_down');
        var deferred = this.switch_items(switchItem, targetItem);
        deferred.done(function() {
            var li = $(ul.find('a.editjoblink[data-id="'+targetItem.id+'"]').parents('li')[0]);
            li.fadeOut(50, function() {
                li.css({backgroundColor: '#fefdb2'});
            }).fadeIn(500, function(){
                li.css({backgroundColor: 'initial'});
            });
            M.util.js_complete('totara_job-move_item_down');
        });
    };
    ListManager.prototype.get_item_by_id = function(itemid) {
        var i;
        for (i in this.items) {
            if (this.items.hasOwnProperty(i) && this.items[i].id == itemid) {
                return this.items[i];
            }
        }
        return false;
    };
    ListManager.prototype.switch_items = function(itema, itemb) {
        var i,
            changeda = false,
            valuea = itemb.sortorder,
            changedb = false,
            valueb = itema.sortorder,
            data = [],
            deferred = $.Deferred(),
            self = this;

        for (i in this.items) {
            if (this.items.hasOwnProperty(i)) {
                if (this.items[i].id === itema.id) {
                    this.items[i].sortorder = valuea;
                    changeda = i;
                }
                if (this.items[i].id === itemb.id) {
                    this.items[i].sortorder = valueb;
                    changedb = i;
                }
                data.push({
                    jobassignid: this.items[i].id,
                    sortorder: this.items[i].sortorder
                });
            }
        }
        if (!changeda && !changedb) {
            // We didn't flip, quit now.
            deferred.reject();
            return deferred.promise();
        }

        var ajaxrequests = [{
            methodname: 'totara_job_external_resort_job_assignments',
            args: {
                userid: this.userid,
                sort: data
            }
        }];

        var deferreds = Ajax.call(ajaxrequests, true, true);
        $.when.apply(null, deferreds).done(
            function() {
                // Turn the list of arguments (unknown length) into a real array.
                self.update_list(arguments[0]);
                deferred.resolve();
            }
        ).fail(
            function(ex) {
                deferred.reject(ex);
            }
        );
        return deferred.promise();
    };

    ListManager.prototype.update_list = function(newpositions) {
        var i, j,
            html = [],
            jobassignid,
            sortorder,
            itemanchor,
            ul = this.container.find('ul'),
            li,
            updated;
        for (i in newpositions) {
            if (newpositions.hasOwnProperty(i)) {

                jobassignid = newpositions[i].jobassignid;
                sortorder = newpositions[i].sortorder;

                // Update the sortorder on the item we are tracking.
                updated = false;
                for (j in this.items) {
                    if (this.items.hasOwnProperty(j)) {
                        if (this.items[j].id == jobassignid) {
                            this.items[j].sortorder = sortorder;
                            updated = true;
                            break;
                        }
                    }
                }
                if (!updated) {
                    // Woah!
                    continue;
                }

                // Now grab the HTML, we'll piece it back together later.
                itemanchor = this.container.find('a[data-id="'+jobassignid+'"]');
                itemanchor.attr('data-sortorder', sortorder);
                li = itemanchor.parent('li');
                html.push('<li>'+li.html()+'</li>');
            }
        }
        ul.empty();
        ul.append.apply(ul, html);
        this.container.attr('data-jobcount', html.length);
    };

    return {
        /**
         * Allow a new job management listing to be initialised for the given used.
         *
         * @param {Integer} userid
         * @returns {ListManager}
         */
        init: function(userid) {
            return new ListManager(userid);
        }
    };

});