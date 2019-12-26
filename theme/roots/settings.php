<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package theme_roots
 */

defined('MOODLE_INTERNAL') || die;

$component = 'theme_roots';

if ($ADMIN->fulltree) {

    // Favicon file setting.
    $name = "{$component}/favicon";
    $title = new lang_string('favicon', 'totara_core');
    $description = new lang_string('favicondesc', 'totara_core');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'favicon', 0, array('accepted_types' => '.ico'));
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Logo file setting.
    $name = "{$component}/logo";
    $title = new lang_string('logo', 'totara_core');
    $description = new lang_string('logodesc', 'totara_core');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'logo', 0 , ['accepted_types' => 'web_image']);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Logo alt text.
    $name = "{$component}/alttext";
    $title = new lang_string('alttext', 'totara_core');
    $description = new lang_string('alttextdesc', 'totara_core');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);
}
