<?php

namespace GoLearningZone\Traits;

use GoLearningZone\Settings\Theme as ThemeSettings;

trait Theme
{
    private function getDefaultPageValues()
    {
        global $CFG, $PAGE;
        $renderer = $this->renderer;

        $bgImage = $this->getSettingFile(ThemeSettings::BG_IMAGE);
        $favicon = $this->getSettingFile(ThemeSettings::FAVICON);

        return [
            'siteurl'        => $CFG->wwwroot,
            'doctype'        => $renderer->doctype(),
            'htmlattributes' => $renderer->htmlattributes(),
            'title'          => $renderer->page_title(),
            'favicon'        => $favicon ? $favicon : $renderer->favicon(),
            'standard_head_html' => $renderer->standard_head_html(),
            'body_attributes' => $renderer->body_attributes(),
            'standard_top_of_body_html' => $renderer->standard_top_of_body_html(),
            'header'         => $renderer->render_header(),
            'jqueryfile'   => $PAGE->requires->jquery(),
            'powerbijsfile'=> $CFG->wwwroot.'/blocks/powerreport/js/powerbi.js',
            'full_header'    => $renderer->full_header(),
            'course_content_header' => $renderer->course_content_header(),
            'main_content'   => $renderer->main_content(),
            'course_content_footer' => $renderer->course_content_footer(),
            'footer'         => $renderer->render_footer(),
            'standard_end_of_body_html' => $renderer->standard_end_of_body_html(),
            'palette'        => $this->getSettingValue(ThemeSettings::PALETTE),
            'breadcrumbs'    => $renderer->page->pagetype != 'site-index' ? $renderer->full_header() : '',
            'bg_image'       => $bgImage ? $bgImage : $CFG->wwwroot.'/theme/golearningzone/pix/standard_bg.png'
        ];
    }

    private function getSettingValue($name)
    {
        $theme = $this->renderer->page->theme;
        return isset($theme->settings->$name) 
            ? $theme->settings->$name 
            : false;
    }

    private function getSettingFile($name)
    {
        $theme = $this->renderer->page->theme;

        $value = isset($theme->settings->$name)
            ? $theme->setting_file_url($name, $name)
            : '';

        return $value;
    }
}
