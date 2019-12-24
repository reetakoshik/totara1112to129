<?php

defined('MOODLE_INTERNAL') || die();

$course = isset($_GET['id']) ? course_get_format($_GET['id'])->get_course() : null;

echo $OUTPUT->render_course($course);