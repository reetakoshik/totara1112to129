<?php

require_once __DIR__.'/../../detailed_course_completion/content/rb_user_role_content.php';

trait activities_content
{
	private function add_activities_content(&$contentoptions)
	{
		$contentoptions[] = new rb_content_option(
            'date',
            get_string('cmadded', 'rb_source_activities'),
            'base.cmadded'
        );
        $contentoptions[] = new rb_content_option(
            'user_role',
            'string in contentoptions',
            'base.cid'
        );
	}
}