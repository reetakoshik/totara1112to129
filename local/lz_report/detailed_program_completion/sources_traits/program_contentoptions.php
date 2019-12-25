<?php

trait program_contentoptions
{
    private function add_program_completion_contentoptions(&$contentoptions) 
    {
        $contentoptions[] = new rb_content_option(
            'completed_org',
            get_string('orgwhencompleted', 'rb_source_program_completion'),
            'completion_organisation.path',
            'completion_organisation'
        );

        $contentoptions[] = new rb_content_option(
            'date',
            get_string('completeddate', 'rb_source_program_completion'),
            'prog_completion.timecompleted'
        );
    }
}