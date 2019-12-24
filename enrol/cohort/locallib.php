<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Local stuff for cohort enrolment plugin.
 *
 * @package    enrol_cohort
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/enrol/locallib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/totara/cohort/lib.php');
require_once($CFG->dirroot . '/group/lib.php');


/**
 * Event handler for cohort enrolment plugin.
 *
 * We try to keep everything in sync via listening to events,
 * it may fail sometimes, so we always do a full sync in cron too.
 */
class enrol_cohort_handler {
    /**
     * Event processor - cohort member added.
     * @param \core\event\cohort_member_added $event
     * @return bool
     */
    public static function member_added(\core\event\cohort_member_added $event) {
        global $DB, $CFG;
        require_once("$CFG->dirroot/group/lib.php");

        if (!enrol_is_enabled('cohort')) {
            return true;
        }

        // Does any enabled cohort instance want to sync with this cohort?
        $sql = "SELECT e.*, r.id as roleexists
                  FROM {enrol} e
             LEFT JOIN {role} r ON (r.id = e.roleid)
                 WHERE e.customint1 = :cohortid AND e.enrol = 'cohort' AND e.status = :enrolstatus
              ORDER BY e.id ASC";
        $params['cohortid'] = $event->objectid;
        $params['enrolstatus'] = ENROL_INSTANCE_ENABLED;
        if (!$instances = $DB->get_records_sql($sql, $params)) {
            return true;
        }

        $plugin = enrol_get_plugin('cohort');
        foreach ($instances as $instance) {
            if ($instance->status != ENROL_INSTANCE_ENABLED ) {
                // No roles for disabled instances.
                $instance->roleid = 0;
            } else if ($instance->roleid and !$instance->roleexists) {
                // Invalid role - let's just enrol, they will have to create new sync and delete this one.
                $instance->roleid = 0;
            }
            unset($instance->roleexists);
            // No problem if already enrolled.
            $plugin->enrol_user($instance, $event->relateduserid, $instance->roleid, 0, 0, ENROL_USER_ACTIVE);

            // Sync groups.
            if ($instance->customint2) {
                if (!groups_is_member($instance->customint2, $event->relateduserid)) {
                    if ($group = $DB->get_record('groups', array('id'=>$instance->customint2, 'courseid'=>$instance->courseid))) {
                        groups_add_member($group->id, $event->relateduserid, 'enrol_cohort', $instance->id);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Event processor - cohort member removed.
     * @param \core\event\cohort_member_removed $event
     * @return bool
     */
    public static function member_removed(\core\event\cohort_member_removed $event) {
        global $DB;

        // Does anything want to sync with this cohort?
        if (!$instances = $DB->get_records('enrol', array('customint1'=>$event->objectid, 'enrol'=>'cohort'), 'id ASC')) {
            return true;
        }

        $plugin = enrol_get_plugin('cohort');
        $unenrolaction = $plugin->get_config('unenrolaction', ENROL_EXT_REMOVED_UNENROL);

        foreach ($instances as $instance) {
            if (!$ue = $DB->get_record('user_enrolments', array('enrolid'=>$instance->id, 'userid'=>$event->relateduserid))) {
                continue;
            }
            if ($unenrolaction == ENROL_EXT_REMOVED_UNENROL) {
                $plugin->unenrol_user($instance, $event->relateduserid);

            } else {
                if ($ue->status != ENROL_USER_SUSPENDED) {
                    $plugin->update_user_enrol($instance, $ue->userid, ENROL_USER_SUSPENDED);
                    $context = context_course::instance($instance->courseid);
                    role_unassign_all(array('userid'=>$ue->userid, 'contextid'=>$context->id, 'component'=>'enrol_cohort', 'itemid'=>$instance->id));
                }
            }
        }

        return true;
    }

    /**
     * Event processor - cohort deleted.
     * @param \core\event\cohort_deleted $event
     * @return bool
     */
    public static function deleted(\core\event\cohort_deleted $event) {
        global $DB;

        // Does anything want to sync with this cohort?
        if (!$instances = $DB->get_records('enrol', array('customint1'=>$event->objectid, 'enrol'=>'cohort'), 'id ASC')) {
            return true;
        }

        $plugin = enrol_get_plugin('cohort');
        $unenrolaction = $plugin->get_config('unenrolaction', ENROL_EXT_REMOVED_UNENROL);

        foreach ($instances as $instance) {
            if ($unenrolaction == ENROL_EXT_REMOVED_SUSPENDNOROLES) {
                $context = context_course::instance($instance->courseid);
                role_unassign_all(array('contextid'=>$context->id, 'component'=>'enrol_cohort', 'itemid'=>$instance->id));
                $plugin->update_status($instance, ENROL_INSTANCE_DISABLED);
            } else {
                $plugin->delete_instance($instance);
            }
        }

        return true;
    }
}


/**
 * Sync all cohort course links.
 * @param progress_trace $trace
 * @param int $courseid one course, empty mean all
 * @param int $cohortid ID of the cohort being synced (Totara performance only)
 * @return int 0 means ok, 1 means error, 2 means plugin disabled
 */
function enrol_cohort_sync(progress_trace $trace, $courseid = NULL, $cohortid = NULL) {
    global $CFG, $DB, $USER;
    require_once("$CFG->dirroot/group/lib.php");

    // Purge all roles if cohort sync disabled, those can be recreated later here by cron or CLI.
    if (!enrol_is_enabled('cohort')) {
        $trace->output('Cohort sync plugin is disabled, unassigning all plugin roles and stopping.');
        role_unassign_all(array('component'=>'enrol_cohort'));
        return 2;
    }

    // Unfortunately this may take a long time, this script can be interrupted without problems.
    core_php_time_limit::raise();
    raise_memory_limit(MEMORY_HUGE);

    // Ensure dynamic cohorts are up to date before starting.
    totara_cohort_check_and_update_dynamic_cohort_members($courseid, $trace, $cohortid);

    $trace->output('Starting user enrolment synchronisation...');

    $allroles = get_all_roles();

    $plugin = enrol_get_plugin('cohort');
    $unenrolaction = $plugin->get_config('unenrolaction', ENROL_EXT_REMOVED_UNENROL);

    // One or all courses?
    $onecourse = $courseid ? "AND e.courseid = :courseid" : "";

    // Get enrol instances where peeps need to be unsuspended.
    $sql = "SELECT DISTINCT e.id
              FROM {cohort_members} cm
              JOIN {enrol} e ON (e.customint1 = cm.cohortid AND e.status = :statusenabled AND e.enrol = 'cohort' $onecourse)
              JOIN {user} u ON (u.id = cm.userid AND u.deleted = 0)
              JOIN {user_enrolments} ue ON (ue.enrolid = e.id AND ue.userid = cm.userid)
             WHERE ue.status = :suspended
          ORDER BY e.id ASC";
    $params = array(
        'courseid' => $courseid,
        'statusenabled' => ENROL_INSTANCE_ENABLED,
        'suspended' => ENROL_USER_SUSPENDED
    );
    $rseids = $DB->get_recordset_sql($sql, $params);
    // Unsuspend the necessary users in the enrol instances.
    foreach ($rseids as $enrol) {
        $ignoreabort = ignore_user_abort(true);
        $now = time();
        $instance = $DB->get_record('enrol', array('id' => $enrol->id));
        $context = context_course::instance($instance->courseid);
        // Do the bulk update in SQL only.
        $sql = "UPDATE {user_enrolments}
                   SET status = :active, timemodified = :now
                 WHERE status = :suspended AND enrolid = :enrolid1
                       AND userid IN (
                           SELECT cm.userid
                             FROM {cohort_members} cm
                             JOIN {enrol} e ON (e.customint1 = cm.cohortid)
                             JOIN {user} u ON (u.id = cm.userid AND u.deleted = 0)
                            WHERE e.id = :enrolid2)";
        $params = array(
            'active' => ENROL_USER_ACTIVE, 'now' => $now,
            'suspended' => ENROL_USER_SUSPENDED,
            'enrolid1' => $instance->id, 'enrolid2' => $instance->id,
        );
        $DB->execute($sql, $params);
        // Invalidate core_access cache for get_suspended_userids.
        cache_helper::invalidate_by_definition('core', 'suspended_userids', array(), array($instance->courseid));
        \totara_core\event\bulk_enrolments_started::create_from_instance($instance)->trigger();
        // Let's pretend concurrent modification in the same second does not happen.
        $sql = "SELECT ue.*
                  FROM {user_enrolments} ue
                  JOIN {user} u ON (u.id = ue.userid AND u.deleted = 0)
                 WHERE ue.enrolid = :enrolid AND ue.status = :active
                       AND ue.timemodified = :now";
        $params = array(
            'active' => ENROL_USER_ACTIVE,
            'enrolid' => $instance->id,
            'now' => $now
        );
        $rsues = $DB->get_recordset_sql($sql, $params);
        // Trigger all events.
        foreach ($rsues as $ue) {
            $trace->output("unsuspending: $ue->userid ==> $instance->courseid via cohort $instance->customint1", 1);
            $event = \core\event\user_enrolment_updated::create(
                array(
                    'objectid' => $ue->id,
                    'courseid' => $instance->courseid,
                    'context' => $context,
                    'relateduserid' => $ue->userid,
                    'other' => array('enrol' => 'cohort')
                )
            );
            $event->add_record_snapshot('enrol', $instance);
            $event->add_record_snapshot('user_enrolments', $ue);
            $event->trigger();
            if ($ue->userid == $USER->id) {
                if (isset($USER->enrol['enrolled'][$instance->courseid])) {
                    unset($USER->enrol['enrolled'][$instance->courseid]);
                }
                if (isset($USER->enrol['tempguest'][$instance->courseid])) {
                    unset($USER->enrol['tempguest'][$instance->courseid]);
                    remove_temp_course_roles($context);
                }
            }
        }
        $rsues->close();
        \totara_core\event\bulk_enrolments_ended::create_from_instance($instance)->trigger();
        ignore_user_abort($ignoreabort);
    }
    $rseids->close();

    // Quick and very dirty way to do new bulk enrolment via SQL only.
    $ignoreabort = ignore_user_abort(true);
    $now = time();
    $maxidsql = "SELECT MAX(ue.id)
                   FROM {user_enrolments} ue";
    $prevmaxid = (int)$DB->get_field_sql($maxidsql);
    // Insert enrolment records for all new users in cohorts and newly synced cohorts.
    $sql = "INSERT INTO {user_enrolments} (enrolid, status, userid, timestart, timeend, modifierid, timecreated, timemodified)

            SELECT e.id, :active, cm.userid, 0, 0, :currentuser, :now1, :now2
              FROM {cohort_members} cm
              JOIN {enrol} e ON (e.customint1 = cm.cohortid AND e.status = :statusenabled AND e.enrol = 'cohort' $onecourse)
              JOIN {user} u ON (u.id = cm.userid AND u.deleted = 0)
         LEFT JOIN {user_enrolments} ue ON (ue.enrolid = e.id AND ue.userid = cm.userid)
             WHERE ue.id IS NULL";
    $params = array(
        'active' => ENROL_USER_ACTIVE, 'currentuser' => $USER->id,
        'now1' => $now, 'now2' => $now, 'statusenabled' => ENROL_INSTANCE_ENABLED,
        'courseid' => $courseid,
    );
    $DB->execute($sql, $params);
    // Now get the new max id of relevant user enrolments - anything in between must be what we just created.
    // Let's ignore any concurrent modifications, the worst case scenario would be some events get triggered twice.
    $newmaxid = (int)$DB->get_field_sql($maxidsql);
    // Trigger enrolment and role events.
    $sql = "SELECT DISTINCT e.id
              FROM {enrol} e
              JOIN {user_enrolments} ue ON (ue.enrolid = e.id)
             WHERE e.enrol = 'cohort' AND ue.id > :prevmaxid AND ue.id <= :newmaxid";
    $params = array('prevmaxid' => $prevmaxid, 'newmaxid' => $newmaxid);
    $rseids = $DB->get_recordset_sql($sql, $params);
    foreach ($rseids as $enrol) {
        $instance = $DB->get_record('enrol', array('id' => $enrol->id));
        \totara_core\event\bulk_enrolments_started::create_from_instance($instance)->trigger();
        $sql = "SELECT ue.*
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON (e.id = ue.enrolid AND e.enrol = 'cohort')
                  JOIN {context} ctx ON (ctx.instanceid = e.courseid AND ctx.contextlevel = :courselevel)
                 WHERE ue.id <= :newmaxid AND ue.id > :prevmaxid AND ue.enrolid = :enrolid AND ue.timecreated = :now";
        $params = array(
            'courselevel' => CONTEXT_COURSE,
            'prevmaxid' => $prevmaxid, 'newmaxid' => $newmaxid,
            'enrolid' => $instance->id, 'now' => $now,
        );
        $rsues = $DB->get_recordset_sql($sql, $params);
        $context = context_course::instance($instance->courseid);
        foreach ($rsues as $ue) {
            $trace->output("enrolling: $ue->userid ==> $instance->courseid via cohort $instance->customint1", 1);
            $event = \core\event\user_enrolment_created::create(
                array(
                    'objectid' => $ue->id,
                    'courseid' => $instance->courseid,
                    'context' => $context,
                    'relateduserid' => $ue->userid,
                    'other' => array('enrol' => 'cohort')
                )
            );
            $event->add_record_snapshot('enrol', $instance);
            $event->add_record_snapshot('user_enrolments', $ue);
            $event->trigger();
            if ($ue->userid == $USER->id) {
                if (isset($USER->enrol['enrolled'][$instance->courseid])) {
                    unset($USER->enrol['enrolled'][$instance->courseid]);
                }
                if (isset($USER->enrol['tempguest'][$instance->courseid])) {
                    unset($USER->enrol['tempguest'][$instance->courseid]);
                    remove_temp_course_roles($context);
                }
            }
        }
        $rsues->close();
        \totara_core\event\bulk_enrolments_ended::create_from_instance($instance)->trigger();
    }
    $rseids->close();
    ignore_user_abort($ignoreabort);

    // Get enrol instances where peeps need to be unenrolled or suspended.
    if ($unenrolaction == ENROL_EXT_REMOVED_UNENROL) {
        $sql = "SELECT DISTINCT e.id
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON (e.id = ue.enrolid AND e.enrol = 'cohort' $onecourse)
             LEFT JOIN {cohort_members} cm ON (cm.cohortid  = e.customint1 AND cm.userid = ue.userid)
                 WHERE cm.id IS NULL
              ORDER BY e.id ASC";
        $params = array('courseid' => $courseid , 'active' => ENROL_USER_ACTIVE);
        $rseids = $DB->get_recordset_sql($sql, $params);
        foreach ($rseids as $enrol) {
            $instance = $DB->get_record('enrol', array('id' => $enrol->id));
            if ($unenrolaction == ENROL_EXT_REMOVED_UNENROL) {
                // Fully unenrol users the slow way.
                $sql = "SELECT DISTINCT ue.*
                      FROM {user_enrolments} ue
                      JOIN {enrol} e ON (e.id = ue.enrolid AND e.enrol = 'cohort')
                 LEFT JOIN {cohort_members} cm ON (cm.cohortid = e.customint1 AND cm.userid = ue.userid)
                     WHERE e.id = :enrolid AND cm.id IS NULL";
                $params = array('enrolid' => $enrol->id);
                $rsuserids = $DB->get_recordset_sql($sql, $params);
                \totara_core\event\bulk_enrolments_started::create_from_instance($instance)->trigger();
                foreach ($rsuserids as $ue) {
                    // Remove enrolment together with roles, group membership, grades, preferences, etc.
                    $plugin->unenrol_user($instance, $ue->userid);
                    $trace->output("unenrolling: $ue->userid ==> $instance->courseid via cohort $instance->customint1", 1);
                }
                $rsuserids->close();
                \totara_core\event\bulk_enrolments_ended::create_from_instance($instance)->trigger();
                continue;
            }
        }

    } else { // Suspend using SQL - ENROL_EXT_REMOVED_SUSPENDNOROLES == $unenrolaction.
        $sql = "SELECT DISTINCT e.id
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON (e.id = ue.enrolid AND e.enrol = 'cohort' $onecourse)
             LEFT JOIN {cohort_members} cm ON (cm.cohortid  = e.customint1 AND cm.userid = ue.userid)
                 WHERE cm.id IS NULL AND ue.status = :active
              ORDER BY e.id ASC";
        $params = array('courseid' => $courseid , 'active' => ENROL_USER_ACTIVE);
        $rseids = $DB->get_recordset_sql($sql, $params);
        foreach ($rseids as $enrol) {
            $instance = $DB->get_record('enrol', array('id' => $enrol->id));
            $ignoreabort = ignore_user_abort(true);
            $now = time();
            $sql = "UPDATE {user_enrolments}
                       SET status = :suspended, timemodified = :now
                     WHERE status = :active AND enrolid = :enrolid1
                           AND userid NOT IN (
                               SELECT cm.userid
                                 FROM {cohort_members} cm
                                 JOIN {enrol} e ON (e.customint1 = cm.cohortid)
                                WHERE e.id = :enrolid2)";
            $params = array(
                'active' => ENROL_USER_ACTIVE, 'now' => $now,
                'suspended' => ENROL_USER_SUSPENDED,
                'enrolid1' => $instance->id, 'enrolid2' => $instance->id,
            );
            $DB->execute($sql, $params);
            $context = context_course::instance($instance->courseid);
            // Invalidate core_access cache for get_suspended_userids.
            cache_helper::invalidate_by_definition('core', 'suspended_userids', array(), array($instance->courseid));
            \totara_core\event\bulk_enrolments_started::create_from_instance($instance)->trigger();
            // Let's pretend concurrent modification in the same second does not happen.
            $sql = "SELECT ue.*
                      FROM {user_enrolments} ue
                      JOIN {user} u ON (u.id = ue.userid AND u.deleted = 0)
                     WHERE ue.enrolid = :enrolid AND ue.status = :suspended
                           AND ue.timemodified = :now";
            $params = array(
                'suspended' => ENROL_USER_SUSPENDED,
                'enrolid' => $instance->id,
                'now' => $now
            );
            $rsues = $DB->get_recordset_sql($sql, $params);
            // Trigger all events.
            foreach ($rsues as $ue) {
                $trace->output("suspending: $ue->userid ==> $instance->courseid via cohort $instance->customint1", 1);
                $event = \core\event\user_enrolment_updated::create(
                    array(
                        'objectid' => $ue->id,
                        'courseid' => $instance->courseid,
                        'context' => $context,
                        'relateduserid' => $ue->userid,
                        'other' => array('enrol' => 'cohort')
                    )
                );
                $event->add_record_snapshot('enrol', $instance);
                $event->add_record_snapshot('user_enrolments', $ue);
                $event->trigger();
                if ($ue->userid == $USER->id) {
                    if (isset($USER->enrol['enrolled'][$instance->courseid])) {
                        unset($USER->enrol['enrolled'][$instance->courseid]);
                    }
                }
            }
            $rsues->close();
            \totara_core\event\bulk_enrolments_ended::create_from_instance($instance)->trigger();
            ignore_user_abort($ignoreabort);
        }
        $rseids->close();
    }

    // Insert role assignment records for all enrolments via SQL.
    $ignoreabort = ignore_user_abort(true);
    $now = time();
    $maxrasidsql = "SELECT MAX(ras.id)
                      FROM {role_assignments} ras
                     WHERE ras.component = 'enrol_cohort'";
    $prevmaxrasid = (int)$DB->get_field_sql($maxrasidsql);
    // Assign roles via SQL only.
    $sql = "INSERT INTO {role_assignments} (roleid, contextid, userid, component, itemid, timemodified, modifierid, sortorder)

            SELECT e.roleid, ctx.id, cm.userid, 'enrol_cohort', e.id, :now, :currentuser, 0
              FROM {cohort_members} cm
              JOIN {enrol} e ON (e.customint1 = cm.cohortid AND e.status = :statusenabled AND e.enrol = 'cohort' $onecourse)
              JOIN {role} r ON (r.id = e.roleid)
              JOIN {context} ctx ON (ctx.instanceid = e.courseid and ctx.contextlevel = :courselevel)
              JOIN {user_enrolments} ue ON (ue.enrolid = e.id AND ue.userid = cm.userid)
              JOIN {user} u ON (u.id = cm.userid AND u.deleted = 0)
         LEFT JOIN {role_assignments} ras
                   ON (ras.contextid = ctx.id AND ras.component = 'enrol_cohort' AND ras.itemid = e.id
                       AND ras.userid = cm.userid AND ras.roleid = e.roleid)
             WHERE ras.id IS NULL AND ue.status = :active";
    $params = array(
        'now' => $now, $now, 'currentuser' => $USER->id,
        'statusenabled' => ENROL_INSTANCE_ENABLED, 'courselevel' => CONTEXT_COURSE,
        'courseid' => $courseid, 'active' => ENROL_USER_ACTIVE,
    );
    $DB->execute($sql, $params);
    $newmaxrasid = (int)$DB->get_field_sql($maxrasidsql);
    $sql = "SELECT ras.*, e.courseid
              FROM {role_assignments} ras
              JOIN {context} ctx ON (ctx.id = ras.contextid AND ctx.contextlevel = :courselevel)
              JOIN {enrol} e ON (e.id = ras.itemid AND e.status = :statusenabled AND e.enrol = 'cohort')
             WHERE ras.component = 'enrol_cohort' AND ras.id > :prevmaxrasid AND ras.id <= :newmaxrasid
          ORDER BY ras.contextid ASC";
    $params = array(
        'statusenabled' => ENROL_INSTANCE_ENABLED, 'courselevel' => CONTEXT_COURSE,
        'prevmaxrasid' => $prevmaxrasid, 'newmaxrasid' => $newmaxrasid,
    );
    $rsras = $DB->get_recordset_sql($sql, $params);
    $context = null;
    foreach ($rsras as $ra) {
        $trace->output("assigning role: $ra->userid ==> $ra->courseid as ".$allroles[$ra->roleid]->shortname, 1);
        unset($ra->courseid);
        if (!$context or $context->id != $ra->contextid) {
            if ($context) {
                \totara_core\event\bulk_role_assignments_ended::create_from_context($context)->trigger();
            }
            $context = context::instance_by_id($ra->contextid);
            $context->mark_dirty();
            \totara_core\event\bulk_role_assignments_started::create_from_context($context)->trigger();
        }
        if ($USER->id == $ra->userid) {
            reload_all_capabilities();
        }
        $event = \core\event\role_assigned::create(array(
            'context' => $context,
            'objectid' => $ra->roleid,
            'relateduserid' => $ra->userid,
            'other' => array(
                'id' => $ra->id,
                'component' => $ra->component,
                'itemid' => $ra->itemid,
            )
        ));
        $event->add_record_snapshot('role_assignments', $ra);
        $event->trigger();
    }
    $rsras->close();
    if ($context) {
        \totara_core\event\bulk_role_assignments_ended::create_from_context($context)->trigger();
    }
    ignore_user_abort($ignoreabort);

    // Remove unwanted roles - sync role can not be changed, we only remove role when suspended.
    $sql = "SELECT DISTINCT e.id
              FROM {role_assignments} ra
              JOIN {context} c ON (c.id = ra.contextid AND c.contextlevel = :coursecontext)
              JOIN {enrol} e ON (e.id = ra.itemid AND e.enrol = 'cohort' $onecourse)
         LEFT JOIN {user_enrolments} ue ON (ue.enrolid = e.id AND ue.userid = ra.userid AND ue.status = :useractive)
             WHERE ra.component = 'enrol_cohort' AND (ue.id IS NULL OR e.status <> :statusenabled)
          ORDER BY e.id ASC";
    $params = array(
        'coursecontext' => CONTEXT_COURSE,
        'useractive' => ENROL_USER_ACTIVE,
        'statusenabled' => ENROL_INSTANCE_ENABLED,
        'courseid' => $courseid,
    );
    $rseids = $DB->get_recordset_sql($sql, $params);
    foreach ($rseids as $enrol) {
        $instance = $DB->get_record('enrol', array('id' => $enrol->id));
        $context = context_course::instance($instance->courseid);
        $ignoreabort = ignore_user_abort(true);
        // Get list of all role assignments to be deleted.
        $sql = "SELECT ras.*, e.courseid
                  FROM {role_assignments} ras
                  JOIN {context} c ON (c.id = ras.contextid AND c.contextlevel = :coursecontext)
                  JOIN {enrol} e ON (e.id = ras.itemid AND e.enrol = 'cohort')
             LEFT JOIN {user_enrolments} ue ON (ue.enrolid = e.id AND ue.userid = ras.userid AND ue.status = :useractive)
                 WHERE ras.component = 'enrol_cohort' AND (ue.id IS NULL OR e.status <> :statusenabled) AND e.id = :enrolid";
        $params = array(
            'coursecontext' => CONTEXT_COURSE,
            'useractive' => ENROL_USER_ACTIVE,
            'statusenabled' => ENROL_INSTANCE_ENABLED,
            'enrolid' => $instance->id,
        );
        $ras = $DB->get_records_sql($sql, $params); // There is no way around this, we need to fetch it all into memory.
        // Delete the role assignments for users that do not have active enrolment record.
        $sql = "DELETE FROM {role_assignments}
                 WHERE component = 'enrol_cohort' AND itemid = :enrolid1
                       AND userid NOT IN (
                           SELECT ue.userid
                             FROM {user_enrolments} ue
                             JOIN {enrol} e ON (e.id = ue.enrolid)
                            WHERE e.id = :enrolid2 AND ue.status = :useractive AND e.status = :statusenabled
                       )";
        $params = array(
            'useractive' => ENROL_USER_ACTIVE,
            'enrolid1' => $instance->id, 'enrolid2' => $instance->id,
            'statusenabled' => ENROL_INSTANCE_ENABLED,
        );
        $DB->execute($sql, $params);
        $context->mark_dirty();
        \totara_core\event\bulk_role_assignments_started::create_from_context($context)->trigger();
        // Trigger events.
        foreach ($ras as $ra) {
            $trace->output("unassigning role: $ra->userid ==> $ra->courseid as ".$allroles[$ra->roleid]->shortname, 1);
            unset($ra->courseid);
            if ($USER->id == $ra->userid) {
                reload_all_capabilities();
            }
            $event = \core\event\role_unassigned::create(array(
                'context' => $context,
                'objectid' => $ra->roleid,
                'relateduserid' => $ra->userid,
                'other' => array(
                    'id' => $ra->id,
                    'component' => $ra->component,
                    'itemid' => $ra->itemid
                )
            ));
            $event->add_record_snapshot('role_assignments', $ra);
            $event->trigger();
        }
        unset($ras);
        \totara_core\event\bulk_role_assignments_ended::create_from_context($context)->trigger();
        ignore_user_abort($ignoreabort);
    }
    $rseids->close();

    // Finally sync groups.
    $affectedusers = groups_sync_with_enrolment('cohort', $courseid);
    foreach ($affectedusers['removed'] as $gm) {
        $trace->output("removing user from group: $gm->userid ==> $gm->courseid - $gm->groupname", 1);
    }
    foreach ($affectedusers['added'] as $ue) {
        $trace->output("adding user to group: $ue->userid ==> $ue->courseid - $ue->groupname", 1);
    }

    // Program cohort memberships will be handled by the programs cron ;)

    // Delete any stale memberships due to deleted cohort(s)
    $trace->output('removing user memberships for deleted cohorts...');
    totara_cohort_delete_stale_memberships();


    $trace->output('...user enrolment synchronisation finished.');

    return 0;
}
