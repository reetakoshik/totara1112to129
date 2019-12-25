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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package totara_job
*/

namespace totara_job\event;

use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when user job assignment is deleted.
 */
class job_assignment_deleted extends \core\event\base {

    /**
     * Create instance of event.
     *
     * @param job_assignment $jobassignment Job assignment object.
     * @param \context $context
     */
    public static function create_from_instance(job_assignment $jobassignment, \context $context) {

        $data = [
            'objectid'      => $jobassignment->id,
            'context'       => $context,
            'relateduserid' => $jobassignment->userid,
        ];

        return self::create($data);
    }

    /**
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'job_assignment';
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised event name.
     */
    public static function get_name() {
        return get_string('eventjobassignmentdeleted', 'totara_job');
    }

    /**
     * Returns description of what happened.
     */
    public function get_description() {
        return get_string('eventjobassignmentdeleted', 'totara_job');
    }

    /**
     * Returns url to job assignment.
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/totara/job/jobassignment.php', ['id' => $this->objectid]);
    }
}