<?php

namespace GoLearningZone\Settings;

class Footer
{
    const PAGE = 'theme_golearningzone_footer';
    const THEME = 'theme_golearningzone';
    const LOGO_IMAGE = 'footerlogo';
    const LOGO_LABLE = 'settings-footer-logo';
    const LOGO_DESCRIPTION = 'settings-footer-logodescription';
    const TWITTER = 'twitter';
    const TWITTER_LABLE = 'settings-footer-twitter';
    const TWITTER_DESCRIPTION = 'settings-footer-twitterdescription';
    const FACEBOOK = 'facebook';
    const FACEBOOK_LABLE = 'settings-footer-facebook';
    const FACEBOOK_DESCRIPTION = 'settings-footer-facebookdescription';
    const LINKEDIN = 'linkedin';
    const LINKEDIN_LABLE = 'settings-footer-linkedin';
    const LINKEDIN_DESCRIPTION = 'settings-footer-linkedindescription';
    const YOUTUBE = 'youtube';
    const YOUTUBE_LABLE = 'settings-footer-youtube';
    const YOUTUBE_DESCRIPTION = 'settings-footer-youtubedescription';
    const COLOR = 'footercolor';
    const COLOR_LABLE = 'settings-footer-color';
    const COLOR_DESCRIPTION = 'settings-footer-colordescription';
    
    private $page;

    public function __construct($ADMIN)
    {
        $this->createPage($ADMIN);
        if ($ADMIN->fulltree) {
            $this->logo();
            $this->twitter();
            $this->facebook();
            $this->linkedin();
            $this->youtube();
            $this->color();
        }
    }

    private function createPage($ADMIN)
    {
        $this->page = new \admin_settingpage(  
            static::PAGE, 
            get_string('settings-footer-name', static::THEME)
        );

        $ADMIN->add('theme_golearningzone', $this->page);
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

    private function twitter()
    {
        $name = static::THEME.'/'.static::TWITTER;
        $title = get_string(static::TWITTER_LABLE, static::THEME);
        $description = get_string(static::TWITTER_DESCRIPTION, static::THEME);
        $setting = new \admin_setting_configtext($name, $title, $description, '');
        $setting->set_updatedcallback('theme_reset_all_caches');
        $this->page->add($setting);
    }

    private function facebook()
    {
        $name = static::THEME.'/'.static::FACEBOOK;
        $title = get_string(static::FACEBOOK_LABLE, static::THEME);
        $description = get_string(static::FACEBOOK_DESCRIPTION, static::THEME);
        $setting = new \admin_setting_configtext($name, $title, $description, '');
        $setting->set_updatedcallback('theme_reset_all_caches');
        $this->page->add($setting);
    }

    private function linkedin()
    {
        $name = static::THEME.'/'.static::LINKEDIN;
        $title = get_string(static::LINKEDIN_LABLE, static::THEME);
        $description = get_string(static::LINKEDIN_DESCRIPTION, static::THEME);
        $setting = new \admin_setting_configtext($name, $title, $description, '');
        $setting->set_updatedcallback('theme_reset_all_caches');
        $this->page->add($setting);
    }

    private function youtube()
    {
        $name = static::THEME.'/'.static::YOUTUBE;
        $title = get_string(static::YOUTUBE_LABLE, static::THEME);
        $description = get_string(static::YOUTUBE_DESCRIPTION, static::THEME);
        $setting = new \admin_setting_configtext($name, $title, $description, '');
        $setting->set_updatedcallback('theme_reset_all_caches');
        $this->page->add($setting);
    }

    private function color()
    {
        $name = static::THEME.'/'.static::COLOR;
        $title = get_string(static::COLOR_LABLE, static::THEME);
        $description = get_string(static::COLOR_DESCRIPTION, static::THEME);
        $colors = [
            'dark'   => get_string('settings-theme-color-dark', static::THEME),
            'bright' => get_string('settings-theme-color-bright', static::THEME),
        ];
        $default = 'dark';
        $setting = new \admin_setting_configselect($name, $title, $description, $default, $colors);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $this->page->add($setting);
    }
}
