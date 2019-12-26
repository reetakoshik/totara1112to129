<?php

namespace GoLearningZone\Partials;

use GoLearningZone\Traits\Theme as ThemeTrait;
use GoLearningZone\Settings\Footer as FooterSettings;

class Footer extends Base
{
    use ThemeTrait;

    public function render()
    {   
        $renderer = $this->renderer;

        return $renderer->render_from_template(
            'theme_golearningzone/footer',
            [
                'logo-link' => $this->getSettingFile(FooterSettings::LOGO_IMAGE), 
                'fb-link'   => $this->getSettingValue(FooterSettings::FACEBOOK), 
                'tw-link'   => $this->getSettingValue(FooterSettings::TWITTER), 
                'yt-link'   => $this->getSettingValue(FooterSettings::YOUTUBE), 
                'li-link'   => $this->getSettingValue(FooterSettings::LINKEDIN),
                'color'     => $this->getSettingValue(FooterSettings::COLOR),
                'right_to_left' => right_to_left()
            ]
        );
    }
}
