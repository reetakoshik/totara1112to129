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
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Verify there are no 'Moodle' words in lang packs.
 */
class totara_core_totara_lang_testcase extends advanced_testcase {
    public function test_totara() {
        global $CFG;

        $exceptions = array();
        require(__DIR__ . '/fixtures/lang_exceptions.php');

        $subsystems = core_component::get_core_subsystems();
        foreach ($subsystems as $name => $unused) {
            $langfile = "$CFG->dirroot/lang/en/$name.php";
            if (!file_exists($langfile)) {
                continue;
            }
            $component = 'core_' . $name;
            $ex = array();
            if (isset($exceptions[$component])) {
                $ex = $exceptions[$component];
            }
            $this->verify_file($component, $langfile, $ex);
        }

        $types = core_component::get_plugin_types();
        foreach ($types as $type => $unused) {
            $plugins = core_component::get_plugin_list($type);
            foreach ($plugins as $name => $fulldir) {
                if (!file_exists("$fulldir/lang/en/")) {
                    // Weird, all plugins should have lang files.
                    continue;
                }
                $component = $type . '_' . $name;
                if ($type === 'mod') {
                    $langfile = "$fulldir/lang/en/$name.php";
                } else {
                    $langfile = "$fulldir/lang/en/$component.php";
                }
                $this->assertFileExists($langfile);
                $ex = array();
                if (isset($exceptions[$component])) {
                    $ex = $exceptions[$component];
                }
                $this->verify_file($component, $langfile, $ex);
            }
        }
    }

    protected function verify_file($component, $file, $exceptions) {
        if ($exceptions === true) {
            // Not used in Totara.
            return;
        }
        $string = array();
        include($file);
        $this->assertInternalType('array', $string);
        foreach ($string as $k => $v) {
            if (in_array($k, $exceptions)) {
                continue;
            }
            // No need to use Unicode stuff here, Moodle is not translated to Unicode.
            if (stripos($v, 'moodle') !== false) {
                $this->fail("Lang pack string '$component', '$k' contains a word 'Moodle' that is not whitelisted in Totara: $v");
            }
        }
    }
}

