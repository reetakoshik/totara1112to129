<?php

require_once __DIR__.'/../activity_search/sources_traits/activities_base.php';
require_once __DIR__.'/../activity_search/sources_traits/activities_columns.php';
require_once __DIR__.'/../activity_search/sources_traits/activities_content.php';
require_once __DIR__.'/../activity_search/sources_traits/activities_filters.php';

class rb_source_activities extends rb_base_source
{
    use activities_base;
    use activities_columns;
    use activities_content;
    use activities_filters;

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
            'type'  => 'activity',
            'value' => 'modulename',
        ],
        [
            'type'  => 'activity',
            'value' => 'imname',
        ],
        [
            'type'  => 'course',
            'value' => 'lcfullname',
        ]
    ];
    public $defaultfilters = [
        [
            'type'  => 'activity',
            'value' => 'modulename',
        ],
        [
            'type'  => 'activity',
            'value' => 'mname',
        ],
        [
            'type'  => 'course',
            'value' => 'cfullname',
        ]
    ];

    public function __construct()
    {    
        $this->add_activity_fields_to_columns($this->columnoptions);
        $this->add_activity_fields_to_filters($this->filteroptions);
        $this->add_activities_content($this->contentoptions);
        
        $this->base = $this->init_base();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_activities');
        $this->cacheable = true;

        parent::__construct();
    }
}
