<?php

trait course_completion_filters
{
    private function add_course_completion_fields_to_filters(&$filters)
    {
        $filters = array_merge($filters, [
            new rb_filter_option(
                'course_completion',
                'completeddate',
                get_string('datecompleted', 'rb_source_course_completion'),
                'date'
            ),
            new rb_filter_option(
                'course_completion',
                'starteddate',
                get_string('datestarted', 'rb_source_course_completion'),
                'date'
            ),
            new rb_filter_option(
                'course_completion',
                'enrolleddate',
                get_string('dateenrolled', 'rb_source_course_completion'),
                'date'
            ),
            new rb_filter_option(
                'course_completion',
                'status',
                get_string('completionstatus', 'rb_source_course_completion'),
                'multicheck',
                array(
                    'selectfunc' => 'completion_status_list',
                    'attributes' => rb_filter_option::select_width_limiter(),
                )
            ),
            new rb_filter_option(
                'course_completion',
                'iscomplete',
                get_string('iscompleteany', 'rb_source_course_completion'),
                'select',
                [
                    'selectfunc' => 'yesno_list',
                    'simplemode' => true,
                ]
            ),
            new rb_filter_option(
                'course_completion',
                'isnotcomplete',
                get_string('isnotcomplete', 'rb_source_course_completion'),
                'select',
                [
                    'selectfunc' => 'yesno_list',
                    'simplemode' => true,
                ]
            ),
            new rb_filter_option(
                'course_completion',
                'iscompletenorpl',
                get_string('iscompletenorpl', 'rb_source_course_completion'),
                'select',
                [
                    'selectfunc' => 'yesno_list',
                    'simplemode' => true,
                ]
            ),
            new rb_filter_option(
                'course_completion',
                'iscompleterpl',
                get_string('iscompleterpl', 'rb_source_course_completion'),
                'select',
                [
                    'selectfunc' => 'yesno_list',
                    'simplemode' => true,
                ]
            ),
            new rb_filter_option(
                'course_completion',
                'isinprogress',
                get_string('isinprogress', 'rb_source_course_completion'),
                'select',
                [
                    'selectfunc' => 'yesno_list',
                    'simplemode' => true,
                ]
            ),
            new rb_filter_option(
                'course_completion',
                'isnotyetstarted',
                get_string('isnotyetstarted', 'rb_source_course_completion'),
                'select',
                [
                    'selectfunc' => 'yesno_list',
                    'simplemode' => true,
                ]
            ),
            new rb_filter_option(
                'course_completion',
                'organisationid',
                get_string('officewhencompletedbasic', 'rb_source_course_completion'),
                'select',
                [
                    'selectfunc' => 'positions_list',
                    'attributes' => rb_filter_option::select_width_limiter()
                ]
            ),
            new rb_filter_option(
                'course_completion',
                'organisationpath',
                get_string('orgwhencompleted', 'rb_source_course_completion'),
                'hierarchy',
                ['hierarchytype' => 'pos']
            ),
            new rb_filter_option(
                'course_completion',
                'organisationid2',
                get_string('multiorgwhencompleted', 'rb_source_course_completion'),
                'hierarchy_multi',
                ['hierarchytype' => 'pos']
            ),
            new rb_filter_option(
                'course_completion',
                'positionid',
                get_string('poswhencompletedbasic', 'rb_source_course_completion'),
                'select',
                [
                    'selectfunc' => 'positions_list',
                    'attributes' => rb_filter_option::select_width_limiter()
                ]
            ),
            new rb_filter_option(
                'course_completion',
                'positionid2',
                get_string('multiposwhencompleted', 'rb_source_course_completion'),
                'hierarchy_multi',
                ['hierarchytype' => 'pos']
            ),
            new rb_filter_option(
                'course_completion',
                'positionpath',
                get_string('poswhencompleted', 'rb_source_course_completion'),
                'hierarchy',
                ['hierarchytype' => 'pos']
            ),
            new rb_filter_option(
                'course_completion',
                'grade',
                get_string('grade', 'rb_source_course_completion'),
                'number'
            ),
            new rb_filter_option(
                'course_completion',
                'passgrade',
                'Required Grade',
                'number'
            ),
            new rb_filter_option(
                'course_completion',
                'enrolled',
                get_string('isenrolled', 'rb_source_course_completion'),
                'enrol',
                [],
                // special enrol filter requires a composite field
                ['course' => 'course_completion.course', 'user' => 'course_completion.userid']
            ),
        ]);
    }

    public function rb_filter_completion_status_list() {
        global $CFG;
        require_once($CFG->dirroot.'/completion/completion_completion.php');
        global $COMPLETION_STATUS;

        $statuslist = array();
        foreach ($COMPLETION_STATUS as $key => $value) {
            $statuslist[(string)$key] = get_string($value, 'completion');
        }
        return $statuslist;
    }
}