<?php

require_once __DIR__.'/../../detailed_course_completion/content/rb_user_role_content.php';

trait quiz_contentoptions
{
    private function add_contentoptions(&$contentoptions) 
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
            'course_completion.timecompleted',
            'course_completion'
        );

        $contentoptions[] = new rb_content_option(
            'user_role',
            'string in contentoptions',
            'course.id',
            ['course']
        );
    }
}
