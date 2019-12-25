<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Cohort related management functions, this file needs to be included manually.
 *
 * @package    core_cohort
 * @copyright  2010 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

class cohort_edit_form extends moodleform {

    /**
     * Define the cohort edit form
     */
    public function definition() {
        global $CFG, $DB, $COHORT_ALERT;

        $mform = $this->_form;
        $editoroptions = $this->_customdata['editoroptions'];
        $cohort = $this->_customdata['data'];
        $placeholder = get_string('datepickerlongyearplaceholder', 'totara_core');
        $hint = get_string('dateformatlongyearhint', 'totara_cohort', $placeholder);

        $mform->addElement('text', 'name', get_string('name', 'cohort'), 'maxlength="254" size="50"');
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);

        $options = $this->get_category_options($cohort->contextid);
        $mform->addElement('select', 'contextid', get_string('context', 'role'), $options);

        $mform->addElement('text', 'idnumber', get_string('idnumber', 'cohort'), 'maxlength="254" size="50"');
        $mform->setType('idnumber', PARAM_TEXT);
        $mform->setDefault('idnumber', totara_cohort_next_automatic_id());

        // Totora: ignore Moodle visibility hacks TL-7124.
        /*
        $mform->addElement('advcheckbox', 'visible', get_string('visible', 'cohort'));
        $mform->setDefault('visible', 1);
        $mform->addHelpButton('visible', 'visible', 'cohort');
        */

        if (!$cohort->id) {
            $mform->addElement('select', 'cohorttype', get_string('type', 'totara_cohort'), cohort::getCohortTypes());
            $mform->addHelpButton('cohorttype', 'type', 'totara_cohort');
        }

        $mform->addElement('editor', 'description_editor', get_string('description', 'cohort'), null, $editoroptions);
        $mform->setType('description_editor', PARAM_RAW);

        $mform->addElement('date_selector', 'startdate', get_string('startdate', 'totara_cohort'), array('optional' => true));
        $mform->addHelpButton('startdate', 'startdatelimited', 'totara_cohort');

        $mform->addElement('date_selector', 'enddate', get_string('enddate', 'totara_cohort'), array('optional' => true));
        $mform->addHelpButton('enddate', 'enddatelimited', 'totara_cohort');

        // alert options
        $alertoptions = get_config('cohort', 'alertoptions');
        if ($alertoptions == '') {
            $alertoptions = array();
        } else {
           $alertoptions = explode(',', $alertoptions);
           $alertoptions = array_combine($alertoptions, $alertoptions);
        }
        foreach ($COHORT_ALERT as $ocode => $oval) {
            if (in_array($ocode, $alertoptions)) {
                $alertoptions[$ocode] = $oval;
            }
        }
        if (!empty($alertoptions)) {
            $mform->addElement(
                'select',
                'alertmembers',
                get_string('alertmembers', 'totara_cohort'),
                $alertoptions
            );
            $mform->addHelpButton('alertmembers', 'alertmembers', 'totara_cohort');
        } else {
            $mform->addElement('hidden', 'alertmembers', COHORT_ALERT_NONE);
        }
        unset($alertoptions);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        if (isset($this->_customdata['returnurl'])) {
            $mform->addElement('hidden', 'returnurl', $this->_customdata['returnurl']->out_as_local_url());
            $mform->setType('returnurl', PARAM_LOCALURL);
        }

        // Display offical Cohort Tags
        if (!empty($CFG->usetags)) {
            $mform->addElement('header', 'tagshdr', get_string('tags', 'tag'));
            $mform->addElement('tags', 'tags', get_string('tags'), array('itemtype' => 'cohort', 'component' => 'core'));
        }

        $this->add_action_buttons();

        $this->set_data($cohort);
    }

    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        $idnumber = trim($data['idnumber']);
        if ($idnumber === '') {
            // Fine, empty is ok.

        } else if ($data['id']) {
            $current = $DB->get_record('cohort', array('id'=>$data['id']), '*', MUST_EXIST);
            if ($current->idnumber !== $idnumber) {
                if ($DB->record_exists('cohort', array('idnumber'=>$idnumber))) {
                    $errors['idnumber'] = get_string('duplicateidnumber', 'cohort');
                }
            }

        } else {
            if ($DB->record_exists('cohort', array('idnumber'=>$idnumber))) {
                $errors['idnumber'] = get_string('duplicateidnumber', 'cohort');
            }
        }

        // Enforce start date before finish date
        if ($data['startdate'] > $data['enddate'] && $data['enddate']) {
            $errstr = get_string('error:startafterfinish','totara_cohort');
            $errors['startdate'] = $errstr;
            $errors['enddate'] = $errstr;
            unset($errstr);
        }
        return $errors;
    }

    protected function get_category_options($currentcontextid) {
        global $CFG;
        require_once($CFG->libdir. '/coursecatlib.php');
        $displaylist = coursecat::make_categories_list('moodle/cohort:manage');
        $options = array();
        $syscontext = context_system::instance();
        if (has_capability('moodle/cohort:manage', $syscontext)) {
            $options[$syscontext->id] = $syscontext->get_context_name();
        }
        foreach ($displaylist as $cid=>$name) {
            $context = context_coursecat::instance($cid);
            $options[$context->id] = $name;
        }
        // Always add current - this is not likely, but if the logic gets changed it might be a problem.
        if (!isset($options[$currentcontextid])) {
            $context = context::instance_by_id($currentcontextid, MUST_EXIST);
            $options[$context->id] = $syscontext->get_context_name();
        }
        return $options;
    }
}

