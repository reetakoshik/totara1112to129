<?php

trait program_completion_joins
{
	private function add_program_completion_tables_to_joinlist(
        &$joinlist,
        $usertable = 'base',
        $useridfield = 'id'
    ) {
        $joinlist[] = new rb_join(
            'prog_user_assignment',
            'INNER',
            '(SELECT programid, userid FROM {prog_user_assignment} GROUP BY programid, userid)',
            "prog_user_assignment.userid = $usertable.$useridfield",
            REPORT_BUILDER_RELATION_ONE_TO_MANY,
            $usertable
        );

        $joinlist[] = new rb_join(
            'prog',
            'LEFT',
            '{prog}',
            "prog.id = prog_user_assignment.programid",
            REPORT_BUILDER_RELATION_ONE_TO_MANY,
            'prog_user_assignment'
        );

        $joinlist[] = new rb_join(
            'prog_completion',
            'LEFT',
            '{prog_completion}',
            "prog_completion.programid = prog.id 
                AND prog_completion.userid = $usertable.$useridfield 
                AND prog_completion.coursesetid = 0",
            REPORT_BUILDER_RELATION_ONE_TO_MANY,
            'prog'
        );

        $joinlist[] = new rb_join(
            'certif_completion',
            'LEFT',
            '{certif_completion}',
            "certif_completion.userid = base.id AND certif_completion.certifid = prog.certifid",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            ['base', 'prog']
        );

        $joinlist[] = new rb_join(
            'completion_organisation',
            'LEFT',
            '{org}',
            'completion_organisation.id = prog_completion.organisationid',
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            'prog_completion'
        );

        $joinlist[] = new rb_join(
            'completion_position',
            'LEFT',
            '{pos}',
            'completion_position.id = prog_completion.positionid',
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            'prog_completion'
        );
	}

    /**
     * Adds the certification table to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     'certif id' field
     * @param string $field Name of table containing program id field to join on
     */
    protected function add_certification_table_to_joinlist(
        &$joinlist,
        $join = 'prog',
        $field = 'certifid'
    ) {
        $joinlist[] = new rb_join(
            'certif',
            'LEFT',
            '{certif}',
            "certif.id = $join.$field",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            $join
        );
    }
}
