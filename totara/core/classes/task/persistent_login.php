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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\task;

class persistent_login extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('persistentlogintask', 'totara_core');
    }

    public function execute() {
        global $CFG;

        if (empty($CFG->persistentloginenable)) {
            mtrace("Persistent login is disabled");
            return;
        }

        mtrace("Deleting expired persistent logins:");
        \totara_core\persistent_login::gc();
        mtrace("done");
    }
}