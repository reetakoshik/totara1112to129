/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @package totara_program
 */
/* global totaraDialog_handler_treeview_multiselect, totaraDialog, totaraDialog_handler, totaraDialogs, totaraDialog_handler_treeview_singleselect */

define(['core/ajax', 'core/templates', 'core/config', 'core/notification', 'core/str'], function(ajaxLib, templatesLib, cfg, Notification, strLib) {
    /**
     * Handles all of the program assignment functionality
     *
     * @param {DOMNode} element The parent DOM element for all interactions with this component
     */
    function ProgramAssignments(element) {
        var self = this;
        this.node = element;
        this.assignments = [];
        this.categories = [];
        this.recent = false;
        this.searchTerm = '';
        this.strings = {};
        this.dialog = null;
        this.baseURL = cfg.wwwroot + '/totara/program/assignment/';
        this.programId = parseInt(element.getAttribute('data-totara_program-id'), 10);
        this.canupdate = element.getAttribute('data-totara_program-canupdate') === "1";
        this.typeid = 0;
        this.actualduedatesdialog = null;
        this.config = {
            COMPLETION_EVENT_ENROLLMENT_DATE: "6",
            COMPLETION_EVENT_FIRST_LOGIN: "1",
            COMPLETION_EVENT_NONE: "0",
            COMPLETION_TIME_NOT_SET: "-1",
            CONDENSED_LIMIT: 5
        };

        this.addOptions = [];

        // Preload useful items
        M.util.js_pending('totara_program--assignments-strings');
        this.stringsPromise = new Promise(function(resolve) {
            var reqstrings = [
                {component: 'totara_program', key: 'ok'},
                {component: 'totara_program', key: 'remove'},
                {component: 'totara_program', key: 'cancel'},
                {component: 'totara_program', key: 'setduedate'},
                {component: 'totara_program', key: 'completioncriteria'},
                {component: 'totara_program', key: 'addorganisationstoprogram'},
                {component: 'totara_program', key: 'addpositionstoprogram'},
                {component: 'totara_program', key: 'addcohortstoprogram'},
                {component: 'totara_program', key: 'addindividualstoprogram'},
                {component: 'totara_program', key: 'addmanagerstoprogram'}
            ];
            strLib.get_strings(reqstrings).done(function(strings) {
                self.strings.ok = strings[0];
                self.strings.remove = strings[1];
                self.strings.cancel = strings[2];
                self.strings.setduedate = strings[3];
                self.strings.completioncriteria = strings[4];

                // this could possibly be done better
                self.addOptions[1] = {name: 'organisations', url: 'find_hierarchy.php?type=organisation', text: strings[5], notificationKey: 'assignmentsaddedorgainisation'};
                self.addOptions[2] = {name: 'positions', url: 'find_hierarchy.php?type=position', text: strings[6], notificationKey: 'assignmentsaddedposition'};
                self.addOptions[3] = {name: 'cohorts', url: 'find_cohort.php?sesskey=' + cfg.sesskey, text: strings[7], notificationKey: 'assignmentsaddedaudience'};
                self.addOptions[5] = {name: 'individuals', url: 'find_individual.php?', text: strings[8], notificationKey: 'assignmentsaddedindividual'};
                self.addOptions[6] = {name: 'managers', url: 'find_manager_hierarchy.php?', text: strings[9], notificationKey: 'assignmentsaddedposition'};
                M.util.js_complete('totara_program--assignments-strings');
                resolve();
            });
        });

        M.util.js_pending('totara_program--assignments-dialogs');
        this.dialogPromise = new Promise(function(resolve) {
            if (window.dialogsInited) {
                resolve();
                M.util.js_complete('totara_program--assignments-dialogs');
            } else {
                window.dialoginits = window.dialoginits || [];
                window.dialoginits.push(function() {
                    resolve();
                    M.util.js_complete('totara_program--assignments-dialogs');
                });
            }
        });
    }

    /**
     * Adds all events required for this element to work correctly
     */
    ProgramAssignments.prototype.events = function() {
        var self = this;

        this.node.addEventListener('totara_core/select_search_text:add', function(event) {
            self.searchTerm = event.detail.val;
        });
        this.node.addEventListener('totara_core/select_search_text:remove', function(event) {
            self.searchTerm = event.detail.val;
        });

        this.node.addEventListener('totara_core/select_search_text:changed', self.filter.bind(self));

        this.node.addEventListener('totara_core/select_region_panel:add', function(event) {
            switch (event.detail.key) {
                case 'recent':
                    self.recent = true;
                    break;
                case 'type':
                    self.categories = event.detail.groupValues;
                    break;
            }
        });

        this.node.addEventListener('totara_core/select_region_panel:remove', function(event) {
            switch (event.detail.key) {
                case 'recent':
                    self.recent = false;
                    break;
                case 'type':
                    self.categories = event.detail.groupValues;
                    break;
            }
        });

        this.node.addEventListener('totara_core/select_region_panel:changed', self.filter.bind(self));

        this.node.addEventListener('change', function(event) {
            if (event.target.id === 'totara_program-assignment--add-users') {
                event.preventDefault();
                self.startAdd(event.target.value);
            }
            if (event.target.getAttribute('data-totara_program__assignments--action') === 'update-below') {
                event.preventDefault();
                var row = event.target.closest('[data-totara_program__assignment-id]');
                var assignmentid = row.getAttribute('data-totara_program__assignment-id');
                var value = 0;

                row.classList.add('totara_program__assignments__loading');
                switch (event.target.tagName.toLowerCase()) {
                    case 'input': // checkbox
                        value = event.target.checked ? 1 : 0;
                        break;
                    case 'select':
                        value = event.target.value;
                        break;
                }

                self.updateBelow(assignmentid, value);
            }
        });

        this.node.addEventListener('click', function(event) {
            var actionElement = event.target.closest('[data-totara_program__assignments--action]');
            if (actionElement !== null) {
                var row = event.target.closest('[data-totara_program__assignment-id]');
                var id = parseInt(row.getAttribute('data-totara_program__assignment-id'), 10);
                switch (actionElement.getAttribute('data-totara_program__assignments--action')) {
                    case "delete":
                        event.preventDefault();
                        self.deleteAssignment(id);
                        break;
                    case "change-due-date":
                        event.preventDefault();
                        self.startChangeDuedate(id);
                        break;
                    case "remove-due-date":
                        event.preventDefault();
                        self.removeDueDate(id);
                        break;
                    case "view-actual-dates":
                        event.preventDefault();
                        self.viewActualDates(id);
                        break;
                }
            }
        });

        // Add handling for click events inside the actual due dates YUI dialog
        document.addEventListener('click', function(e) {
            var target = e.target.closest('.moodle-dialogue-content .embeddedshortname_program_assignment_duedates a, ' +
                                          '.moodle-dialogue-content .embeddedshortname_cert_assignment_duedates a');
            if (target && target.getAttribute('href')) {
                e.preventDefault();
                M.util.js_pending('totara_program--assignments-view-duedates-change');
                fetch(target.getAttribute('href'), {
                    credentials: 'same-origin',
                    method: 'get'
                }).then(function(result) {
                    return result.text();
                }).then(function(result) {
                    self.actualduedatesdialog.set('bodyContent', result);
                    M.util.js_complete('totara_program--assignments-view-duedates-change');
                }).catch(Notification.exception);
            }
        });
    };

    /**
     * Delete an assignment to this program
     *
     * @param {int} id The id of the assignment to delete
     * @param {Event} event the event that triggered the deletion process
     */
    ProgramAssignments.prototype.deleteAssignment = function(id) {
        var self = this;
        var row = this.node.querySelector('[data-totara_program__assignment-id="' + id + '"]');
        var name = row.querySelector('.totara_program__assignments__row-name').innerText;
        row.classList.add('totara_program__assignments__loading');
        M.util.js_pending('totara_program--assignments-delete-assignment');
        var remove = new Promise(function(resolve) {
            strLib.get_string('removeassignmentconfirmation', 'totara_program').done(resolve);
        });
        Promise.all([remove, this.stringsPromise]).then(function(results) {
            M.util.js_complete('totara_program--assignments-delete-assignment');
            Notification.confirm('', results[0], self.strings.remove, self.strings.cancel, function() {
                // Delete row
                ajaxLib.call([{
                    methodname: 'totara_program_assignment_delete',
                    args: {
                        assignment_id: id
                    }
                }])[0].then(function(response) {
                    if (response.success) {
                        row.remove();
                        self._clearNotifications();
                        strLib.get_string('removedfromprogram', 'totara_program', name).done(function(message) {
                            Notification.addNotification({
                                message: message,
                                type: 'success',
                                closebutton: true,
                                announce: true
                            });
                        });
                    }
                    self._updateStatus(response.status);
                    if (self.node.querySelectorAll('.totara_program__assignments__results__table tbody tr').length === 0) {
                        templatesLib.render('totara_program/assignment__no-results').done(function(html, js) {
                            self.node.querySelector('.totara_program__assignments__results').innerHTML = html;
                            templatesLib.runTemplateJS(js);
                        });
                    }

                }).fail(Notification.exception);
            }, function() {
                // Delete canceled
                row.classList.remove('totara_program__assignments__loading');
            });
        }).catch(Notification.exception);
    };

    /**
     * Make a webservice call to filter the assignments to this program
     */
    ProgramAssignments.prototype.filter = function() {
        var self = this;
        var searchParams = {
            categories: this.categories,
            term: this.searchTerm,
            recent: this.recent,
            program_id: this.programId
        };
        var container = this.node.querySelector('.totara_program__assignments__results');
        container.classList.add('totara_program__assignments__loading');
        ajaxLib.call([{
            methodname: 'totara_program_assignment_filter',
            args: searchParams
        }])[0].then(function(results) {
            if (results.toomany) {
                return templatesLib.render('totara_program/assignment__too-many');
            } else if (results.count === 0) {
                return templatesLib.render('totara_program/assignment__no-results');
            } else {
                results.canupdate = self.canupdate;
                return templatesLib.render('totara_program/assignment_table', results);
            }
        }).then(function(html, js) {
            if (self.categories === searchParams.categories
                && self.term === searchParams.searchTerm
                && self.recent === searchParams.recent) {
                // check filter matches request
                container.innerHTML = html;
                templatesLib.runTemplateJS(js);
                container.classList.remove('totara_program__assignments__loading');
            }
        }).fail(Notification.exception);
    };

    /**
     * Loads the functionality to change an assignments due date
     *
     * @param {int} id The id of the assignment to change the due date for
     */
    ProgramAssignments.prototype.startChangeDuedate = function(id) {
        var self = this;
        var strings = new Promise(function(resolve) {
            var requiredStrings = [
                {component: 'totara_core', key: 'datepickerlongyeardisplayformat'},
                {component: 'totara_program', key: 'chooseitem'},
                {component: 'moodle', key: 'none'},
                {component: 'totara_hierarchy', key: 'selected'}
            ];
            strLib.get_strings(requiredStrings).done(resolve);
        });

        M.util.js_pending('totara_program--assignments-change-duedates');

        Promise.all([strings, this.stringsPromise, this.dialogPromise]).then(function(result) {
            var strings = {
                dateFormat: result[0][0],
                chooseitem: result[0][1],
                none: result[0][2]
            };
            var TDEventHandler = function() {
                var handler = new totaraDialog_handler_treeview_singleselect('instance', 'instancetitle');
                var tdEventHandler = this;
                var buttonsObj = {};

                this.save = function() {
                    document.getElementById('instance').value = document.getElementById('treeview_selected_val_' + this.handler._title).value;
                    document.getElementById('instancetitle').innerHTML = document.getElementById('treeview_selected_text_' + this.handler._title).innerText;
                    tdEventHandler.hide();
                };

                this.clear = function() {
                    document.getElementById('instance').value = 0;
                    document.getElementById('instancetitle').value = '';
                };

                buttonsObj[self.strings.ok] = tdEventHandler.save.bind(tdEventHandler);
                buttonsObj[self.strings.cancel] = handler._cancel.bind(handler);

                var completionEventSelected = ' <span id="treeview_currently_selected_span_completion-event-dialog" style="display:none">('
                    + '<label for="treeview_selected_text_completion-event-dialog">' + result[0][3] + ':&nbsp</label>'
                    + '<em><span id="treeview_selected_text_completion-event-dialog"></span></em>)</span>'
                    + '<input type="hidden" id="treeview_selected_val_completion-event-dialog" name="treeview_selected_val_completion-event-dialog" value=""/>';


                // Call the parent dialog object and link us
                totaraDialog.call(
                    this,
                    'completion-event-dialog',
                    'unused2', // buttonid unused
                    {
                        buttons: buttonsObj,
                        title: '<h2>' + strings.chooseitem + completionEventSelected + '</h2>'
                    },
                    'unused2', // default_url unused
                    handler
                );
            };

            // This is the API for the JavaScript used by totara/program/assignment/set_completion.php
            totaraDialogs.completionevent = new TDEventHandler();

            // The completion dialog handler
            var TDCompletionHandler = function() {};

            TDCompletionHandler.prototype = new totaraDialog_handler();

            TDCompletionHandler.prototype.first_load = function() {
                var contentNode = this._dialog.dialog[0];
                var completionHandler = this;
                M.totara_core.build_datepicker(null, 'input[name="completiontime"]', strings.dateFormat);

                contentNode.querySelector('.fixeddate').addEventListener('click', function() {
                    var completiontime = contentNode.querySelector('.completiontime').value;
                    var completiontimehour = contentNode.querySelector('.completiontimehour').value;
                    var completiontimeminute = contentNode.querySelector('.completiontimeminute').value;

                    M.util.js_pending('totara_program--assignments-change-duedates-fixed');
                    new Promise(function(resolve, reject) {
                        strLib.get_string('datepickerlongyearregexjs', 'totara_core').then(function(string) {
                            var dateFormat = new RegExp(string);
                            if (dateFormat.test(completiontime) === false) {
                                reject();
                            } else {
                                resolve();
                            }
                        });
                    }).then(function() {
                        // webservice call
                        var data = {
                            assignment_id: id,
                            duedate: completiontime,
                            hour: completiontimehour,
                            minute: completiontimeminute
                        };
                        ajaxLib.call([{
                            methodname: 'totara_program_assignment_set_fixed_due_date',
                            args: data
                        }])[0].then(function(result) {
                            self._changeDuedate(id, result);
                            completionHandler._dialog.hide();
                            M.util.js_complete('totara_program--assignments-change-duedates-fixed');
                        });
                    }).catch(function() {
                        strLib.get_string('datepickerlongyearplaceholder', 'totara_core').then(function(string) {
                            M.util.js_complete('totara_program--assignments-change-duedates-fixed');
                            return strLib.get_string('pleaseentervaliddate', 'totara_program', string);
                        }).then(function(string) {
                            Notification.alert('', string, self.strings.ok);
                        });
                    });
                });

                contentNode.querySelector('.relativeeventtime').addEventListener('click', function() {
                    var timeunit = contentNode.querySelector('#timeamount').value;
                    var completionevent = contentNode.querySelector('#eventtype').value;
                    var completioninstance = contentNode.querySelector('#instance').value === "" ? 0 : contentNode.querySelector('#instance').value;
                    var unitformat = /^\d{1,3}$/;
                    if (unitformat.test(timeunit) === false) {
                        strLib.get_string('pleaseentervalidunit', 'totara_program').done(function(string) {
                            Notification.alert('', string, self.strings.ok);
                        });
                    } else if (completioninstance == 0 && completionevent != self.config.COMPLETION_EVENT_FIRST_LOGIN &&
                        completionevent != self.config.COMPLETION_EVENT_ENROLLMENT_DATE) {
                        strLib.get_string('pleasepickaninstance', 'totara_program').done(function(string) {
                            Notification.alert('', string, self.strings.ok);
                        });
                    } else {
                        var data = {
                              assignment_id: id,
                              num: contentNode.querySelector('#timeamount').value,
                              period: contentNode.querySelector('#timeperiod').value,
                              event: contentNode.querySelector('#eventtype').value,
                              eventinstanceid: completioninstance
                        };
                        ajaxLib.call([{
                            methodname: 'totara_program_assignment_set_relative_due_date',
                            args: data
                        }])[0].then(function(result) {
                            self._changeDuedate(id, result);
                            completionHandler._dialog.hide();
                        }).fail(Notification.exception);
                    }
                });

            };

            TDCompletionHandler.prototype.every_load = function() {
                var contentNode = this._dialog.dialog[0];
                var eventType = contentNode.querySelector('[name="event"]'),
                    instanceName = contentNode.querySelector('[name="eventinstancename"]');

                if (eventType) {
                    contentNode.querySelector('[name="eventtype"]').value = eventType.value;
                }
                if (instanceName) {
                    contentNode.querySelector('#instancetitle').innerText = instanceName.value;
                }
            };

            var buttons = {};
            buttons[self.strings.cancel] = function() {
                self.dialog.hide();
            };

            var findUrl = 'set_completion.php?programid=' + self.programId + '&assignmentid=' + id;
            self.dialog = new totaraDialog(
                'completion-dialog',
                null,
                {
                    buttons: buttons,
                    title: '<h2>' + self.strings.completioncriteria + '</h2>'
                },
                self.baseURL + findUrl,
                new TDCompletionHandler()
            );

            self.dialog.open();
            document.getElementById('completion-dialog').style.height = "175px";
            M.util.js_complete('totara_program--assignments-change-duedates');
        }).catch(Notification.exception);
    };

    /**
     * Updates the due date for the given assignment in the UI
     *
     * @param {int} id The id of the assignment to update
     * @param {Object} response the context required for the due date template
     */
    ProgramAssignments.prototype._changeDuedate = function(id, response) {
        var self = this;
        var row = this.node.querySelector('[data-totara_program__assignment-id="' + id + '"]');
        var duedatecontext = {
            canupdate: this.canupdate,
            duedate: response.duedate,
            duedateupdatable: response.duedateupdatable
        };
        var actualduedatecontext = {
            actualduedate: response.actualduedate
        };
        this._updateStatus(response.status);
        row.classList.add('totara_program__assignments__loading');

        var due = templatesLib.render('totara_program/assignment__due_date', duedatecontext).then(function(html) {
            row.querySelector('.totara_program__assignments__row-duedate').innerHTML = html;
        });

        templatesLib.render('totara_program/assignment__actual_date', actualduedatecontext).then(function(html) {
            row.querySelector('.totara_program__assignments__row-actual-duedate').innerHTML = html;
            return due;
        }).then(function() {
            row.classList.remove('totara_program__assignments__loading');
        });
        self.updateSuccess(id);
    };

    /**
     * Removes a due date from a program assignment
     *
     * @param {int} id id of the assignment to remove the due date from
     */
    ProgramAssignments.prototype.removeDueDate = function(id) {
        var self = this;
        ajaxLib.call([{
            methodname: 'totara_program_assignment_remove_due_date',
            args: {assignment_id: id}
        }])[0].then(function(context) {
            self._changeDuedate(id, context);
        });
    };

    /**
     * Shows dialog for actual due dates for a progam assignment
     *
     * @param {int} id id of the assignment to show date for
     */
    ProgramAssignments.prototype.viewActualDates = function(id) {
        var self = this;

        var url = cfg.wwwroot + '/totara/program/assignment/duedates_report.php?programid='
            + self.programId + '&assignmentid=' + id;

        M.util.js_pending('totara_program--assignments-view-duedates');
        fetch(url, {
            credentials: 'same-origin',
            method: 'get'
        }).then(function(result) {
            return result.text();
        }).then(function(result) {
            if (self.actualduedatesdialog) {
                self.actualduedatesdialog.destroy();
                self.actualduedatesdialog = null;
            }
            self.actualduedatesdialog = new M.core.dialogue({
                headerContent: null,
                bodyContent: result,
                width: 900,
                centered: true,
                modal: true,
                render: true
            }).show();
            M.util.js_complete('totara_program--assignments-view-duedates');
        }).catch(Notification.exception);
    };

    /**
     * Begins the process of adding an assignment to the program
     *
     * @param {int} type The type of the assignment to add
     */
    ProgramAssignments.prototype.startAdd = function(type) {
        var self = this;
        if (type === '') {
            return;
        }
        this.typeid = type;
        M.util.js_pending('totara_program--assignments-addItems');
        Promise.all([this.stringsPromise, this.dialogPromise]).then(function() {
            var buttons = {};
            buttons[self.strings.ok] = function() {
                self.addAssignments();
            };
            buttons[self.strings.cancel] = function() {
                self.dialog.hide();
            };

            var handler = new totaraDialog_handler_treeview_multiselect();
            var findUrl = self.addOptions[type].url + '&programid=' + self.programId;
            self.dialog = new totaraDialog(
                'add-assignment-dialog-' + type,
                null,
                {
                    buttons: buttons,
                    title: '<h2>' + self.addOptions[type].text + '</h2>'
                },
                self.baseURL + findUrl,
                handler

            );

            self.dialog.open();

            M.util.js_complete('totara_program--assignments-addItems');
            self.node.querySelector('#totara_program-assignment--add-users').value = "";
        });
    };

    /**
     * Makes the required web service calls to add assignments to the program
     */
    ProgramAssignments.prototype.addAssignments = function() {
        // Ugly way to get the user id's
        var selected = this.dialog.handler._container.get(0).querySelectorAll('.selected > div > span.clickable');
        var newids = [];
        var self = this;

        // Loop through the selected elements
        for (var value = 0; value < selected.length; value++) {
            // Get id
            var itemid;
            var element = selected[value];
            if (element.getAttribute('data-jaid')) {
                // Hack: if there's a jaid data attribute, use that.
                itemid = parseInt(element.getAttribute('data-jaid'), 10);
            } else {
                itemid = element.id.split('_');
                itemid = itemid[itemid.length - 1]; // The last item is the actual id
                itemid = parseInt(itemid, 10);
            }

            if (newids.indexOf(itemid) === -1) {
                newids.push(itemid);
            }
        }

        if (newids.length > 0) {
            M.util.js_pending('totara_program--assignments-addingItems');
            self.dialog.showLoading();
            new Promise(function(resolve, reject) {
                ajaxLib.call([{
                    methodname: 'totara_program_assignment_create',
                    args: {
                        programid: self.programId,
                        typeid: self.typeid,
                        items: newids
                    }
                }])[0].done(resolve).fail(reject);
            }).then(function(response) {
                var status = self._updateStatus(response.status);
                var notifications = new Promise(function(resolve) {
                    self._clearNotifications();
                    if (response.items.length < self.config.CONDENSED_LIMIT) {
                        response.items.forEach(function(item) {
                            strLib.get_string('assignmentadded', 'totara_program', item.name).done(function(message) {
                                Notification.addNotification({
                                    message: message,
                                    type: 'success',
                                    closebutton: true,
                                    announce: true
                                });
                                resolve();
                            });
                        });
                    } else {
                        strLib.get_string(self.addOptions[self.typeid].notificationKey, 'totara_program', response.items.length).done(function(message) {
                            Notification.addNotification({
                                message: message,
                                type: 'success',
                                closebutton: true,
                                announce: true
                            });
                            resolve();
                        });
                    }
                });
                var updateDisplay = new Promise(function(resolve, reject) {
                    // Figure out which results to add
                    var toAdd = response.items.filter(function(item) {
                        var hasCategories = self.categories.length === 0; // if no categories selected in filter, show all;
                        var inCategories;

                        if (self.searchTerm && item.name.toLowerCase().indexOf(self.searchTerm.toLowerCase()) === -1) {
                            return false;
                        }

                        inCategories = self.categories.some(function(category) {
                            return parseInt(category, 10) === item.type_id;
                        });
                        return hasCategories || inCategories;
                    });

                    // If there's nothing to add, then there's nothing to do.
                    if (toAdd.length === 0) {
                        resolve();
                        return;
                    }

                    // Too many results to display
                    if (self.node.getElementsByClassName('totara_program__assignments__results-too-many').length > 0) {
                        resolve();
                        return;
                    }
                    var rows = self.node.querySelectorAll('[data-totara_program__assignment]');
                    if (toAdd.length + rows.length > 100) {
                        templatesLib.render('totara_program/assignment__too-many').done(function(html, js) {
                            self.node.querySelector('.totara_program__assignments__results').innerHTML = html;
                            templatesLib.runTemplateJS(js);
                            resolve();
                        });
                        return;
                    }

                    // Below max
                    if (self.node.getElementsByClassName('totara_program__assignments__results-no-results').length > 0) {
                        // Previously had no items
                        var context = {
                            items: toAdd,
                            canupdate: self.canupdate
                        };
                        templatesLib.render('totara_program/assignment_table', context).done(function(html, js) {
                            self.node.querySelector('.totara_program__assignments__results').innerHTML = html;
                            templatesLib.runTemplateJS(js);
                            resolve();
                        });
                    } else {
                        // add results to bottom
                        var promises = toAdd.map(function(item) {
                            return new Promise(function(resolve) {
                                item.canupdate = self.canupdate;
                                templatesLib.render('totara_program/assignment__table__row', item).done(resolve);
                            });
                        });
                        Promise.all(promises).then(function(results) {
                            var allHTML = "";
                            results.forEach(function(html) {
                                allHTML += html;
                            });
                            self.node.querySelector('.totara_program__assignments__results__table tbody')
                                .insertAdjacentHTML('beforeend', allHTML);

                            resolve();
                        }).catch(reject);
                    }
                });
                return Promise.all([status, notifications, updateDisplay]);
            }).then(function() {
                self.dialog.hide();
                M.util.js_complete('totara_program--assignments-addingItems');
            }).catch(Notification.exception);
        } else {
            this.dialog.hide();
        }
    };

    /**
     * Updates the include all below functionality
     *
     * @param {int} assignmentid The id of the program assignment that is being updated
     * @param {int} value either a 1 or 0 depending on whether the program assignment is to include items
     *          below it in the hierarchy
     */
    ProgramAssignments.prototype.updateBelow = function(assignmentid, value) {
        var self = this;
        ajaxLib.call([{
            methodname: 'totara_program_assignment_set_include_children',
            args: {
                assignmentid: assignmentid,
                value: value
            }
        }])[0].done(function(response) {
            var row = self.node.querySelector('[data-totara_program__assignment-id="' + assignmentid + '"]');
            self._updateStatus(response.status);
            self.updateSuccess(assignmentid);
            row.querySelector('[data-totara_program__assignments--learnercount]').innerHTML = response.numusers;

            row.classList.remove('totara_program__assignments__loading');
        }).fail(Notification.exception);
    };

    /**
     * Adds a notification stating that the assignment is successfully updated
     *
     * @param {int} assignmentid the id of the assignment being updated
     */
    ProgramAssignments.prototype.updateSuccess = function(assignmentid) {
        var queryselector = '[data-totara_program__assignment-id="' + assignmentid + '"] .totara_program__assignments__row-name';
        var name = this.node.querySelector(queryselector).textContent;
        this._clearNotifications();
        strLib.get_string('individualassignmentupdated', 'totara_program', name).done(function(message) {
            Notification.addNotification({
                message: message,
                type: 'success',
                closebutton: true,
                announce: true
            });
        });
    };

    /**
     * Update the status message
     *
     * @param {object} status The status context object for the string
     * @returns {ES6Promise}
     */
    ProgramAssignments.prototype._updateStatus = function(status) {
        var exceptionCount = status.exception_count;
        var exceptionTab = document.querySelector('#program-assignments .tabtree li:last-child');
        var stringProm = new Promise(function(resolve) {
            strLib.get_string('exceptions', 'totara_program', exceptionCount).done(function(exceptionString) {
                if (exceptionCount === parseInt(exceptionTab.getAttribute('data-totara_program-exception_count'), 10)) {
                    exceptionTab.querySelector('a').innerText = exceptionString;
                }
                resolve();
            });
        });
        var context = {
            message: status.status_string,
            extraclasses: 'notifynotice',
            announce: true,
            closebutton: false
        };
        var templateProm = new Promise(function(resolve) {
            templatesLib.render('core/notification_' + status.state, context).done(function(html, js) {
                document.querySelector('[data-totara_program--notification]').innerHTML = html;
                templatesLib.runTemplateJS(js);
                resolve();
            });
        });

        // locking get_string
        exceptionTab.setAttribute('data-totara_program-exception_count', exceptionCount);

        // enable & update exception tab
        if (exceptionCount > 0) {
            // This should be the exceptions tab
            exceptionTab.classList.remove('disabled');
            exceptionTab.querySelector('a').setAttribute('href', cfg.wwwroot + '/totara/program/exceptions.php?id=' + this.programId);
        } else {
            exceptionTab.classList.add('disabled');
            exceptionTab.querySelector('a').removeAttribute('href');
        }
        return Promise.all([stringProm, templateProm]);

    };

    /**
     * Clears notifications
     */
    ProgramAssignments.prototype._clearNotifications = function() {
        document.getElementById('user-notifications').innerHTML = "";
    };

    /**
     * Initialise our widget
     * @param {string} element
     * @returns {Promise}
     */
    function init(element) {
        return new Promise(function(resolve) {
            var controller = new ProgramAssignments(element);
            controller.events();
            resolve(controller);
        });
    }

    return {
        init: init
    };
});
