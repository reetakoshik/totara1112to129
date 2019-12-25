<?php

/**
 * used by block cron to obtain daily usage stats.
 *
 * @param int $from - timestamp for start of stats generation
 * @param int $to - timestamp for end of stats generation
 * @return array
 */
function tool_policy_timespent($from, $to) {
    global $CFG;
    $minutesbetweensession = 30; //used to define new session
    if (!empty($CFG->block_totara_stats_minutesbetweensession)) {
        $minutesbetweensession = $CFG->block_totara_stats_minutesbetweensession;
    }
    //calculate timespent by each user
    $logs = totara_stats_get_logs($from, $to);
    $totalTime = array();
    $lasttime = array();
        if (!empty($logs)){
            foreach($logs as $aLog){
                if (empty($lasttime[$aLog->userid])) {
                    $lasttime[$aLog->userid] = $from;
                }
                if (!isset($totalTime[$aLog->userid])) {
                    $totalTime[$aLog->userid] = 0;
                }

                $delta = $aLog->time - $lasttime[$aLog->userid];
                if ($delta < $minutesbetweensession * MINSECS){
                    $totalTime[$aLog->userid] = $totalTime[$aLog->userid] + $delta;
                }
                $lasttime[$aLog->userid] = $aLog->time;
            }
        }
    $logs->close();
    return $totalTime;
}