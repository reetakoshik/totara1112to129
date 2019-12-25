<?php

trait activity_completion_columns
{
    private function add_activity_completion_fields_to_columns(&$columns, $table = 'base') 
    {
        $columns[] = new rb_column_option(
            'module_completion',
            'completionstate',
            get_string('completionstate', 'rb_source_detailed_activity_completion'),
            "$table.completionstate",
            [
                'joins'       => $table,
                'displayfunc' => 'module_completion_completionstate',
            ]
        );

        $columns[] = new rb_column_option(
            'module_completion',
            'timecompleted',
            get_string('timecompleted', 'rb_source_detailed_activity_completion'),
            "$table.timemodified",
            [
                'joins'       => $table,
                'displayfunc' => 'module_completion_timecompleted',
                'extrafields' => [
                    'timecompleted' => "$table.timecompleted",
                    'timemodified'  => "$table.timemodified"
                ],
            ]
        );

        $columns[] = new rb_column_option(
            'module',
            'added',
            get_string('cmadded', 'rb_source_activities'),
            "base.added",
            [
                'joins'       => 'base',
                'displayfunc' => 'nice_date',
                'extrafields' => ['cid' => "base.added"]
            ]
        );

        $columns[] = new rb_column_option(
            'module',
            'cmtimemodified',
            get_string('timemodified', 'rb_source_activities'),
            "base.cmtimemodified",
            [
                'joins'       => 'base',
                'displayfunc' => 'nice_date',
                'extrafields' => ['cid' => "base.cmtimemodified"]
            ]
        );
    }

    private function add_activity_fields_to_columns(&$columns, $join = 'base') 
    {
        $columns[] = new rb_column_option(
            'module',
            'title',
            get_string('moduletitle', 'rb_source_detailed_activity_completion'),
            "$join.title",
            [   
                'joins'       => ['module'],
                'displayfunc' => 'module_title',
                'extrafields' => [
                    'module_id'   => 'course_module.id',
                    'module_type' => 'module.name'
                ],
            ]
        );

        $columns[] = new rb_column_option(
            'module',
            'type',
            get_string('moduletype', 'rb_source_detailed_activity_completion'),
            'module.name',
            [
                'joins'       => ['course_module', 'course', 'module'],
                'displayfunc' => 'module_name',
                'extrafields' => [
                    'course_id' => 'course.id',
                    'id'        => 'course_module.id',
                    'name'      => 'module.name'
                ]
            ]
        );
    }

    public function rb_display_module_title($title, $row)
    {
        $id = $row->module_id;
        $module = $row->module_type;
        return "<a target=\"_blank\" href=\"/mod/$module/view.php?id=$id\">$title</a>";
    }

    public function rb_display_module_name($name, $row)
    {
        $name = $row->name; // for some reason in emebdded report $name is a link and not a string from db
        $name = get_string('modulename', "mod_$name");
        $modinfo = get_fast_modinfo($row->course_id);
        $mod = $modinfo->get_cm($row->id);
        return $mod->render_icon().' '.$name; 
    }

    public function rb_display_module_completion_timecompleted($timecompleted, $row)
    {
        $timecompleted = $row->timecompleted ? $row->timecompleted : $row->timemodified;

        if ($timecompleted) {
            return userdate($timecompleted);
        }

        return '';
    }

    public function rb_display_module_completion_completionstate($completionstate)
    {
        $completionStatusText = [
            COMPLETION_INCOMPLETE    => get_string('incomplete', 'totara_program'),
            COMPLETION_COMPLETE      => get_string('complete', 'completion'),
            COMPLETION_COMPLETE_PASS => get_string('completion-pass', 'completion'),
            COMPLETION_COMPLETE_FAIL => get_string('completion-fail', 'completion'),
            COMPLETION_COMPLETE_RPL  => get_string('completeviarpl', 'completion'),
        ];

        return isset($completionStatusText[$completionstate])
            ? $completionStatusText[$completionstate]
            : $completionStatusText[COMPLETION_INCOMPLETE];
    }
}
