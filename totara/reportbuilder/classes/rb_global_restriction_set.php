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
 * @author petr Skoda <petr.skoda@totaralms.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/classes/rb_global_restriction.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib/assign/lib.php');

/**
 * Class rb_global_restriction_set describes the set of restrictions selected for selected report report.
 *
 * @author petr Skoda <petr.skoda@totaralms.com>
 * @package totara_reportbuilder
 */
class rb_global_restriction_set implements Iterator {
    /** @var rb_global_restriction[] $restrictions list of active restrictions */
    protected $restrictions;

    /** Return no records for users without active restrictions. */
    const NO_ACTIVE_NONE = 0;

    /** Return all records for users without active restrictions. */
    const NO_ACTIVE_ALL = 1;

    /**
     * Constructor.
     *
     * @throws coding_exception if an invalid restriction has been provided.
     *
     * @param array $globalrestrictions of stdClass|rb_global_restriction records
     */
    protected function __construct(array $globalrestrictions) {
        $this->restrictions = array();
        foreach ($globalrestrictions as $restriction) {
            if (is_object($restriction) && isset($restriction->name) && isset($restriction->id)) {
                $this->restrictions[$restriction->id] = $restriction;
            } else {
                throw new coding_exception("Incorrect restriction provided");
            }
        }
    }

    /**
     * Create restriction set from page parameters.
     * This is intended for embedded and user report pages only.
     *
     * This method updates $SESSION->rb_global_restriction
     * and processes 'globalrestrictionids' page parameter.
     *
     * @param stdClass|false $reportrecord
     * @return rb_global_restriction_set or null if not active
     */
    public static function create_from_page_parameters($reportrecord) {
        global $CFG, $USER, $SESSION, $PAGE;

        if (empty($reportrecord)) {
            // Not a valid report record, its very likely this has occurred on an uninitialised embedded report.
            // This situation is acceptable, global restrictions cannot be applied here at this point but the code
            // will initialise the report shortly and they will work on the next request.
            return null;
        }

        if (empty($CFG->enableglobalrestrictions) or $reportrecord->globalrestriction == reportbuilder::GLOBAL_REPORT_RESTRICTIONS_DISABLED) {
            // Restrictions are disabled.
            return null;
        }

        $available = self::get_user_all_restrictions($USER->id, false);
        if (!$available) {
            $SESSION->rb_global_restriction = array();
            if (get_config('reportbuilder', 'noactiverestrictionsbehaviour') == self::NO_ACTIVE_ALL) {
                // Show all.
                return null;
            } else {
                // Show none.
                return new self(array());
            }
        }

        $forceredirect = false;
        $selectedids = null;
        $globalrestrictionids = optional_param('globalrestrictionids', null, PARAM_SEQUENCE);
        if ($globalrestrictionids === null) {
            if (isset($SESSION->rb_global_restriction)) {
                $selectedids = $SESSION->rb_global_restriction;
            }
        } else {
            require_sesskey();
            $forceredirect = true;
            $selectedids = ($globalrestrictionids === '' ? array() : explode(',', $globalrestrictionids));
        }

        // Find selected.
        $filtered = array();
        if ($selectedids) {
            foreach ($selectedids as $id) {
                if (isset($available[$id])) {
                    $filtered[$id] = $available[$id];
                }
            }
        }

        if (!$filtered) {
            // Pick first if not found.
            $first = reset($available);
            $filtered[$first->id] = $first;
        }

        // Remember selection, the UI needs this.
        $SESSION->rb_global_restriction = array_keys($filtered);

        // Make sure we redirect after user action,
        // unless we are running PHPUnit tests.
        if (!PHPUNIT_TEST and $forceredirect) {
            redirect($PAGE->url);
        }

        foreach ($filtered as $restriction) {
            if ($restriction->allrecords) {
                // All records because at least one restriction gives full access to all records.
                return null;
            }
        }

        return new self($filtered);
    }

    /**
     * Factory method.
     *
     * Create set from given restriction ids, no guessing is involved.
     * User assignments are not verified.
     *
     * @param stdClass $reportrecord
     * @param int[] $globalrestrictionids
     * @return rb_global_restriction_set or null if not active
     */
    public static function create_from_ids($reportrecord, array $globalrestrictionids = null) {
        global $CFG, $DB;

        if (empty($CFG->enableglobalrestrictions) or $reportrecord->globalrestriction == reportbuilder::GLOBAL_REPORT_RESTRICTIONS_DISABLED) {
            // Restrictions are disabled.
            return null;
        }

        if ($globalrestrictionids === null) {
            // We do not know the current user here, we should not guess anything here, let's play it safe.
            if (get_config('reportbuilder', 'noactiverestrictionsbehaviour') == self::NO_ACTIVE_ALL) {
                // All records.
                return null;
            } else {
                // No records.
                return new self(array());
            }
        }

        if (!$globalrestrictionids) {
            // No records.
            return new self(array());
        }

        list($sql, $params) = $DB->get_in_or_equal($globalrestrictionids);
        $selected = $DB->get_records_sql('SELECT * FROM {report_builder_global_restriction} WHERE id ' . $sql, $params);

        foreach ($selected as $restriction) {
            if ($restriction->allrecords) {
                // All records because at least one restriction gives full access to all records.
                return null;
            }
        }

        return new self($selected);
    }

    /**
     * Returns list of current restriction ids.
     * @return int[]
     */
    public function get_current_restriction_ids() {
        return array_keys($this->restrictions);
    }

    /**
     * Returns the query to be used for global report restriction joins.
     *
     * @return array (string, array) sql statement to be used in joins
     */
    public function get_join_query() {
        global $DB;

        $assignsqls = array();
        $params = array();

        if (!$this->restrictions) {
            // No data should be returned ever, all settings hackery needs to be
            // done in create_from_page_parameters() or friends.
            if ($DB->get_dbfamily() === 'mysql') {
                $noresultsql = "SELECT -1 AS id FROM DUAL WHERE 1=2";
            } else {
                $noresultsql = "SELECT -1 AS id WHERE 1=2";
            }
            return array($noresultsql, array());
        }

        // For all selected restrictions.
        foreach ($this->restrictions as $restriction) {
            $assignment = new totara_assign_reportbuilder_record('reportbuilder', $restriction);

            // Get all assigned users SQL to assigned records to view.
            $join = $assignment->get_users_from_groups_sql('u', 'id');
            $partsql = "SELECT id FROM {user} u {$join[0]}";
            $joinnamed = self::convert_qm_named($partsql, $join[1], 'grr'.$restriction->id);
            $assignsqls[] = $joinnamed[0];
            $params = array_merge($params, $joinnamed[1]);
        }

        $sql = implode ("\n UNION \n", $assignsqls);
        return array($sql, $params);
    }

    /**
     * Convert question mark params to named params
     *
     * @throws coding_exception If the number of params does not match the number of qm uses.
     *
     * @param string $sql
     * @param array $params
     * @param string $prefix
     * @return array($sql, $params) converted
     */
    public static function convert_qm_named($sql, array $params = null, $prefix='conv') {
        global $DB;
        $params = (array)$params;
        $newparams = array();
        $pos = strpos($sql, '?');
        while ($pos !== false) {
            if (!$params) {
                throw new coding_exception("Cannot convert SQL parameters from QM to named");
            }
            $unique = $DB->get_unique_param($prefix);
            $value = array_shift($params);
            $sql = substr_replace($sql, ':' . $unique, $pos, 1);
            $newparams[$unique] = $value;
            $pos = strpos($sql, '?');
        }
        if ($params) {
            throw new coding_exception("Cannot convert SQL parameters from QM to named");
        }
        return array($sql, $newparams);
    }

    /**
     * Return list of restrictions available to user.
     *
     * If user does'nt have restrictions (if user is not in any "Restricted users" list) then empty array will be returned
     *
     * @param int $userid user to restrict id, 0 means not logged in, null means use $USER->id
     * @param bool $nocache Do not use cache (cache is static variable)
     * @return stdClass[] List of available restrictions ids as (id => stdClass record of report_builder_global_restriction)
     */
    public static function get_user_all_restrictions($userid, $nocache = false) {
        global $DB, $USER;
        static $cache = array();

        if ($userid === null) {
            $userid = $USER->id;
        }

        if (!$userid) {
            // Not-logged-in.
            return array();
        }

        if (!$nocache && isset($cache[$userid])) {
            return $cache[$userid];
        }

        // Get all active restrictions.
        $restrictions = $DB->get_records('report_builder_global_restriction', array('active' => 1), 'sortorder ASC');

        if (count($restrictions) === 0) {
            // There are no active global restrictions presently, this makes it easy.
            $cache[$userid] = array();
            return array();
        }

        // Unordered list of restriction ids allowed for user.
        $userrestrictions = array();

        // Make union SQL for each restriction.
        $assignsqls = array();
        $params = array();

        foreach ($restrictions as $restriction) {
            if ($restriction->allusers) {
                // Anybody can use this restriction.
                $userrestrictions[$restriction->id] = $restriction->id;
                continue;
            }

            // Get all assigned users SQL and restrict it by only current user.
            $assignment = new totara_assign_reportbuilder_user('reportbuilder', $restriction);
            $join = $assignment->get_users_from_groups_sql('u', 'id');
            $partsql = "SELECT {$restriction->id} AS restrictionid FROM {user} u {$join[0]} WHERE u.id = ?";
            $assignsqls[] = $partsql;
            $params = array_merge($params, $join[1], array($userid));
        }

        if (count($assignsqls) !== 0) {
            // We need to load the restriction sets for users so that we only include restrictions sets that
            // can be used bu this user.
            $sql = implode ("\n UNION \n", $assignsqls);
            $res = $DB->get_records_sql($sql, $params);
            foreach ($res as $r) {
                $userrestrictions[$r->restrictionid] = $r->restrictionid;
            }
        }

        // Create result restriction array in the expected order.
        $result = array();
        foreach ($restrictions as $restriction) {
            if (isset($userrestrictions[$restriction->id])) {
                $result[$restriction->id] = $restriction;
            }
        }

        $cache[$userid] = $result;
        return $result;
    }

    /**
     * Return list of restrictions ids available to user.
     *
     * If user does'nt have restrictions (if user is not in any "Restricted users" list) then empty array will be returned
     *
     * @param int $userid user to restrict id, 0 means not logged in, null means use $USER->id
     * @param bool $nocache Do not use cache (cache is static variable)
     * @return stdClass[]|null  List of available restrictions ids as (id, id, id) or null if user has no restrictions.
     */
    public static function get_user_all_restrictions_ids($userid, $nocache = false) {
        $restrs = self::get_user_all_restrictions($userid, $nocache);
        if (empty($restrs)) {
            return null;
        }
        return array_keys($restrs);
    }

    /**
     * Iterator interface implementation
     */
    public function current () {
        return current($this->restrictions);
    }

    /**
     * Iterator interface implementation
     */
    public function key () {
        return key($this->restrictions);
    }

    /**
     * Iterator interface implementation
     */
    public function next () {
        next($this->restrictions);
    }

    /**
     * Iterator interface implementation
     */
    public function rewind () {
        reset($this->restrictions);
    }

    /**
     * Iterator interface implementation
     */
    public function valid() {
        return !is_null(key($this->restrictions));
    }
}

