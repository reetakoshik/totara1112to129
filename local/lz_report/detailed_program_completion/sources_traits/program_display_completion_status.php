<?php

trait program_display_completion_status
{
    private $is_display_completion_status_executed = false;

    public function rb_display_program_completion_status($id, $row, $isexport)
    {
        global $DB;
        global $PAGE;

        $this->display_completion_status_exec_once();

        $completion = $DB->get_records('prog_completion', ['id' => $id]);

        if (!$completion) {
            return '';
        }

        $completion  = array_values($completion)[0];

        if ($isexport) {
            $program = new program($completion->programid);
            $progress = $program->get_progress($completion->userid);
            if ($progress == 0) {
                return get_string('notyetstarted', 'completion');
            } elseif ($progress == 100) {
                return get_string('complete');
            } else {
                return get_string('inprogress', 'completion');
            }
        }

        $statusData    = $this->load_program_completion_status_data($completion);
        $progressBar   = $this->render_status_progressbar($completion);
        $completionRow = $this->render_details($statusData);

        return $progressBar.$completionRow;
    }

    private function render_status_progressbar($completion)
    {
        global $OUTPUT;

        $program  = new program($completion->programid);
        $progress = $program->get_progress($completion->userid);

        $templatename = $progress == 100 
            ? 'local_lz_report/progressbar_completed'
            : 'local_lz_report/progressbar_inprogress';

        $id = 'program_progress_'.$completion->userid. '_' . $completion->programid;
        return $OUTPUT->render_from_template($templatename, ['progress' => $progress, 'pid' => $id]);
    }

    private function render_details($statusData)
    {
        global $OUTPUT;
        $templatename = 'local_lz_report/program_status_row';
        $params = $statusData + [
            'emptyMessage' => get_string('empty-message', 'rb_source_detailed_program_completion')
        ];
        return $OUTPUT->render_from_template($templatename, $params);
    }

    private function load_program_completion_status_data($programCompletion)
    {
        global $DB;

        $userid    = $programCompletion->userid;
        $programid = $programCompletion->programid;
        $program   = new program($programid);
        $courseSets = $program->get_content()->get_course_sets();

        $COMPLETIONS = [
            COMPLETION_STATUS_NOTYETSTARTED => [
                'text'  => get_string('notyetstarted', 'completion'),
                'class' => ''
            ],
            COMPLETION_STATUS_INPROGRESS    => [
                'text'  => get_string('inprogress', 'completion'),
                'class' => 'text-warning'
            ],
            COMPLETION_STATUS_COMPLETE      => [
                'text'  => get_string('complete'),
                'class' => 'text-success'
            ],
            COMPLETION_STATUS_COMPLETEVIARPL => [
                'text'  => get_string('completeviarpl', 'completion'),
                'class' => 'text-success'
            ]
        ];

        $ret = ['course_sets' => []];

        foreach ($courseSets as $courseSet) {
            $courses = [];
            foreach ($courseSet->get_courses() as $course) {
                $status = $DB->get_field(
                    'course_completions',
                    'status',
                    ['userid' => $userid, 'course' => $course->id]
                );
                $status = $status ? $status : COMPLETION_STATUS_NOTYETSTARTED;
                $completionText = isset($COMPLETIONS[$status]) 
                    ? $COMPLETIONS[$status]
                    : $COMPLETIONS[COMPLETION_STATUS_NOTYETSTARTED];
                $timecompleted = '';
                if (in_array($status, [COMPLETION_STATUS_COMPLETE, COMPLETION_STATUS_COMPLETEVIARPL])) {
                    $timecompleted = $DB->get_field(
                        'course_completions',
                        'timecompleted',
                        ['userid' => $userid, 'course' => $course->id]
                    );
                    $timecompleted = (new Datetime())->setTimestamp($timecompleted)->format('d/m/Y');
                }
                $courses[] = [
                    'name'             => $course->fullname,
                    'completionStatus' => $completionText['text'],
                    'completionClass'  => $completionText['class'],
                    'completionDate'   => $timecompleted
                ];
            }
            $ret['course_sets'][] = [
                'name'         => $courseSet->label,
                'requirements' => $this->get_completion_explanation_html($courseSet),
                'courses'      => $courses
            ];
        }

        return $ret;
    }

    public function get_completion_explanation_html($courseSet)
    {
        if ($courseSet->completiontype == COMPLETIONTYPE_ALL) {
            return str_replace('.', '', get_string('completeallcourses', 'totara_program'));
        }

        if ($courseSet->completiontype == COMPLETIONTYPE_ANY) {
            return str_replace('.', '', get_string('completeanycourse', 'totara_program'));
        } 

        $a = new stdClass();
        $a->mincourses = $courseSet->mincourses;
        return str_replace('.', '', get_string('completemincourses', 'totara_program', $a));  
    }

    private function display_completion_status_exec_once()
    {
        global $PAGE;
        if ($this->is_display_completion_status_executed) {
            return;
        }
        $PAGE->requires->js('/local/lz_report/js/report.js');
        $PAGE->requires->js_init_call('initProgramReport');
        $this->is_display_completion_status_executed = true;
    }
}

