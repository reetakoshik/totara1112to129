<?php

namespace GoLearningZone\Pages;

use GoLearningZone\Traits\Theme as ThemeTrait;
use GoLearningZone\Settings\LoginPage as LoginPageSettings;
use GoLearningZone\Settings\Theme as ThemeSettings;
use GoLearningZone\Settings\Footer as FooterSettings;

class Login extends Base
{
    use ThemeTrait;

    public function render()
    {
        $template = 'theme_golearningzone/login';

        $params = $this->getDefaultPageValues() + [
            'logo'       => $this->getSettingFile(LoginPageSettings::LOGO_IMAGE),
            'background' => $this->getBackground(),
            'themecolor'      => $this->getSettingValue(ThemeSettings::COLOR),
            'logo-link' => $this->getSettingFile(FooterSettings::LOGO_IMAGE), 
                'fb-link'   => $this->getSettingValue(FooterSettings::FACEBOOK), 
                'tw-link'   => $this->getSettingValue(FooterSettings::TWITTER), 
                'yt-link'   => $this->getSettingValue(FooterSettings::YOUTUBE), 
                'li-link'   => $this->getSettingValue(FooterSettings::LINKEDIN),
                'color'     => $this->getSettingValue(FooterSettings::COLOR),
                'right_to_left' => right_to_left()
        ];//$this->getSettingValue(FooterSettings::COLOR)

        return $this->renderer->render_from_template($template, $params);
    }    

    private function getBackground()
    {
        global $CFG;

        $bgImage = $this->getSettingFile(LoginPageSettings::BG_IMAGE);
        $bgImage = $bgImage ? $bgImage : "{$CFG->wwwroot}/theme/golearningzone/pix/default/login_bg.jpg";

        return $bgImage;
    }
}
