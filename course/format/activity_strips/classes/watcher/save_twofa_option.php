<?php

namespace format_activity_strips\watcher;

class save_twofa_option 
{
    public static function execute(\format_activity_strips\hook\save_twofa_option $hook)
    {
        global $DB;

        $module = $DB->get_record('modules', ['name' => $hook->modulename]);
        $activity = $DB->get_record('course_modules', ['module' => $module->id, 'instance' => $hook->data->id]);

        $DB->delete_records('display_options', ['cmid' => $activity->id]);

        $DB->insert_record('display_options', (object)[
        	'cmid' => $activity->id,
        	'enable_twofa' => !empty($hook->data->twofa) ? 1: 0
        ]);
    }
}