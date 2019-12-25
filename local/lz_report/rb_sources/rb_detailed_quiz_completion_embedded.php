<?php

require_once 'rb_source_detailed_quiz_completion.php';

class rb_detailed_quiz_completion_embedded extends rb_base_embedded
{
    public $contentmode;
    public $contentsettings;
    public $url;
    public $hidden;
    public $accessmode;
    public $accesssettings;
    public $source = 'detailed_quiz_completion';
    public $shortname = 'detailed_quiz_completion';
    public $fullname = '';
    public $embeddedparams = [];
    public $columns = [];
    public $filters = [
        [
            'type'     => 'user',
            'value'    => 'fullname',
            'advanced' => 0
        ],
        [
            'type'     => 'quiz_attempt',
            'value'    => 'state',
            'advanced' => 0
        ],
        [
            'type'     => 'quiz_attempt',
            'value'    => 'sumgrades',
            'advanced' => 0
        ],
    ];

    public function __construct()
    {
        $this->columns = [
            [
                'type'    => 'user',
                'value'   => 'namelinkicon',
                'heading' => get_string('usernamelinkicon', 'totara_reportbuilder'),
            ],
            [
                'type'    => 'quiz',
                'value'   => 'name',
                'heading' => get_string('quiz_title', 'rb_source_detailed_quiz_completion'),
            ],
            [
                'type'    => 'course',
                'value'   => 'courselink',
                'heading' => get_string('courselink', 'rb_source_activities'),
            ],
            [
                'type'    => 'quiz_attempt',
                'value'   => 'state',
                'heading' => get_string('quiz_attempt_state', 'rb_source_detailed_quiz_completion'),
            ],
            [
                'type'    => 'quiz_attempt',
                'value'   => 'sumgrades',
                'heading' => get_string('quiz_attempt_sumgrades', 'rb_source_detailed_quiz_completion'),
            ],
            [
                'type'    => 'quiz_attempt',
                'value'   => 'timestart',
                'heading' => get_string('quiz_attempt_timestart', 'rb_source_detailed_quiz_completion'),
            ]
        ];
        $this->fullname  = get_string('sourcetitle', 'rb_source_detailed_quiz_completion');
        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;
        parent::__construct();
    }

    public function embedded_global_restrictions_supported()
    {
        return true;
    }

    public function is_capable($reportfor, $report)
    {
        return true;
    }
}