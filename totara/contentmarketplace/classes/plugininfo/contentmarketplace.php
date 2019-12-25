<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Sergey Vidusov <sergey.vidusov@androgogic.com>
 * @package totara_contentmarketplace
 */

namespace totara_contentmarketplace\plugininfo;

defined('MOODLE_INTERNAL') || die();

class contentmarketplace extends \core\plugininfo\base {

    public static function get_enabled_plugins() {
        global $DB;
        $sql = "SELECT plugin
            FROM {config_plugins}
            WHERE ".$DB->sql_like('plugin', ':pluginname')."
                AND name = 'enabled'
                AND value = '1'";
        $records = $DB->get_records_sql($sql, array('pluginname' => 'contentmarketplace_%'));
        if (!$records) {
            return array();
        }

        $enabled = array();
        foreach ($records as $record) {
            $name = str_replace('contentmarketplace_', '', $record->plugin);
            $enabled[$name] = $name;
        }
        return $enabled;
    }

    public function is_uninstall_allowed() {
        if ($this->is_standard()) {
            return false;
        }
        return true;
    }

    /**
     * @return \totara_contentmarketplace\local\contentmarketplace\contentmarketplace
     */
    public function contentmarketplace() {
        $classname = "\\{$this->component}\\contentmarketplace";
        return new $classname();
    }

    /**
     * @return \totara_contentmarketplace\local\contentmarketplace\search
     */
    public function search() {
        $classname = "\\{$this->component}\\search";
        return new $classname();
    }

    /**
     * @return \totara_contentmarketplace\local\contentmarketplace\collection
     */
    public function collection() {
        $classname = "\\{$this->component}\\collection";
        return new $classname();
    }

    /**
     * @param string $name
     * @param bool $required If set to true (default) and the plugin doesn't exist a coding_exception is thrown.
     * @return contentmarketplace|null
     */
    public static function plugin($name, $required = true) {
        $plugin = \core_plugin_manager::instance()->get_plugin_info("contentmarketplace_{$name}");
        if ($plugin === null) {
            if ($required) {
                throw new \coding_exception('Unknown content marketplace plugin requested.');
            }
            return null;
        }
        if (!$plugin instanceof contentmarketplace) {
            throw new \coding_exception('Content marketplace plugin is not of the correct type.');
        }
        return $plugin;
    }

    public function enable() {
        global $USER;
        $userid = (isloggedin()) ? $USER->id : -1;
        set_config('enabled_by', $userid, $this->component);
        set_config('enabled_on', time(), $this->component);
        $this->set_enabled(1);
    }

    public function disable() {
        set_config('enabled_by', '', $this->component);
        set_config('enabled_on', '', $this->component);
        $this->set_enabled(0);
    }

    protected function set_enabled($value) {
        set_config('enabled', $value, $this->component);
        \core_plugin_manager::reset_caches();
    }

    public function has_never_been_enabled() {
        return get_config($this->component, 'enabled') === false;
    }
}
