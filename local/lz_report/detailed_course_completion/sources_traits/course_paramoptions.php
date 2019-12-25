<?php

trait course_paramoptions
{
    public function add_course_paramoptions(&$paramoptions, $coursetable = 'base')
    {
        $paramoptions[] = new rb_param_option('courseid', "$coursetable.id", [$coursetable]);
    }
}