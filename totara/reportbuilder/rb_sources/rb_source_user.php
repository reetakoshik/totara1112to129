<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
 * Copyright (C) 1999 onwards Martin Dougiamas
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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("{$CFG->dirroot}/completion/completion_completion.php");

/**
 * A report builder source for the "user" table.
 */
class rb_source_user extends rb_base_source {
    use \totara_job\rb\source\report_trait;

    /**
     * Whether the "staff_facetoface_sessions" report exists or not (used to determine
     * whether or not to display icons that link to it)
     * @var boolean
     */
    private $staff_f2f;

    /*
     * Indicate if the actions column is permitted on the source.
     * NOTE: you need to extend this source and override this if you want to enable user actions to your reports.
     * @var boolean.
     */
    protected $allow_actions_column = null;

    /**
     * Constructor
     *
     * @param int $groupid (ignored)
     * @param rb_global_restriction_set $globalrestrictionset
     */
    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        global $DB;
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }

        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Allow the actions column to be used in the user source
        if (!isset($this->allow_actions_column)) {
            $this->allow_actions_column = (get_class($this) === 'rb_source_user');
        }

        $this->base = "{user}";
        list($this->sourcewhere, $this->sourceparams) = $this->define_sourcewhere();
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = array();
        $this->staff_f2f = $DB->get_field('report_builder', 'id', array('shortname' => 'staff_facetoface_sessions'));
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_user');
        $this->usedcomponents[] = 'totara_program';

        // Apply global report restrictions.
        $this->add_global_report_restriction_join('base', 'id', 'base');

        parent::__construct();
    }

    /**
     * Are the global report restrictions implemented in the source?
     * @return null|bool
     */
    public function global_restrictions_supported() {
        return true;
    }

    /**
     * Get staff_f2f
     *
     * @return bool|mixed
     */
    public function get_staff_f2f() {
        return $this->staff_f2f;
    }

    //
    //
    // Methods for defining contents of source
    //
    //

    /**
     * Define some extra SQL for the base to limit the data set.
     *
     * @return array The SQL and parmeters that defines the WHERE for the source.
     */
    protected function define_sourcewhere() {
        // There is now a separate report for deleted user accounts.
        return array("base.deleted = 0", array());
    }


    /**
     * Creates the array of rb_join objects required for this->joinlist
     *
     * @return array
     */
    protected function define_joinlist() {

        $joinlist = array(
            new rb_join(
                'totara_stats_comp_achieved',
                'LEFT',
                "(SELECT userid, count(data2) AS number
                    FROM {block_totara_stats}
                    WHERE eventtype = 4
                    GROUP BY userid)",
                'base.id = totara_stats_comp_achieved.userid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'course_completions_courses_started',
                'LEFT',
                "(SELECT userid, COUNT(id) as number
                    FROM {course_completions}
                    WHERE timestarted > 0 OR timecompleted > 0
                    GROUP BY userid)",
                'base.id = course_completions_courses_started.userid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'totara_stats_courses_completed',
                'LEFT',
                "(SELECT userid, count(DISTINCT course) AS number
                    FROM {course_completions}
                    WHERE status >= " . COMPLETION_STATUS_COMPLETE . "
                    GROUP BY userid)",
                'base.id = totara_stats_courses_completed.userid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'totara_stats_course_completion_imports',
                'LEFT',
                // Note that a userid does not exist on the tcic table
                // so we have to use username for joining.
                "(SELECT u.id, count(DISTINCT tcic.courseidnumber) AS number
                    FROM {totara_compl_import_course} tcic
                    INNER JOIN {user} u ON tcic.username = u.username
                    WHERE tcic.importevidence = 1
                    GROUP BY u.id)",
                'base.id = totara_stats_course_completion_imports.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'prog_extension_count',
                'LEFT',
                "(SELECT userid, count(*) as extensioncount
                    FROM {prog_extension} pe
                    WHERE pe.userid = userid AND pe.status = 0
                    GROUP BY pe.userid)",
                'base.id = prog_extension_count.userid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            )
        );

        $joinlist[] = new rb_join(
            'user_extra',
            'LEFT',
            '{totara_userdata_user}',
            'base.id = user_extra.userid');

        $joinlist[] = new rb_join(
            'suspended_purge_type',
            'LEFT',
            '{totara_userdata_purge_type}',
            'user_extra.suspendedpurgetypeid = suspended_purge_type.id',
            null,
            'user_extra');

        $joinlist[] = new rb_join(
            'deleted_purge_type',
            'LEFT',
            '{totara_userdata_purge_type}',
            'user_extra.deletedpurgetypeid = deleted_purge_type.id',
            null,
            'user_extra');

        $this->add_totara_job_tables($joinlist, 'base', 'id');

        return $joinlist;
    }

    /**
     * Creates the array of rb_column_option objects required for
     * $this->columnoptions
     *
     * @return array
     */
    protected function define_columnoptions() {
        global $DB;

        $columnoptions = array();
        $this->add_core_user_columns($columnoptions, 'base');
        $this->add_totara_job_columns($columnoptions);

        // A column to display a user's profile picture
        $columnoptions[] = new rb_column_option(
                        'user',
                        'userpicture',
                        get_string('userspicture', 'rb_source_user'),
                        'base.id',
                        array(
                            'displayfunc' => 'user_icon',
                            'noexport' => true,
                            'defaultheading' => get_string('picture', 'rb_source_user'),
                            'extrafields' => array(
                                'userpic_picture' => 'base.picture',
                                'userpic_firstname' => 'base.firstname',
                                'userpic_firstnamephonetic' => 'base.firstnamephonetic',
                                'userpic_middlename' => 'base.middlename',
                                'userpic_lastname' => 'base.lastname',
                                'userpic_lastnamephonetic' => 'base.lastnamephonetic',
                                'userpic_alternatename' => 'base.alternatename',
                                'userpic_imagealt' => 'base.imagealt',
                                'userpic_email' => 'base.email'
                            )
                        )
        );

        // A column to display the "My Learning" icons for a user
        $columnoptions[] = new rb_column_option(
                        'user',
                        'userlearningicons',
                        get_string('mylearningicons', 'rb_source_user'),
                        'base.id',
                        array(
                            'displayfunc' => 'user_learning_icons',
                            'noexport' => true,
                            'defaultheading' => get_string('options', 'rb_source_user')
                        )
        );

        $columnoptions[] = new rb_column_option(
            'suspended_purge_type',
            'fullname',
            get_string('suspendedpurgetype', 'totara_userdata'),
            'suspended_purge_type.fullname',
            array(
                'displayfunc' => 'format_string',
                'joins' => array('suspended_purge_type')
            )
        );

        $columnoptions[] = new rb_column_option(
            'suspended_purge_type',
            'id',
            'ID',
            'suspended_purge_type.id',
            array(
                'addtypetoheading' => true,
                'joins' => array('suspended_purge_type'),
                'displayfunc' => 'integer'
            )
        );

        $columnoptions[] = new rb_column_option(
            'deleted_purge_type',
            'fullname',
            get_string('deletedpurgetype', 'totara_userdata'),
            'deleted_purge_type.fullname',
            array(
                'displayfunc' => 'format_string',
                'joins' => array('deleted_purge_type')
            )
        );

        $columnoptions[] = new rb_column_option(
            'deleted_purge_type',
            'id',
            'ID',
            'deleted_purge_type.id',
            array(
                'addtypetoheading' => true,
                'joins' => array('deleted_purge_type'),
                'displayfunc' => 'integer'
            )
        );

        // A column to display the number of achieved competencies for a user
        // We need a COALESCE on the field for 0 to replace nulls, which ensures correct sorting order.
        $columnoptions[] = new rb_column_option(
                        'statistics',
                        'competenciesachieved',
                        get_string('usersachievedcompcount', 'rb_source_user'),
                        'COALESCE(totara_stats_comp_achieved.number,0)',
                        array(
                            'displayfunc' => 'integer',
                            'joins' => 'totara_stats_comp_achieved',
                            'dbdatatype' => 'integer',
                        )
        );

        // A column to display the number of started courses for a user
        // We need a COALESCE on the field for 0 to replace nulls, which ensures correct sorting order.
        $columnoptions[] = new rb_column_option(
                        'statistics',
                        'coursesstarted',
                        get_string('userscoursestartedcount', 'rb_source_user'),
                        'COALESCE(course_completions_courses_started.number,0)',
                        array(
                            'displayfunc' => 'integer',
                            'joins' => 'course_completions_courses_started',
                            'dbdatatype' => 'integer',
                        )
        );

        // A column to display the number of completed courses for a user
        // We need a COALESCE on the field for 0 to replace nulls, which ensures correct sorting order.
        $columnoptions[] = new rb_column_option(
                        'statistics',
                        'coursescompleted',
                        get_string('userscoursescompletedcount', 'rb_source_user'),
                        'COALESCE(totara_stats_courses_completed.number,0)',
                        array(
                            'displayfunc' => 'integer',
                            'joins' => 'totara_stats_courses_completed',
                            'dbdatatype' => 'integer',
                        )
        );

        // A column to display the number of course completions as evidence for a user.
        // We need a COALESCE on the field for 0 to replace nulls, which ensures correct sorting order.
        $columnoptions[] = new rb_column_option(
            'statistics',
            'coursecompletionsasevidence',
            get_string('coursecompletionsasevidence', 'rb_source_user'),
            'COALESCE(totara_stats_course_completion_imports.number,0)',
            array(
                'displayfunc' => 'integer',
                'joins' => 'totara_stats_course_completion_imports',
                'dbdatatype' => 'integer',
            )
        );

        $usednamefields = totara_get_all_user_name_fields_join('base', null, true);
        $allnamefields = totara_get_all_user_name_fields_join('base');
        $columnoptions[] = new rb_column_option(
                        'user',
                        'namewithlinks',
                        get_string('usernamewithlearninglinks', 'rb_source_user'),
                        $DB->sql_concat_join("' '", $usednamefields),
                        array(
                            'displayfunc' => 'user_with_components_links',
                            'defaultheading' => get_string('user', 'rb_source_user'),
                            'extrafields' => array_merge(array('id' => 'base.id',
                                                               'picture' => 'base.picture',
                                                               'imagealt' => 'base.imagealt',
                                                               'email' => 'base.email',
                                                               'deleted' => 'base.deleted'),
                                                         $allnamefields),
                            'dbdatatype' => 'char',
                            'outputformat' => 'text'
                        )
        );

        $columnoptions[] = new rb_column_option(
                        'user',
                        'extensionswithlink',
                        get_string('extensions', 'totara_program'),
                        'prog_extension_count.extensioncount',
                        array(
                            'joins' => 'prog_extension_count',
                            'displayfunc' => 'program_extension_link',
                            'extrafields' => array('user_id' => 'base.id')
                        )
        );

        $usednamefields = totara_get_all_user_name_fields_join('base', null, true);

        if ($this->allow_actions_column) {
            $columnoptions[] = new rb_column_option(
                'user',
                'actions',
                get_string('actions', 'totara_reportbuilder'),
                'base.id',
                array(
                    'displayfunc' => 'user_actions',
                    'noexport' => true,
                    'nosort' => true,
                    'graphable' => false,
                    'extrafields' => array(
                        'fullname' => $DB->sql_concat_join("' '", $usednamefields),
                        'username' => 'base.username',
                        'email' => 'base.email',
                        'mnethostid' => 'base.mnethostid',
                        'confirmed' => 'base.confirmed',
                        'suspended' => 'base.suspended',
                        'deleted' => 'base.deleted'
                    )
                )
            );
        }

        return $columnoptions;
    }

    public function rb_filter_purge_type_suspended_list() {
        global $DB;
        $options = $DB->get_records_menu('totara_userdata_purge_type', array('userstatus' => \totara_userdata\userdata\target_user::STATUS_SUSPENDED), '', 'id, fullname');
        $options = array_map('format_string', $options);
        \core_collator::asort($options);
        return $options;
    }

    public function rb_filter_purge_type_deleted_list() {
        global $DB;
        $options = $DB->get_records_menu('totara_userdata_purge_type', array('userstatus' => \totara_userdata\userdata\target_user::STATUS_DELETED), '', 'id, fullname');
        $options = array_map('format_string', $options);
        \core_collator::asort($options);
        return $options;
    }

    /**
     * Creates the array of rb_filter_option objects required for $this->filteroptions
     * @return array
     */
    protected function define_filteroptions() {
        $filteroptions = array();

        $filteroptions[] = new rb_filter_option(
            'suspended_purge_type',
            'id',
            get_string('suspendedpurgetype', 'totara_userdata'),
            'select',
            array(
                'selectfunc' => 'purge_type_suspended_list',
            )
        );

        $filteroptions[] = new rb_filter_option(
            'deleted_purge_type',
            'id',
            get_string('deletedpurgetype', 'totara_userdata'),
            'select',
            array(
                'selectfunc' => 'purge_type_deleted_list',
            )
        );

        $this->add_core_user_filters($filteroptions);

        $roles = get_roles_used_in_context(context_system::instance());

        // We only want this filter to be available on reports that user the user source.
        $filteroptions[] = new rb_filter_option(
            'user',
            'roleid',
            get_string('usersystemrole', 'totara_reportbuilder'),
            'system_role',
            [
                'selectchoices' => [
                    '' => get_string('chooserole', 'totara_reportbuilder'),
                    '0' => get_string('anyrole', 'totara_reportbuilder')
                ] + role_fix_names($roles, null, null, true),
            ],
            'base.id'
        );

        $this->add_totara_job_filters($filteroptions, 'base');

        return $filteroptions;
    }


    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'user',
                'value' => 'namelinkicon',
            ),
            array(
                'type' => 'user',
                'value' => 'username',
            ),
            array(
                'type' => 'user',
                'value' => 'lastlogin',
            ),
        );
        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'user',
                'value' => 'fullname',
            ),
        );

        return $defaultfilters;
    }
    /**
     * Creates the array of rb_content_option object required for $this->contentoptions
     * @return array
     */
    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions, 'base');

        // Add the time created content option.
        $contentoptions[] = new rb_content_option(
            'date',
            get_string('timecreated', 'rb_source_user'),
            'base.timecreated'
        );

        return $contentoptions;
    }

    /**
     * A rb_column_options->displayfunc helper function to display the
     * "My Learning" icons for each user row
     *
     * @deprecated Since Totara 12.0
     * @global object $CFG
     * @param integer $itemid ID of the user
     * @param object $row The rest of the data for the row
     * @return string
     */
    public function rb_display_learning_icons($itemid, $row) {
        debugging('rb_source_user::rb_display_learning_icons has been deprecated since Totara 12.0. Use user_learning_icons::display', DEBUG_DEVELOPER);
        global $CFG, $OUTPUT;

        static $systemcontext;
        if (!isset($systemcontext)) {
            $systemcontext = context_system::instance();
        }

        $disp = html_writer::start_tag('span', array('style' => 'white-space:nowrap;'));

        // Learning Records icon
        if (totara_feature_visible('recordoflearning')) {
            $disp .= html_writer::start_tag('a', array('href' => $CFG->wwwroot . '/totara/plan/record/index.php?userid='.$itemid));
            $disp .= $OUTPUT->flex_icon('recordoflearning', ['classes' => 'ft-size-300']);
            $disp .= html_writer::end_tag('a');
        }

        // Face To Face Bookings icon
        if ($this->staff_f2f) {
            $disp .= html_writer::start_tag('a', array('href' => $CFG->wwwroot . '/my/bookings.php?userid='.$itemid));
            $disp .= $OUTPUT->flex_icon('calendar', ['classes' => 'ft-size-300']);
            $disp .= html_writer::end_tag('a');
        }

        // Individual Development Plans icon
        if (totara_feature_visible('learningplans')) {
            if (has_capability('totara/plan:accessplan', $systemcontext)) {
                $disp .= html_writer::start_tag('a', array('href' => $CFG->wwwroot . '/totara/plan/index.php?userid=' . $itemid));
                $disp .= $OUTPUT->flex_icon('learningplan', ['classes' => 'ft-size-300']);
                $disp .= html_writer::end_tag('a');
            }
        }

        $disp .= html_writer::end_tag('span');
        return $disp;
    }

    /**
     * Display program extension link.
     *
     * @deprecated Since Totara 12.0
     * @param $extensioncount
     * @param $row
     * @param $isexport
     * @return string
     */
    function rb_display_extension_link($extensioncount, $row, $isexport) {
        debugging('rb_source_user::rb_display_extension_link has been deprecated since Totara 12.0. Use totara_program\rb\display\program_extension_link::display', DEBUG_DEVELOPER);
        global $CFG;
        if (empty($extensioncount)) {
            return '0';
        }
        if (isset($row->user_id) && !$isexport) {
            return html_writer::link("{$CFG->wwwroot}/totara/program/manageextensions.php?userid={$row->user_id}", $extensioncount);
        } else {
            return $extensioncount;
        }
    }


    /**
     * A rb_column_options->displayfunc helper function for showing a user's links column on the My Team page.
     * To pass the correct data, first:
     *      $usednamefields = totara_get_all_user_name_fields_join($base, null, true);
     *      $allnamefields = totara_get_all_user_name_fields_join($base);
     * then your "field" param should be:
     *      $DB->sql_concat_join("' '", $usednamefields)
     * to allow sorting and filtering, and finally your extrafields should be:
     *      array_merge(array('id' => $base . '.id',
     *                        'picture' => $base . '.picture',
     *                        'imagealt' => $base . '.imagealt',
     *                        'email' => $base . '.email'),
     *                  $allnamefields)
     *
     * @deprecated Since Totara 12.0
     * @param string $user Users name
     * @param object $row All the data required to display a user's name, icon and link
     * @param boolean $isexport If the report is being exported or viewed
     * @return string
     */
    function rb_display_user_with_links($user, $row, $isexport = false) {
        debugging('rb_source_user::rb_display_user_with_links has been deprecated since Totara 12.0. Use user_with_components_links::display', DEBUG_DEVELOPER);
        global $CFG, $OUTPUT, $USER;

        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->dirroot . '/totara/feedback360/lib.php');

        // Process obsolete calls to this display function.
        if (isset($row->userpic_picture)) {
            $picuser = new stdClass();
            $picuser->id = $row->user_id;
            $picuser->picture = $row->userpic_picture;
            $picuser->imagealt = $row->userpic_imagealt;
            $picuser->firstname = $row->userpic_firstname;
            $picuser->firstnamephonetic = $row->userpic_firstnamephonetic;
            $picuser->middlename = $row->userpic_middlename;
            $picuser->lastname = $row->userpic_lastname;
            $picuser->lastnamephonetic = $row->userpic_lastnamephonetic;
            $picuser->alternatename = $row->userpic_alternatename;
            $picuser->email = $row->userpic_email;
            $row = $picuser;
        }

        $userid = $row->id;

        if ($isexport) {
            return $this->rb_display_user($user, $row, true);
        }

        $usercontext = context_user::instance($userid, MUST_EXIST);
        $show_profile_link = user_can_view_profile($row, null, $usercontext);

        $user_pic = $OUTPUT->user_picture($row, array('courseid' => 1, 'link' => $show_profile_link));

        $recordstr = get_string('records', 'rb_source_user');
        $requiredstr = get_string('required', 'rb_source_user');
        $planstr = get_string('plans', 'rb_source_user');
        $profilestr = get_string('profile', 'rb_source_user');
        $bookingstr = get_string('bookings', 'rb_source_user');
        $appraisalstr = get_string('appraisals', 'totara_appraisal');
        $feedback360str = get_string('feedback360', 'totara_feedback360');
        $goalstr = get_string('goalplural', 'totara_hierarchy');
        $rol_link = html_writer::link("{$CFG->wwwroot}/totara/plan/record/index.php?userid={$userid}", $recordstr);
        $required_link = html_writer::link(new moodle_url('/totara/program/required.php',
                array('userid' => $userid)), $requiredstr);
        $plan_link = html_writer::link("{$CFG->wwwroot}/totara/plan/index.php?userid={$userid}", $planstr);
        $profile_link = html_writer::link("{$CFG->wwwroot}/user/view.php?id={$userid}", $profilestr);
        $booking_link = html_writer::link("{$CFG->wwwroot}/my/bookings.php?userid={$userid}", $bookingstr);
        $appraisal_link = html_writer::link("{$CFG->wwwroot}/totara/appraisal/index.php?subjectid={$userid}", $appraisalstr);
        $feedback_link = html_writer::link("{$CFG->wwwroot}/totara/feedback360/index.php?userid={$userid}", $feedback360str);
        $goal_link = html_writer::link("{$CFG->wwwroot}/totara/hierarchy/prefix/goal/mygoals.php?userid={$userid}", $goalstr);

        $show_plan_link = totara_feature_visible('learningplans') && dp_can_view_users_plans($userid);

        $links = html_writer::start_tag('ul');
        $links .= $show_plan_link ? html_writer::tag('li', $plan_link) : '';
        $links .= $show_profile_link ? html_writer::tag('li', $profile_link) : '';
        $links .= html_writer::tag('li', $booking_link);
        $links .= html_writer::tag('li', $rol_link);

        // Show link to managers, but not to temporary managers.
        $ismanager = \totara_job\job_assignment::is_managing($USER->id, $userid, null, false);
        if ($ismanager && totara_feature_visible('appraisals')) {
            $links .= html_writer::tag('li', $appraisal_link);
        }

        if (totara_feature_visible('feedback360') && feedback360::can_view_other_feedback360s($userid)) {
            $links .= html_writer::tag('li', $feedback_link);
        }

        if (totara_feature_visible('goals')) {
            if (has_capability('totara/hierarchy:viewstaffcompanygoal', $usercontext, $USER->id) ||
                has_capability('totara/hierarchy:viewstaffpersonalgoal', $usercontext, $USER->id)) {
                $links .= html_writer::tag('li', $goal_link);
            }
        }

        if ((totara_feature_visible('programs') || totara_feature_visible('certifications')) && prog_can_view_users_required_learning($userid)) {
            $links .= html_writer::tag('li', $required_link);
        }

        $links .= html_writer::end_tag('ul');

        if ($show_profile_link) {
            $user_tag = html_writer::link(new moodle_url("/user/profile.php", array('id' => $userid)),
                fullname($row), array('class' => 'name'));
        }
        else {
            $user_tag = html_writer::span(fullname($row), 'name');
        }

        $return = $user_pic . $user_tag . $links;

        return $return;
    }

    /**
     * Display for count
     *
     * @deprecated Since Totara 12.0
     * @param $result
     * @return int
     */
    function rb_display_count($result) {
        debugging('rb_source_user::rb_display_count has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        return $result ? $result : 0;
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'deleted',
                'base.deleted'
            ),
        );

        return $paramoptions;
    }

    /**
     * Returns expected result for column_test.
     * @param rb_column_option $columnoption
     * @return int
     */
    public function phpunit_column_test_expected_count($columnoption) {
        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_expected_count() cannot be used outside of unit tests');
        }
        if (get_class($this) === 'rb_source_user') {
            return 2;
        }
        return parent::phpunit_column_test_expected_count($columnoption);
    }
}

// end of rb_source_user class

