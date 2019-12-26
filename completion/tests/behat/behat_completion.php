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
 * Completion steps definitions.
 *
 * @package    core_completion
 * @category   test
 * @copyright  2013 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode;

/**
 * Steps definitions to deal with course and activities completion.
 *
 * @package    core_completion
 * @category   test
 * @copyright  2013 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_completion extends behat_base {

    /**
     * Checks that the specified user has completed the specified activity of the current course.
     *
     * @Then /^"(?P<user_fullname_string>(?:[^"]|\\")*)" user has completed "(?P<activity_name_string>(?:[^"]|\\")*)" activity$/
     * @param string $userfullname
     * @param string $activityname
     */
    public function user_has_completed_activity($userfullname, $activityname) {
        \behat_hooks::set_step_readonly(false);

        // Will throw an exception if the element can not be hovered.
        $titleliteral = behat_context_helper::escape($userfullname . ", " . $activityname . ": Completed");
        $xpath = "//table[@id='completion-progress']" .
            "/descendant::span[contains(@class, 'sr-only') and contains(., $titleliteral)]";

        $this->execute("behat_completion::go_to_the_current_course_activity_completion_report");
        $this->wait_for_pending_js();
        $this->execute("behat_general::should_exist",
            array($this->escape($xpath), "xpath_element")
        );
    }

    /**
     * Checks that the specified user has not completed the specified activity of the current course.
     *
     * @Then /^"(?P<user_fullname_string>(?:[^"]|\\")*)" user has not completed "(?P<activity_name_string>(?:[^"]|\\")*)" activity$/
     * @param string $userfullname
     * @param string $activityname
     */
    public function user_has_not_completed_activity($userfullname, $activityname) {
        \behat_hooks::set_step_readonly(false);

        // Will throw an exception if the element can not be hovered.
        $titleliteral = behat_context_helper::escape($userfullname . ", " . $activityname . ": Not completed");
        $xpath = "//table[@id='completion-progress']" .
            "/descendant::span[contains(@class, 'sr-only') and contains(., $titleliteral)]";

        $this->execute("behat_completion::go_to_the_current_course_activity_completion_report");
        $this->wait_for_pending_js();
        $this->execute("behat_general::should_exist", array($this->escape($xpath), "xpath_element"));
    }

    /**
     * Goes to the current course activity completion report.
     *
     * @Given /^I go to the current course activity completion report$/
     */
    public function go_to_the_current_course_activity_completion_report() {
        $completionnode = get_string('pluginname', 'report_progress');
        $reportsnode = get_string('courseadministration') . ' > ' . get_string('reports');

        $this->execute("behat_navigation::i_navigate_to_node_in", array($completionnode, $reportsnode));
    }

    /**
     * Toggles site-wide completion tracking
     *
     * @When /^completion tracking is "(?P<completion_status_string>([Ee]nabled|[Dd]isabled)*)" site\-wide$/
     * @param string $completionstatus
     */
    public function completion_is_toggled_sitewide($completionstatus) {

        $toggle = (strtolower($completionstatus) == 'enabled') ? '1' : '';

        $this->execute('behat_auth::i_log_in_as', 'admin');
        $this->execute("behat_general::i_am_on_homepage");
        $this->execute("behat_general::i_click_on", array('Advanced features', 'link'));
        $this->execute("behat_forms::i_set_the_field_to", array("Enable completion tracking", "{$toggle}"));
        $this->execute("behat_forms::press_button", "Save changes");
        $this->execute('behat_auth::i_log_out');
    }

    /**
     * Toggles completion tracking for course being in the course page.
     *
     * @When /^completion tracking is "(?P<completion_status_string>Enabled|Disabled)" in current course$/
     * @param string $completionstatus The status, enabled or disabled.
     */
    public function completion_is_toggled_in_course($completionstatus) {

        $toggle = strtolower($completionstatus) == 'enabled' ? get_string('yes') : get_string('no');

        // Go to course editing.
        $this->execute("behat_general::click_link", get_string('editsettings'));

        // Expand all the form fields.
        $this->execute("behat_forms::i_expand_all_fieldsets");

        // Enable completion.
        $this->execute("behat_forms::i_set_the_field_to",
            array(get_string('enablecompletion', 'completion'), $toggle));

        // Save course settings.
        $this->execute("behat_forms::press_button", get_string('savechangesanddisplay'));
    }

    /**
     * Checks if the activity with specified name is maked as complete.
     *
     * @Given /^the "(?P<activityname_string>(?:[^"]|\\")*)" "(?P<activitytype_string>(?:[^"]|\\")*)" activity with "(manual|auto)" completion should be marked as complete$/
     */
    public function activity_marked_as_complete($activityname, $activitytype, $completiontype) {
        if ($completiontype == "manual") {
            $imgalttext = get_string("completion-alt-manual-y", 'core_completion', $activityname);
        } else {
            $imgalttext = get_string("completion-alt-auto-y", 'core_completion', $activityname);
        }
        $activityxpath = "//li[contains(concat(' ', @class, ' '), ' modtype_" . strtolower($activitytype) . " ')]";
        $activityxpath .= "[descendant::*[contains(text(), '" . $activityname . "')]]";

        $xpathtocheck = "//span[contains(@class, 'sr-only') and contains(., '$imgalttext')]";
        $this->execute("behat_general::should_exist_in_the",
            array($xpathtocheck, "xpath_element", $activityxpath, "xpath_element")
        );

    }

    /**
     * Checks if the activity with specified name is maked as not complete.
     *
     * @Given /^the "(?P<activityname_string>(?:[^"]|\\")*)" "(?P<activitytype_string>(?:[^"]|\\")*)" activity with "(manual|auto)" completion should be marked as not complete$/
     */
    public function activity_marked_as_not_complete($activityname, $activitytype, $completiontype) {
        if ($completiontype == "manual") {
            $imgalttext = get_string("completion-alt-manual-n", 'core_completion', $activityname);
        } else {
            $imgalttext = get_string("completion-alt-auto-n", 'core_completion', $activityname);
        }
        $activityxpath = "//li[contains(concat(' ', @class, ' '), ' modtype_" . strtolower($activitytype) . " ')]";
        $activityxpath .= "[descendant::*[contains(text(), '" . $activityname . "')]]";

        $xpathtocheck = "//span[contains(@class, 'sr-only') and contains(., '$imgalttext')]";
        $this->execute("behat_general::should_exist_in_the",
            array($xpathtocheck, "xpath_element", $activityxpath, "xpath_element")
        );
    }

    /**
     * Add completion records for the specified users and courses
     *
     * @Given /^the following courses are completed:$/
     * @throws Exception
     * @throws coding_exception
     */
    public function the_following_courses_are_completed(TableNode $table) {
        global $DB;

        $required = array(
            'user',
            'course', // Course shortname
            'timecompleted',
        );
        $optional = array(
            'timeenrolled',
            'timestarted',
        );
        $datevalues = array('timecompleted', 'timeenrolled', 'timestarted');

        $data = $table->getHash();
        $firstrow = reset($data);

        // Check required fields are present.
        foreach ($required as $reqname) {
            if (!isset($firstrow[$reqname])) {
                throw new Exception('Course completions require the field '.$reqname.' to be set');
            }
        }

        foreach ($data as $row) {
            // Copy values, ready to pass on to the generator.
            $record = array();
            foreach ($row as $fieldname => $value) {
                if (in_array($fieldname, $required)) {
                    $record[$fieldname] = $value;
                } else if (in_array($fieldname, $optional)) {
                    $record[$fieldname] = $value;
                } else {
                    throw new Exception('Unknown field '.$fieldname.' in course completion');
                }
            }

            if (!$userid = $DB->get_field('user', 'id', array('username' => $record['user']))) {
                throw new Exception('Unknown user '. $record['user']);
            }
            if (!$courseid = $DB->get_field('course', 'id', array('shortname' => $record['course']))) {
                throw new Exception('Unknown course '. $record['course']);
            }

            foreach($datevalues as $item) {
                $convertkey = isset($record[$item]) ? $item : 'timecompleted';
                switch(strtolower($record[$convertkey])) {
                    case 'today':
                        $record[$item] = time();
                        break;

                    case 'tomorrow':
                        $record[$item] = strtotime("+1 day");
                        break;

                    case 'yesterday':
                        $record[$item] = strtotime("-1 day");
                        break;

                    case 'last week':
                        $record[$item] = strtotime("-1 week");
                        break;

                    case 'last month':
                        $record[$item] = strtotime("-1 month");
                        break;

                    default:
                        $record[$item] = $record[$convertkey];
                }
            }

            $params = array(
                'userid' => $userid,
                'course' => $courseid,
                'timeenrolled' => $record['timeenrolled'],
                'timestarted' => $record['timestarted'],
                'timecompleted' => $record['timecompleted'],
                'reaggregate' => 0,
                'status' => COMPLETION_STATUS_COMPLETEVIARPL,
                'rplgrade' => 100,
            );

            $existing = $DB->get_record('course_completions', array('userid' => $userid, 'course' => $courseid), '*', IGNORE_MISSING);
            if ($existing) {
                $params['id'] = $existing->id;
                $DB->update_record('course_completions', $params);
            }
            else {
                $DB->insert_record('course_completions', $params);
            }
        }

        // Purge the completion caches
        completion_info::purge_progress_caches();
    }

    /**
     * When viewing the course completion report this marks the given user complete by the given role
     *
     * It is expected that the current user holds the given role, otherwise this won't work.
     *
     * @see behat_general::i_click_on
     *
     * @Given /^I mark "(?P<fullname>(?:[^"]|\\")*)" complete by "(?P<activity_name_string>(?:[^"]|\\")*)" in the course completion report$/
     * @param string $fullname
     * @param string $role
     */
    public function i_mark_user_complete_by_role_in_the_course_completion_report(string $fullname, string $role) {

        // Confirm that the navbar looks correct, we need to be on the course completion report interface.
        $this->execute('behat_general::should_exist', ['//div[@id="page-navbar"]//ol[contains(., "ReportsCourse completion")]', 'xpath_element']);

        $xpath_fullname = behat_context_helper::escape($fullname);
        $xpath_role = behat_context_helper::escape($role);
        $xpath_title = behat_context_helper::escape(get_string('clicktomarkusercomplete', 'report_completion'));

        $xpath = "//table[@id='completion-progress']//th/a[.={$xpath_fullname}]/ancestor::tr/td[count(//table[@id='completion-progress']/thead/tr/th[.={$xpath_role}]/preceding-sibling::th)+1]/a[@title={$xpath_title}]";
        $method = 'behat_general::i_click_on';
        $this->execute($method, [$xpath, 'xpath_element']);
    }

    /**
     * When viewing the course completion report this marks the given user complete via RPL with the given note.
     *
     * @see behat_forms::i_set_the_field_to
     * @see behat_general::i_click_on
     *
     * @Given /^I mark "(?P<fullname>(?:[^"]|\\")*)" complete by RPL with "(?P<note>(?:[^"]|\\")*)" in the course completion report$/
     * @param string $fullname
     * @param string $note
     */
    public function i_mark_user_complete_by_rpl_in_the_course_completion_report(string $fullname, string $note) {

        // Confirm that the navbar looks correct, we need to be on the course completion report interface.
        $this->execute('behat_general::should_exist', ['//div[@id="page-navbar"]//ol[contains(., "ReportsCourse completion")]', 'xpath_element']);

        $xpath_fullname = behat_context_helper::escape($fullname);
        $xpath_title = behat_context_helper::escape(get_string('recognitionofpriorlearning', 'core_completion'));

        $xpath = "//table[@id='completion-progress']//th/a[.={$xpath_fullname}]/ancestor::tr/td[count(//table[@id='completion-progress']/thead/tr/th[.={$xpath_title}]/preceding-sibling::th)+3]/a[contains(@class, 'rpledit')]";
        // Click to activate.
        $this->execute('behat_general::i_click_on', [$xpath, 'xpath_element']);
        $this->execute('behat_forms::i_set_the_field_to', ['rplinput', $note]);
        // Click again to save.
        $this->execute('behat_general::i_click_on', [$xpath, 'xpath_element']);
    }
}
