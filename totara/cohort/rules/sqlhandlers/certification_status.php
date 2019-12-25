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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_cohort
 */

/**
 * This file contains sqlhandler for rules based on certification status
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Handles rules for certification status.
 */
class cohort_rule_sqlhandler_certification_status extends cohort_rule_sqlhandler {

    // Statuses.
    const CERTIFIED         = 10;
    const EXPIRED           = 20;
    const NEVER_CERTIFIED   = 30;

    // Assignment statuses.
    const ASSIGNED          = 10;
    const UNASSIGNED        = 20;

    /**
     * @var array
     */
    public $params = [
        'status'            => '0',
        'assignmentstatus'  => '0',
        'listofids'         => '1'
    ];

    /**
     * @var int Count of certifications included.
     */
    public $certcount = 0;

    /**
     * Get array or certification statuses
     *
     * @return array
     */
    public static function statuses() {
        return [
            self::CERTIFIED         => 'ruledesc-learning-certificationstatus-currentlycertified',
            self::EXPIRED           => 'ruledesc-learning-certificationstatus-currentlyexpired',
            self::NEVER_CERTIFIED   => 'ruledesc-learning-certificationstatus-nevercertified'
        ];
    }

    /**
     * Get array or certification assignment statuses
     *
     * @return array
     */
    public static function assignment_statuses() {
        return [
            self::ASSIGNED    => 'ruledesc-learning-certificationstatus-assigned',
            self::UNASSIGNED  => 'ruledesc-learning-certificationstatus-unassigned'
        ];
    }

    /**
     * Get the status
     *
     * @param $status
     * @return bool|mixed
     */
    public static function get_status($status) {
       if (isset(self::statuses()[$status])) {
            return self::statuses()[$status];
       }
       return false;
    }

    /**
     * Get the assignment status
     *
     * @param $status
     * @return bool|mixed
     */
    public static function get_assignment_status($status) {
        if (isset(self::assignment_statuses()[$status])) {
            return self::assignment_statuses()[$status];
        }
        return false;
    }

    /**
     * Create the rule sql
     *
     * @return stdClass
     * @throws coding_exception
     */
    public function get_sql_snippet() {
        // Do some checks.
        if (empty($this->listofids) || empty($this->status) || empty($this->assignmentstatus)) {
            // We should never get here.
            throw new \coding_exception('Dynamic audience certification rule has missing parameters');
        }

        // Statuses array.
        $statuses = explode(',', $this->status);

        // Check all statuses are valid.
        array_walk($statuses, function($status){
            if (!self::get_status($status)) {
                // Status is invalid.
                throw new \coding_exception('Dynamic audience certification rule has invalid status');
            }
        });

        // Assignment statuses.
        $assignmentstatus = explode(',', $this->assignmentstatus);

        // Check all assignment statuses are valid.
        array_walk($assignmentstatus, function($status){
            if (!self::get_assignment_status($status)) {
                // Assignment status is invalid.
                throw new \coding_exception('Dynamic audience certification rule has invalid assignment status');
            }
        });

        // Set count of certifications included.
        $this->certcount = count($this->listofids);

        // Database params.
        $all_params = [];

        //
        // Statuses.
        //

        // If all statuses are selected.
        if (count($statuses) == count(self::statuses())) {
            $status_sql = "1=1";
        } else {
            $sql_chunks = [];

            // Currently certified.
            if (in_array(self::CERTIFIED, $statuses)) {
                list($sql, $params) = $this->get_sql_snippet_certified();
                $sql_chunks[] = "({$sql})";
                $all_params = array_merge($all_params, $params);
            }

            // Currently expired.
            if (in_array(self::EXPIRED, $statuses)) {
                list($sql, $params) = $this->get_sql_snippet_expired();
                $sql_chunks[] = "({$sql})";
                $all_params = array_merge($all_params, $params);
            }

            // Never certified.
            if (in_array(self::NEVER_CERTIFIED, $statuses)) {
                list($sql, $params) = $this->get_sql_snippet_never_certified();
                $sql_chunks[] = "({$sql})";
                $all_params = array_merge($all_params, $params);
            }

            // Join together using OR.
            $status_sql = "(" . implode(' OR ', $sql_chunks) . ")";
        }

        //
        // Assignment statuses.
        //

        // If all statuses are selected.
        if (count($assignmentstatus) == count(self::assignment_statuses())) {
            $assignments_status_sql = "1=1";
        } else {
            $sql_chunks = [];

            // Assigned.
            if (in_array(self::ASSIGNED, $assignmentstatus)) {
                list($sql, $params) = $this->get_sql_snippet_assigned();
                $sql_chunks[] = "({$sql})";
                $all_params = array_merge($all_params, $params);
            }

            // Unassigned.
            if (in_array(self::UNASSIGNED, $assignmentstatus)) {
                list($sql, $params) = $this->get_sql_snippet_unassigned();
                $sql_chunks[] = "({$sql})";
                $all_params = array_merge($all_params, $params);
            }

            // Join together using OR.
            $assignments_status_sql = "(" . implode(' OR ', $sql_chunks) . ")";
        }

        // Create the $sqlhandler object.
        $sqlhandler = new stdClass();
        $sqlhandler->sql = " ({$status_sql}) AND ({$assignments_status_sql}) ";
        $sqlhandler->params = $all_params;


        // Example sqlhandler sql structure.
        //
        //  (
        //     (certified sql)
        //     OR
        //     (expired sql)
        //     OR
        //     (never certified sql)
        //  ) AND (
        //     (assigned sql)
        //     OR
        //     (unassigned sql)
        //  )

        return $sqlhandler;
    }

    /**
     * Get the certified sql snippet
     *
     * @param bool $iscertified Used to get certifified or not certified users
     * @param string $paramprefix The param prefix
     * @return array
     */
    public function get_sql_snippet_certified($iscertified = true, $paramprefix = 'cs_') {
        global $DB;

        $paramprefix = $paramprefix . $this->ruleid . '_';

        list($sqlin, $params) = $DB->get_in_or_equal($this->listofids, SQL_PARAMS_NAMED, $paramprefix);

        $operator = $iscertified ? '=' : '!=';

        $sql = "{$this->certcount} {$operator} (SELECT count(DISTINCT(p.id))
                          FROM {prog} p
                          JOIN {certif} c ON c.id = p.certifid
                     LEFT JOIN {certif_completion} cc ON cc.certifid = c.id
                     LEFT JOIN {certif_completion_history} cch ON cch.certifid = c.id
                         WHERE p.id {$sqlin}
                           AND (
                                (cc.userid = u.id AND cc.timecompleted != 0 AND cc.timeexpires > :{$paramprefix}timeexpires)
                                OR
                                (cch.userid = u.id AND cch.timecompleted != 0 AND cch.timeexpires > :{$paramprefix}timeexpireshistory)
                               )
                         )";

        $timenow = time();
        $params[$paramprefix . 'timeexpires'] = $timenow;
        $params[$paramprefix . 'timeexpireshistory'] = $timenow;

        return [$sql, $params];
    }

    /**
     * Get the expired sql snippet
     *
     * @return array
     */
    public function get_sql_snippet_expired() {
        global $DB;

        $paramprefix = 'ex_' . $this->ruleid . '_';

        list($sqlin, $params) = $DB->get_in_or_equal($this->listofids, SQL_PARAMS_NAMED, $paramprefix);

        $expired_sql = "{$this->certcount} = (SELECT count(DISTINCT(p.id))
                              FROM {prog} p
                              JOIN {certif} c ON c.id = p.certifid
                         LEFT JOIN {certif_completion} cc ON cc.certifid = c.id
                         LEFT JOIN {certif_completion_history} cch ON cch.certifid = c.id
                             WHERE p.id {$sqlin}
                               AND (
                                    (
                                        cc.userid = u.id
                                        AND (cc.status = " . CERTIFSTATUS_EXPIRED . "
                                        OR (cc.status = " . CERTIFSTATUS_INPROGRESS . " AND cc.renewalstatus = " . CERTIFRENEWALSTATUS_EXPIRED . "))
                                    )
                                    OR
                                    (
                                        cch.userid = u.id
                                        AND cch.timecompleted != 0
                                        AND cch.timeexpires < :{$paramprefix}timeexpireshistory
                                    )
                                   )
                         )";

        // Include in the sql users that are NOT currently certified.
        list($notcertified_sql, $params2) = $this->get_sql_snippet_certified(false, 'ex2_');
        $sql = $expired_sql . " AND " . $notcertified_sql;
        $params = array_merge($params, $params2);
        $params[$paramprefix . 'timeexpireshistory'] = time();

        return [$sql, $params];
    }

    /**
     * Get the never certified sql snippet
     *
     * @return array
     */
    public function get_sql_snippet_never_certified() {
        global $DB;

        list($sqlin, $params) = $DB->get_in_or_equal($this->listofids, SQL_PARAMS_NAMED, 'ns_'.$this->ruleid);

        $sql = "NOT EXISTS (SELECT 1
                              FROM {prog} p
                              JOIN {certif} c ON c.id = p.certifid
                         LEFT JOIN {certif_completion} cc ON cc.certifid = c.id
                         LEFT JOIN {certif_completion_history} cch ON cch.certifid = c.id
                             WHERE p.id {$sqlin}
                               AND (
                                    (cc.userid = u.id AND cc.timecompleted != 0)
                                    OR
                                    (cch.userid = u.id AND cch.timecompleted != 0)
                                   )
                         )";

        return [$sql, $params];
    }

    /**
     * Get the assigned sql snippet
     *
     * @return array
     */
    public function get_sql_snippet_assigned() {
        global $DB;

        list($sqlin, $params) = $DB->get_in_or_equal($this->listofids, SQL_PARAMS_NAMED, 'as_'.$this->ruleid);

        $sql = "{$this->certcount} = (SELECT count(DISTINCT(p.id))
                          FROM {prog_user_assignment} pua
                          JOIN {prog} p ON p.id = pua.programid
                         WHERE p.id {$sqlin}
                           AND pua.userid = u.id)";

        return [$sql, $params];
    }

    /**
     * Get the unassigned sql snippet
     *
     * @return array
     */
    public function get_sql_snippet_unassigned() {
        global $DB;

        list($sqlin, $params) = $DB->get_in_or_equal($this->listofids, SQL_PARAMS_NAMED, 'na_'.$this->ruleid);

        $sql = "NOT EXISTS (SELECT 1
                              FROM {prog_user_assignment} pua
                              JOIN {prog} p ON p.id = pua.programid
                             WHERE p.id {$sqlin}
                               AND pua.userid = u.id)";

        return [$sql, $params];
    }
}
