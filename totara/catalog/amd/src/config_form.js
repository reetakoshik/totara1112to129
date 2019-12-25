/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_catalog
 */

define(['core/str', 'totara_form/form'], function(mdlstr, FormLib) {
    var unloadFunction = null,
        dirtyAttribute = 'data-totara_catalog--dirty';

    var init = function(currenttab, reload) {
        var pendingKey = 'totaraCatalogAdminFormInit' + currenttab,
            formId = 'tf_fid_' + currenttab;

        M.util.js_pending(pendingKey);

        // Grab the strings for the page leave confirmation
        var stringsLoad = new Promise(function(resolve) {
            mdlstr.get_string('discard_unsaved_changes', 'totara_catalog').then(resolve);
        }).then(function(discardUnsavedChanges) {
            // Create unload functionality
            if (unloadFunction === null) {
                unloadFunction = function(e) {
                    var form = document.getElementById(formId);
                    if (form.getAttribute(dirtyAttribute) === 'true') {
                        e.preventDefault();
                        e.returnValue = discardUnsavedChanges; // for chrome
                        return discardUnsavedChanges;
                    }
                };

                window.addEventListener('beforeunload', unloadFunction);
            }
        });

        // Usually at this point, the form would not have been initialised so implement some tracking to get the form
        var formLoad = new Promise(function(resolve, reject) {
            var form = FormLib.getFormInstance(formId); // Likely to be null at this point
            if (form === null || reload) {
                // Reload is required to observe as the result from getFormInstance doesn't get replaced until after the form has been initialised
                var formElement = document.getElementById(formId);
                if (formElement === null) {
                    // The form should exist in HTML, if not, there's bigger issues
                    reject();
                }

                var observerCallback = function() {
                    if (formElement.getAttribute('data-totara_form-initialised') === 'true') {
                        observer.disconnect();
                        resolve(FormLib.getFormInstance(formId));
                    }
                };

                var observer = new MutationObserver(observerCallback);
                observer.observe(formElement, {
                    attributes: true, // required for IE11
                    attributeFilter: ['data-totara_form-initialised']
                });

            } else {
                resolve(form);
            }
        }).then(function(form) {
            var formDom = form.form[0];
            // Add click event removing unload event
            var saveButton = formDom.querySelector('[data-name="submitbutton"], [name="submitbutton"]');
            var undoButton = formDom.querySelector('[data-name="cancelbutton"], [name="cancelbutton"]');

            var removeDirty = function() {
                formDom.removeAttribute(dirtyAttribute);
            };

            var addDirty = function() {
                formDom.setAttribute(dirtyAttribute, 'true');
            };

            if (reload) {
                addDirty();
            }

            saveButton.addEventListener('click', removeDirty);
            undoButton.addEventListener('click', removeDirty);

            // Overwrite form change functionality
            form._parentValueChanged = form.valueChanged;
            form.valueChanged = function(id, value) {
                var alerts = form.form[0].parentElement.querySelectorAll('.alert'),
                    alert = 0;

                for (alert = 0; alert < alerts.length; alert++) {
                    alerts[alert].remove();
                }

                // Enable buttons
                saveButton.removeAttribute('disabled');
                saveButton.setAttribute('name', saveButton.getAttribute('data-name'));
                saveButton.closest('[data-item-classification="element"]').setAttribute('data-element-frozen', '0');
                undoButton.removeAttribute('disabled');
                undoButton.setAttribute('name', undoButton.getAttribute('data-name'));
                undoButton.closest('[data-item-classification="element"]').setAttribute('data-element-frozen', '0');

                addDirty();

                this._parentValueChanged(id, value);
            };

            // Clear unload on form reload
            form._parentReload = form.reload;
            form.reload = function(button) {
                var ct = currenttab;
                M.util.js_pending(pendingKey + '_reload');

                this.ajaxSubmit(button, true).then(function() {
                    var parent = formDom.parentElement;

                    // Ensure new form has been added to the DOM before calling init
                    // Otherwise we'll be initing the old form again
                    var observerCallback = function() {
                        init(ct, true);
                        observer.disconnect();
                        M.util.js_complete(pendingKey + '_reload');
                    };
                    var observer = new MutationObserver(observerCallback);
                    observer.observe(parent, {
                        childList: true
                    });
                });
            };
        });

        // Form has been set up once these promises have been resolved, so clear behat flag.
        Promise.all([stringsLoad, formLoad]).then(function() {
            M.util.js_complete(pendingKey);
        });
    };

    return {
        init: init
    };
});