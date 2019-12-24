<?php

require_once 'rb_source_activities.php';

class rb_activities_embedded extends rb_base_embedded {

    public $url, $source, $fullname, $filters, $columns;
    public $contentmode, $contentsettings, $embeddedparams;
    public $hidden, $accessmode, $accesssettings, $shortname;

    public function __construct($data)
    {
        $this->source = 'activities';
        $this->shortname = 'activities';
		$this->url = '/local/lz_report/activity_search/embedded.php';
        $this->fullname = get_string('sourcetitle', 'rb_source_activities');
        $this->columns = [
            [
                'type'    => 'activity',
                'value'   => 'lmodulename',
                'heading' => get_string('link', 'rb_source_activities'),
            ],
            [
                'type'    => 'activity',
                'value'   => 'imname',
                'heading' => get_string('icon', 'rb_source_activities'),
            ],
            [
                'type'    => 'course',
                'value'   => 'lcfullname',
                'heading' => get_string('courselink', 'rb_source_activities'),
            ],
        ];

        $this->filters = [
            [
                'type'     => 'activity',
                'value'    => 'modulename',
                'advanced' => 0,
            ],
            [
                'type'     => 'activity',
                'value'    => 'mname',
                'advanced' => 0,
            ],
            [
                'type'     => 'course',
                'value'    => 'cfullname',
                'advanced' => 0,
            ],
        ];

        // no restrictions
        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;

        parent::__construct();
    }
    /**
     * Check if the user is capable of accessing this report.
     * We use $reportfor instead of $USER->id and $report->get_param_value() instead of getting params
     * some other way so that the embedded report will be compatible with the scheduler (in the future).
     *
     * @param int $reportfor userid of the user that this report is being generated for
     * @param reportbuilder $report the report object - can use get_param_value to get params
     * @return boolean true if the user can access this report
     */
    public function is_capable($reportfor, $report)
    {
       return true;
    }
}
