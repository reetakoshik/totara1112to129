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
 * mod_workshop data generator.
 *
 * @package    mod_workshop
 * @category   test
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * mod_workshop data generator class.
 *
 * @package    mod_workshop
 * @category   test
 * @copyright  2013 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_workshop_generator extends testing_module_generator {

    public function create_instance($record = null, array $options = null) {
        global $CFG;
        require_once($CFG->libdir.'/filelib.php');

        $workshopconfig = get_config('workshop');

        // Add default values for workshop.
        $record = (array)$record + array(
            'strategy' => $workshopconfig->strategy,
            'grade' => $workshopconfig->grade,
            'gradinggrade' => $workshopconfig->gradinggrade,
            'gradedecimals' => $workshopconfig->gradedecimals,
            'nattachments' => 1,
            'submissionfiletypes' => null,
            'maxbytes' => $workshopconfig->maxbytes,
            'latesubmissions' => 0,
            'useselfassessment' => 0,
            'overallfeedbackmode' => 1,
            'overallfeedbackfiles' => 0,
            'overallfeedbackfiletypes' => null,
            'overallfeedbackmaxbytes' => $workshopconfig->maxbytes,
            'useexamples' => 0,
            'examplesmode' => $workshopconfig->examplesmode,
            'submissionstart' => 0,
            'submissionend' => 0,
            'phaseswitchassessment' => 0,
            'assessmentstart' => 0,
            'assessmentend' => 0,
        );
        if (!isset($record['gradecategory']) || !isset($record['gradinggradecategory'])) {
            require_once($CFG->libdir.'/gradelib.php');
            $courseid = is_object($record['course']) ? $record['course']->id : $record['course'];
            $gradecategories = grade_get_categories_menu($courseid);
            reset($gradecategories);
            $defaultcategory = key($gradecategories);
            $record += array(
                'gradecategory' => $defaultcategory,
                'gradinggradecategory' => $defaultcategory
            );
        }
        if (!isset($record['instructauthorseditor'])) {
            $record['instructauthorseditor'] = array(
                'text' => 'Instructions for submission '.($this->instancecount+1),
                'format' => FORMAT_MOODLE,
                'itemid' => file_get_unused_draft_itemid()
            );
        }
        if (!isset($record['instructreviewerseditor'])) {
            $record['instructreviewerseditor'] = array(
                'text' => 'Instructions for assessment '.($this->instancecount+1),
                'format' => FORMAT_MOODLE,
                'itemid' => file_get_unused_draft_itemid()
            );
        }
        if (!isset($record['conclusioneditor'])) {
            $record['conclusioneditor'] = array(
                'text' => 'Conclusion '.($this->instancecount+1),
                'format' => FORMAT_MOODLE,
                'itemid' => file_get_unused_draft_itemid()
            );
        }

        return parent::create_instance($record, (array)$options);
    }

    /**
     * Generates a submission authored by the given user.
     *
     * @param int $workshopid Workshop instance id.
     * @param int $authorid Author user id.
     * @param stdClass|array $options Optional explicit properties.
     * @return int The new submission id.
     */
    public function create_submission($workshopid, $authorid, $options = null) {
        global $DB;

        $timenow = time();
        $options = (array)$options;

        $record = $options + array(
            'workshopid' => $workshopid,
            'example' => 0,
            'authorid' => $authorid,
            'timecreated' => $timenow,
            'timemodified' => $timenow,
            'title' => 'Generated submission',
            'content' => 'Generated content',
            'contentformat' => FORMAT_MARKDOWN,
            'contenttrust' => 0,
        );

        $id = $DB->insert_record('workshop_submissions', $record);

        return $id;
    }

    /**
     * Generates an allocation of the given submission for peer-assessment by the given user
     *
     * @param int $submissionid Submission id.
     * @param int $reviewerid Reviewer's user id.
     * @param stdClass|array $options Optional explicit properties.
     * @return int The new assessment id.
     */
    public function create_assessment($submissionid, $reviewerid, $options = null) {
        global $DB;

        $timenow = time();
        $options = (array)$options;

        $record = $options + array(
            'submissionid' => $submissionid,
            'reviewerid' => $reviewerid,
            'weight' => 1,
            'timecreated' => $timenow,
            'timemodified' => $timenow,
            'grade' => null,
        );

        $id = $DB->insert_record('workshop_assessments', $record);

        return $id;
    }

    /**
     * @param $assessmentid
     * @param $dimensionid - for the grading dimension. Use the generator method get_accumulative_dimensionid()
     *   if you are not concerned with the actual dimension will do.
     * @param $grade
     * @return int
     * @throws dml_exception
     */
    public function create_grade($assessmentid, $dimensionid, $grade, $comment = null) {
        global $DB;

        $record = new stdClass();
        $record->assessmentid = $assessmentid;
        $record->strategy = 'accumulative';
        $record->dimensionid = $dimensionid;
        $record->grade = $grade;
        $record->peercomment = empty($comment) ? 'Test comment' : $comment;
        $record->peercommentformat = FORMAT_MOODLE;

        return $DB->insert_record('workshop_grades', $record);
    }

    /**
     * If you need a grading dimension id to create a grade, this will return one if it exists
     * for a given workshop, or create one.
     *
     * @param $workshop
     * @return int
     * @throws dml_exception
     */
    public function get_accumulative_dimensionid($workshop) {
        global $DB;

        $dimensionid = $DB->get_field('workshopform_accumulative', 'id', ['workshopid' => $workshop->id]);

        if ($dimensionid) {
            return $dimensionid;
        }

        $record = new stdClass();
        $record->workshopid = $workshop->id;
        $record->sort = 1;
        $record->description = '';
        $record->descriptionformat = FORMAT_MOODLE;
        $record->grade = 10;
        $record->weight = 1;

        return $DB->insert_record('workshopform_accumulative', $record);
    }
}
