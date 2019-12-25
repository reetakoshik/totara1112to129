<?php

require_once 'rb_source_detailed_course_completion.php';

class rb_detailed_course_completion_embedded extends rb_base_embedded
{
    public $contentmode;
    public $contentsettings;
    public $url;
    public $hidden;
    public $accessmode;
    public $accesssettings;
    public $source = 'detailed_course_completion';
    public $shortname = 'detailed_course_completion';
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
            'type'     => 'course_completion',
            'value'    => 'status',
            'advanced' => 0
        ],
        [
            'type'     => 'course_completion',
            'value'    => 'completeddate',
            'advanced' => 0
        ]
    ];

    public function __construct()
    {
        $this->fullname  = get_string('sourcetitle', 'rb_source_detailed_course_completion');
        $this->columns = [
            [
                'type'    => 'user',
                'value'   => 'namelinkicon',
                'heading' => get_string('usernamelinkicon', 'totara_reportbuilder'),
            ],
            [
                'type'    => 'course_completion',
                'value'   => 'status',
                'heading' =>  get_string('completionstatus', 'rb_source_course_completion'),
            ],
            [
                'type'    => 'course_completion',
                'value'   => 'completeddate',
                'heading' => get_string('completiondate', 'rb_source_course_completion'),
            ]
        ];
        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;
        if (isset($_GET['id'])) {
            $this->embeddedparams['courseid'] = intval($_GET['id']);
        }
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
