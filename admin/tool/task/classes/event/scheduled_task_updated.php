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
 * @package tool_task
 */

namespace tool_task\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when the scheduled task is updated.
 */
class scheduled_task_updated extends \core\event\base {

    /** @var bool flag for prevention of direct create() call */
    protected static $preventcreatecall = true;

    /**
     * Create instance of event.
     * @param \scheduled_task $task
     * @return scheduled_task_updated
     */
    public static function create_from_schedule($task) {
        $other = [
            'objectid' => get_class($task),
            'name' => $task->get_name()
        ];

        $data = [
            'context' => \context_system::instance(),
            'other' => $other
        ];

        self::$preventcreatecall = false;
        $event = self::create($data);
        self::$preventcreatecall = true;

        return $event;
    }

    /**
     * Init method.
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Return localised event name.
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventscheduledtaskupdated', 'tool_task');
    }

    /**
     * Returns description of what happened.
     * @return string
     */
    public function get_description(): string {

        $msg = sprintf(
            "The user with id '%s' updated a scheduled task: '%s'",
            $this->userid, $this->other['name']
        );
        return $msg;
    }

    /**
     * Returns relevant URL.
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/admin/tool/task/scheduledtasks.php', ['action' => 'edit', 'task' => $this->other['objectid']]);
    }

    /**
     * Custom validation.
     * @return void
     */
    protected function validate_data() {
        if (self::$preventcreatecall) {
            throw new \coding_exception('cannot call scheduled_task_updated::create() directly, use scheduled_task_updated::create_from_schedule() instead.');
        }

        parent::validate_data();
    }
}