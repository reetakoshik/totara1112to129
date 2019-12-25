<?php

require_once __DIR__.'/../detailed_quiz_completion/sources_traits/quiz_completion_columns.php';
require_once __DIR__.'/../detailed_quiz_completion/sources_traits/quiz_completion_joins.php';
require_once __DIR__.'/../detailed_quiz_completion/sources_traits/quiz_completion_filters.php';
require_once __DIR__.'/../detailed_quiz_completion/sources_traits/quiz_contentoptions.php';

class rb_source_detailed_quiz_completion extends rb_base_source
{
    use quiz_contentoptions;
    use quiz_completions_columns;
    use quiz_completions_joins;
    use quiz_completions_filters;
    
    public $sourcetitle    = '';
    public $base           = '{quiz_attempts}';
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
            'type'  => 'quiz',
            'value' => 'name',
        ],
        [
            'type'  => 'course',
            'value' => 'courselink',
        ],
        [
            'type'  => 'quiz_attempt',
            'value' => 'state',
        ],
        [
            'type'  => 'quiz_attempt',
            'value' => 'sumgrades',
        ],
        [
            'type'  => 'quiz_attempt',
            'value' => 'timestart',
        ]
    ];
    public $defaultfilters = [
        [
            'type'  => 'user',
            'value' => 'fullname',
        ],
        [
            'type'  => 'course',
            'value' => 'fullname',
        ],
        [
            'type'  => 'quiz',
            'value' => 'name',
        ],
        [
            'type'  => 'quiz_attempt',
            'value' => 'state',
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

        $this->add_user_table_to_joinlist($this->joinlist, 'base', 'userid');
        $this->add_quiz_table_to_joinlist($this->joinlist, 'base', 'quiz');
        $this->add_course_table_to_joinlist($this->joinlist, 'quiz', 'course');
        $this->add_course_category_table_to_joinlist($this->joinlist, 'course', 'category');

        $this->add_global_report_restriction_join('base', 'userid');

        $this->add_user_fields_to_columns($this->columnoptions);
        $this->add_quiz_attempts_fields_to_columns($this->columnoptions, 'base');
        $this->add_quiz_fields_to_columns($this->columnoptions, 'quiz', 'question');
        $this->add_course_fields_to_columns($this->columnoptions);
        $this->add_course_category_fields_to_columns($this->columnoptions);

        $this->add_user_fields_to_filters($this->filteroptions);
        $this->add_course_fields_to_filters($this->filteroptions);
        $this->add_quiz_fields_to_filters($this->filteroptions);

        $this->add_basic_user_content_options($this->contentoptions);
        $this->add_contentoptions($this->contentoptions);

        $this->add_quiz_paramoptions($this->paramoptions);
        
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_detailed_quiz_completion');

        parent::__construct();
    }

    private function add_quiz_paramoptions(&$paramoptions, $quizTable = 'course_module')
    {
        $paramoptions[] = new rb_param_option('quizid', "$quizTable.id", [$quizTable]);
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