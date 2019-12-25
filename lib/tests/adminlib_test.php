<?php
/*
 * This file is part of Totara LMS
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
 * @copyright 2018 onwards Totara Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Brian Barnes <brian.barnes@totaralearning.com>
 * @package   core_output
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');

class adminlib_testcase extends basic_testcase {
    function test_admin_setting_configselect_constructor() {

        // Test traditional case
        $options = array(
            'opt1' => 'option 1',
            'opt2' => 'option 2',
            'opt3' => 'option 3',
            'opt4' => 'option 4',
            'opt5' => 'option 5',
        );

        $configsetting = new admin_setting_configselect('a', 'b', 'c', 'opt5', $options);

        $this->assertSame($configsetting->choices, $options);

        // Test option groups
        $options = array(
            'opt 1' => array(
                'opt1a' => 'option 1a',
                'opt1b' => 'option 1b',
                'opt1c' => 'option 1c',
                'opt1d' => 'option 1d',
            ),
            'opt 2' => array(
                'opt2a' => 'option 2a',
                'opt2b' => 'option 2b',
                'opt2c' => 'option 2c',
                'opt2d' => 'option 2d',
            )
        );

        $configsetting = new admin_setting_configselect('a', 'b', 'c', 'opt5', $options);

        $this->assertSame($configsetting->optgroups, $options);
        $flatterned = $options['opt 1'] + $options['opt 2'];
        $this->assertSame($configsetting->choices, $flatterned);

        // Test mixed groups
        $options = array(
            'opt1' => array(
                'opt1a' => 'option 1a',
                'opt1b' => 'option 1b',
                'opt1c' => 'option 1c',
                'opt1d' => 'option 1d',
            ),
            'opt2' => 'option 2',
            'opt3' => array(
                'opt3a' => 'option 3a',
                'opt3b' => 'option 3b',
                'opt3c' => 'option 3c',
                'opt3d' => 'option 3d',
            ),
            'opt4' => 'option 4',
            'opt5' => 'option 5'
        );

        $configsetting = new admin_setting_configselect('a', 'b', 'c', 'opt5', $options);
        $this->assertSame($configsetting->optgroups, array('opt1' => $options['opt1'], 'opt3' => $options['opt3']));
        $flatterned = $options['opt1'] + array('opt2' => 'option 2') + $options['opt3'] + array('opt4' => 'option 4', 'opt5' => 'option 5');
        $this->assertSame($configsetting->choices, $flatterned);

        // Test numeric keys
        $options = array(
            1 => array(
                2 => 'option 1a',
                3 => 'option 1b',
                4 => 'option 1c',
                5 => 'option 1d',
            ),
            6 => 'option 2',
            7 => array(
                8 => 'option 3a',
                9 => 'option 3b',
                10 => 'option 3c',
                11 => 'option 3d',
            ),
            12 => 'option 4',
            13 => 'option 5'
        );

        $configsetting = new admin_setting_configselect('a', 'b', 'c', 6, $options);
        $this->assertSame($configsetting->optgroups, array(1 => $options[1], 7 => $options[7]));
        $flatterned = $options[1] + array(6 => 'option 2') + $options[7] + array(12 => 'option 4', 13 => 'option 5');
        $this->assertSame($configsetting->choices, $flatterned);
    }


    function test_admin_setting_configmultiselect_constructor() {

        // Test traditional case
        $options = array(
            'opt1' => 'option 1',
            'opt2' => 'option 2',
            'opt3' => 'option 3',
            'opt4' => 'option 4',
            'opt5' => 'option 5',
        );

        $configsetting = new admin_setting_configmultiselect('a', 'b', 'c', 'opt5', $options);

        $this->assertSame($configsetting->choices, $options);

        // Test option groups
        $options = array(
            'opt 1' => array(
                'opt1a' => 'option 1a',
                'opt1b' => 'option 1b',
                'opt1c' => 'option 1c',
                'opt1d' => 'option 1d',
            ),
            'opt 2' => array(
                'opt2a' => 'option 2a',
                'opt2b' => 'option 2b',
                'opt2c' => 'option 2c',
                'opt2d' => 'option 2d',
            )
        );

        $configsetting = new admin_setting_configmultiselect('a', 'b', 'c', 'opt5', $options);

        $this->assertSame($configsetting->optgroups, $options);
        $flatterned = $options['opt 1'] + $options['opt 2'];
        $this->assertSame($configsetting->choices, $flatterned);

        // Test mixed groups
        $options = array(
            'opt1' => array(
                'opt1a' => 'option 1a',
                'opt1b' => 'option 1b',
                'opt1c' => 'option 1c',
                'opt1d' => 'option 1d',
            ),
            'opt2' => 'option 2',
            'opt3' => array(
                'opt3a' => 'option 3a',
                'opt3b' => 'option 3b',
                'opt3c' => 'option 3c',
                'opt3d' => 'option 3d',
            ),
            'opt4' => 'option 4',
            'opt5' => 'option 5'
        );

        $configsetting = new admin_setting_configmultiselect('a', 'b', 'c', 'opt5', $options);
        $this->assertSame($configsetting->optgroups, array('opt1' => $options['opt1'], 'opt3' => $options['opt3']));
        $flatterned = $options['opt1'] + array('opt2' => 'option 2') + $options['opt3'] + array('opt4' => 'option 4', 'opt5' => 'option 5');
        $this->assertSame($configsetting->choices, $flatterned);

        // Test numeric keys
        $options = array(
            1 => array(
                2 => 'option 1a',
                3 => 'option 1b',
                4 => 'option 1c',
                5 => 'option 1d',
            ),
            6 => 'option 2',
            7 => array(
                8 => 'option 3a',
                9 => 'option 3b',
                10 => 'option 3c',
                11 => 'option 3d',
            ),
            12 => 'option 4',
            13 => 'option 5'
        );

        $configsetting = new admin_setting_configmultiselect('a', 'b', 'c', 6, $options);
        $this->assertSame($configsetting->optgroups, array(1 => $options[1], 7 => $options[7]));
        $flatterned = $options[1] + array(6 => 'option 2') + $options[7] + array(12 => 'option 4', 13 => 'option 5');
        $this->assertSame($configsetting->choices, $flatterned);
    }
}