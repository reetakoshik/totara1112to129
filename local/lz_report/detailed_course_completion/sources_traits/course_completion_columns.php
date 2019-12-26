<?php

trait course_completion_columns
{
    private function add_course_completion_fields_to_column(&$columns) 
    {
        $columns = array_merge($columns, [
            new rb_column_option(
                'course_completion',
                'iscomplete',
                get_string('iscompleteany', 'rb_source_course_completion'),
                'CASE WHEN course_completion.status = ' . COMPLETION_STATUS_COMPLETE . ' 
                      OR course_completion.status = ' . COMPLETION_STATUS_COMPLETEVIARPL . ' THEN 1 ELSE 0 END',
                [
                    'displayfunc'    => 'yes_or_no',
                    'dbdatatype'     => 'boolean',
                    'defaultheading' => get_string('iscomplete', 'rb_source_course_completion'),
                    'joins'          => ['course_completion']
                ]
            ),
            new rb_column_option(
                'course_completion',
                'isnotcomplete',
                get_string('isnotcomplete', 'rb_source_course_completion'),
                'CASE WHEN course_completion.status = ' . COMPLETION_STATUS_COMPLETE . ' OR course_completion.status = ' . COMPLETION_STATUS_COMPLETEVIARPL . ' THEN 0 ELSE 1 END',
                [
                    'displayfunc'    => 'yes_or_no',
                    'dbdatatype'     => 'boolean',
                    'defaultheading' => get_string('isnotcomplete', 'rb_source_course_completion'),
                    'joins'          => ['course_completion']
                ]
            ),
            new rb_column_option(
                'course_completion',
                'iscompletenorpl',
                get_string('iscompletenorpl', 'rb_source_course_completion'),
                'CASE WHEN course_completion.status = ' . COMPLETION_STATUS_COMPLETE . ' THEN 1 ELSE 0 END',
                [
                    'displayfunc'    => 'yes_or_no',
                    'dbdatatype'     => 'boolean',
                    'defaultheading' => get_string('iscomplete', 'rb_source_course_completion'),
                    'joins'          => ['course_completion']
                ]
            ),
            new rb_column_option(
                'course_completion',
                'iscompleterpl',
                get_string('iscompleterpl', 'rb_source_course_completion'),
                'CASE WHEN course_completion.status = ' . COMPLETION_STATUS_COMPLETEVIARPL . ' THEN 1 ELSE 0 END',
                array(
                    'displayfunc'    => 'yes_or_no',
                    'dbdatatype'     => 'boolean',
                    'defaultheading' => get_string('iscomplete', 'rb_source_course_completion'),
                    'joins'          => ['course_completion']
                )
            ),
            new rb_column_option(
                'course_completion',
                'isinprogress',
                get_string('isinprogress', 'rb_source_course_completion'),
                'CASE WHEN course_completion.status = ' . COMPLETION_STATUS_INPROGRESS . ' THEN 1 ELSE 0 END',
                array(
                    'displayfunc'    => 'yes_or_no',
                    'dbdatatype'     => 'boolean',
                    'defaultheading' => get_string('isinprogress', 'rb_source_course_completion'),
                    'joins'          => ['course_completion']
                )
            ),
            new rb_column_option(
                'course_completion',
                'isnotyetstarted',
                get_string('isnotyetstarted', 'rb_source_course_completion'),
                'CASE WHEN course_completion.status = ' . COMPLETION_STATUS_NOTYETSTARTED . ' THEN 1 ELSE 0 END',
                array(
                    'displayfunc'    => 'yes_or_no',
                    'dbdatatype'     => 'boolean',
                    'defaultheading' => get_string('isnotyetstarted', 'rb_source_course_completion'),
                    'joins'          => ['course_completion']
                )
            ),
            new rb_column_option(
                'course_completion',
                'completeddate',
                get_string('completiondate', 'rb_source_course_completion'),
                'course_completion.timecompleted',
                array(
                    'displayfunc' => 'nice_date_with_status',
                    'dbdatatype'  => 'timestamp', 
                    'joins'       => ['course_completion'],
                    'extrafields' => [
                        'status' => 'course_completion.status'
                    ]
                )
            ),
            new rb_column_option(
                'course_completion',
                'starteddate',
                get_string('datestarted', 'rb_source_course_completion'),
                'course_completion.timestarted',
                [
                    'displayfunc' => 'nice_date',
                    'dbdatatype'  => 'timestamp',
                    'joins'       => ['course_completion']
                ]
            ),
            new rb_column_option(
                'course_completion',
                'enrolleddate',
                get_string('dateenrolled', 'rb_source_course_completion'),
                'course_completion.timeenrolled',
                [
                    'displayfunc' => 'nice_date',
                    'dbdatatype'  => 'timestamp',
                    'joins'       => ['course_completion']
                ]
            ),
            new rb_column_option(
                'course_completion',
                'organisationid',
                get_string('completionorgid', 'rb_source_course_completion'),
                'course_completion.organisationid',
                ['joins' => 'course_completion']
            ),
            new rb_column_option(
                'course_completion',
                'organisationid2',
                get_string('completionorgid', 'rb_source_course_completion'),
                'course_completion.organisationid',
                [
                    'selectable' => false,
                    'joins'      => 'course_completion'
                ]
            ),
            new rb_column_option(
                'course_completion',
                'organisationpath',
                get_string('completionorgpath', 'rb_source_course_completion'),
                'completion_organisation.path',
                [
                    'joins' => ['completion_organisation', 'course_completion'],
                    'selectable' => false
                ]
            ),
            new rb_column_option(
                'course_completion',
                'organisation',
                get_string('completionorgname', 'rb_source_course_completion'),
                'completion_organisation.fullname',
                [
                    'joins'        => ['completion_organisation','course_completion'],
                    'dbdatatype'   => 'char',
                    'outputformat' => 'text'
                ]
            ),
            new rb_column_option(
                'course_completion',
                'positionid',
                get_string('completionposid', 'rb_source_course_completion'),
                'course_completion.positionid',
                [
                    'joins' => 'course_completion'
                ]
            ),
            new rb_column_option(
                'course_completion',
                'positionid2',
                get_string('completionposid', 'rb_source_course_completion'),
                'course_completion.positionid',
                ['selectable' => false, 'joins' => 'course_completion']
            ),
            new rb_column_option(
                'course_completion',
                'positionpath',
                get_string('completionpospath', 'rb_source_course_completion'),
                'completion_position.path',
                array('joins' => ['completion_position', 'course_completion'], 'selectable' => false)
            ),
            new rb_column_option(
                'course_completion',
                'position',
                get_string('completionposname', 'rb_source_course_completion'),
                'completion_position.fullname',
                array('joins' => 'completion_position',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text')
            ),
            new rb_column_option(
                'course_completion',
                'grade',
                get_string('grade', 'rb_source_course_completion'),
                'CASE WHEN course_completion.status = ' . COMPLETION_STATUS_COMPLETEVIARPL . ' THEN course_completion.rplgrade
                      ELSE grade_grades.finalgrade END',
                array(
                    'joins' => 'grade_grades',
                    'displayfunc' => 'course_grade_percent',
                )
            ),
            new rb_column_option(
                'course_completion',
                'passgrade',
                get_string('passgrade', 'rb_source_course_completion'),
                'criteria.gradepass',
                array(
                    'joins' => 'criteria',
                    'displayfunc' => 'percent',
                )
            ),
            new rb_column_option(
                'course_completion',
                'gradestring',
                get_string('requiredgrade', 'rb_source_course_completion'),
                'CASE WHEN course_completion.status = ' . COMPLETION_STATUS_COMPLETEVIARPL . ' THEN course_completion.rplgrade
                      ELSE grade_grades.finalgrade END',
                array(
                    'joins' => array('criteria', 'grade_grades'),
                    'displayfunc' => 'grade_string',
                    'extrafields' => array(
                        'gradepass' => 'criteria.gradepass',
                    ),
                    'defaultheading' => get_string('grade', 'rb_source_course_completion'),
                )
            ),
        ]);
    }

    public function rb_display_nice_date_with_status($date, $row, $isexport)
    {
        if (!$date) {
            return $date;
        }

        $viarpl = $row->status == COMPLETION_STATUS_COMPLETEVIARPL 
            ? '('.get_string('completeviarpl', 'completion').')'
            : '';

        return userdate($date, get_string('strfdateshortmonth', 'langconfig'))." $viarpl";
    }
}
