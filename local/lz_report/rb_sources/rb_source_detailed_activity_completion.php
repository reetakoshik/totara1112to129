<?php

require_once __DIR__.'/../detailed_activity_completion/sources_traits/activity_completion_columns.php';
require_once __DIR__.'/../detailed_activity_completion/sources_traits/activity_completion_joins.php';
require_once __DIR__.'/../detailed_activity_completion/sources_traits/activity_completion_filters.php';
require_once __DIR__.'/../detailed_activity_completion/sources_traits/activity_completion_content.php';
require_once __DIR__.'/../detailed_course_completion/sources_traits/course_completion_columns.php';
require_once __DIR__.'/../detailed_course_completion/sources_traits/course_completion_joins.php';
require_once __DIR__.'/../detailed_course_completion/sources_traits/course_paramoptions.php';
 
class rb_source_detailed_activity_completion extends rb_base_source 
{   
    use \core_course\rb\source\report_trait;
    use \totara_job\rb\source\report_trait;
    use activity_completion_columns;
    use activity_completion_joins;
    use activity_completion_filters;
    use activity_completion_content;
    use course_completion_columns;
    use course_completion_joins;
    use course_paramoptions;

    public $sourcetitle    = '';
    public $base           = '';
    public $sourcewhere    = '';
    public $joinlist       = [];
    public $columnoptions  = [];
    public $filteroptions  = [];
    public $contentoptions = [];
    public $paramoptions   = [];
    public $defaultcolumns = [
        [
            'type'    => 'user',
            'value'   => 'namelinkicon',
        ],
        [
            'type'    => 'module',
            'value'   => 'type',
        ],
        [
            'type'  => 'module',
            'value' => 'title',
        ],
        [
            'type'  => 'course',
            'value' => 'courselink',
        ],
        [
            'type'  => 'module_completion',
            'value' => 'completionstate',
        ],
        [
            'type'  => 'module_completion',
            'value' => 'timecompleted',
        ]
    ];
    public $defaultfilters = [
        [
            'type'  => 'user',
            'value' => 'fullname',
        ],
        [
            'type'  => 'module',
            'value' => 'type',
        ],
        [
            'type'  => 'module',
            'value' => 'title',
        ],
        [
            'type'  => 'course',
            'value' => 'fullname',
        ],
        [
            'type'  => 'module_completion',
            'value' => 'completionstate',
        ],
        [
            'type'  => 'module_completion',
            'value' => 'timecompleted',
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

        $this->init_base();

        $this->add_activity_table_to_joinlist($this->joinlist);
        $this->add_core_user_tables($this->joinlist, 'course_user_enrolments', 'userid');
        $this->add_core_course_tables($this->joinlist, 'course_module', 'course');
        $this->add_core_course_category_tables($this->joinlist, 'course', 'category');
        $this->add_course_completion_tables_to_joinlist(
            $this->joinlist, 'course_module', 'course', 'auser', 'id'
        );
        $this->add_totara_job_tables($this->joinlist, 'course_user_enrolments', 'userid');

        $this->add_global_report_restriction_join('course_user_enrolments', 'userid', 'course_user_enrolments');

        $this->add_activity_completion_fields_to_columns($this->columnoptions, 'course_module_completion');
        $this->add_activity_fields_to_columns($this->columnoptions);
        $this->add_core_user_columns($this->columnoptions);
        $this->add_course_completion_status_field_to_columns($this->columnoptions);
        $this->add_core_course_columns($this->columnoptions);
        $this->add_course_completion_fields_to_column($this->columnoptions);
        $this->add_core_course_category_columns($this->columnoptions, 'course_category');
        $this->add_totara_job_columns($this->columnoptions);

        $this->add_core_user_filters($this->filteroptions);
        $this->add_core_course_filters($this->filteroptions);
        $this->add_core_course_category_filters($this->filteroptions);
        $this->add_activity_fields_to_filters($this->filteroptions);
        $this->add_activity_completion_fields_to_filters($this->filteroptions);
        $this->add_totara_job_filters($this->filteroptions);

        $this->add_basic_user_content_options($this->contentoptions);
        $this->add_course_completion_content($this->contentoptions);

        $this->add_course_paramoptions($this->paramoptions, 'course');

        $this->sourcetitle = get_string('sourcetitle', 'rb_source_detailed_activity_completion');
 
        parent::__construct();
    }

    private function init_base()
    {
        global $DB;

        $modules = $DB->get_records('modules');

        $modulenames = [];
        $modulenamejoins = '';
        foreach ($modules as $module) {
            $modulenames[] = 'COALESCE('.$module->name . ".name, '')";
            $modulenamejoins .= " 
                LEFT JOIN {{$module->name}} {$module->name}
                    ON {$module->name}.id = cm.instance AND cm.module = {$module->id}
            ";
        }

        $modulenames = $DB->sql_concat_join("''", $modulenames);
        $timemodified = $this->select_timemodified_column($modules);

        $contextlevel = CONTEXT_COURSE;

        $this->base = "(
            SELECT  cm.id AS id,
                    $modulenames AS title,
                    cm.id AS coursemoduleid,
                    cm.course AS courseid,
                    cm.added AS added,
                    $timemodified
            FROM {course_modules} cm
            $modulenamejoins
            WHERE cm.completion > 0 AND $modulenames <> ''
        )";
    }

    private function select_timemodified_column($modules)
    {
        global $DB;
        $concatarr = [];
        foreach ($modules as $module) {
            if (in_array($module->name, array('data'))) {
                // Skip modules that dont have time modified.
                continue;
            }
            $concatarr[] = "COALESCE({$module->name}.timemodified, 0)";
        }
        $timemodified = join('+', $concatarr).' AS cmtimemodified';
        return $timemodified;
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
                'joins'       => ['course_completion']
            ]
        );
    }

    public function rb_display_course_completion_status($status)
    {
        $STATUS_TEXTS = [
            COMPLETION_STATUS_NOTYETSTARTED  => get_string('notyetstarted', 'completion'),
            COMPLETION_STATUS_INPROGRESS     => get_string('inprogress', 'completion'),
            COMPLETION_STATUS_COMPLETE       => get_string('complete', 'totara_program'),
            COMPLETION_STATUS_COMPLETEVIARPL => get_string('completeviarpl', 'completion')
        ];
        return $STATUS_TEXTS[$status];
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
