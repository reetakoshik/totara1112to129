<?php
/*
 * This file is part of Totara LMS
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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\signup;

use mod_facetoface\signup\state\state;
use mod_facetoface\signup\condition\condition;
use mod_facetoface\signup\restriction\restriction;
use \stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * This class is used to define state transitions options
 */
final class transition {
    /**
     * @var state
     */
    private $to = null;

    /**
     * All conditions must met to perform state transition.
     * @var string[] conditions
     */
    private $conditions = [];

    /**
     * The user performing the state transition must meet these restrictions.
     * @var string[] restrictions
     */
    private $restrictions = [];

    /**
     * Transition must be created using tansition::to() factory function
     */
    private function __construct(state $state) {
        $this->to = $state;
    }

    /**
     * Create new transition to state
     * @param state $state
     * @return transition
     */
    public static function to(state $state) : transition {

        return new transition($state);
    }

    /**
     * Add conditions that requred to be met for state transition
     * Conditions are lazy-initialised when they needed, because most of them will not be needed
     * @param $conditions string[] condition classes
     */
    public function with_conditions(string ...$conditions) : transition {
        $this->conditions = array_merge($this->conditions, $conditions);
        return $this;
    }

    /**
     * Add restrictions on the users that can perform the transition
     * Restrictions are lazy-initialised when they needed, because most of them will not be needed
     * @param $restrictions all restrictions
     */
    public function with_restrictions(string ...$restrictions) : transition {
        $this->restrictions = array_merge($this->restrictions, $restrictions);
        return $this;
    }

    /**
     * Check if all signup conditions are passing
     * @param \stdClass $actor User who performs the transition (it can be admin approver, signed up user, etc)
     * @return bool
     */
    public function possible(\stdClass $actor = null) : bool {
        global $USER;
        if (empty($actor)) {
            $actor = $USER;
        }
        foreach ($this->conditions as $conditionclass) {
            /**
             * @var condition $condition
             */
            $condition = new $conditionclass($this->to->get_signup());
            if (!$condition->pass()) {
                return false;
            }
        }
        foreach ($this->restrictions as $restrictionclass) {
            /**
             * @var restriction $restriction
             */
            $restriction = new $restrictionclass($this->to->get_signup(), $actor);
            if (!$restriction->pass()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Traverse all conditions and restrictions and tell if they pass or not
     * @return string[]
     */
    public function debug_conditions() : array {
        $results = ['conditions' => [], 'restrictions' => []];

        foreach ($this->conditions as $conditionclass) {
            $condition = new $conditionclass($this->to->get_signup());
            if (isset($results['conditions'][$conditionclass])) {
                $results['conditions'][] = "Duplicate of $conditionclass in one transition";
            }
            if ($condition->pass($this->to->get_signup())) {
                $results['conditions'][$conditionclass] = 'PASS';

            } else {
                $failure = $condition->get_failure($this->to->get_signup());
                if (empty($failure)) {
                    $failure = 'FAIL';
                }
                $results['conditions'][$conditionclass] = $failure;
            }
        }

        foreach ($this->restrictions as $restrictionclass) {
            /**
             * @var restriction $restriction
             */
            $restriction = new $restrictionclass($this->to->get_signup());
            if (isset($results['restrictions'][$restrictionclass])) {
                $results['restrictions'][] = "Duplicate of $restrictionclass in one transition";
            }
            if ($restriction->pass($this->to->get_signup())) {
                $results['restrictions'][$restrictionclass] = 'PASS';

            } else {
                $failure = $restriction->get_failure($this->to->get_signup());
                if (empty($failure)) {
                    $failure = 'FAIL';
                }
                $results['restrictions'][$restrictionclass] = $failure;
            }
        }

        return $results;
    }

    /**
     * This function will return the reason strings for any failing conditions or restrictions
     * @return string[]
     */
    public function get_failures() : array {
        $failures = [];
        foreach ($this->conditions as $conditionclass) {
            /**
             * @var condition $condition
             */
            $condition = new $conditionclass($this->to->get_signup());
            if (!$condition->pass($this->to->get_signup())) {
                $failures = array_merge($failures, $condition->get_failure($this->to->get_signup()));
            }
        }

        foreach ($this->restrictions as $restrictionclass) {
            /**
             * @var restriction $restriction
             */
            $restriction = new $restrictionclass($this->to->get_signup());
            if (!$restriction->pass($this->to->get_signup())) {
                $failures = array_merge($failures, $restriction->get_failure($this->to->get_signup()));
            }
        }

        return $failures;
    }

    /**
     * Get target signup state
     * @return state
     */
    public function get_to() {
        return $this->to;

    }
}
