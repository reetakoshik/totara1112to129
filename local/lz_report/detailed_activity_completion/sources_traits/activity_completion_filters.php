<?php

trait activity_completion_filters
{
    private function add_activity_fields_to_filters(&$filters)
    {
        $filters[] = new rb_filter_option(
            'module',
            'title',
            get_string('moduletitle', 'rb_source_detailed_activity_completion'),
            'text'
        );

        $filters[] = new rb_filter_option(
            'module',
            'type',
            get_string('moduletype',  'rb_source_detailed_activity_completion'),
            'multicheck',
            ['selectfunc' => 'activity_type']
        );
    }

	private function add_activity_completion_fields_to_filters(&$filters)
    {
        $filters[] = new rb_filter_option(
            'module_completion',
            'completionstate',
            get_string('completionstate', 'rb_source_detailed_activity_completion'),
            'select',
            ['selectfunc' => 'activity_completion_state']
        );

        $filters[] = new rb_filter_option(
            'module_completion',
            'timecompleted',
            get_string('timecompleted', 'rb_source_detailed_activity_completion'),
            'date'
        );

        $filters[] = new rb_filter_option(
            'module',
            'added',
            get_string('cmadded', 'rb_source_activities'),
            'date'
        );

        $filters[] = new rb_filter_option(
            'module',
            'cmtimemodified',
            get_string('timemodified', 'rb_source_activities'),
            'date'
        );
    }

    public function rb_filter_activity_completion_state()
    {
        return [
            COMPLETION_INCOMPLETE    => get_string('incomplete', 'totara_program'),
            COMPLETION_COMPLETE      => get_string('complete', 'completion'),
            COMPLETION_COMPLETE_PASS => get_string('completion-pass', 'completion'),
            COMPLETION_COMPLETE_FAIL => get_string('completion-fail', 'completion'),
            COMPLETION_COMPLETE_RPL  => get_string('completeviarpl', 'completion'),
        ];
    }

    public function rb_filter_activity_type()
    {
        global $DB;
        $res = $DB->get_records_sql("SELECT id, name FROM {modules} modules");
        $keys = array_map(function($item) {
            return $item->name;
        }, array_values($res));
        $values = array_map(function($item) {
            return get_string('modulename', "mod_{$item->name}");
        }, array_values($res));
        return array_combine($keys, $values);
    }
}
