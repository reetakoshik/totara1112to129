<?php

namespace GoLearningZone\Pages;

use GoLearningZone\Traits\Theme as ThemeTrait;

class Popup extends Base
{
    use ThemeTrait;

    public function render()
    {	
        $template = 'theme_golearningzone/popup';

        $params = $this->getDefaultPageValues();

        return $this->renderer->render_from_template($template, $params);
    }    
}
