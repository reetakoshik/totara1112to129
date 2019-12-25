/**
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
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package totara
 * @subpackage facetoface
 */

M.totara_f2f_notification_template = M.totara_f2f_notification_template || {

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
    init: function(Y, args){
        var module = this;

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
            throw new Error('M.totara_f2f_notification_template.init()-> jQuery dependency required for this module to function.');
        }

        var templates = M.totara_f2f_notification_template.config.templates;

        $(function() {

            // Attach event to drop down
            $('select#id_templateid').change(function() {
                var select = $(this);

                // Get current value
                var current = select.val();

                // Overwrite form data.
                if (current !== '0') {
                    $('input#id_title').val(templates[current].title);
                    $('textarea#id_body_editor').val(templates[current].body);

                    var isChecked = !!Number(templates[current].ccmanager);
                    $('input#id_ccmanager').prop('checked', isChecked);

                    var templatecontent = "";
                    if (templates[current].managerprefix) {
                        templatecontent = templates[current].managerprefix;
                    }
                    $('textarea#id_managerprefix_editor').val(templatecontent);

                } else {
                    $('input#id_title').val('');
                    $('textarea#id_body_editor').val('');
                    $('input#id_ccmanager').prop('checked', false);
                    $('textarea#id_managerprefix_editor').val('');
                }
                // Try to update editor
                var bodyeditor = Y.one('#id_body_editor').getData('editor');
                if(bodyeditor && typeof bodyeditor.updateFromTextArea === "function") {
                    bodyeditor.updateFromTextArea();
                }

                var prefixeditor = Y.one('#id_managerprefix_editor').getData('editor');
                if(prefixeditor && typeof prefixeditor.updateFromTextArea === "function") {
                    prefixeditor.updateFromTextArea();
                }
            });

            // Detecting element error here
            var f2fbookedtypeelement = $('select#f2f-booked-type'),
                container = f2fbookedtypeelement.parent('div'),
                errormsgbox = null;

            if (f2fbookedtypeelement.attr('data-error') == 'error') {
                // Idicating that it has an error here, and it is going to input message with an icon
                f2fbookedtypeelement.addClass('f2f-booked-type-error');

                var errormsg = $('<span></span>');
                errormsg.text(M.util.get_string('required', 'core'));

                errormsgbox = $('<div></div>');
                errormsgbox.addClass('f2f-booked-type-error-hint').append(errormsg);

                container.append(errormsgbox);
            }

            // Just remove the error outline when user is changing the value to
            // something else.
            f2fbookedtypeelement.change(function() {
                var self = $(this);
                if (self.val() != 0) {
                    self.removeClass('f2f-booked-type-error');

                    // Removing the text here, only if it exist
                    if (errormsgbox) {
                        errormsgbox.remove();
                    }
                }
            });
        });

        // We want to listen to changes, however when the editor processes the body and manager copy it may
        // change spacing, encode entities, or change non-visual markup.
        // These all lead to a change event, which we don't care about, as its the editor changing content, not the user.
        // To get around this (and its a hack sorry) we just want until the user has clicked or pressed a key down.
        // Only then do we start listening.
        $('body').on('keydown click', function() {
            // Reset the template to the empty option as soon as user enters some text in any of these fields.
            $('#id_title, #id_body_editor, #id_managerprefix_editor').change(function() {
                $('#id_templateid').val('0');
            });
        });
    }
}
