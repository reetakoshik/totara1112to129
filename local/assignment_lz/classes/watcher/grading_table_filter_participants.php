<?php

namespace local_assignment_lz\watcher;
  
class grading_table_filter_participants
{
    public static function execute(\local_assignment_lz\hook\grading_table_filter_participants $hook)
    {
    	global $USER;

        $user_enrolled = is_enrolled($hook->obj->get_context(), $USER, 'mod/assign:manageallocations');
        $user_roles = get_user_roles($hook->obj->get_context());
        $user_has_proper_role = false;
        $role_from_config = get_config('local_assignment_lz', 'role');

        foreach ($user_roles as $role) {
            if ($role->shortname == $role_from_config) {
                $user_has_proper_role = true;
            }
        }

        // check user's access
        if (!is_siteadmin() && $user_has_proper_role && $user_enrolled) {

            // copy/paste from core code
            if ($hook->instance->markingworkflow &&
                $hook->instance->markingallocation &&
                has_capability('mod/assign:manageallocations', $hook->obj->get_context()) &&
                has_capability('mod/assign:grade', $hook->obj->get_context())) {

                $hook->additionaljoins .= ' LEFT JOIN {assign_user_flags} uf
                                     ON u.id = uf.userid
                                     AND uf.assignment = :assignmentid3';

                $hook->params['assignmentid3'] = (int) $hook->instance->id;

                $hook->additionalfilters .= ' AND uf.allocatedmarker = :markerid';
                $hook->params['markerid'] = $USER->id;
            }
        }
    }
}