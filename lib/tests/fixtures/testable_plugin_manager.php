<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Provides testable_core_plugin_manager class.
 *
 * @package     core
 * @category    test
 * @copyright   2015 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Testable variant of the core_plugin_manager
 *
 * @copyright 2015 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testable_core_plugin_manager extends core_plugin_manager {

    /** @var testable_core_plugin_manager holds the singleton instance */
    protected static $singletoninstance;

    /**
     * Allows us to inject items directly into the plugins info tree.
     *
     * Do not forget to call our reset_caches() after using this method to force a new
     * singleton instance.
     *
     * @param string $type plugin type
     * @param string $name plugin name
     * @param \core\plugininfo\base $plugininfo plugin info class
     */
    public function inject_testable_plugininfo($type, $name, \core\plugininfo\base $plugininfo) {

        // Let the parent initialize the ->pluginsinfo tree.
        parent::get_plugins();

        // Inject the additional plugin info.
        $this->pluginsinfo[$type][$name] = $plugininfo;
    }
}
