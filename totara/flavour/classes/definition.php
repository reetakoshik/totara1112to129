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
 * A flavour definition class. All flavour plugins must override this.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @package totara_flavour
 */
abstract class definition {

    /**
     * An array of settings with flavour defaults.
     *
     * $newdefaults = array(
     *     'some_component' => array(
     *          'settingname' => 'somedefaultvalue',
     *     )
     * );
     *
     * @var array[]
     */
    protected $defaultsettings;

    /**
     * An array of settings with fixed values.
     *
     * $enforcedsettings = array(
     *     'some_component' => array(
     *          'settingname' => 'somefixedvalue',
     *     )
     * );
     *
     * @var array[]
     */
    protected $enforcedsettings;

    /**
     * An array of settings that are prohibited in the flavour,
     * these settings cannot be enabled.
     *
     * $prohibitedsettings = array(
     *     'some_component' => array(
     *          'settingname' => true,
     *     )
     * );
     *
     * @var array[]
     */
    protected $prohibitedsettings;

    /**
     * Constructs a flavour definition instance.
     */
    final public function __construct() {
        $this->defaultsettings = $this->load_default_settings();
        $this->enforcedsettings = $this->load_enforced_settings();
        $this->prohibitedsettings = $this->load_prohibited_settings();

        // Normalise the plugin names, we do not want to force Totara customers to use the word 'moodle'.
        if (isset($this->defaultsettings[''])) {
            foreach ($this->defaultsettings[''] as $k => $v) {
                $this->defaultsettings['moodle'][$k] = $v;
            }
            unset($this->defaultsettings['']);
        }
        if (isset($this->enforcedsettings[''])) {
            foreach ($this->enforcedsettings[''] as $k => $v) {
                $this->enforcedsettings['moodle'][$k] = $v;
            }
            unset($this->enforcedsettings['']);
        }
        if (isset($this->prohibitedsettings[''])) {
            foreach ($this->prohibitedsettings[''] as $k => $v) {
                $this->prohibitedsettings['moodle'][$k] = $v;
            }
            unset($this->prohibitedsettings['']);
        }
    }

    /**
     * Returns the flavour component. e.g. flavour_enterprise.
     *
     * @return string
     */
    abstract public function get_component();

    /**
     * Returns the definition name.
     * @return string
     */
    final public function get_name() {
        return get_string('pluginname', $this->get_component());
    }

    /**
     * Returns an array of settings for which the defaults have been overridden.
     * @return array[]
     */
    public function get_default_settings() {
        return $this->defaultsettings;
    }

    /**
     * Returns an array of enforced settings.
     * @return array[]
     */
    public function get_enforced_settings() {
        return $this->enforcedsettings;
    }

    /**
     * Returns an array of features prohibited in flavour.
     * @return array[]
     */
    public function get_prohibited_settings() {
        return $this->prohibitedsettings;
    }

    /**
     * Returns the notification to display during installation.
     *
     * @param \core_admin_renderer $output
     * @return string
     */
    public function get_active_flavour_notice(\core_admin_renderer $output) {
        if (!get_string_manager()->string_exists('activenoticetitle', $this->get_component())) {
            return null;
        }
        $html = $output->heading(get_string('activenoticetitle', $this->get_component()), '3');
        $html .= markdown_to_html(get_string('activenoticeinfo', $this->get_component()));
        return $html;
    }

    /**
     * Executes any flavour specific post activation steps.
     *
     * This is called also during installation and upgrade
     * when $CFG->forceflavour activated the first time.
     *
     * @return void
     */
    public function additional_activation_steps() {
        // Add your post install steps here.
        return;
    }

    /**
     * Executes any flavour specific upgrade steps.
     *
     * This is executed for the current active flavour
     * during each upgrade.
     *
     * @return void
     */
    public function additional_upgrade_steps() {
        // Add your post install steps here.
        return;
    }

    /**
     * Returns an array of setting defaults that differ for this flavour.
     *
     * This method is using the same format as /local/defaults.php
     * described in /local/readme.txt file.
     *
     * NOTE: NULL value means ask user during installation or upgrade.
     *
     * @return array[]
     */
    protected function load_default_settings() {
        // Override if flavour customises defaults without enforcing them.
        return array();
    }

    /**
     * Returns an array of enforced settings for this flavour.
     *
     * This method is using the same format as /local/defaults.php
     * described in /local/readme.txt file.

     * NOTE: it is not possible to enforce NULL value.
     *
     * @return array[]
     */
    protected function load_enforced_settings() {
        // Override if flavour hard codes any values.
        return array();
    }

    /**
     * Returns an array of setting defaults that are prohibited by this flavour,
     * by default it is the list of enforced settings.
     *
     * @return array[]
     */
    protected function load_prohibited_settings() {
        $prohibited = array();
        foreach ($this->load_enforced_settings() as $plugin => $settings) {
            foreach ($settings as $name => $unused) {
                $prohibited[$plugin][$name] = true;
            }
        }
        return $prohibited;
    }
}
