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
 * Totara feature overview setting renderable
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @package totara_flavour
 */
class overview_setting implements \renderable {

    /**
     * The name of the setting.
     * This is the untranslated raw name.
     * @var string
     */
    public $name;

    /**
     * The component that owns this setting.
     * @var string
     */
    public $component;

    /**
     * The current value of this setting.
     * @var mixed
     */
    public $currentvalue;

    /**
     * An array describing how each flavour sets this setting up.
     * @var array[]
     */
    protected $setup = array();

    /**
     * Constructs a new overview renderable setting.
     *
     * @param string $name
     * @param string $component
     * @param mixed $fakevalue null for real settings, value for fake settings
     */
    public function __construct($name, $component, $fakevalue) {
        $this->name = $name;
        $this->component = $component;

        if (!is_null($fakevalue)) {
            $this->currentvalue = $fakevalue;
        } else {
            $current = get_config($this->component, $name);
            if ($current === false) {
                $this->currentvalue = null;
            } else {
                $this->currentvalue = $current;
            }
        }
    }

    /**
     * Returns the translated name for this setting.
     *
     * @return string
     */
    public function get_name() {
        if (get_string_manager()->string_exists('setting_' . $this->component . '_' . $this->name, 'totara_flavour')) {
            // If there is a string defined in the flavour pack use that.
            return get_string('setting_' . $this->component . '_' . $this->name, 'totara_flavour');
        } else if ($this->component === 'moodle' and get_string_manager()->string_exists('setting_core_' . $this->name, 'totara_flavour')) {
                // If there is a string defined in the flavour pack use that.
                return get_string('setting_core_' . $this->name, 'totara_flavour');
        } else {
            debugging('Setting without name translation in the overview report '.$this->name, DEBUG_DEVELOPER);
            // Finally just use the name of setting as is.
            if ($this->component === 'moodle' || $this->component === 'core') {
                return $this->name;
            }
            return $this->component . '/' . $this->name;
        }
    }

    /**
     * Returns the description
     *
     * @return string
     */
    public function get_description() {
        // We may or may not have what we need.
        if (get_string_manager()->string_exists('setting_' . $this->component . '_' . $this->name . '_desc', 'totara_flavour')) {
            // If there is a string defined in the flavour pack use that.
            return get_string('setting_' . $this->component . '_' . $this->name . '_desc', 'totara_flavour');
        } else if ($this->component === 'moodle' and  get_string_manager()->string_exists('setting_core_' . $this->name.'_desc', 'totara_flavour')) {
                // If there is a string defined in the flavour pack use that.
                return get_string('setting_core_' . $this->name . '_desc', 'totara_flavour');
        } else {
            // Finally just use the name of setting as is.
            return '';
        }
    }

    /**
     * Registers how a flavour has set up this setting.
     *
     * @param string $flavour The name of the flavour this setup is for.
     * @param bool $prohibited True if this setting is prohibited by the flavour, false otherwise.
     */
    public function register_flavour_setup($flavour, $prohibited) {
        $this->setup[$flavour] = array(
            'prohibited' => (bool)$prohibited,
        );
    }

    /**
     * Is this setting prohibited in current flavour?
     * @param string $flavour
     * @return bool
     */
    public function is_prohibited($flavour) {
        return !empty($this->setup[$flavour]['prohibited']);
    }

    /**
     * Returns true if this setting is on.
     *
     * This method is very hacky, each setting can choose to do its own thing.
     * As such this method attempts to guess whether the setting is on or off.
     *
     * @return bool
     */
    public function is_on() {
        $totarafeatures = totara_advanced_features_list();
        foreach ($totarafeatures as $name) {
            if ($this->name === 'enable' . $name) {
                return ((int)$this->currentvalue !== TOTARA_DISABLEFEATURE);
            }
        }
        return !empty($this->currentvalue);
    }

    /**
     * Is this setting enforced via config.php?
     *
     * @return bool
     */
    public function is_set_in_configphp() {
        global $CFG;

        if ($this->component === 'moodle') {
            return array_key_exists($this->name, $CFG->config_php_settings);
        }

        if (!isset($CFG->forced_plugin_settings[$this->component])) {
            return false;
        }

        return array_key_exists($this->name, $CFG->forced_plugin_settings[$this->component]);
    }
}
