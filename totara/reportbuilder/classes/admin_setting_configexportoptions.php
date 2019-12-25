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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');

/**
 * Admin setting for export options in reportbuilder.
 */
class totara_reportbuilder_admin_setting_configexportoptions extends admin_setting_configmulticheckbox {
    /**
     * Constructs the new export options setting.
     */
    public function __construct() {
        $defaultoptions = array(
            'excel' => 1,
            'csv' => 1,
            'ods' => 1,
            'pdfportrait' => 1,
            'pdflandscape' => 1,
        );

        parent::__construct('reportbuilder/exportoptions',
            new lang_string('exportoptions', 'totara_reportbuilder'),
            new lang_string('reportbuilderexportoptions_help', 'totara_reportbuilder'),
            $defaultoptions,
            null
        );
    }

    /**
     * Loads the export options.
     * @return bool
     */
    public function load_choices() {
        if (is_array($this->choices)) {
            return true;
        }

        $this->choices = \totara_core\tabexport_writer::get_export_classes();
        foreach ($this->choices as $type => $class) {
            // Different plugins may use the same option name, use plugin name here instead.
            $this->choices[$type] = get_string('pluginname', 'tabexport_' . $type);
            if (!$class::is_ready()) {
                $this->choices[$type] = get_string('plugindisabled', 'core_plugin') . ': ' . $this->choices[$type];
            }
        }

        // Fusion is a special Reportbuilder hack.
        $this->choices['fusion'] = new lang_string('exportfusion', 'totara_reportbuilder');

        return true;
    }
}
