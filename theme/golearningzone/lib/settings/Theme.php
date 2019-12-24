<?php

namespace GoLearningZone\Settings;

class Theme
{
    const PAGE = 'theme_golearningzone_theme';
    const PAGE_NAME = 'settings-theme-name';
    const THEME = 'theme_golearningzone';
    const PALETTE = 'palette';
    const PALETTE_LABLE = 'settings-theme-palette';
    const PALETTE_DESCRIPTION = 'settings-theme-palettedescription';
    const COLOR = 'themecolor';
    const COLOR_LABLE = 'settings-theme-color';
    const COLOR_DESCRIPTION = 'settings-theme-colordescription';
    const CUSTOMPALETTE = 'themecustompalette';
    const CUSTOMPALETTE_LABLE = 'settings-theme-custompalette';
    const CUSTOMPALETTE_DESCRIPTION = 'settings-theme-custompalettedescription';
    const BG_IMAGE = 'themebackgroundimage';
    const BG_LABLE = 'settings-theme-bgimage';
    const BG_DESCRIPTION = 'settings-theme-bgimagedescription';
    const FAVICON = 'themefavicon';
    const FAVICON_LABLE = 'settings-theme-favicon';
    const FAVICON_DESCRIPTION = 'settings-theme-favicondescription';
    
    private $page;

    public function __construct($ADMIN)
    {
        $this->createPage($ADMIN);
        if ($ADMIN->fulltree) {
            $this->palette();
            $this->customPalette();
            $this->color();
            $this->backgroundImage();
            $this->favicon();
        }
    }

    private function createPage($ADMIN)
    {
        $pageName = get_string(static::PAGE_NAME, static::THEME);

        $this->page = new \admin_settingpage(static::PAGE, $pageName);

        $ADMIN->add(static::THEME, $this->page);
    }

    private function palette()
    {
        $name = static::THEME.'/'.static::PALETTE;
        $title = get_string(static::PALETTE_LABLE, static::THEME);
        $description = get_string(static::PALETTE_DESCRIPTION, static::THEME);
        $palettes = [
            'blue'   => get_string('settings-theme-palette-blue', static::THEME), 
            'green'  => get_string('settings-theme-palette-green', static::THEME),
            'red'    => get_string('settings-theme-palette-red', static::THEME), 
            'orange' => get_string('settings-theme-palette-orange', static::THEME),
            'custom' => get_string('settings-theme-palette-custom', static::THEME),
        ];
        $setting = new \admin_setting_configselect($name, $title, $description, 'blue', $palettes);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $this->page->add($setting);
    }

    private function customPalette()
    {
        $name = static::THEME.'/'.static::CUSTOMPALETTE;
        $title = get_string(static::CUSTOMPALETTE_LABLE, static::THEME);
        $description = get_string(static::CUSTOMPALETTE_DESCRIPTION, static::THEME);
        $default = '#70007D';
        $setting = new \admin_setting_configcolourpicker($name, $title, $description, $default, null, false);
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

    private function backgroundImage()
    {
        $name = static::THEME.'/'.static::BG_IMAGE;
        $title = get_string(static::BG_LABLE, static::THEME);
        $description = get_string(static::BG_DESCRIPTION, static::THEME);
        $setting = new \admin_setting_configstoredfile($name, $title, $description, static::BG_IMAGE);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $this->page->add($setting);
    }

    private function favicon()
    {
        $name = static::THEME.'/'.static::FAVICON;
        $title = get_string(static::FAVICON_LABLE, static::THEME);
        $description = get_string(static::FAVICON_DESCRIPTION, static::THEME);
        $setting = new \admin_setting_configstoredfile($name, $title, $description, static::FAVICON);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $this->page->add($setting);
    }
}
