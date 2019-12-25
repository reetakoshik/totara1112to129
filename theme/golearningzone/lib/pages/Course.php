<?php

namespace GoLearningZone\Pages;

use GoLearningZone\Traits\Theme as ThemeTrait;

class Course extends Base
{
    use ThemeTrait;

    private $courseFormatRenderer;

    public function __construct($renderer, $courseFormatRenderer = null, $course = null)
    {
        parent::__construct($renderer);
        $this->courseFormatRenderer = $courseFormatRenderer;
        $this->course = $course;
    }

    public function render()
    {
        $template = 'theme_golearningzone/course';

        $context = $this->course ? \context_course::instance($this->course->id) 
                                 : \context_system::instance();

        $showBlocks = isset($this->course->show_course_blocks) ? $this->course->show_course_blocks : false;
        $alwaysShowBlocks = has_capability('moodle/course:update', $context);

        $params = $this->getDefaultPageValues() + [
            'side-post'         => $this->renderer->blocks('side-post'),
            'introduction'      => isset($this->course->course_time) && $this->course->summary 
                                    ? $this->renderCourseIntroduction($this->course, $context)
                                    : false,
            'introduction-size' => isset($this->course->course_outcomes) && $this->course->course_outcomes 
                                    ? 9 
                                    : 12,
            'outcomes'          => isset($this->course->course_outcomes) && $this->course->course_outcomes 
                                    ? $this->renderCourseOutcomes($this->course) 
                                    : false,
            'outcomes-size'     => isset($this->course->summary) && $this->course->summary ? 3 : 12,
            'main_content-size' => !$alwaysShowBlocks && !$showBlocks ? 12 : 9,
            'blocks-size'       => 3,
            'render-side-blocks'=> !$alwaysShowBlocks && !$showBlocks ? false : true,
            'coursename'        => isset($this->course) ? ($this->course->fullname ? $this->course->fullname : $this->course->shortname) : '',
            'translations'      => $this->getCompletionTranslations()
        ];
        return $this->renderer->render_from_template($template, $params);
    } 

    private function renderCourseIntroduction($course, $context)
    {
        $template = 'theme_golearningzone/course_introduction';

        $header = get_string('course_introduction', 'theme_golearningzone');
        if (get_string_manager()->string_exists('introduction', "format_{$this->course->format}")) {
            $header = get_string('introduction', "format_{$this->course->format}");
        } 
        
        $params = [
            'header'    => $header,
            'summary'   => file_rewrite_pluginfile_urls($course->summary, 'pluginfile.php', $context->id, 'course', 'summary', null),
            'duration'  => $course->course_time,
        ];

        return $this->renderer->render_from_template($template, $params);
    }   

    private function renderCourseOutcomes($course)
    {
        $template = 'theme_golearningzone/course_outcomes';

        $header = get_string('course_what_you_will_learn', 'theme_golearningzone');
        if (get_string_manager()->string_exists('what_you_will_learn', "format_{$this->course->format}")) {
            $header = get_string('what_you_will_learn', "format_{$this->course->format}");
        } 

        $params = [
            'header' => $header,
            'text'   => $course->course_outcomes,
        ];
        return $this->renderer->render_from_template($template, $params);
    }

    private function getCompletionTranslations()
    {
        return [
            'completed'      => get_string('completed', 'theme_golearningzone'),
            'not-completed'  => get_string('not-completed', 'theme_golearningzone'),
            'failed'         => get_string('failed', 'theme_golearningzone'),
            'mark-completed' => get_string('mark-completed', 'theme_golearningzone')
        ];
    }
}
