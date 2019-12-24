<?php

require_once __DIR__.'/../detailed_course_completion/sources_traits/course_completion_columns.php';
require_once __DIR__.'/../detailed_course_completion/sources_traits/course_completion_joins.php';
require_once __DIR__.'/../detailed_course_completion/sources_traits/course_completion_filters.php';
require_once __DIR__.'/../detailed_course_completion/sources_traits/course_contentoptions.php';
require_once __DIR__.'/../detailed_course_completion/sources_traits/course_paramoptions.php';
require_once __DIR__.'/../detailed_course_completion/sources_traits/course_display_completion_status.php';
 
class rb_source_detailed_course_completion extends rb_base_source 
{   
    use course_completion_columns;
    use course_completion_joins;
    use course_completion_filters;
    use course_contentoptions;
    use course_paramoptions;
    use course_display_completion_status;

    public $sourcetitle    = '';
    public $base           = '{course}';
    public $joinlist       = [];
    public $columnoptions  = [];
    public $filteroptions  = [];
    public $contentoptions = [];
    public $paramoptions   = [];
    public $defaultcolumns = [
        [
            'type'  => 'user',
            'value' => 'namelinkicon',
        ],
        [
            'type'  => 'course',
            'value' => 'courselink',
        ],
        [
            'type'  => 'course_completion',
            'value' => 'status',
        ],
        [
            'type'  => 'course_completion',
            'value' => 'completeddate',
        ]
    ];

    function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null)
    {
        global $CFG;

        require_once $CFG->dirroot . '/completion/completion_completion.php';
        require_once $CFG->dirroot . '/completion/criteria/completion_criteria.php';

        $this->globalrestrictionset = $globalrestrictionset;
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }

        $this->sourcetitle = get_string('sourcetitle', 'rb_source_detailed_course_completion');

        $this->add_course_category_table_to_joinlist($this->joinlist, 'base', 'category');
        $this->add_course_completion_tables_to_joinlist($this->joinlist, 'base', 'id', 'auser', 'id');
        $this->add_user_table_to_joinlist($this->joinlist, 'course_user_enrolments', 'userid');
        $this->add_job_assignment_tables_to_joinlist($this->joinlist, 'auser', 'id');

        $this->add_global_report_restriction_join('course_user_enrolments', 'userid', 'course_user_enrolments');

        $this->add_course_fields_to_columns($this->columnoptions, 'base');
        $this->add_course_category_fields_to_columns($this->columnoptions, 'course_category', 'base', 'coursecount');
        $this->add_course_completion_status_field_to_columns($this->columnoptions);
        $this->add_course_completion_fields_to_column($this->columnoptions);
        $this->add_user_fields_to_columns($this->columnoptions);
        $this->add_job_assignment_fields_to_columns($this->columnoptions);

        $this->add_course_completion_fields_to_filters($this->filteroptions);
        $this->add_user_fields_to_filters($this->filteroptions);
        $this->add_course_fields_to_filters($this->filteroptions);
        $this->add_course_category_fields_to_filters($this->filteroptions);
        $this->add_job_assignment_fields_to_filters($this->filteroptions);
        $this->add_core_tag_fields_to_filters('core', 'course', $this->filteroptions);
        /*  
            Disabled because of the error message since totara 10
            Duplicate filter option user-usercohortids detected in source rb_source_detailed_course_completion
        */
        // $this->add_cohort_user_fields_to_filters($this->filteroptions);

        $this->add_cohort_course_fields_to_filters($this->filteroptions);

        $this->add_basic_user_content_options($this->contentoptions);
        $this->add_contentoptions($this->contentoptions);

        $this->add_course_paramoptions($this->paramoptions);
 
        parent::__construct();
    }

    private function add_course_completion_status_field_to_columns(&$columns)
    {
        $columns[] = new rb_column_option(
            'course_completion',
            'status',
            get_string('completionstatus', 'rb_source_course_completion'),
            'course_completion.status',
            [
                'displayfunc' => 'course_completion_status',
                'joins'       => ['course_completion'], // removed 'role_assignments' because of performance issues on maccabi
                'extrafields' => [
                    'id' => 'course_completion.id'
                ]
            ]
        );
    }

    
    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported()
    {
        return true;
    }
}
