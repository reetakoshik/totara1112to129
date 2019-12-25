<?php

namespace GoLearningZone\Pages;

use GoLearningZone\Traits\Theme as ThemeTrait;

class DefaultgoalPage extends Base
{
    use ThemeTrait;

    public function render()
    {   
        global $CFG;
        $renderer = $this->renderer;
        $template = 'theme_golearningzone/standardgoal';

        $showBlocks = $renderer->page->blocks->region_has_content('side-pre', $renderer) 
                || $renderer->page->blocks->region_has_content('side-pre', $renderer);

        $params = $this->getDefaultPageValues() + [
            'side-pre'    => $this->renderer->blocks('side-pre'),
            'side-post'   => $this->renderer->blocks('side-post'),
            'main-size'   => $showBlocks ? 9 : 12,
            'blocks-size' => $showBlocks ? 3 : 0,
            'siteurl'     => $CFG->wwwroot
        ];

        return $this->renderer->render_from_template($template, $params);
    }    
}
