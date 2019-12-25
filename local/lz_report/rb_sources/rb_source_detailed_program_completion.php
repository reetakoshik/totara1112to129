<?php

require_once __DIR__.'/../detailed_program_completion/sources_traits/program_completion_columns.php';
require_once __DIR__.'/../detailed_program_completion/sources_traits/program_completion_joins.php';
require_once __DIR__.'/../detailed_program_completion/sources_traits/program_completion_filters.php';
require_once __DIR__.'/../detailed_program_completion/sources_traits/program_contentoptions.php';
require_once __DIR__.'/../detailed_program_completion/sources_traits/program_display_completion_status.php';
require_once __DIR__.'/../detailed_program_completion/sources_traits/program_paramoptions.php';
 
class rb_source_detailed_program_completion extends rb_base_source 
{   
    use program_completion_columns;
    use program_completion_joins;
    use program_completion_filters;
    use program_contentoptions;
    use program_display_completion_status;
    use program_paramoptions;

    public $sourcetitle    = '';
    public $base           = '{user}';
    public $sourcewhere    = '';
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
            'type'  => 'prog',
            'value' => 'proglinkicon',
        ],
        [
            'type'  => 'progcompletion',
            'value' => 'status',
        ],
        [
            'type'  => 'progcompletion',
            'value' => 'duedate',
        ],
        [
            'type'  => 'progcompletion',
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

        $this->add_user_fields_to_columns($this->columnoptions, 'base');
        $this->add_program_fields_to_columns($this->columnoptions, 'prog');
        $this->add_program_completion_fields_to_column($this->columnoptions, 'prog_completion');
        $this->add_course_category_fields_to_columns($this->columnoptions, 'course_category', 'prog');
        $this->add_job_assignment_fields_to_columns($this->columnoptions);
        $this->add_certification_fields_to_columns($this->columnoptions, 'certif', 'totara_certification');
        
        $this->add_program_completion_tables_to_joinlist($this->joinlist, 'base', 'id');
        $this->add_job_assignment_tables_to_joinlist($this->joinlist, 'base', 'id');
        $this->add_course_category_table_to_joinlist($this->joinlist, 'prog', 'category');
        $this->add_certification_table_to_joinlist($this->joinlist, 'prog', 'certifid');

        $this->add_global_report_restriction_join('base', 'id');

        $this->add_program_completion_fields_to_filters($this->filteroptions);
        $this->add_user_fields_to_filters($this->filteroptions);
        $this->add_course_category_fields_to_filters($this->filteroptions, 'prog', 'category');
        $this->add_job_assignment_fields_to_filters($this->filteroptions, 'base');
        $this->add_program_fields_to_filters($this->filteroptions, "totara_program");
        $this->add_cohort_user_fields_to_filters($this->filteroptions);
        $this->add_cohort_program_fields_to_filters($this->filteroptions, "totara_program");

        $this->add_basic_user_content_options($this->contentoptions, 'base');
        $this->add_program_completion_contentoptions($this->contentoptions);

        $this->add_program_paramoptions($this->paramoptions);
        
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_detailed_program_completion');
 
        parent::__construct();
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