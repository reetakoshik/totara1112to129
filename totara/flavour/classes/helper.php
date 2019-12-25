<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @package totara_flavour
 */

namespace totara_flavour;

defined('MOODLE_INTERNAL') || die();

/**
 * Totara flavour helper class
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @package totara_flavour
 */
class helper {
    /**
     * Activates Totara flavour.
     *
     * @param string $component
     */
    public static function set_active_flavour($component) {
        if ($component === '') {
            unset_config('currentflavour', 'totara_flavour');
            return;
        }

        if ($component !== clean_param($component, PARAM_COMPONENT) or strpos($component, 'flavour_') !== 0) {
            throw new \coding_exception('Invalid flavour component name');
        }

        $flavour = self::get_flavour_definition($component);
        if (!$flavour) {
            throw new \coding_exception('Unknown flavour component name');
        }

        self::enforce_flavour($flavour, true);
    }

    /**
     * Executes post installation steps for flavours.
     */
    public static function execute_post_install_steps() {
        global $CFG;

        if (isset($CFG->forceflavour) and $CFG->forceflavour !== '') {

            $component = 'flavour_' . $CFG->forceflavour;
            // As we are during installation check that the set flavour is available for trying to set it.
            if ($component !== clean_param($component, PARAM_COMPONENT) or strpos($component, 'flavour_') !== 0) {
                return;
            }
            if (!self::get_flavour_definition($component)) {
                return;
            }

            self::set_active_flavour($component);
            return;
        }

        $flavour = self::get_active_flavour_definition();
        if ($flavour) {
            self::enforce_flavour($flavour, true);
        }
    }

    /**
     * Executes post upgrade steps for flavours.
     */
    public static function execute_post_upgrade_steps() {
        global $CFG;

        if (isset($CFG->forceflavour) and $CFG->forceflavour === '') {
            self::set_active_flavour('');
            return;
        }

        $flavour = self::get_active_flavour_definition();
        if ($flavour) {
            self::enforce_flavour($flavour, false);
        }
    }

    /**
     * Executes post upgradesettings.php steps for flavours.
     *
     * This exists because upgradesettings.php is executed under
     * admin account which guarantees all flavour settings
     * can be forced properly.
     */
    public static function execute_post_upgradesettings_steps() {
        $flavour = self::get_active_flavour_definition();
        if ($flavour) {
            self::enforce_flavour($flavour, false);
        }
    }

    /**
     * Make sure the flavour is active and all settings enforced.
     *
     * NOTE: this may run under non-admin account.
     *
     * @param definition $flavour
     * @param bool $forceactivation
     */
    protected static function enforce_flavour(definition $flavour, $forceactivation) {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');

        $component = $flavour->get_component();
        $previous = get_config('totara_flavour', 'currentflavour');

        if ($previous !== $component) {
            set_config('currentflavour', $component, 'totara_flavour');
            // We must reset admin tree caches here too.
            $activation = true;

        } else {
            $activation = false;
        }

        // Make sure the there are no stale caches and defaults.
        $root = admin_get_root(true, true);

        // NOTE: the settings are fully enforced only when run under admin account,
        //       it is recommended to run the CLI tool after each change and upgrade
        //       because it runs as admin user.

        // Create a fake setting form data just with our forced settings and
        // write them to config tables if current values are different.
        $enforcedsettings = $flavour->get_enforced_settings();
        $formsdata = array();
        self::create_fake_settings_formdata($root, $enforcedsettings, $formsdata);
        admin_write_settings((object)$formsdata);

        if ($forceactivation or $activation) {
            $flavour->additional_activation_steps();
        } else {
            $flavour->additional_upgrade_steps();
        }
    }

    /**
     * Recursively find the proper data structure expected by admin_write_settings().
     *
     * @param mixed $node
     * @param array $enforcedsettings
     * @param array $formsdata the result
     * @return void
     */
    protected static function create_fake_settings_formdata($node, $enforcedsettings, &$formsdata) {
        if ($node instanceof \admin_category) {
            $children = $node->get_children();
            if ($children) {
                foreach ($children as $child) {
                    self::create_fake_settings_formdata($child, $enforcedsettings, $formsdata);
                }
            }
        } else if ($node instanceof \admin_settingpage) {
            foreach ($node->settings as $setting) {
                $plugin = is_null($setting->plugin) ? 'moodle' : $setting->plugin;
                /** @var \admin_setting $setting */
                if (isset($enforcedsettings[$plugin][$setting->name])) {
                    $fullname = $setting->get_full_name();
                    $formsdata[$fullname] = $enforcedsettings[$plugin][$setting->name];
                }
            }
        }
    }

    /**
     * Returns an array of setting defaults.
     *
     * @return array[]
     */
    public static function get_defaults_setting() {
        $flavour = self::get_active_flavour_definition();
        if (!$flavour) {
            return array();
        }
        return $flavour->get_default_settings();
    }

    /**
     * Returns an array of enforced settings.
     *
     * @return array[]
     */
    public static function get_enforced_settings() {
        $flavour = self::get_active_flavour_definition();
        if (!$flavour) {
            return array();
        }
        return $flavour->get_enforced_settings();
    }

    /**
     * Returns an array of enforced settings.
     *
     * @return array[]
     */
    public static function get_prohibited_settings() {
        $flavour = self::get_active_flavour_definition();
        if (!$flavour) {
            return array();
        }
        return $flavour->get_prohibited_settings();
    }

    /**
     * Returns the component of the selected flavour.
     *
     * @return string
     */
    public static function get_active_flavour_component() {
        $flavour = self::get_active_flavour_definition();
        if (!$flavour) {
            return null;
        }
        return $flavour->get_component();
    }

    /**
     * Returns the installation notification for the given flavour.
     *
     * @param \core_admin_renderer $output
     * @return string
     */
    public static function get_active_flavour_notice(\core_admin_renderer $output) {
        $flavour = self::get_active_flavour_definition();
        if (!$flavour) {
            return null;
        }
        return $flavour->get_active_flavour_notice($output);
    }

    /**
     * Returns an array of available flavours.
     *
     * @return definition[] indexed by component names
     */
    public static function get_available_flavour_definitions() {
        $plugins = array();

        $active = self::get_active_flavour_definition();
        if ($active) {
            $plugins[$active->get_component()] = $active;
        }

        foreach (\core_component::get_plugin_list('flavour') as $fname => $unused) {
            $component = 'flavour_' . $fname;
            if (isset($plugins[$component])) {
                // Skip active.
                continue;
            }
            $definition = self::get_flavour_definition($component);
            if (!$definition) {
                debugging("Invalid Totara flavour '$component' detected, verify the definition class exists." , DEBUG_DEVELOPER);
                continue;
            }
            $plugins[$component] = $definition;
        }

        return $plugins;
    }

    /**
     * Get selected flavour.
     *
     * @return definition null means no flavour active.
     */
    public static function get_active_flavour_definition() {
        global $CFG;

        if (isset($CFG->forceflavour)) {
            if ($CFG->forceflavour === '') {
                // Flavours are completely disabled.
                return null;
            }

            $flavour = self::get_flavour_definition('flavour_' . $CFG->forceflavour);
            if (!$flavour) {
                debugging('Invalid flavour specified in $CFG->forceflavour', DEBUG_DEVELOPER);
                return null;
            }

            return $flavour;
        }

        if (during_initial_install()) {
            // Nothing could be configured yet, we are doing the initial install.
            return null;
        }

        $current = get_config('totara_flavour', 'currentflavour');
        if (!$current) {
            // Nothing configured.
            return null;
        }

        $flavour = self::get_flavour_definition($current);
        if (!$flavour) {
            // Invalid flavour value, ignore it.
            return null;
        }

        return $flavour;
    }

    /**
     * Get definition instance.
     *
     * @param string $component
     * @return definition or null if does not exist
     */
    protected static function get_flavour_definition($component) {
        $classname = $component . '\definition';
        if (!class_exists($classname)) {
            return null;
        }
        $flavour = new $classname();
        if (!($flavour instanceof definition)) {
            return null;
        }
        return $flavour;
    }
}
