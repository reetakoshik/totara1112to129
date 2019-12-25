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
 * @category  test
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/tests/behat/behat_data_generators.php');

class totara_core_behat_helper_generator extends behat_data_generators {
    public function protected_get($name, $data) {
        return $this->{'get_' . $name . '_id'}($data);
    }

    public function protected_preprocess($name, $data) {
        return $this->{'preprocess_' . $name}($data);
    }

    public function protected_process($name, $data) {
        return $this->{'process_' . $name}($data);
    }

    public function get_exists($name) {
        return method_exists($this, 'get_' . $name . '_id');
    }

    public function preprocess_exists($name) {
        return method_exists($this, 'preprocess_' . $name);
    }

    public function process_exists($name) {
        return method_exists($this, 'process_' . $name);
    }
}
