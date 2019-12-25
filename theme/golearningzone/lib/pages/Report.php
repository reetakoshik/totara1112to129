<?php

namespace GoLearningZone\Pages;
use GoLearningZone\Traits\Theme as ThemeTrait;

class Report extends Base
{
    use ThemeTrait;

    public function render()
    {   
        
        global $CFG;
        $template = 'theme_golearningzone/report';

        $params = $this->getDefaultPageValues() + [
            'side-pre'  => $this->renderer->blocks('side-pre'),
            'side-post' => $this->renderer->blocks('side-post'),
            'siteurl'   => $CFG->wwwroot
        ];

        return $this->renderer->render_from_template($template, $params);
    }    
}
