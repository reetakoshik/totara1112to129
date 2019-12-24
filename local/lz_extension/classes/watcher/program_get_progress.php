<?php

namespace local_lz_extension\watcher;
  
class program_get_progress 
{
    public static function execute(\local_lz_extension\hook\program_get_progress $hook)
    {
        global $DB;
        
        if (!prog_courseset_group_complete($hook->courseset_group, $hook->userid, false)) {
            $coursesInGroup = 0;
            foreach ($hook->courseset_group as $courseset) {
                $courses = $courseset->get_courses();
                $coursesInGroup += count($courses);
            }

            $coursesCompletedInGroup = 0;
            foreach ($hook->courseset_group as $courseset) {
                $courses = $courseset->get_courses();
                foreach ($courses as $course) {
                    $completion_info = new \completion_info($course);
                    $status = $DB->get_field(
                        'course_completions',
                        'status',
                        ['userid' => $hook->userid, 'course' => $course->id]
                    );
                    if ($status === COMPLETION_STATUS_INPROGRESS) {
                        $coursesCompletedInGroup += 0.5;
                    } elseif ($completion_info->is_course_complete($hook->userid)) {
                        $coursesCompletedInGroup += 1;   
                    }
                }
            }

            $hook->courseset_group_complete_count += $coursesCompletedInGroup / $coursesInGroup;
        }
    }
}