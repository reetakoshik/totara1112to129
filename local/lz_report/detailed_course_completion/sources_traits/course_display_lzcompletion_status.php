<?php

trait course_display_lzcompletion_status
{
    private $is_display_completion_status_executed = false;

    public function rb_display_course_lzcompletion_status($status, $row, $isexport)
    {
        global $DB;
        global $PAGE;

        $id = $row->id;

        $this->display_completion_status_exec_once();

        $completion    = $DB->get_records('course_completions', ['id' => $id]);
        if (!$completion) {
            return '';
        }

        $completion  = array_values($completion)[0];

        if ($isexport) {
            $COMPLETIONS = [
                COMPLETION_STATUS_NOTYETSTARTED  => get_string('notyetstarted', 'completion'),
                COMPLETION_STATUS_INPROGRESS     => get_string('inprogress', 'completion'),
                COMPLETION_STATUS_COMPLETE       => get_string('complete'),
                COMPLETION_STATUS_COMPLETEVIARPL => get_string('completeviarpl', 'completion')
            ];
            $status = $completion->status;
            return isset($COMPLETIONS[$status]) 
                ? $COMPLETIONS[$status] 
                : $COMPLETIONS[COMPLETION_STATUS_NOTYETSTARTED];
        }
        
        $rowsData    = $this->load_row_data($completion);
        $progressBar = $this->get_status_progressbar($completion, $rowsData['rows']);
        $details     = $this->render_details($rowsData);

        return $progressBar.$details;
    }

    private function load_row_data($courseCompletion)
    {
        global $DB;
        global $CFG;

        $course = $DB->get_record('course', ['id' => $courseCompletion->course], '*', MUST_EXIST);
        $info = new completion_info($course);
        $completions = $info->get_completions($courseCompletion->userid);
        
        $last_type = '';
        $agg_type = false;
        $rows = [];
        foreach ($completions as $completion) {
            $criteria = $completion->get_criteria();
            
            try {
                $details = $criteria->get_details($completion);
            } catch (\Exception $e) {
                continue;
            }
            
            $criteria_group = '';

            if ($last_type !== $details['type']) {
                $last_type = $details['type'];
                $criteria_group .= $last_type;
                // Reset agg type.
                $agg_type = true;
            } else {
                // Display aggregation type.
                if ($agg_type) {
                    $agg = $info->get_aggregation_method($criteria->criteriatype);
                    $criteria_group .= '('. html_writer::start_tag('i');
                    if ($agg == COMPLETION_AGGREGATION_ALL) {
                        $aggstr = core_text::strtolower(get_string('all', 'completion'));
                    } else {
                        $aggstr = core_text::strtolower(get_string('any', 'completion'));
                    }

                    $criteria_group .= html_writer::end_tag('i') .core_text::strtolower(get_string('xrequired', 'block_completionstatus', $aggstr)).')';
                    $agg_type = false;
                }
            }

            $rows[] = [
                'criteria_group'   => $criteria_group,
                'criteria'         => $details['criteria'],
                'requirement'      => $details['requirement'],
                'status'           => $details['status'],
                'complete'         => $completion->is_complete() ? get_string('yes') : get_string('no'),
                'completion_date'  => $completion->timecompleted
                    ? userdate(
                        $completion->timecompleted, 
                        get_string('strftimedate', 'langconfig')
                    ) : '-'
            ];
        }

        return [
            'rows'        => $rows,
            'criteriastr' => $info->get_aggregation_method() == COMPLETION_AGGREGATION_ALL
                ? get_string('criteriarequiredall', 'completion')
                : get_string('criteriarequiredany', 'completion')
        ];
    }

    private function get_status_progressbar($completion, $rows)
    {
        global $OUTPUT;
        static $pid = 1;
        $STATUS_BROGRESSBAR = [
            COMPLETION_STATUS_NOTYETSTARTED  => 'local_lz_report/progressbar_notyetstarted',
            COMPLETION_STATUS_INPROGRESS     => 'local_lz_report/progressbar_inprogress',
            COMPLETION_STATUS_COMPLETE       => 'local_lz_report/progressbar_completed',
            COMPLETION_STATUS_COMPLETEVIARPL => 'local_lz_report/progressbar_completed',
        ];

        $templatename = isset($STATUS_BROGRESSBAR[$completion->status]) 
            ? $STATUS_BROGRESSBAR[$completion->status]
            : $STATUS_BROGRESSBAR[COMPLETION_STATUS_NOTYETSTARTED];

        $total = count($rows);
        $completed = 0;
        foreach ($rows as $row) {
            if ($row['completion_date'] !== '-') {
                $completed++;
            }
        }

        $progress = $total ? (int)($completed / $total * 100) : 0;
        $ppid = $pid;
        $pid++;
       return $OUTPUT->render_from_template($templatename, ['progress' => $progress,'pid' => $ppid]);
    }

    private function render_details($rowsData)
    {
        global $OUTPUT;
        $templatename = 'local_lz_report/course_status_row';
        $params = [
            'rows'          => $rowsData['rows'],
            'criteriastr'   => $rowsData['criteriastr'],
            'completion-requirements' => get_string(
                'completion-requirements', 
                'rb_source_detailed_course_completion'
            ),
            'empty_message' => get_string('empty-message', 'rb_source_detailed_course_completion'),
            'header'        => [
                'criteria_group'   => get_string('criteriagroup', 'block_completionstatus'),
                'criteria'         => get_string('criteria', 'completion'),
                'requirement'      => get_string('requirement', 'block_completionstatus'),
                'status'           => get_string('status'),
                'complete'         => get_string('complete'),
                'completion_date'  => get_string('completiondate', 'report_completion')
            ]
        ];
        return $OUTPUT->render_from_template($templatename, $params);
    }

    private function display_completion_status_exec_once()
    {
        global $PAGE;
        if ($this->is_display_completion_status_executed) {
            return;
        }
        $PAGE->requires->js('/local/lz_report/js/report.js');
        $PAGE->requires->js_init_call('initCourseReport');
        $this->is_display_completion_status_executed = true;
    }
}
