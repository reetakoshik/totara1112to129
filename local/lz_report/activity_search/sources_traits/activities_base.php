<?php

require_once __DIR__.'/../../../../totara/coursecatalog/lib.php';

trait activities_base
{
	private function init_base()
    {  
    	global $DB;

        $accessiblecourses = $this->get_accessible_courses();
          
        $modules = $DB->get_records('modules');

        $otherModulesJoins = implode(' ', $this->join_modulename_column($modules));
        $modulename   = $this->select_modulename_column($modules);
        $moduleintro  = $this->select_moduleintro_column($modules);
        $timemodified = $this->select_timemodified_column($modules);
        $enrolled 	  = $this->select_enrolled_column($accessiblecourses);

        $where = !is_siteadmin() ? $this->where($accessiblecourses) : '(1=1)';

        return "(
        	SELECT  cm.id,
            	    cm.instance,
            	    cm.added AS cmadded,
            	    m.name AS mname,
            	    c.id AS cid,
            	    c.fullname AS cfullname,
            	    cc.name AS ccname,
            	    $modulename,
            	    $moduleintro,
            	    $timemodified,
            	    $enrolled
            FROM {course_modules} cm
            LEFT JOIN {modules} m on m.id=cm.module
            LEFT JOIN {course} c ON cm.course=c.id
            LEFT JOIN {course_categories} cc ON cc.id=c.category
            $otherModulesJoins
            WHERE $where
        )";
    }

    private function select_modulename_column($modules)
    {
    	global $DB;
    	$concatarr = [];
        foreach ($modules as $module) {
            $concatarr[] = "COALESCE({$module->name}.name, '')";
        }
        $modulename = $DB->sql_concat_join("''", $concatarr). ' AS modulename';
        return $modulename;
    }

    private function select_moduleintro_column($modules)
    {
    	global $DB;
    	$concatarr = [];
        foreach ($modules as $module) {
            if (in_array($module->name, ['lesson'])) {
                // Skip modules that dont have intro.
                continue;
            }
            $concatarr[] = "{$module->name}.intro";
        }
        $moduleintro = $DB->sql_concat_join("''", $concatarr). ' AS moduleintro';
        return $moduleintro;
    }

    private function select_timemodified_column($modules)
    {
    	global $DB;
    	$concatarr = [];
        foreach ($modules as $module) {
            if (in_array($module->name, array('data'))) {
                // Skip modules that dont have time modified.
                continue;
            }
            $concatarr[] = "COALESCE({$module->name}.timemodified, 0)";
        }
        $timemodified = join('+', $concatarr).' AS timemodified';
        return $timemodified;
    }

    private function select_enrolled_column($courses)
    {
    	$enrolled = get_string('enrolled', 'rb_source_activities');
        $notenrolled = get_string('notenrolled', 'rb_source_activities');
    	$courses = implode(',', $courses);
        if (empty($courses)) {
            $courses = 0;
        }
        $enrolled = "CASE WHEN (c.id IN($courses)) 
        				THEN '$enrolled' 
        				ELSE '$notenrolled' 
        			END AS enrollment";
        return $enrolled;
    }

    private function join_modulename_column($modules)
    {
    	$joins = [];
        foreach ($modules as $module) {
        	$joins[] = "LEFT JOIN {{$module->name}} {$module->name} 
        		ON {$module->name}.id = cm.instance  AND cm.module = {$module->id}";
        }
        return $joins;
    }

    private function get_accessible_courses()
    {
    	global $DB;
    	list($where, $params) = totara_visibility_where();
        $accessiblecourses = $DB->get_records_sql_menu("
            SELECT DISTINCT(course.id)
            FROM {course} course
            INNER JOIN {context} ctx ON ctx.instanceid = course.id
            WHERE $where
        ", $params);

        $accessiblecourses = array_keys($accessiblecourses);
        return $accessiblecourses;
    }

    private function where($accessiblecourses)
    {
    	$accessiblecourses = implode(',', $accessiblecourses);
    	if (!$accessiblecourses) {
    		$accessiblecourses = 0;
    	}
    	$where = "
    		c.id IN($accessiblecourses) ";

       	return $where;
    }
}