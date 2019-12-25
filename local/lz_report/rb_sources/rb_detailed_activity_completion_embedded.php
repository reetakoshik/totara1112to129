<?php

class rb_detailed_activity_completion_embedded extends rb_base_embedded
{
    public $contentmode;
    public $contentsettings;
    public $url;
    public $hidden;
    public $accessmode;
    public $accesssettings;
    public $source = 'detailed_activity_completion';
    public $shortname = 'detailed_activity_completion';
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
            'type'     => 'module',
            'value'    => 'title',
            'advanced' => 0
        ],
        [
            'type'     => 'module_completion',
            'value'    => 'completionstate',
            'advanced' => 0
        ],
        [
            'type'     => 'module_completion',
            'value'    => 'timecompleted',
            'advanced' => 0
        ]
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
                'type'    => 'module',
                'value'   => 'type',
                'heading' => get_string('moduletype', 'rb_source_detailed_activity_completion'),
            ],
            [
                'type'    => 'module',
                'value'   => 'title',
                'heading' => get_string('moduletitle', 'rb_source_detailed_activity_completion'),
            ],
            [
                'type'    => 'course',
                'value'   => 'courselink',
                'heading' => get_string('courselink', 'rb_source_activities'),
            ],
            [
                'type'    => 'module_completion',
                'value'   => 'completionstate',
                'heading' => get_string('completionstate', 'rb_source_detailed_activity_completion'),
            ],
            [
                'type'    => 'module_completion',
                'value'   => 'timecompleted',
                'heading' => get_string('timecompleted', 'rb_source_detailed_activity_completion'),
            ]
        ];
        $this->fullname  = get_string('sourcetitle', 'rb_source_detailed_activity_completion');
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