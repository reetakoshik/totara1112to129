<?php

trait activities_columns
{
    private function add_activity_fields_to_columns(&$columns, $table = 'base') 
    {
    	$columns[] = new rb_column_option(
            'activity',
            'modulename',
            get_string('name', 'rb_source_activities'),
            'base.modulename'
        );
        $columns[] = new rb_column_option(
            'activity',
            'lmodulename',
            get_string('link', 'rb_source_activities'),
            'base.modulename',
            array(
                'displayfunc' => 'modlink',
                'extrafields' => array(
                    'modulename' => 'base.modulename',
                    'id' => 'base.id',
                    'mname' => 'base.mname'
                )
            )
        );
        $columns[] = new rb_column_option(
            'activity',
            'mname',
            get_string('type', 'rb_source_activities'),
            'base.mname'
        );
        $columns[] = new rb_column_option(
            'activity',
            'imname',
            get_string('icon', 'rb_source_activities'),
            'base.mname',
            array('displayfunc' => 'modicons')
        );
        $columns[] = new rb_column_option(
            'activity',
            'moduleintro',
            get_string('summary', 'rb_source_activities'),
            'base.moduleintro'
        );
        $columns[] = new rb_column_option(
            'activity',
            'cmadded',
            get_string('cmadded', 'rb_source_activities'),
            'base.cmadded',
            array('displayfunc' => 'nice_date', 'extrafields' => array('cid' => 'base.cmadded'))
        );
        $columns[] = new rb_column_option(
            'activity',
            'timemodified',
            get_string('timemodified', 'rb_source_activities'),
            'base.timemodified',
            array('displayfunc' => 'nice_date', 'extrafields' => array('cid' => 'base.timemodified'))
        );
        $columns[] = new rb_column_option(
            'course',
            'cfullname',
            get_string('coursefullname', 'rb_source_activities'),
            'base.cfullname'
        );
        $columns[] = new rb_column_option(
            'course',
            'lcfullname',
            get_string('courselink', 'rb_source_activities'),
            'base.cfullname',
            array('displayfunc' => 'courselink', 'extrafields' => array('cid' => 'base.cid', 'cfullname' => 'base.cfullname'))
        );
        $columns[] = new rb_column_option(
            'course',
            'cid',
            get_string('courseid', 'rb_source_activities'),
            'base.cid'
        );
        $columns[] = new rb_column_option(
            'course',
            'ccname',
            get_string('coursecategory', 'rb_source_activities'),
            'base.ccname'
        );
        $columns[] = new rb_column_option(
            'course',
            'enrccname',
            get_string('enrollment', 'rb_source_activities'),
            'base.enrollment'
        );
    }

    public function rb_display_modicons($mods, $row, $isexport = false)
    {
        global $OUTPUT, $CFG;
        $modules = explode('|', $mods);

        // Sort module list before displaying to make cells all consistent.
        sort($modules);

        $out = array();
        $glue = '';
        foreach ($modules as $module) {
            if (empty($module)) {
                continue;
            }
            $n = get_string_manager()->string_exists('pluginname', $module);
            $name = $n ? get_string('pluginname', $module) : ucfirst($module);
            if ($isexport) {
                $out[] = $name;
                $glue = ', ';
            } else {
                $glue = '';
                if (file_exists($CFG->dirroot . '/mod/' . $module . '/pix/icon.gif') ||
                    file_exists($CFG->dirroot . '/mod/' . $module . '/pix/icon.png')
                ) {
                    $out[] = $OUTPUT->pix_icon('icon', $name, $module);
                } else {
                    $out[] = $name;
                }
            }
        }

        return implode($glue, $out);
    }

    public function rb_display_modlink($mods, $row, $isexport = false)
    {
        if ($isexport) {
            return '';
        }

        $url = new moodle_url('/mod/' . $row->mname . '/view.php', array('id' => $row->id));

        return html_writer::link($url, $row->modulename);
    }

    public function rb_display_courselink($mods, $row, $isexport = false)
    {
        if ($isexport) {
            return '';
        }

        $url = new moodle_url('/course/view.php', array('id' => $row->cid));

        return html_writer::link($url, $row->cfullname);
    }
}