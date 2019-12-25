<?php

trait program_completion_columns
{
    private function add_program_completion_fields_to_column(
        &$columns,
        $progCompletionTable = 'prog_completion'
    ) {
    	$columns[] = new rb_column_option(
            'progcompletion',
            'status',
            get_string('details','rb_source_detailed_program_completion'),
            "$progCompletionTable.id",
            [
                'displayfunc' => 'program_completion_status',
                'joins'       => $progCompletionTable
            ]
        );

        $columns[] = new rb_column_option(
            'progcompletion',
            'starteddate',
            get_string('dateassigned', 'rb_source_program_completion'),
            "$progCompletionTable.timestarted",
            [
                'displayfunc' => 'nice_date',
                'dbdatatype'  => 'timestamp',
                'joins'       => $progCompletionTable
            ]
        );
        $columns[] = new rb_column_option(
            'progcompletion',
            'iscomplete',
            get_string('iscomplete', 'rb_source_program_completion'),
            "CASE WHEN $progCompletionTable.status = " . STATUS_PROGRAM_COMPLETE . ' THEN 1 ELSE 0 END',
            [
                'displayfunc'    => 'yes_or_no',
                'dbdatatype'     => 'boolean',
                'defaultheading' => get_string('iscomplete', 'rb_source_program_completion'),
                'joins'          => $progCompletionTable
            ]
        );
        $columns[] = new rb_column_option(
            'progcompletion',
            'isnotcomplete',
            get_string('isnotcomplete', 'rb_source_program_completion'),
            "CASE WHEN $progCompletionTable.status <> " . STATUS_PROGRAM_COMPLETE . ' THEN 1 ELSE 0 END',
            // NOTE: STATUS_PROGRAM_INCOMPLETE comparison would be less future-proof here.
            [
                'displayfunc'    => 'yes_or_no',
                'dbdatatype'     => 'boolean',
                'defaultheading' => get_string('isnotcomplete', 'rb_source_program_completion'),
                'joins'          => $progCompletionTable
            ]
        );
        $columns[] = new rb_column_option(
            'progcompletion',
            'isinprogress',
            get_string('isinprogress', 'rb_source_program_completion'),
            "CASE WHEN $progCompletionTable.timestarted > 0 AND $progCompletionTable.status <> " . STATUS_PROGRAM_COMPLETE . ' THEN 1 ELSE 0 END',
            [
                'displayfunc'    => 'yes_or_no',
                'dbdatatype'     => 'boolean',
                'defaultheading' => get_string('isinprogress', 'rb_source_program_completion'),
                'joins'          => $progCompletionTable
            ]
        );
        $columns[] = new rb_column_option(
            'progcompletion',
            'isnotstarted',
            get_string('isnotstarted', 'rb_source_program_completion'),
            "CASE WHEN $progCompletionTable.timestarted = 0 AND $progCompletionTable.status <> " . STATUS_PROGRAM_COMPLETE . ' THEN 1 ELSE 0 END',
            [
                'displayfunc'    => 'yes_or_no',
                'dbdatatype'     => 'boolean',
                'defaultheading' => get_string('isnotstarted', 'rb_source_program_completion'),
                'joins'          => $progCompletionTable
            ]
        );

        $columns[] = new rb_column_option(
            'progcompletion',
            'finalstatus',
            get_string('finalstatus', 'rb_source_detailed_program_completion'),
            " CASE
                        WHEN certif.id IS NOT NULL AND certif_completion.certifpath = ". CERTIFPATH_RECERT ." THEN 1
                        WHEN prog_completion.status = ". STATUS_PROGRAM_COMPLETE ." THEN 1
                    ELSE 0
                    END",
            [
                'displayfunc' => 'yes_or_no',
                'dbdatatype'  => 'boolean',
                'joins'       => ['certif', 'certif_completion', 'prog_completion'],
            ]
        );

        $columns[] = new rb_column_option(
            'progcompletion',
            'completeddate',
            get_string('completeddate', 'rb_source_program_completion'),
            "$progCompletionTable.timecompleted",
            array('displayfunc' => 'nice_date', 'dbdatatype' => 'timestamp')
        );

        $columns[] = new rb_column_option(
            'progcompletion',
            'duedate',
            get_string('duedate', 'rb_source_program_completion'),
            "$progCompletionTable.timedue",
            [
                'displayfunc' => 'nice_datetime',
                'dbdatatype'  => 'timestamp',
                'joins'       => $progCompletionTable
            ]
        );

        $columns[] = new rb_column_option(
            'progcompletion',
            'organisationid',
            get_string('completionorgid', 'rb_source_program_completion'),
            "$progCompletionTable.organisationid",
            ['joins' =>  $progCompletionTable]
        );

        $columns[] = new rb_column_option(
            'progcompletion',
            'organisationid2',
            get_string('completionorgid', 'rb_source_program_completion'),
            "$progCompletionTable.organisationid",
            [
                'selectable' => false,
                'joins'      => $progCompletionTable
            ]
        );

        $columns[] = new rb_column_option(
            'progcompletion',
            'organisationpath',
            get_string('completionorgpath', 'rb_source_program_completion'),
            'completion_organisation.path',
            [
                'joins'      => [$progCompletionTable, 'completion_organisation'],
                'selectable' => false
            ]
        );

        $columns[] = new rb_column_option(
            'progcompletion',
            'organisation',
            get_string('completionorgname', 'rb_source_program_completion'),
            'completion_organisation.fullname',
            [
                'joins'        =>  ['completion_organisation', $progCompletionTable],
                'dbdatatype'   => 'char',
                'outputformat' => 'text'
            ]
        );

        $columns[] = new rb_column_option(
            'progcompletion',
            'positionid',
            get_string('completionposid', 'rb_source_program_completion'),
            "$progCompletionTable.positionid",
            ['joins' => $progCompletionTable]
        );

        $columns[] = new rb_column_option(
            'progcompletion',
            'positionid2',
            get_string('completionposid', 'rb_source_program_completion'),
            "$progCompletionTable.positionid",
            [
                'selectable' => false,
                'joins'      => $progCompletionTable
            ]
        );

        $columns[] = new rb_column_option(
            'progcompletion',
            'positionpath',
            get_string('completionpospath', 'rb_source_program_completion'),
            'completion_position.path',
            [
                'joins'      => ['completion_position', $progCompletionTable],
                'selectable' => false
            ]
        );

        $columns[] = new rb_column_option(
            'progcompletion',
            'position',
            get_string('completionposname', 'rb_source_program_completion'),
            'completion_position.fullname',
            [
                'joins'        => ['completion_position', $progCompletionTable],
                'dbdatatype'   => 'char',
                'outputformat' => 'text'
            ]
        );

        $columns[] = new rb_column_option(
            'progcompletion',
            'iscertified',
            get_string('iscertified', 'rb_source_certification_completion'),
            'CASE WHEN certif_completion.certifpath = ' . CERTIFPATH_RECERT . ' THEN 1 ELSE 0 END',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'yes_or_no',
                'dbdatatype' => 'boolean',
                'defaultheading' => get_string('iscertified', 'rb_source_certification_completion'),
            )
        );
        $columns[] = new rb_column_option(
            'progcompletion',
            'isnotcertified',
            get_string('isnotcertified', 'rb_source_certification_completion'),
            'CASE WHEN certif_completion.certifpath <> ' . CERTIFPATH_RECERT . ' THEN 1 ELSE 0 END',
            array(
                'joins' => 'certif_completion',
                'displayfunc' => 'yes_or_no',
                'dbdatatype' => 'boolean',
                'defaultheading' => get_string('isnotcertified', 'rb_source_certification_completion'),
            )
        );
    }

    /**
     * Adds some common certification info to the $columnoptions array
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $join Name of the join that provides the 'program' table
     * @param string $langfile Source for translation, totara_program or totara_certification
     *
     * @return Boolean
     */
    protected function add_certification_fields_to_columns(
        &$columnoptions,
        $join = 'certif',
        $langfile = 'totara_certification'
    ) {
        $columnoptions[] = new rb_column_option(
            'prog',
            'type',
            get_string('type', 'rb_source_detailed_program_completion'),
            " CASE WHEN $join.id > 0 " .
            " THEN '" . get_string('type_certif', 'rb_source_detailed_program_completion')."'".
            " ELSE '" . get_string('program', 'totara_program')."' END ",
            [ 'joins' => $join ]
        );
        $columnoptions[] = new rb_column_option(
            'certif',
            'recertifydatetype',
            get_string('recertdatetype', 'totara_certification'),
            "$join.recertifydatetype",
            [
                'joins'       => $join,
                'displayfunc' => 'recertifydatetype',
            ]
        );

        $columnoptions[] = new rb_column_option(
            'certif',
            'activeperiod',
            get_string('activeperiod', 'totara_certification'),
            "$join.activeperiod",
            [
                'joins'        => $join,
                'dbdatatype'   => 'char',
                'outputformat' => 'text',
                'displayfunc'  => 'activeperiod'
            ]
        );

        $columnoptions[] = new rb_column_option(
            'certif',
            'windowperiod',
            get_string('windowperiod', 'totara_certification'),
            "$join.windowperiod",
            [
                'joins'        => $join,
                'dbdatatype'   => 'char',
                'outputformat' => 'text',
                'displayfunc'  => 'windowperiod'
            ]
        );

        return true;
    }

    public function rb_display_recertifydatetype($recertifydatetype, $row)
    {
        switch ($recertifydatetype) {
            case CERTIFRECERT_COMPLETION:
                return get_string('editdetailsrccmpl', 'totara_certification');
            case CERTIFRECERT_EXPIRY:
                return get_string('editdetailsrcexp', 'totara_certification');
            case CERTIFRECERT_FIXED:
                return get_string('editdetailsrcfixed', 'totara_certification');
        }
        return get_string('not-applicable', 'rb_source_detailed_program_completion');
    }

    public function rb_display_windowperiod($value)
    {
        return $value ? $value : get_string('not-applicable', 'rb_source_detailed_program_completion');
    }

    public function rb_display_activeperiod($value)
    {
        return $value ? $value : get_string('not-applicable', 'rb_source_detailed_program_completion');
    }
}
