<?php

require_once(dirname(__FILE__) . '/../config.php');
require_once($CFG->libdir . '/adminlib.php');

$strheading = 'Element Library';
$url = new moodle_url('/elementlibrary/index.php');

// Start setting up the page
admin_externalpage_setup('elementlibrary');
$params = array();
$PAGE->set_context(context_system::instance());
$PAGE->set_url($url);
$PAGE->set_title($strheading);
$PAGE->set_heading($strheading);

echo $OUTPUT->header();

echo $OUTPUT->heading($strheading);

echo $OUTPUT->box_start();
echo $OUTPUT->container('This page contains a set of sample elements used on this site. It can be used to ensure that everything has been correctly themed (remember to check in a right-to-left language too), and for developers to see examples of how to implement particular elements. Developers: if you need an element that is not represented here, add it here first - the idea is to build up a library of all the elements used across the site.');

echo $OUTPUT->container_start();
echo $OUTPUT->heading('Moodle elements', 3);
echo html_writer::start_tag('ul');
echo html_writer::tag('li', html_writer::link(new moodle_url('/elementlibrary/headings.php'), 'Headings'));
echo html_writer::tag('li', html_writer::link(new moodle_url('/elementlibrary/common.php'), 'Common tags'));
echo html_writer::tag('li', html_writer::link(new moodle_url('/elementlibrary/lists.php'), 'Lists'));
echo html_writer::tag('li', html_writer::link(new moodle_url('/elementlibrary/tables.php'), 'Tables'));
echo html_writer::tag('li', html_writer::link(new moodle_url('/elementlibrary/forms.php'), 'Form elements'));
echo html_writer::tag('li', html_writer::link(new moodle_url('/elementlibrary/mform.php'), 'Moodle form elements'));
echo html_writer::tag('li', html_writer::link(new moodle_url('/elementlibrary/tabs.php'), 'Moodle tab bar elements'));
echo html_writer::tag('li', html_writer::link(new moodle_url('/elementlibrary/images.php'), 'Images'));
echo html_writer::tag('li', html_writer::link(new moodle_url('/elementlibrary/notifications.php'), 'Notifications'));
echo html_writer::tag('li', html_writer::link(new moodle_url('/elementlibrary/pagelayouts.php'), 'Page Layouts'));
echo html_writer::end_tag('ul');
echo $OUTPUT->heading('Totara specific elements', 3);
echo html_writer::start_tag('ul');
echo html_writer::tag('li', html_writer::link(new moodle_url('/elementlibrary/toolbar.php'), 'Toolbar'));
echo html_writer::tag('li', html_writer::link(new moodle_url('/elementlibrary/dialogs.php'), 'Dialogs'));
echo html_writer::tag('li', html_writer::link(new moodle_url('/elementlibrary/verticaltabs.php'), 'Vertical tabs'));
echo html_writer::tag('li', html_writer::link(new moodle_url('/elementlibrary/flex_icons.php'), 'Flexible icons'));

// Only display the pattern library if Roots is a parent.
// This is as new patterns will not be available in deprecated
// themes by default (e.g. Standard Totara Responsive). Where
// these patterns are used in core backwards compatiblity will
// be provided for legacy themes.
if ($PAGE->theme->name === 'roots' || in_array('roots', $PAGE->theme->parents)) {
    echo html_writer::tag('li', html_writer::link(new moodle_url('/elementlibrary/pattern_library.php'), 'Pattern library'));
} else {
    // Display a sensible message to the user.
    echo html_writer::tag('li', 'Pattern library (only available in Bootstrap 3 themes extending Roots or Basis)');
}

echo html_writer::end_tag('ul');
echo $OUTPUT->container_end();

echo $OUTPUT->box_end();

echo $OUTPUT->footer();
