<?php

namespace format_activity_strips\watcher;

require_once __DIR__.'/../../lib/activity_completion.php';

class self_completion_form 
{
    public static function execute(\format_activity_strips\hook\self_completion_form $hook)
    {
    	global $DB;

        $record = $DB->get_record('display_options', ['cmid' => $hook->params['activity_id']]);
        $twofa = $record && $record->enable_twofa ? 1 : 0;

        if ($twofa) {
            $hook->form = new \format_activity_strips\activity_completion($hook->params);
        }
    }
}
