<?php

namespace GoLearningZone\Settings;

class LoginPage
{
    const THEME = 'theme_golearningzone';
    const BG_IMAGE = 'backgroundimage';
    const BG_LABLE = 'settings-loginpage-bgimage';
    const BG_DESCRIPTION = 'settings-loginpage-bgimagedescription';
    const LOGO_IMAGE = 'loginlogo';
    const LOGO_LABLE = 'settings-loginpage-logo';
    const LOGO_DESCRIPTION = 'settings-loginpage-logodescription';
    
    private $page;

    public function __construct($ADMIN)
    {
        $this->createPage($ADMIN);
        if ($ADMIN->fulltree) {
            $this->backgroundImage();
            $this->logo();
        }
    }

    private function createPage($ADMIN)
    {
        $this->page = new \admin_settingpage(  
            'theme_golearningzone_loginpage', 
            get_string('settings-loginpage-name', 'theme_golearningzone')
        );

        $ADMIN->add('theme_golearningzone', $this->page);
    }

    private function backgroundImage()
    {
        $name = static::THEME.'/'.static::BG_IMAGE;
        $title = get_string(static::BG_LABLE, static::THEME);
        $description = get_string(static::BG_DESCRIPTION, static::THEME);
        $setting = new \admin_setting_configstoredfile($name, $title, $description, static::BG_IMAGE);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $this->page->add($setting);
    }

    private function logo()
    {
        $name = static::THEME.'/'.static::LOGO_IMAGE;
        $title = get_string(static::LOGO_LABLE, static::THEME);
        $description = get_string(static::LOGO_DESCRIPTION, static::THEME);
        $setting = new \admin_setting_configstoredfile($name, $title, $description, static::LOGO_IMAGE);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $this->page->add($setting);
    }
}
