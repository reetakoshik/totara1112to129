<?php

class rb_user_role_content extends rb_base_content
{
    const TYPE = 'user_role_content';

    public function sql_restriction($field, $reportid)
    {
        global $DB;

        $contextlevel = CONTEXT_COURSE;

        $roleFilter = [];

        $settings = reportbuilder::get_all_settings($reportid, static::TYPE);
        $roles = role_fix_names(get_all_roles(), context_system::instance(), ROLENAME_ORIGINAL);
        foreach ($roles as $key => $role) {
            $key = "role_{$role->shortname}";
            if (array_key_exists($key, $settings) && $settings[$key]) {
                $roleFilter[] = "'$role->shortname'";
            }
        }

        if (!$roleFilter) {
            return ['(1=1)', []];
        }

        $res = $DB->get_fieldset_sql("
            SELECT DISTINCT(course.id)
            FROM {course} course
            JOIN {context} context ON context.instanceid = course.id AND context.contextlevel = $contextlevel
            JOIN {role_assignments} role_assignments ON context.id = role_assignments.contextid
            JOIN {role} role ON role_assignments.roleid = role.id
            JOIN {user} u ON u.id = role_assignments.userid
            WHERE role.shortname IN (".join(',', $roleFilter).")
              AND u.id = {$this->reportfor}"
        );

        $coursesIds = join(',', array_values($res));
        if ($coursesIds) {
            return ["($field IN ($coursesIds) )", []];
        }

        return ['(1<>1)', []];
    }

    public function text_restriction($title, $reportid)
    {
        return '';
    }

    public function form_template(&$mform, $reportid, $title = '')
    {
        $mform->addElement(
            'header',
            'user_role_header',
            get_string('form-header', 'rb_source_detailed_course_completion')
        );
        $mform->setExpanded('user_role_header');
        $mform->addElement(
            'checkbox',
            'user_role_enable',
            '',
            get_string('form-checkbox', 'rb_source_detailed_course_completion')
        );
        $enable = reportbuilder::get_setting($reportid, static::TYPE, 'enable');
        $mform->setDefault('user_role_enable', $enable);
        $mform->disabledIf('user_role_enable', 'contentenabled', 'eq', 0);

        $roles = role_fix_names(get_all_roles(), context_system::instance(), ROLENAME_ORIGINAL);

        foreach ($roles as $key => $role) {
            $mform->addElement(
                'checkbox',
                "role_{$role->shortname}",
                '',
                $role->localname
            );
            $default = reportbuilder::get_setting($reportid, static::TYPE, "role_{$role->shortname}");
            $mform->setDefault("role_{$role->shortname}", $default);
            $mform->disabledIf("role_{$role->shortname}", 'user_role_enable', 'notchecked');
            $mform->disabledIf("role_{$role->shortname}", 'contentenabled', 'eq', 0);
        }
    }

    public function form_process($reportid, $fromform)
    {
        $enable = isset($fromform->user_role_enable) && $fromform->user_role_enable ? 1 : 0;
        $status = reportbuilder::update_setting($reportid, static::TYPE, 'enable', $enable);

        $roles = role_fix_names(get_all_roles(), context_system::instance(), ROLENAME_ORIGINAL);
        foreach ($roles as $key => $role) {
            $key = "role_{$role->shortname}";
            $value = isset($fromform->$key) && $fromform->$key ? 1 : 0;
            reportbuilder::update_setting($reportid, static::TYPE, $key, $value);
        }

        return $status;
    }
}
