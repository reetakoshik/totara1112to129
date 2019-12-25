<?php

trait program_completion_filters
{
	private function add_program_completion_fields_to_filters(&$filteroptions)
    {
    	$filteroptions[] = new rb_filter_option(
            'progcompletion',
            'starteddate',
            get_string('dateassigned', 'rb_source_program_completion'),
            'date'
        );

        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'completeddate',
            get_string('completeddate', 'rb_source_program_completion'),
            'date'
        );

        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'duedate',
            get_string('duedate', 'rb_source_program_completion'),
            'date'
        );

        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'status',
            get_string('programcompletionstatus', 'rb_source_program_completion'),
            'select',
            array (
                'selectchoices' => array(
                    0 => get_string('incomplete', 'totara_program'),
                    1 => get_string('complete', 'totara_program'),
                ),
                'attributes' => rb_filter_option::select_width_limiter(),
                'simplemode' => true,
            )
        );
        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'iscomplete',
            get_string('iscomplete', 'rb_source_program_completion'),
            'select',
            array(
                'selectfunc' => 'yesno_list',
                'simplemode' => true,
            )
        );
        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'isnotcomplete',
            get_string('isnotcomplete', 'rb_source_program_completion'),
            'select',
            array(
                'selectfunc' => 'yesno_list',
                'simplemode' => true,
            )
        );
        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'isinprogress',
            get_string('isinprogress', 'rb_source_program_completion'),
            'select',
            array(
                'selectfunc' => 'yesno_list',
                'simplemode' => true,
            )
        );
        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'isnotstarted',
            get_string('isnotstarted', 'rb_source_program_completion'),
            'select',
            array(
                'selectfunc' => 'yesno_list',
                'simplemode' => true,
            )
        );
        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'organisationid',
            get_string('orgwhencompletedbasic', 'rb_source_program_completion'),
            'select',
            array(
                'selectfunc' => 'organisations_list',
                'attributes' => rb_filter_option::select_width_limiter(),
            )
        );

        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'organisationid2',
            get_string('multiorgwhencompleted', 'rb_source_program_completion'),
            'hierarchy_multi',
            array(
                'hierarchytype' => 'org',
            )
        );

        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'organisationpath',
            get_string('orgwhencompleted', 'rb_source_program_completion'),
            'hierarchy',
            array(
                'hierarchytype' => 'org',
            )
        );

        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'positionid',
            get_string('poswhencompletedbasic', 'rb_source_program_completion'),
            'select',
            array(
                'selectfunc' => 'positions_list',
                'attributes' => rb_filter_option::select_width_limiter()
            )
        );

        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'positionid2',
            get_string('multiposwhencompleted', 'rb_source_program_completion'),
            'hierarchy_multi',
            array(
                'hierarchytype' => 'pos',
            )
        );

        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'positionpath',
            get_string('poswhencompleted', 'rb_source_program_completion'),
            'hierarchy',
            array(
                'hierarchytype' => 'pos',
            )
        );

        $filteroptions[] = new rb_filter_option(
            'prog',
            'type',
            get_string('type', 'rb_source_detailed_program_completion'),
            'select',
            array(
                'selectchoices' => [
                    get_string('type_certif', 'rb_source_detailed_program_completion') => get_string('type_certif', 'rb_source_detailed_program_completion'),
                    get_string('program', 'totara_program') => get_string('program', 'totara_program'),
                ]
            )
        );

        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'iscertified',
            get_string('iscertified', 'rb_source_certification_completion'),
            'select',
            array(
                'selectfunc' => 'yesno_list',
                'simplemode' => true,
            )
        );
        $filteroptions[] = new rb_filter_option(
            'progcompletion',
            'isnotcertified',
            get_string('isnotcertified', 'rb_source_certification_completion'),
            'select',
            array(
                'selectfunc' => 'yesno_list',
                'simplemode' => true,
            )
        );
    }
}