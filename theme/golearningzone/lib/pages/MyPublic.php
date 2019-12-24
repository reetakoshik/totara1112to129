<?php

namespace GoLearningZone\Pages;

use GoLearningZone\Traits\Theme as ThemeTrait;

class MyPublic extends Base
{
    use ThemeTrait;

    public function render()
    {
        $renderer = $this->renderer;
        $template = 'theme_golearningzone/my_public';

        $showBlocks = $renderer->page->blocks->region_has_content('side-pre', $renderer) 
                || $renderer->page->blocks->region_has_content('side-pre', $renderer);

        $params = $this->getDefaultPageValues() + [
            'side-pre'    => $this->renderer->blocks('side-pre'),
            'side-post'   => $this->renderer->blocks('side-post'),
            'main-size'   => $showBlocks ? 9 : 12,
            'blocks-size' => $showBlocks ? 3 : 0
        ];

        return $this->renderer->render_from_template($template, $params);
    }    
}
