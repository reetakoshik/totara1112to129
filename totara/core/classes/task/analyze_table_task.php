<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\task;

use totara_core\access;

defined('MOODLE_INTERNAL') || die();

/**
 * Perform ANALYZE TABLE query to context and context_map.
 */
class analyze_table_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to administrators).
     *
     * @return string
     */
    public function get_name() {
        return get_string('analyzetabletask', 'totara_core');
    }

    /**
     * Perform ANALYZE TABLE query to context and context_map.
     */
    public function execute() {
        list($afterbuild, $countthreshold) = access::get_analyze_context_table_configs();
        if ($afterbuild) {
            mtrace("... already analyzed");
            return;
        }
        // context
        $starttime = microtime();
        access::analyze_table('context');
        $difftime = microtime_diff($starttime, microtime());
        mtrace("... context used {$difftime} seconds");
        // context_map
        $starttime = microtime();
        access::analyze_table('context_map');
        $difftime = microtime_diff($starttime, microtime());
        mtrace("... context_map used {$difftime} seconds");
    }
}
