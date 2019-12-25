<?php

require_once __DIR__.'/../../detailed_course_completion/content/rb_user_role_content.php';

trait activity_completion_content
{
	private function add_course_completion_content(&$contentoptions)
	{
        $contentoptions[] = new rb_content_option(
            'completed_org',
            get_string('orgwhencompleted', 'rb_source_course_completion'),
            'completion_organisation.path',
            'completion_organisation'
        );

        $contentoptions[] = new rb_content_option(
            'date',
            get_string('completiondate', 'rb_source_course_completion'),
            'base.timecompleted'
        );

        $contentoptions[] = new rb_content_option(
            'user_role',
            'string in contentoptions',
            'course.id',
            ['course']
        );
	}
}
