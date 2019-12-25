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
 * Defines the form for editing question categories.
 *
 * @package    moodlecore
 * @subpackage questionbank
 * @copyright  2007 Jamie Pratt me@jamiep.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');


/**
 * Form for editing qusetions categories (name, description, etc.)
 *
 * @copyright  2007 Jamie Pratt me@jamiep.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_category_edit_form extends moodleform {

    protected function definition() {
        global $CFG, $DB;
        $mform    = $this->_form;

        $contexts   = $this->_customdata['contexts'];
        $currentcat   = $this->_customdata['currentcat'];

        $mform->addElement('header', 'categoryheader', get_string('addcategory', 'question'));

        $questioncategoryel = $mform->addElement('questioncategory', 'parent', get_string('parentcategory', 'question'),
                    array('contexts'=>$contexts, 'top'=>true, 'currentcat'=>$currentcat, 'nochildrenof'=>$currentcat));
        $mform->setType('parent', PARAM_SEQUENCE);
        if (question_is_only_toplevel_category_in_context($currentcat)) {
            $mform->hardFreeze('parent');
        }
        $mform->addHelpButton('parent', 'parentcategory', 'question');

        $mform->addElement('text', 'name', get_string('name'),'maxlength="254" size="50"');
        $mform->setDefault('name', '');
        $mform->addRule('name', get_string('categorynamecantbeblank', 'question'), 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('editor', 'info', get_string('categoryinfo', 'question'),
                array('rows' => 10), array('noclean' => 1));
        $mform->setDefault('info', '');
        $mform->setType('info', PARAM_RAW);

        $this->add_action_buttons(false, get_string('addcategory', 'question'));

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
    }

    public function set_data($current) {
        if (is_object($current)) {
            $current = (array) $current;
        }
        if (!empty($current['info'])) {
            $current['info'] = array('text' => $current['info'],
                    'infoformat' => $current['infoformat']);
        } else {
            $current['info'] = array('text' => '', 'infoformat' => FORMAT_HTML);
        }
        parent::set_data($current);
    }

    /**
     * Totara - Validation to pick up parent/child category conflicts (TL-7317).
     *
     * @param array $data to be validated
     * @param array $files to be validated (required by parent but not currently validated in this function)
     *
     * @return array containing error messages (empty array if there were no errors)
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!empty($data['parent'])) {
            list($parentid) = explode(',', $data['parent']);
            $parentsarray = array();
            if ($parenterror = $this->check_for_invalid_question_category_parents($data['name'], $data['id'], $parentid, $parentsarray)) {
                $errors['parent'] = $parenterror;
            }
        }

        return $errors;
    }

    /**
     * Totara - Function for validation to pick up parent/child category conflicts (TL-7317).
     *
     * Recursively checks that a proposed new parent is not already a child of the record within
     * question_categories table.
     * Will return error string if a conflict is found between child and proposed parent, so that
     * an exception or similar can be thrown.
     * Will also return error string if somewhere in the chain of parents being checked,
     * a parent field is null or refers to non-existent record.
     *
     * @param string $name - name of the category being updated, for adding to error message
     * @param int $childid - id of the record to have its parent updated
     * @param int $parentid - id of the proposed parent
     * @param array &$parentsarray - reference of array showing parent records we've already cycled through
     *
     * @return mixed bool false if no invalid parents found, or error message string if there are
     */
    protected function check_for_invalid_question_category_parents($name, $childid, $parentid, &$parentsarray) {
        global $DB;

        if ($parentid == 0) {
            // The top-level parent is 0, so there was no loop.
            return false;
        }

        if ($childid == $parentid) {
            // Simple instance of a loop.
            return get_string('movecategoryparentconflict', 'error', $name);
        }

        if (!$record = $DB->get_record('question_categories', array('id' => $parentid), 'id, parent')) {
            // A parent field refers to a record that does not exist.
            return get_string('parentcategorymissing', 'question');
        }

        $parentsarray[$parentid] = $parentid;

        if (!isset($record->parent)) {
            // This shouldn't happen as parent field in db should have a "not null" restriction.
            return get_string('parentcategorymissing', 'question');
        } else if (isset($parentsarray[$record->parent])) {
            // We've already encountered this parent, there is a pre-existing loop.
            return get_string('parentloopdetected', 'question');
        }

        // if we've got this far, carry on to the parent of this parent until we get a true or false
        return $this->check_for_invalid_question_category_parents($name, $childid, $record->parent, $parentsarray);
    }
}
