<?php

return [
    'setup_page' => function($reportid) {
        require_login();
        global $PAGE;
        $PAGE->set_context(context_system::instance());
        $PAGE->set_url('/totara/reportbuilder/report.php', array('id' => $reportid));
        $PAGE->set_totara_menu_selected('myreports');
        $PAGE->set_pagelayout('noblocks');
    },
    'users_assigned_to_program' => function($reportid) {
        $report = new summarized_reportbuilder($reportid);
        $select = [
            'prog.id as program_id',
            'prog.fullname as program_fullname',
            'COUNT(base.id) as users_count'
        ];
        $groupBy = ['program_id', 'program_fullname'];
        $records = $report->make_query($select, $groupBy);
        $ret = [];
        foreach ($records as $record) {
            $ret[] = [
                'id'          => $record->program_id,
                'fullname'    => $record->program_fullname,
                'users_count' => $record->users_count,
            ];
        }
        return $ret;
    },
    'users_with_program_completion_status' => function($reportid) {
        $report = new summarized_reportbuilder($reportid);
        $select = [
            'prog_completion.status as completion_status',
            'COUNT(DISTINCT base.id) as users_count'
        ];
        $groupBy = ['completion_status'];
        $records = $report->make_query($select, $groupBy);
        $ret = [];
        foreach ($records as $record) {
            $completion_status = $record->completion_status
                ? get_string('complete', 'totara_program')
                : get_string('incomplete', 'totara_program');
            $ret[] = [
                'completion_status' => $completion_status,
                'users_count'       => $record->users_count,
            ];
        }
        return $ret;
    },
    'print_json' => function($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
];
