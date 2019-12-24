<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @package totara_message
 */

namespace totara_message\task;

/**
 * Remove orphaned message meta data used for tasks and alerts.
 */
class cleanup_messages_task extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('cleanupmessagestask', 'totara_message');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $DB;

        // Tidy up orphaned metadata records - shouldn't be any - but odd things could happen with core messages cron.
        $sql = "SELECT mm.id
                FROM {message_metadata} mm
                LEFT JOIN {message} m ON mm.messageid = m.id
                LEFT JOIN {message_read} mr ON mm.messagereadid = mr.id
                WHERE m.id IS NULL AND mr.id IS NULL";
        $allidstodelete = $DB->get_fieldset_sql($sql);

        if (!empty($allidstodelete)) {
            // We may have really large numbers so split it up into smaller batches.
            $batchidstodelete = array_chunk($allidstodelete, 25000);

            foreach ($batchidstodelete as $idstodelete) {
                list($insql, $params) = $DB->get_in_or_equal($idstodelete);
                $sql = "DELETE
                        FROM {message_metadata}
                        WHERE id {$insql}";
                $DB->execute($sql, $params);
            }
        }
    }
}

