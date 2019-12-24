<?php

namespace local_lz_extension\watcher;
  
class rb_source_dp_course_construct 
{
    public static function execute(\local_lz_extension\hook\rb_source_dp_course_construct $hook)
    {
        global $DB, $USER;
        
        $sql = $hook->source->get_dp_status_base_sql();

        $excludeCourseIds = implode(', ', array_keys($DB->get_records_sql("
            SELECT DISTINCT course.id
            FROM mdl_user_enrolments ue
            JOIN mdl_enrol e ON ue.enrolid = e.id AND e.status = 0
            JOIN mdl_course course ON course.id = e.courseid
            JOIN mdl_context context ON context.instanceid = course.id AND context.contextlevel = 50
            JOIN mdl_role_assignments role_assignments 
                ON context.id = role_assignments.contextid AND role_assignments.roleid <> 5
            WHERE ue.userid = {$USER->id}
        ")));

        $contextlevel = CONTEXT_COURSE;
        $id = $DB->get_record('role', ['shortname' => 'student'])->id;

        if ($excludeCourseIds) {
            $aditionalFilter = " 
                JOIN {context} context ON context.instanceid = course.id AND context.contextlevel = $contextlevel
                JOIN {role_assignments} role_assignments 
                    ON context.id = role_assignments.contextid
                    AND role_assignments.roleid = $id 
            ";

            $sql = str_replace(
                "JOIN {enrol} e ON ue.enrolid = e.id",
                "JOIN {enrol} e ON ue.enrolid = e.id AND e.status = 0
                 JOIN {course} course ON course.id = e.courseid AND course.id NOT IN($excludeCourseIds)
                 $aditionalFilter",
                $sql
            );

            $sql = str_replace(
                "FROM {course_completions} cc",
                "FROM {course_completions} cc
                 JOIN {course} course ON course.id = cc.course AND course.id NOT IN($excludeCourseIds)
                 $aditionalFilter",
                $sql
            );

            $sql = str_replace(
                "JOIN {dp_plan} p1 ON pca1.planid = p1.id",
                "JOIN {dp_plan} p1 ON pca1.planid = p1.id
                 JOIN {course} course ON course.id = pca1.courseid AND course.id NOT IN($excludeCourseIds)
                 $aditionalFilter",
                $sql
            );
        }

        if ($USER->id) {
            $sql = str_replace('basesub', "basesub WHERE basesub.userid = {$USER->id}", $sql);
        }

        $hook->source->base = $sql;
    }
}