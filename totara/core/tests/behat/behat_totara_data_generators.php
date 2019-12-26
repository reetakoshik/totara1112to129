<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_core
 * @category  test
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/helper_generator.php');

use Behat\Gherkin\Node\TableNode as TableNode;
use Behat\Behat\Tester\Exception\PendingException as PendingException;

/**
 * Class to set up quickly a Given environment.
 *
 * Acceptance tests are block-boxed, so this steps definitions should only
 * be used to set up the test environment as we are not replicating user steps.
 *
 * All data generators should be in lib/testing/generator/*, shared between phpunit
 * and behat and they should be called from here, if possible using the standard
 * 'create_$elementname($options)' and if it's not possible (data generators arguments will not be
 * always the same) or the element is not suitable to be a data generator, create a
 * 'process_$elementname($options)' method and use the data generator from there if possible.
 */
class behat_totara_data_generators extends behat_base {
    /**
     * Each component element specifies:
     * - The data generator sufix used.
     * - The required fields.
     * - The mapping between other elements references and database field names.
     * @var array
     */
    protected static $componentelements = array(
        // NOTE: this could be dynamic, but it is not a problem for Totara.
        'mod_facetoface' => array (
            'global rooms' => array(
                'datagenerator' => 'global_room_for_behat',
                'required' => array('name'),
            ),
            'custom rooms' => array(
                'datagenerator' => 'custom_room_for_behat',
                'required'      => array('name'),
            ),
            'global assets' => array(
                'datagenerator' => 'global_asset_for_behat',
                'required' => array('name')
            ),
        ),
        'totara_core' => array(
            'custom profile fields' => array(
                'datagenerator' => 'custom_profile_field',
                'required' => array('datatype'),
            ),
            'custom course fields' => array(
                'datagenerator' => 'custom_course_field',
                'required' => array('datatype'),
            ),
            'custom program fields' => array(
                'datagenerator' => 'custom_program_field',
                'required' => array('datatype'),
            ),
            'custom profile field assignments' => array(
                'datagenerator' => 'profile_custom_field_assignment',
                'required' => array('username', 'fieldname', 'value'),
            ),
        ),
        'totara_cohort' => array(
            'cohort enrolments' => array(
                'datagenerator' => 'cohort_enrolment',
                'required' => array('cohort', 'course'),
                'switchids' => array(
                    'cohort' => 'cohortid',
                    'course' => 'courseid',
                    'role' => 'roleid',
                ),
            ),
            'cohort members' => array(
                'datagenerator' => 'cohort_member',
                'required' => array('cohort', 'user'),
                'switchids' => array(
                    'cohort' => 'cohortid',
                    'user' => 'userid',
                ),
            ),
        ),
        'totara_program' => array(
            'programs' => array(
                'datagenerator' => 'program',
                'required' => array('shortname'),
                'switchids' => array(
                    'category' => 'category'
                ),
            ),
            'program assignments' => array(
                'datagenerator' => 'prog_assign',
                'required' => array('user', 'program'),
                'switchids' => array(
                    'user' => 'userid',
                    'program' => 'programid',
                ),
            ),
            'certifications' => array(
                'datagenerator' => 'certification',
                'required' => array('shortname'),
            ),
        ),
        'totara_hierarchy' => array(
            'position frameworks' => array(
                'datagenerator' => 'pos_frame',
                'required' => array('idnumber'),
            ),
            'positions' => array(
                'datagenerator' => 'pos',
                'required' => array('fullname', 'idnumber', 'pos_framework'),
                'switchids' => array(
                    'pos_framework' => 'frameworkid',
                ),
            ),
            'position type' => array(
                'datagenerator' => 'pos_type',
                'required' => array('fullname', 'idnumber'),
            ),
            'organisation frameworks' => array(
                'datagenerator' => 'org_frame',
                'required' => array('idnumber'),
            ),
            'organisations' => array(
                'datagenerator' => 'org',
                'required' => array('fullname', 'idnumber', 'org_framework'),
                'switchids' => array(
                    'org_framework' => 'frameworkid',
                ),
            ),
            'organisation type' => array(
                'datagenerator' => 'org_type',
                'required' => array('fullname', 'idnumber'),
            ),
            'competency frameworks' => array(
                'datagenerator' => 'comp_frame',
                'required' => array('idnumber'),
            ),
            'competencies' => array(
                'datagenerator' => 'comp',
                'required' => array('fullname', 'idnumber', 'comp_framework'),
                'switchids' => array(
                    'comp_framework' => 'frameworkid',
                ),
            ),
            'competency type' => array(
                'datagenerator' => 'comp_type',
                'required' => array('fullname', 'idnumber'),
            ),
            'goal frameworks' => array(
                'datagenerator' => 'goal_frame',
                'required' => array('idnumber'),
            ),
            'goals' => array(
                'datagenerator' => 'goal',
                'required' => array('fullname', 'idnumber', 'goal_framework'),
                'switchids' => array(
                    'goal_framework' => 'frameworkid',
                ),
            ),
            'goal type' => array(
                'datagenerator' => 'goal_type',
                'required' => array('fullname', 'idnumber'),
            ),
            'hierarchy type assignments' => array(
                'datagenerator' => 'hierarchy_type_assign',
                'required' => array('hierarchy', 'field', 'typeidnumber', 'idnumber', 'value'),
            ),
            'checkbox field for hierarchy type' => array(
                'datagenerator' => 'hierarchy_type_checkbox',
                'required' => array('hierarchy', 'typeidnumber', 'value'),
            ),
            'menu field for hierarchy type' => array(
                'datagenerator' => 'hierarchy_type_generic_menu',
                'required' => array('hierarchy', 'typeidnumber', 'value'),
            ),
            'textinput field for hierarchy type' => array(
                'datagenerator' => 'hierarchy_type_text',
                'required' => array('hierarchy', 'typeidnumber', 'value'),
            ),
            'goal assignments' => array(
                'datagenerator' => 'goal_assign',
                'required' => array('user', 'goal'),
                'switchids' => array(
                    'user' => 'userid',
                    'goal' => 'goalid',
                ),
            ),
        ),
        'totara_plan' => array (
            'plans' => array(
                'datagenerator' => 'learning_plan',
                'required' => array('user', 'name')
            ),
            'objectives' => array(
                'datagenerator' => 'learning_plan_objective_for_behat',
                'required' => array('user', 'plan', 'name')
            ),
        ),
        'totara_appraisal' => array (
            'appraisals' => array(
                'datagenerator' => 'appraisal',
                'required' => array('name'),
            ),
            'stages' => array(
                'datagenerator' => 'stage_for_behat',
                'required' => array('appraisal', 'name'),
            ),
            'pages' => array(
                'datagenerator' => 'page_for_behat',
                'required' => array('appraisal', 'stage', 'name'),
            ),
            'questions' => array(
                'datagenerator' => 'question_for_behat',
                'required' => array('appraisal', 'stage', 'page', 'name'),
            ),
            'assignments' => array(
                'datagenerator' => 'assignment_for_behat',
                'required' => array('appraisal', 'type', 'id'),
            ),
            'messages' => array(
                'datagenerator' => 'message_for_behat',
                'required' => array('appraisal', 'recipients'),
            ),
            'appraisal_job_assignments' => array(
                'datagenerator' => 'appraisal_job_assignments_for_behat',
                'required' => array('appraisal', 'jobassignment'),
            ),
        ),
        'totara_reportbuilder' => array(
            'report_restrictions' => array(
                'datagenerator' => 'global_restriction',
                'required' => array()
            ),
            'standard_report' => array(
                'datagenerator' => 'default_standard_report',
                'required' => array('fullname', 'shortname', 'source'),
            )
        ),
        'auth_approved' => array(
            'signups' => array(
                'datagenerator' => 'signup',
                'required' => array()
            )
        ),
        'tool_sitepolicy' => array(
            'draftpolicies' => array(
                'datagenerator' => 'draft_policy',
                    'required' => array(),
            ),
            'publishedpolicies' => array(
                'datagenerator' => 'published_policy',
                'required' => array(),
            ),
            'multiversionpolicies' => array(
                'datagenerator' => 'multiversion_policy',
                'required' => array(),
            )
        ),
        'mod_forum' => array(
            'post' => array(
                'age_data' => 'age_post'
            )
        ),
        'mod_glossary' => array(
            'entry' => array(
                'age_data' => 'age_entry'
            )
        ),
        'mod_lesson' => array(
            'timer' => array (
                'age_data' => 'wind_back_timer'
            )
        ),
        'mod_quiz' => array(
            'responses' => array(
                'age_data' => 'age_quiz_responses'
            )
        )

    );

    /**
     * Creates the specified element. More info about available elements in http://docs.moodle.org/dev/Acceptance_testing#Fixtures.
     *
     * @Given /^the following "(?P<element_string>(?:[^"]|\\")*)" exist in "([a-z0-9_]*)" plugin:$/
     *
     * @throws Exception
     * @throws PendingException
     * @param string    $elementname The name of the entity to add
     * @param string    $component The Frankenstyle name of the plugin
     * @param TableNode $data
     */
    public function the_following_exist_in_plugin($elementname, $component, TableNode $data) {
        \behat_hooks::set_step_readonly(false);

        // Now that we need them require the data generators.
        require_once(__DIR__ . '/../../../../lib/testing/generator/lib.php');

        if (empty(self::$componentelements[$component][$elementname])) {
            throw new PendingException($elementname . ' data generator is not implemented');
        }

        if (empty(self::$componentelements[$component][$elementname]['datagenerator'])) {
            throw new PendingException($elementname . ' datagenerator attribute not specified');
        }

        $helper = new totara_core_behat_helper_generator();
        $componentgenerator = testing_util::get_data_generator()->get_plugin_generator($component);

        $elementdatagenerator = self::$componentelements[$component][$elementname]['datagenerator'];
        $requiredfields = self::$componentelements[$component][$elementname]['required'];
        if (!empty(self::$componentelements[$component][$elementname]['switchids'])) {
            $switchids = self::$componentelements[$component][$elementname]['switchids'];
        }

        foreach ($data->getHash() as $elementdata) {

            // Check if all the required fields are there.
            foreach ($requiredfields as $requiredfield) {
                if (!isset($elementdata[$requiredfield])) {
                    throw new Exception($elementname . ' requires the field ' . $requiredfield . ' to be specified');
                }
            }

            // Switch from human-friendly references to ids.
            if (isset($switchids)) {
                foreach ($switchids as $element => $field) {
                    // Not all the switch fields are required, default vars will be assigned by data generators.
                    if (isset($elementdata[$element])) {
                        // Temp $id var to avoid problems when $element == $field.
                        if (method_exists($this, 'get_' . $element . '_id')) {
                            $id = $this->{'get_' . $element . '_id'}($elementdata[$element]);
                            unset($elementdata[$element]);
                            $elementdata[$field] = $id;
                        } else if ($helper->get_exists($elementdatagenerator)) {
                            $id = $helper->protected_get($elementdatagenerator, $elementdata[$element]);
                            unset($elementdata[$element]);
                            $elementdata[$field] = $id;
                        } else {
                            // Nothing to change.
                        }
                    }
                }
            }

            // Preprocess the entities that requires a special treatment.
            if (method_exists($this, 'preprocess_' . $elementdatagenerator)) {
                $elementdata = $this->{'preprocess_' . $elementdatagenerator}($elementdata);
            } else if ($helper->preprocess_exists($elementdatagenerator)) {
                $elementdata = $helper->protected_preprocess($elementdatagenerator, $elementdata);
            }

            // Creates element.
            $methodname = 'create_' . $elementdatagenerator;
            if (method_exists($componentgenerator, $methodname)) {
                // Using data generators directly.
                $componentgenerator->{$methodname}($elementdata);

            } else if (method_exists($this, 'process_' . $elementdatagenerator)) {
                // Using an alternative to the direct data generator call.
                $this->{'process_' . $elementdatagenerator}($elementdata);

            } else if ($helper->preprocess_exists($elementdatagenerator)) {
                $helper->protected_process($elementdatagenerator, $elementdata);

            } else {
                throw new PendingException($elementname . ' data generator is not implemented');
            }
        }
    }

    /**
     * Age the specified element's data.
     *
     * @Given /^I age the "(?P<element_key>(?:[^"]|\\")*)" "(?P<element_name>(?:[^"]|\\")*)" in the "(?P<component>(?:[^"]|\\")*)" plugin "(?P<seconds_number>\d+)" seconds$/
     * @throws Exception
     * @throws PendingException
     * @param string $element_key The element key to age
     * @param string $element_name The element name to age
     * @param string $component The Frankenstyle name of the plugin
     * @param int $seconds to age data
     */
    public function i_age_the_data_x_seconds($elementkey, $elementname, $component, $seconds) {
        \behat_hooks::set_step_readonly(true);

        // Now that we need them require the data generators.
        require_once(__DIR__ . '/../../../../lib/testing/generator/lib.php');

        if (empty(self::$componentelements[$component][$elementname])) {
            throw new PendingException($elementname . ' data generator is not implemented');
        }

        if (empty(self::$componentelements[$component][$elementname]['age_data'])) {
            throw new PendingException($component . '.' . $elementname . ' data aging is not implemented');
        }

        $helper = new totara_core_behat_helper_generator();
        $componentgenerator = testing_util::get_data_generator()->get_plugin_generator($component);

        $methodname = self::$componentelements[$component][$elementname]['age_data'];
        if (method_exists($componentgenerator, $methodname)) {
            $componentgenerator->{$methodname}($elementkey, $seconds);
        } else {
            throw new PendingException($component . ' data aging method ' . $methodname . ' is not implemented');
        }
    }

    public function get_manager_id($username) {
        return $this->get_user_id($username);
    }

    public function get_user_id($username) {
        global $DB;

        if (!$id = $DB->get_field('user', 'id', array('username' => $username))) {
            throw new Exception('The specified user with username "' . $username . '" does not exist');
        }

        return $id;
    }

    public function get_program_id($shortname) {
        global $DB;
        return $DB->get_field('prog', 'id', array('shortname' => $shortname), MUST_EXIST);
    }

    public function get_org_framework_id($idnumber) {
        global $DB;
        return $DB->get_field('org_framework', 'id', array('idnumber' => $idnumber));
    }

    public function get_organisation_id($idnumber) {
        global $DB;
        return $DB->get_field('org', 'id', array('idnumber' => $idnumber));
    }

    public function get_pos_framework_id($idnumber) {
        global $DB;
        return $DB->get_field('pos_framework', 'id', array('idnumber' => $idnumber));
    }

    public function get_position_id($idnumber) {
        global $DB;
        return $DB->get_field('pos', 'id', array('idnumber' => $idnumber));
    }

    public function get_comp_framework_id($idnumber) {
        global $DB;
        return $DB->get_field('comp_framework', 'id', array('idnumber' => $idnumber));
    }

    public function get_competency_id($idnumber) {
        global $DB;
        return $DB->get_field('comp', 'id', array('idnumber' => $idnumber));
    }

    public function get_goal_framework_id($idnumber) {
        global $DB;
        return $DB->get_field('goal_framework', 'id', array('idnumber' => $idnumber));
    }

    public function get_goal_id($idnumber) {
        global $DB;
        return $DB->get_field('goal', 'id', array('idnumber' => $idnumber));
    }

    /**
     * Gets the cohort id from it's idnumber.
     * @throws Exception
     * @param string $idnumber
     * @return int
     */
    protected function get_cohort_id($idnumber) {
        global $DB;

        if (!$id = $DB->get_field('cohort', 'id', array('idnumber' => $idnumber))) {
            throw new Exception('The specified cohort with idnumber "' . $idnumber . '" does not exist');
        }
        return $id;
    }

    /**
     * Gets the course id from it's shortname.
     * @throws Exception
     * @param string $shortname
     * @return int
     */
    protected function get_course_id($shortname) {
        global $DB;

        if (!$id = $DB->get_field('course', 'id', array('shortname' => $shortname))) {
            throw new Exception('The specified course with shortname "' . $shortname . '" does not exist');
        }
        return $id;
    }

    /**
     * Gets the role id from it's shortname.
     * @throws Exception
     * @param string $roleshortname
     * @return int
     */
    protected function get_role_id($roleshortname) {
        global $DB;

        if (!$id = $DB->get_field('role', 'id', array('shortname' => $roleshortname))) {
            throw new Exception('The specified role with shortname "' . $roleshortname . '" does not exist');
        }

        return $id;
    }

    /**
     * Gets the category is from its idnumber
     * @throws Exception
     * @param string $categoryidnumber
     * @return int
     */
    protected function get_category_id($categoryidnumber) {
        global $DB;

        if (empty($categoryidnumber)) {
            // If empty, the data generator will provide a default value.
            return $categoryidnumber;
        }

        if (!$id = $DB->get_field('course_categories', 'id', array('idnumber' => $categoryidnumber))) {
            throw new Exception('The specified category with idnumber "' . $categoryidnumber . '" does not exist');
        }

        return $id;
    }
}
