<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author Andrew Bell <andrewb@learningpool.com>
 * @author Ryan Lynch <ryanlynch@learningpool.com>
 * @author Barry McKay <barry@learningpool.com>
 *
 * @package auth_approved
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');


final class auth_approved_setting_domainwhitelist extends admin_setting_configtextarea {
    public function __construct($name) {
        parent::__construct(
            $name,
            new lang_string('domainwhitelist', 'auth_approved'),
            new lang_string('domainwhitelist_desc', 'auth_approved'),
            '', PARAM_RAW, 60, 3);
    }

    public function write_setting($data) {
        $data = \auth_approved\util::normalise_domain_list($data);
        return parent::write_setting($data);
    }
}
