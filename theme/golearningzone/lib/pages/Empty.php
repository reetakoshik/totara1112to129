<?php

namespace GoLearningZone\Pages;

use GoLearningZone\Traits\Theme as ThemeTrait;
use GoLearningZone\Settings\Theme as ThemeSettings;

class EmptyPage extends Base
{
    use ThemeTrait;

    public function render()
    {   
        global $CFG;
        $template = 'theme_golearningzone/empty';
        $renderer = $this->renderer;
        $bgImage = $this->getSettingFile(ThemeSettings::BG_IMAGE);

        $params = [
            'doctype'        => $renderer->doctype(),
            'htmlattributes' => $renderer->htmlattributes(),
            'title'          => $renderer->page_title(),
            'favicon'        => $renderer->favicon(),
            'standard_head_html' => $renderer->standard_head_html(),
            'body_attributes' => $renderer->body_attributes(),
            'standard_top_of_body_html' => $renderer->standard_top_of_body_html(),
            'main_content'   => $renderer->main_content(),
            'course_content_footer' => $renderer->course_content_footer(),
            'standard_end_of_body_html' => $renderer->standard_end_of_body_html(),
            'palette'        => $this->getSettingValue(ThemeSettings::PALETTE),
            'bg_image'       => $bgImage ? $bgImage : $CFG->wwwroot.'/theme/golearningzone/pix/standard_bg.png'
        ];

        return $this->renderer->render_from_template($template, $params);
    }    
}
