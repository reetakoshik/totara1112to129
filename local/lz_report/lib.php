<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/mod/quiz/locallib.php');
require_once __DIR__.'/etc/config.php';

function local_lz_report_extend_navigation_course($navigation, $course, $context)
{
    if (!has_capability(VIEW_LZ_REPORT_CAPABILITY, $context)) {
        return;
    }

    $node = $navigation->add(
        get_string('menu-item-name', 'local_lz_report'),
        null,
        navigation_node::TYPE_SECTION
    );

    $node->add(
        get_string('sourcetitle', 'rb_source_detailed_course_completion'),
        new moodle_url(
            '/local/lz_report/detailed_course_completion/embedded.php',
            ['courseid' => $course->id]
        ),
        navigation_node::TYPE_SETTING,
        null,
        null,
        new pix_icon('i/report', '')
    );   

    $node->add(
        get_string('sourcetitle', 'rb_source_detailed_activity_completion'),
        new moodle_url(
            '/local/lz_report/detailed_activity_completion/embedded.php',
            ['courseid' => $course->id]
        ),
        navigation_node::TYPE_SETTING,
        null,
        null,
        new pix_icon('i/report', '')
    );
}

function local_lz_report_extend_settings_navigation($navigation, $context)
{
    if (!has_capability(VIEW_LZ_REPORT_CAPABILITY, $context)) {
        return;
    }

    $node = $navigation->find('progadmin', navigation_node::TYPE_COURSE);
    if ($node) {
        $node->add(
            get_string('sourcetitle', 'rb_source_detailed_program_completion'),
            new moodle_url(
                '/local/lz_report/detailed_program_completion/embedded.php',
                ['programid' => $context->instanceid]
            ),
            navigation_node::TYPE_SETTING,
            null,
            null,
            new pix_icon('i/report', '')
        );
    }

    $quizReportOverview = $navigation->find('quiz_report_overview', navigation_node::TYPE_SETTING);
    if ($quizReportOverview) {
        $quiznode = $quizReportOverview->parent;
        $node = navigation_node::create(
            get_string('sourcetitle', 'rb_source_detailed_quiz_completion'),
            new moodle_url(
                '/local/lz_report/detailed_quiz_completion/embedded.php',
                ['quizid' => $context->instanceid]
            ),
            navigation_node::TYPE_SETTING,
            null,
            null,
            new pix_icon('i/report', '')
        );
        $quiznode->add_node($node);
    }
    
}