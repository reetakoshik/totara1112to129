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
 * @package totara
 * @subpackage totara_cohort
 */
M.totara_cohortvisiblelearning = M.totara_cohortvisiblelearning || {

    Y: null,
    // optional php params and defaults defined here, args passed to init method
    // below will override these values
    config: {},
    // public handler reference for the dialog
    totaraDialog_handler_preRequisite: null,

    /**
     * module initialisation method called by php js_init_call()
     *
     * @param object    YUI instance
     * @param string    args supplied in JSON format
     */
    init: function(Y, args) {
        // save a reference to the Y instance (all of its dependencies included)
        var module = this;
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

        // Check jQuery dependency is available.
        if (typeof $ === 'undefined') {
            throw new Error('M.totara_positionuser.init()-> jQuery dependency required for this module to function.');
        }

        var tableselector = "[data-source='rb_source_cohort_associations_visible']";
        // Add hooks to visibility of learning content.
        // Update when visibility drop-down list change.
        $(tableselector).on('change', 'form .custom-select', function() {
            var learningcontent = $(this).attr('id').split('_');
            var learningcontenttype = learningcontent[1];
            var learningcontentid = learningcontent[2];
            var learningvisibilityvalue = $(this).val();

            if (module.config.cohort_visibility[learningvisibilityvalue]) {
                $.ajax({
                    type: "POST",
                    url: M.cfg.wwwroot + '/totara/cohort/updatevisiblelearning.php',
                    data: ({
                        id: learningcontentid,
                        type: learningcontenttype,
                        value: learningvisibilityvalue,
                        sesskey: M.cfg.sesskey
                    })
                }).done(function(response) {
                    if (response.result && response.data.length) {
                        var identifier, item;
                        for (var i = 0; i < response.data.length; i++) {
                            item = response.data[i];
                            identifier = 'menu_' + learningcontenttype + '_' + learningcontentid + '_' + item;
                            $('.' + identifier).val(learningvisibilityvalue);
                        }
                    }
                });
            } else {
                alert(M.util.get_string('invalidentry', 'error'));
            }
        });

        // Pressing the delete button should just sending an AJAX to the server here, and
        // should remove the row after ajax has been done.
        $(tableselector).on('click', ".cohort-association-visible-delete", function(event) {
            event.preventDefault();
            var self = this;
            var url = self.href;
            var confirmed = confirm(M.util.get_string('deletelearningconfirm', 'totara_cohort'));
            var parent = self.closest('tr');

            if (!confirmed) {
                return;
            }

            $.ajax({
                type: "GET",
                url: url,
                beforeSend: function() {
                    require(['core/templates'], function(templates) {
                        templates.renderIcon('loading', M.util.get_string('savingrule', 'totara_cohort')).done(function(html) {
                            if (self.parentNode !== undefined) {
                                self.parentNode.innerHTML = html;
                            }
                        });
                    });
                }
            }).done(function() {
                // Deleting the row here
                parent.remove();
            }).fail(function() {
                alert(M.util.get_string('error:badresponsefromajax', 'totara_cohort'));
                //Reload the broken page
                location.reload();
            });
        });
    }
};
