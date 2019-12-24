<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @package auth_approved
 */

namespace auth_approved\bulk;

defined('MOODLE_INTERNAL') || die();

/**
 * Base class for all bulk actions on pending requests.
 */
abstract class base {
    /** @var \reportbuilder */
    protected $report;

    /** @var int cut off time for request processing */
    protected $bulktime;

    /**
     * Bulk action.
     *
     * @param \reportbuilder $report
     * @param int $bulktime timestamp
     */
    final public function __construct(\reportbuilder $report, $bulktime) {
        $this->report = $report;
        $this->bulktime = $bulktime;
    }

    /**
     * Returns list of pending request ids for this bulk action.
     *
     * @return array
     */
    public function get_request_ids() {
        global $DB;

        list($sql, $params, $cache) = $this->report->build_query(false, true, false);

        $rs = $DB->get_recordset_sql($sql, $params);

        $requestids = array();
        foreach ($rs as $record) {
            if ($record->bulk_status != \auth_approved\request::STATUS_PENDING) {
                // Bulk actions are for pending requests only!
                continue;
            }
            if ($record->bulk_timemodified > $this->bulktime) {
                // Something changed, so better ignore this request in bulk action.
                continue;
            }
            $requestids[$record->bulk_id] = $record->bulk_id;
        }

        return $requestids;
    }

    /**
     * Returns the return url back to the report that initiated this action.
     *
     * @return \moodle_url
     */
    public function get_return_url() {
        return new \moodle_url($this->report->get_current_url());
    }

    /**
     * Execute action.
     *
     * NOTE: this method is not supposed to return,
     *       so either render some form or redirect.
     *
     * @return void
     */
    public abstract function execute();

    /**
     * Returns short action name.
     *
     * @return string
     */
    public static function get_component() {
        $classname = get_called_class();
        $parts = explode('\\', $classname);
        $component = reset($parts);
        return $component;
    }

    /**
     * Returns short action name.
     *
     * @return string
     */
    public static function get_name() {
        $classname = get_called_class();
        $parts = explode('\\', $classname);
        $name = array_pop($parts);
        return $name;
    }

    /**
     * Returns human readable localised bulk action name.
     *
     * @return string
     */
    public static function get_fullname() {
        return get_string('bulkaction' . static::get_name(), static::get_component());
    }

    /**
     * Is this action available for current user?
     *
     * @return bool
     */
    public static function is_available() {
        return has_capability('auth/approved:approve', \context_system::instance());
    }
}
