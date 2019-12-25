<?php

namespace GoLearningZone\Settings;

class Header
{
    const PAGE = 'theme_golearningzone_header';
    const PAGE_NAME = 'settings-header-name';
    const THEME = 'theme_golearningzone';
    const LOGO_IMAGE = 'headerlogo';
    const LOGO_LABLE = 'settings-header-logo';
    const LOGO_DESCRIPTION = 'settings-header-logodescription';
    const BADGES = 'headerbadges';
    const BADGES_LABLE = 'settings-header-badges';
    const BADGES_DESCRIPTION = 'settings-header-badgesdescription';
    const ALERTS = 'headeralerts';
    const ALERTS_LABLE = 'settings-header-alerts';
    const ALERTS_DESCRIPTION = 'settings-header-alertsdescription';
    const NOTIFICATIONS = 'headernotifications';
    const NOTIFICATIONS_LABLE = 'settings-header-notifications';
    const NOTIFICATIONS_DESCRIPTION = 'settings-header-notificationsdescription';
    const MESSAGES = 'headermessages';
    const MESSAGES_LABLE = 'settings-header-messages';
    const MESSAGES_DESCRIPTION = 'settings-header-messagesdescription';
    const SEARCH = 'headersearch';
    const SEARCH_LABLE = 'settings-header-search';
    const SEARCH_DESCRIPTION = 'settings-header-searchdescription';
    
    private $page;

    public function __construct($ADMIN)
    {
        $this->createPage($ADMIN);
        if ($ADMIN->fulltree) {
            $this->logo();
            $this->badges();
//            $this->alerts();
            $this->notifications();
            $this->messages();
            $this->search();
        }
    }

    private function createPage($ADMIN)
    {
        $pageName = get_string(static::PAGE_NAME, static::THEME);

        $this->page = new \admin_settingpage(static::PAGE, $pageName);

        $ADMIN->add(static::THEME, $this->page);
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

    private function badges()
    {
        $name = static::THEME.'/'.static::BADGES;
        $title = get_string(static::BADGES_LABLE, static::THEME);
        $description = get_string(static::BADGES_DESCRIPTION, static::THEME);
        $yes = 1;
        $no = 0;
        $default = $yes;
        $setting = new \admin_setting_configcheckbox($name, $title, $description, $default);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $this->page->add($setting);
    }

    private function alerts()
    {
        $name = static::THEME.'/'.static::ALERTS;
        $title = get_string(static::ALERTS_LABLE, static::THEME);
        $description = get_string(static::ALERTS_DESCRIPTION, static::THEME);
        $yes = 1;
        $no = 0;
        $default = $yes;
        $setting = new \admin_setting_configcheckbox($name, $title, $description, $default);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $this->page->add($setting);
    }

    private function notifications()
    {
        $name = static::THEME.'/'.static::NOTIFICATIONS;
        $title = get_string(static::NOTIFICATIONS_LABLE, static::THEME);
        $description = get_string(static::NOTIFICATIONS_DESCRIPTION, static::THEME);
        $yes = 1;
        $no = 0;
        $default = $yes;
        $setting = new \admin_setting_configcheckbox($name, $title, $description, $default);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $this->page->add($setting);
    }

    private function messages()
    {
        $name = static::THEME.'/'.static::MESSAGES;
        $title = get_string(static::MESSAGES_LABLE, static::THEME);
        $description = get_string(static::MESSAGES_DESCRIPTION, static::THEME);
        $yes = 1;
        $no = 0;
        $default = $yes;
        $setting = new \admin_setting_configcheckbox($name, $title, $description, $default);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $this->page->add($setting);
    }

    private function search()
    {
        $name = static::THEME.'/'.static::SEARCH;
        $title = get_string(static::SEARCH_LABLE, static::THEME);
        $description = get_string(static::SEARCH_DESCRIPTION, static::THEME);
        $yes = 1;
        $no = 0;
        $default = $yes;
        $setting = new \admin_setting_configcheckbox($name, $title, $description, $default);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $this->page->add($setting);
    }
}