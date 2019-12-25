<?php

require_once 'rb_source_detailed_program_completion.php';

class rb_detailed_program_completion_embedded extends rb_base_embedded
{
    public $contentmode;
    public $contentsettings;
    public $url;
    public $hidden;
    public $accessmode;
    public $accesssettings;
    public $source = 'detailed_program_completion';
    public $shortname = 'detailed_program_completion';
    public $fullname = '';
    public $filters = [];
    public $embeddedparams = [];
    public $columns = [];

    public function __construct()
    {
        $this->columns = [
            [
                'type'    => 'base',
                'value'   => 'namelinkicon',
                'heading' => get_string('usernamelinkicon', 'totara_reportbuilder'),
            ],
            [
                'type'    => 'prog',
                'value'   => 'proglinkicon',
                'heading' => get_string('prognamelinkedicon', 'totara_program'),
            ],
            [
                'type'    => 'progcompletion',
                'value'   => 'status',
                'heading' => get_string('completionstatus', 'rb_source_dp_course'),
            ],
            [
                'type'    => 'progcompletion',
                'value'   => 'duedate',
                'heading' => get_string('programduedate', 'totara_program'),
            ],
            [
                'type'    => 'progcompletion',
                'value'   => 'completeddate',
                'heading' => get_string('completeddate', 'rb_source_program_completion'),
            ]
        ];
        $this->fullname  = get_string('sourcetitle', 'rb_source_detailed_program_completion');
        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;
        if (isset($_GET['id'])) {
            $this->embeddedparams['programid'] = intval($_GET['id']);
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
