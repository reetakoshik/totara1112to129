/*
 * This file is part of Totara LMS
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
 * @author  Simon Player <simon.player@totaralearning.com>
 * @package availability_language
 */

/**
 * JavaScript for form editing language conditions.
 *
 * @module moodle-availability_language-form
 */
M.availability_language = M.availability_language || {};

/**
 * @class M.availability_language.form
 * @extends M.core_availability.plugin
 */
M.availability_language.form = Y.Object(M.core_availability.plugin);

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Array} standardFields Array of objects with .field, .display
 */
M.availability_language.form.initInner = function(standardFields) {
    this.standardFields = standardFields;
};

/**
 * Create the html.
 *
 * @method getNode
 * @param {Object} json The availability data
 * @return {Object} node The DOM node
 */
M.availability_language.form.getNode = function(json) {
    // Create HTML structure.
    var html = '<span class="availability-group"><label><span class="p-r-1">' +
        M.util.get_string('conditiontitle', 'availability_language') + '</span> ' +
        '<select name="field" class="custom-select">' +
        '<option value="choose">' + M.util.get_string('choosedots', 'moodle') + '</option>';
    var fieldInfo;
    for (var i = 0; i < this.standardFields.length; i++) {
        fieldInfo = this.standardFields[i];
        html += '<option value="' + fieldInfo.field + '">' + fieldInfo.display + '</option>';
    }

    html += '</select></label>';

    var node = Y.Node.create('<span class="form-inline">' + html + '</span>');

    // Set initial values if specified.
    if (json.lang !== undefined &&
        node.one('select[name=field] > option[value=' + json.lang + ']')) {
        node.one('select[name=field]').set('value', json.lang);
    }

    // Add event handlers (first time only).
    if (!M.availability_language.form.addedEvents) {
        M.availability_language.form.addedEvents = true;
        var updateForm = function() {
            M.core_availability.form.update();
        };
        var root = Y.one('.availability-field');
        root.delegate('change', function() {
            updateForm(this);
        }, '.availability_language select');
        root.delegate('change', function() {
            updateForm(this);
        }, '.availability_language input[name=value]');
    }

    return node;
};

/**
 * Sets the field values
 *
 * @param {Object} value
 * @param {Object} node
 */
M.availability_language.form.fillValue = function(value, node) {
    // Set field.
    value.lang = node.one('select[name=field]').get('value');
};

/**
 * Check for errors
 *
 * @param {Array} errors
 * @param {Object} node
 */
M.availability_language.form.fillErrors = function(errors, node) {
    var value = {};
    this.fillValue(value, node);

    // Check language.
    if (value.lang === undefined || value.lang == 'choose') {
        errors.push('availability_language:error_selectfield');
    }
};