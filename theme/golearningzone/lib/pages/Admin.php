<?php

namespace GoLearningZone\Pages;

use GoLearningZone\Traits\Theme as ThemeTrait;

class Admin extends Base
{
    use ThemeTrait;

    public function render()
    {
        global $CFG;
        $template = 'theme_golearningzone/admin';
        $params = $this->getDefaultPageValues() + [
            'side-pre'  => $this->renderer->blocks('side-pre'),
            'side-post' => $this->renderer->blocks('side-post'),
            'siteurl'   => $CFG->wwwroot
        ];
        return $this->renderer->render_from_template($template, $params);  
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
