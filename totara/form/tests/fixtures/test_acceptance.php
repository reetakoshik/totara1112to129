<?php
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
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @package totara_form
 */

/**
 * This file facilitates test example forms.
 */

require_once('../../../../config.php');

$displaydebugging = false;
if (!defined('BEHAT_SITE_RUNNING') || !BEHAT_SITE_RUNNING) {
    if (debugging()) {
        $displaydebugging = true;
    } else {
        throw new coding_exception('Invalid access detected.');
    }
}

$form_type = optional_param('form_select', false, PARAM_RAW);

require_login();

// Only admins can access this page.
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$classes = \core_component::get_namespace_classes('form\testform', '\totara_form\form\testform\form');
if ($form_type && !in_array($form_type, $classes)) {
    throw new coding_exception('Invalid form class passed.');
}
$formclass = $form_type;
unset($form_type);

$PAGE->set_url(new moodle_url('/totara/form/tests/fixtures/test_acceptance.php'));
$PAGE->set_context($context);

if ($formclass) {
    $currentdata = call_user_func([$formclass, 'get_current_data_for_test']);
    $currentdata['form_select'] = $formclass;
    $params = call_user_func([$formclass, 'get_params_for_test']);
    /** @var \totara_form\form\testform\form $form */
    $form = new $formclass($currentdata, $params);
}

$options = array();
foreach ($classes as $class) {
    $name = s(call_user_func([$class, 'get_form_test_name']));
    $value = s($class);
    $options[$value] = $name.' ['.$value.']';
}
$select = new single_select($PAGE->url, 'form_select', $options, $formclass);
$select->method = 'POST';
$select->label = 'Test form';

$PAGE->set_title('Totara form acceptance tests');
$PAGE->set_heading('Totara form acceptance tests');
$PAGE->set_pagelayout('noblocks');
$PAGE->set_button($OUTPUT->render($select));

echo $OUTPUT->header();
echo $OUTPUT->heading('Form acceptance testing page');

if ($displaydebugging) {
    // This is intentionally hard coded - this page is not in the navigation and should only ever be used by developers.
    $msg = 'This page only exists to facilitate acceptance testing, if you are here for any other reason please file an improvement request.';
    echo $OUTPUT->notification($msg, 'notifysuccess');
    // We display a developer debug message as well to ensure that this doesn't not get shown during behat testing.
    debugging('This is a developer resource, please contact your system admin if you have arrived here by mistake.', DEBUG_DEVELOPER);
}

if (isset($form)) {
    if ($form->is_cancelled()) {

        echo $OUTPUT->notification('The form has been cancelled', 'notifysuccess');

    } else if ($data = $form->get_data()) {

        // Add to the data array any files that were used.
        foreach ($form->get_files() as $elname => $list) {
            $files = array();
            if ($list !== null) {
                foreach ($list as $file) {
                    /** @var \stored_file $file */
                    if ($file->is_directory()) {
                        $path = $file->get_filepath();
                    } else {
                        $path = $file->get_filepath() . $file->get_filename();
                    }
                    $files[] = $path;
                }
            }
            $data->$elname = join(', ', $files);
        }

        echo $OUTPUT->notification('The form has been submit', 'notifysuccess');
        $data = call_user_func([$form, 'process_after_submit'], $data);
        $table = new html_table();
        $table->id = 'form_results';
        $table->caption = 'The following form values were submit';
        $table->head = ['Name', 'Value', 'Post data'];
        $table->data = [];
        foreach ($data as $name => $value) {
            $postdata = 'No post data';
            if (array_key_exists($name, $_POST)) {
                if (empty($_POST[$name])) {
                    $postdata = 'Provided but empty';
                } else {
                    $postdata = 'Data present, type '.gettype($_POST[$name]);
                }
            }
            // Wrap the value with angle quotes to make behat "contains" matching accurate in tests.
            $table->data[] = [$name, '«' . $formclass::format_for_display($name, $value) . '»', $postdata];
        }
        echo $OUTPUT->render($table);
        echo $OUTPUT->single_button(new moodle_url($PAGE->url, ['form_select' => $formclass]), 'Reset');
        echo $OUTPUT->single_button($PAGE->url, 'Start again');

    } else if ($form->initialise_in_js()) {

        $formclass = get_class($form);

        $buttons = $OUTPUT->single_button(new moodle_url($PAGE->url, ['form_select' => $formclass]), 'Reset');
        $buttons .= $OUTPUT->single_button($PAGE->url, 'Start again');

        $formclass = json_encode($formclass);
        $buttons = json_encode($buttons);

        $js = <<<EOD
require(["totara_form/form"], function(Form) {

    function formatResultsAsHTML(results) {
        var lines = [
                '<table id="form_results" class="generaltable">',
                '<caption>The following form values were submit</caption>',
                '<thead><tr><th>Name</th><th>Value</th></tr></thead>',
                '<tbody>'
            ],
            i,
            value;
        for (var i in results) {
            if (!results.hasOwnProperty(i)) {
                continue;
            }
            value = results[i];
            if (value === null) {
                value = '--null--';
            } else if ($.isArray(value)) {
                if (value.length === 0) {
                    value = '[ ]';
                } else {
                    value = "[ '" + value.join("' , '") + "' ]";
                 }
            }
            lines.push('<tr><td>' + i + '</td><td>«' + encodeText(value) + '»</td></tr>');
        }
        lines.push('</tbody></table>');
        lines.push($buttons);
        return lines.join();
    }

    function encodeText(text) {
        return $('<div/>').text(text).html();
    }

    var promise = Form.load({$formclass}, "", {}, "formtarget");
    if (!promise) {
        alert('Initialisation failed - there have been errors.');
    }
    promise.done(function(outcome, formid) {
        if (Form.getFormInstance(formid) === null) {
            alert('Load returned before form initialised');
        }
        if (outcome !== 'display') {
            alert('Failed to load the form via JS');
            return;
        }
        Form.addActionListeners(formid, {
            submitted: function(form, data) {
                Form.debug('Handling the successful form submission', Form, Form.LOGLEVEL.info);
                var container = $('#' + formid).parent(),
                    html = formatResultsAsHTML(data.data);
                container.empty();
                container.html(html);
            },
            cancelled: function() {
                alert('Form has been cancelled');
            }
        });
    });
});
EOD;

        $PAGE->requires->js_amd_inline($js);
        echo $OUTPUT->heading('Form: '.$form->get_form_test_name(), 2);
        echo "<div id='formtarget'>The form should load here.</div>";

    } else {

        if ($form->is_reloaded()) {
            echo $OUTPUT->notification('The form has been reloaded', 'notifysuccess');
        }
        echo $OUTPUT->heading('Form: '.$form->get_form_test_name(), 2);
        echo $form->render();

    }
}

echo $OUTPUT->footer();