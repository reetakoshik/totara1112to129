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
        $blocksSizes = $this->calculateBlocksSizes();
        $context = $this->course ? \context_course::instance($this->course->id) 
                                 : \context_system::instance();

        $showBlocks = isset($this->course->show_course_blocks) ? $this->course->show_course_blocks : false;
        $alwaysShowBlocks = has_capability('moodle/course:update', $context);
        $params = $this->getDefaultPageValues() + [
            'is_editing'    => $this->renderer->page->user_is_editing(),
            'first'         => $this->blocks('first', $blocksSizes[0][0]),
            'side-pre'         => $this->renderer->blocks('side-pre'),
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


    private function calculateBlocksSizes()
    {
        $defaultBlocksSizes = [
            [12],
            [8, 4],
            [4, 4, 4],
            [4, 4, 4]
        ];

        $renderer = $this->renderer;

        if ($renderer->page->user_is_editing()) {
            return $defaultBlocksSizes;
        }

        function rowBlockSize(array $defaultSizes) {
            $args = func_get_args();
            array_shift($args);
            $columns = $args;

            if (count($columns) !== count($defaultSizes)) {
                throw new Exception("Diffrent count of columns and sizes", 1);
            }

            $sizes = $defaultSizes;
            $hasEmptyColumns = false;
            $displayedColumnsCount = 0;
            foreach ($columns as $columnNumber => $column) {
                if (!$column) {
                    $sizes[$columnNumber] = 0;
                    $hasEmptyColumns = true;
                } else {
                    $displayedColumnsCount++;
                }
            }

            if ($hasEmptyColumns) {
                $newSize = $displayedColumnsCount ? 12 / $displayedColumnsCount : 0;
                foreach ($sizes as $columnNumber => $size) {
                    if ($size) {
                        $sizes[$columnNumber] = $newSize;
                    }
                }
            }

            return $sizes;
        }

        $renderer = $this->renderer;

        $blocksSizes = [
            0 => rowBlockSize(
                $defaultBlocksSizes[0],
                $renderer->page->blocks->region_has_content('first', $renderer)
            ),
            1 => rowBlockSize(
                $defaultBlocksSizes[1],
                $renderer->page->blocks->region_has_content('second-left', $renderer),
                $renderer->page->blocks->region_has_content('second-right', $renderer)
            ),
            2 => rowBlockSize(
                $defaultBlocksSizes[2],
                $renderer->page->blocks->region_has_content('third-left', $renderer),
                $renderer->page->blocks->region_has_content('third-center', $renderer),
                $renderer->page->blocks->region_has_content('third-right', $renderer)
            ),
            3 => rowBlockSize(
                $defaultBlocksSizes[3],
                $renderer->page->blocks->region_has_content('fourth-left', $renderer),
                $renderer->page->blocks->region_has_content('fourth-center', $renderer),
                $renderer->page->blocks->region_has_content('fourth-right', $renderer)
            )
        ];

        return $blocksSizes;
    }

    private function blocks($name, $width = 12)
    {
        $renderer = $this->renderer;
        $block = $renderer->blocks($name);

         if (!$width || !$block) {
             return '';
         }
         

        return $renderer->render_from_template(
            'theme_golearningzone/front_page_block_wrapper',
            [
                'size'  => $width,
                'name'  => $name,
                'block' => $block,
            ]
        );
    }
    
}
