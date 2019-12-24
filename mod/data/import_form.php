<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/csvlib.class.php');

class mod_data_import_form extends moodleform {

    function definition() {
        global $CFG;
        $mform =& $this->_form;

        // Start Totara T-14272 changes.
        global $DB, $PAGE;
        $fields = $DB->get_records('data_fields', array('dataid' => $PAGE->activityrecord->id), '', 'name, id, type');
        $structure = array();
        foreach ($fields as $field) {
            $structure[] = '"'.$field->name.'"';
        }
        $html  = html_writer::start_tag('div');
        $html .= html_writer::tag('p', get_string('csvimportfilestructinfo', 'data'), array('class' => "informationbox"));
        $html .= html_writer::start_tag('pre');
        $html .= implode(",", $structure);
        $html .= html_writer::empty_tag('br') . "..." . html_writer::empty_tag('br') . "..." . html_writer::empty_tag('br') . "...";
        $html .= html_writer::end_tag('pre');
        $html .= html_writer::end_tag('div');
        $mform->addElement('static', 'fieldsrequire', '', $html);
        // End Totara T-14272 changes.

        $mform->addElement('filepicker', 'recordsfile', get_string('csvfile', 'data'));

        $delimiters = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'fielddelimiter', get_string('fielddelimiter', 'data'), $delimiters);
        $mform->setDefault('fielddelimiter', 'comma');

        $mform->addElement('text', 'fieldenclosure', get_string('fieldenclosure', 'data'));
        $mform->setType('fieldenclosure', PARAM_CLEANHTML);
        $choices = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('fileencoding', 'mod_data'), $choices);
        $mform->setDefault('encoding', 'UTF-8');

        $submit_string = get_string('submit');
        // data id
        $mform->addElement('hidden', 'd');
        $mform->setType('d', PARAM_INT);

        $this->add_action_buttons(false, $submit_string);
    }
}
