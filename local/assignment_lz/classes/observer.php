<?php

require_once($CFG->dirroot . '/mod/assign/locallib.php');

class local_assignment_lz_observer {

    private static $managers = [];

    /**
     * Manage 'assignment' events
     * @param \core\event\base $eventdata
     */
    public static function notify_direct_manager(core\event\base $eventdata) {
        global $COURSE, $USER, $DB;

        $assign = $eventdata->get_assign();
        $cm_info = $assign->get_course_module();
        $assignment = $assign->get_instance();

        // 'marking workflow' & 'marking allocation' should be enabled
        if($assignment->markingworkflow && $assignment->markingallocation) {

            $managers = self::getManagers($USER->id);

            if(!empty($managers)) {

                // Enroll user's manager with a specific role to the course
                $enrol = $DB->get_record('enrol', array('courseid' =>  $eventdata->courseid, 'enrol' => 'manual'));
                $plugin = enrol_get_plugin('manual');

                $role_config = get_config('local_assignment_lz', 'role');
                $role_shortname = $role_config ? $role_config : 'teacher';

                $role = $DB->get_record('role', array('shortname'=>$role_shortname), 'id');

                foreach ($managers as $manager) {
                    $plugin->enrol_user($enrol, $manager, $role->id);
                }

                // Assign manager as a marker to the submission
                $assignmentObj = new stdClass();
                $assignmentObj->userid     = $USER->id;
                $assignmentObj->assignment = $assignment->id;

                $assignment_props = $DB->get_record('assign_user_flags', (array)$assignmentObj, 'id');

                $assignmentObj->id              = $assignment_props ? $assignment_props->id : '';
                $assignmentObj->workflowstate   = 'inmarking';
                $assignmentObj->allocatedmarker = $managers[0];

                $assignment_props
                    ? $DB->update_record('assign_user_flags', $assignmentObj)
                    : $DB->insert_record('assign_user_flags', $assignmentObj, false);

                // Notify direct manager
                $event_arr = explode('\\', $eventdata->eventname);
                $eventname = str_replace('_', '', array_pop($event_arr));

                $mdata = new stdClass();
                $mdata->username   = $USER->firstname . ' ' . $USER->lastname;
                $mdata->assignment = $assignment->name;
                $mdata->url = html_writer::link(new moodle_url('/mod/assign/view.php', ['id' => $cm_info->id]), 'It is available on the web site.');
                $mdata->date = date('D, j M Y, g:i A', $eventdata->timecreated);
                $mdata->path = html_writer::link(new moodle_url('/course/view.php', ['id' => $COURSE->id]), $COURSE->shortname)
                      .' -> ' .html_writer::link(new moodle_url('/mod/assign/index.php', ['id' => $COURSE->id]), 'Assignment')
                      .' -> ' .html_writer::link(new moodle_url('/mod/assign/view.php', ['id' => $cm_info->id]), $assignment->name);

                $message = new \core\message\message();
                $message->name              = $eventname;
                $message->component         = 'local_assignment_lz';
                $message->modulename        = 'assignment_lz';
                $message->contexturl        = $eventdata->get_url()->out();
                $message->contexturlname    = $assignment->name;
                $message->courseid          = $eventdata->courseid;
                $message->notification      = 1;
                $message->userfrom          = '-10';
                $message->userto            = $managers[0];
                $message->subject           = get_string($eventname.':smallmessage', 'local_assignment_lz', $mdata);
                $message->smallmessage      = get_string($eventname.':smallmessage', 'local_assignment_lz', $mdata);
                $message->fullmessage       = get_string($eventname.':fullmessage', 'local_assignment_lz', $mdata);
                $message->fullmessageformat = FORMAT_PLAIN;
                $message->fullmessagehtml   = get_string($eventname.':fullmessagehtml', 'local_assignment_lz', $mdata);

                message_send($message);
            }
        }
    }

    /**
     * Retrieve all managers recursively
     * @param $userid
     * @return array
     */
    private static function getManagers($userid)
    {
        global $DB;

        $userid = is_array($userid) ? $userid : [$userid];

        $sql = <<<SQL
            SELECT manager.id
            FROM {user} u
               LEFT JOIN {job_assignment} uja
                  ON uja.userid = u.id
               LEFT JOIN {job_assignment} mja
                  ON mja.id = uja.managerjaid
               LEFT JOIN {user} manager
                  ON mja.userid = manager.id
            WHERE u.id IN (?)
            GROUP BY manager.id
SQL;

        $managers = $DB->get_records_sql($sql, $userid);
        $managers = array_filter(array_keys($managers));

        if(!empty($managers)) {
            self::$managers = array_merge(self::$managers, $managers);
            self::getManagers($managers);
        }
        return self::$managers;
    }

}
