<?php

trait activities_filters
{
	private function add_activity_fields_to_filters(&$filters)
    {
    	$filters[] = new rb_filter_option(
            'activity',
            'modulename',
            get_string('name', 'rb_source_activities'),
            'text'
        );
        $filters[] = new rb_filter_option(
            'activity',
            'modulename',
            get_string('link', 'rb_source_activities'),
            'text'
        );
        $filters[] = new rb_filter_option(
            'activity',
            'mname',
            get_string('type', 'rb_source_activities'),
            'multicheck',
            ['selectfunc' => 'activity_type']
        );
        $filters[] = new rb_filter_option(
            'activity',         // Type.
            'mname',           // value
            get_string('icon', 'rb_source_activities'), // label
            'multicheck',     // filtertype
            array(            // Options.
                'selectfunc' => 'modules_list',
                'concat' => true, // Multicheck filter need to know that we work with concatenated values.
                'simplemode' => true,
                'showcounts' => array(
                    'joins' => array("LEFT JOIN (SELECT course, name FROM {course_modules} cm " .
                        "LEFT JOIN {modules} m ON m.id = cm.module) course_mods_filter " .
                        "ON base.id = course_mods_filter.course"),
                    'dataalias' => 'course_mods_filter',
                    'datafield' => 'name')
            )
        );
        $filters[] = new rb_filter_option(
            'activity',
            'moduleintro',
            get_string('summary', 'rb_source_activities'),
            'text'
        );
        $filters[] = new rb_filter_option(
            'activity',
            'cmadded',
            get_string('cmadded', 'rb_source_activities'),
            'date'
        );
        $filters[] = new rb_filter_option(
            'activity',
            'timemodified',
            get_string('timemodified', 'rb_source_activities'),
            'date'
        );
        $filters[] = new rb_filter_option(
            'course',
            'cfullname',
            get_string('coursefullname', 'rb_source_activities'),
            'text'
        );
        $filters[] = new rb_filter_option(
            'course',
            'courselink',
            get_string('courselink', 'rb_source_activities'),
            'text'
        );
        $filters[] = new rb_filter_option(
            'course',
            'cid',
            get_string('courseid', 'rb_source_activities'),
            'text'
        );
        $filters[] = new rb_filter_option(
            'course',
            'ccname',
            get_string('coursecategory', 'totara_reportbuilder'),
            'select',
            array(
                'selectfunc' => 'course_categories_list',
                'attributes' => rb_filter_option::select_width_limiter(),
            )
        );
        $filters[] = new rb_filter_option(
            'course',
            'enrollment',
            get_string('enrollment', 'rb_source_activities'),
            'multicheck',
            array(
                'selectfunc' => 'enroll_list',
                'attributes' => rb_filter_option::select_width_limiter(),
            )
        );
    }

    public function rb_filter_enroll_list()
    {
        $yn = array();
        $yn[1] = get_string('enrolled', 'rb_source_activities');
        $yn[0] = get_string('notenrolled', 'rb_source_activities');

        return $yn;
    }

    public function rb_filter_activity_type()
    {
        global $DB;
        $res = $DB->get_records_sql("SELECT id, name FROM {modules} modules");
        $keys = array_map(function($item) {
            return $item->name;
        }, array_values($res));
        $values = array_map(function($item) {
            return get_string('modulename', "mod_{$item->name}");
        }, array_values($res));
        return array_combine($keys, $values);
    }
}