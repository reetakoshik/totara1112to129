<?php

namespace GoLearningZone\Pages;

use GoLearningZone\Traits\Theme as ThemeTrait;
use GoLearningZone\Settings\LoginPage as LoginPageSettings;
use GoLearningZone\Settings\Theme as ThemeSettings;

class Login extends Base
{
    use ThemeTrait;

    public function render()
    {
        $template = 'theme_golearningzone/login';

        $params = $this->getDefaultPageValues() + [
            'logo'       => $this->getSettingFile(LoginPageSettings::LOGO_IMAGE),
            'background' => $this->getBackground(),
            'color'      => $this->getSettingValue(ThemeSettings::COLOR),
        ];

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
