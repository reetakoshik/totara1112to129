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
 * @package availability_time_since_completion
 */

/**
 * JavaScript for form editing time_since_completion conditions.
 *
 * @module moodle-availability_time_since_completion-form
 */
M.availability_time_since_completion = M.availability_time_since_completion || {};

/**
 * @class M.availability_time_since_completion.form
 * @extends M.core_availability.plugin
 */
M.availability_time_since_completion.form = Y.Object(M.core_availability.plugin);

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Array} cms Array of objects containing cmid => name
 */
M.availability_time_since_completion.form.initInner = function(cms) {
    this.cms = cms;
};

/**
 * Create the html.
 *
 * @method getNode
 * @param {Object} json The availability data
 * @return {Object} node The DOM node
 */
M.availability_time_since_completion.form.getNode = function(json) {
    // Create HTML structure.
    var html = '<span class="availability-group form-group">';

    // Time amount.
    html += '<label for="timeamount" class="sr-only">' + M.util.get_string('label_timeamount', 'availability_time_since_completion') + '</label>' +
        '<input name="timeamount" id="timeamount">';

    // Time period.
    html += '<label for="timeperiod" class="sr-only">' + M.util.get_string('label_timeperiod', 'availability_time_since_completion') + '</label>' +
        '<select class="custom-select" name="timeperiod" id="timeperiod" title="' + M.util.get_string('label_timeperiod', 'availability_time_since_completion') + '">' +
        '<option value="3">' + M.util.get_string('option_days', 'availability_time_since_completion') + '</option>' +
        '<option value="4">' + M.util.get_string('option_weeks', 'availability_time_since_completion') + '</option>' +
        '<option value="5">' + M.util.get_string('option_years', 'availability_time_since_completion') + '</option>' +
        '</select>';

    html += '<span class="col-form-label p-r-1"> ' + M.util.get_string('aftercompletion', 'availability_time_since_completion') + ' </span>';

    // Activity.
    html += '<label for="cm" class="sr-only">' + M.util.get_string('label_cm', 'availability_time_since_completion') + '</label>' +
        '<select class="custom-select" name="cm" id="cm" title="' + M.util.get_string('label_cm', 'availability_time_since_completion') + '">' +
        '<option value="0">' + M.util.get_string('choosedots', 'moodle') + '</option>';
    for (var i = 0; i < this.cms.length; i++) {
        var cm = this.cms[i];
        // String has already been escaped using format_string.
        html += '<option value="' + cm.id + '">' + cm.name + '</option>';
    }
    html += '</select>';

    // Completion status.
    html += '<label for="expectedcompletion" class="sr-only">' + M.util.get_string('label_completion', 'availability_time_since_completion') + '</label>' +
        '<select class="custom-select" ' + 'name="expectedcompletion" id="expectedcompletion" title="' + M.util.get_string('label_completion', 'availability_time_since_completion') + '">' +
        '<option value="1">' + M.util.get_string('option_complete', 'availability_time_since_completion') + '</option>' +
        '<option value="2">' + M.util.get_string('option_pass', 'availability_time_since_completion') + '</option>' +
        '<option value="3">' + M.util.get_string('option_fail', 'availability_time_since_completion') + '</option>' +
        '</select>';

    var node = Y.Node.create('<div class="form-inline">' + html + '</div>');

    //
    // Set initial values.
    //
    if (json.cm !== undefined &&
        node.one('select[name=cm] > option[value=' + json.cm + ']')) {
        node.one('select[name=cm]').set('value', json.cm);
    }

    if (json.expectedcompletion !== undefined) {
        node.one('select[name=expectedcompletion]').set('value', json.expectedcompletion);
    }

    // Time unit amount.
    if (json.timeamount) {
        node.one('input[name=timeamount]').set('value', json.timeamount);
    } else {
        node.one('input[name=timeamount]').set('value', '1');
    }

    // Time unit period.
    if (json.timeperiod !== undefined) {
        node.one('select[name=timeperiod]').set('value', json.timeperiod);
    } else {
        node.one('select[name=timeperiod]').set('value', '3');
    }

    // Add event handlers (first time only).
    if (!M.availability_time_since_completion.form.addedEvents) {
        M.availability_time_since_completion.form.addedEvents = true;
        var root = Y.one('.availability-field');

        root.delegate('change', function() {
            // Whichever dropdown changed, just update the form.
            M.core_availability.form.update();
        }, '.availability_time_since_completion select');

        root.delegate('valuechange', function() {
            M.core_availability.form.update();
        }, '.availability_time_since_completion input');

        root.delegate('click', function() {
            M.core_availability.form.update();
        }, '.availability_time_since_completion input[type=checkbox]');
    }

    return node;
};

/**
 * Sets the field values
 *
 * @param {Object} value
 * @param {Object} node
 */
M.availability_time_since_completion.form.fillValue = function(value, node) {
    value.cm = parseInt(node.one('select[name=cm]').get('value'), 10);
    value.expectedcompletion = parseInt(node.one('select[name=expectedcompletion]').get('value'), 10);

    value.timeamount = parseInt(node.one('input[name=timeamount]').get('value'), 10);
    value.timeperiod = parseInt(node.one('select[name=timeperiod]').get('value'), 10);
};

/**
 * Check for errors
 *
 * @param {Array} errors
 * @param {Object} node
 */
M.availability_time_since_completion.form.fillErrors = function(errors, node) {
    var cmid = parseInt(node.one('select[name=cm]').get('value'), 10);
    if (cmid === 0) {
        errors.push('availability_completion:error_selectcmid');
    }

    var expectedcompletion = parseInt(node.one('select[name=expectedcompletion]').get('value'), 10);
    if (((expectedcompletion === 2) || (expectedcompletion === 3))) {
        this.cms.forEach(function(cm) {
            if (cm.id === cmid) {
                if (cm.completiongradeitemnumber === null) {
                    errors.push('availability_time_since_completion:error_selectcmidpassfail');
                }
            }
        });
    }

    // Time amount.
    var timeamount = node.one('input[name=timeamount]').get('value');
    if (timeamount == 0 || timeamount != parseInt(timeamount, 10)) {
        errors.push('availability_time_since_completion:error_selecttimeamountfail');
    }
};
