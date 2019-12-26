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

namespace mod_facetoface\signup\state;

use mod_facetoface\exception\signup_exception;
use mod_facetoface\signup as signup;
use mod_facetoface\signup\transition as transition;

defined('MOODLE_INTERNAL') || die();

/**
 * This class is used in booking class and responsible for definition of all booking states transitions
 */
abstract class state {
    /**
     * @var signup
     */
    protected $signup = null;

    /**
     * state constructor.
     * @param signup $signup Signup which this state belongs to
     */
    public function __construct(signup $signup) {
        $this->signup = $signup;
    }

    /**
     * Get signup which current state belongs to
     * @return signup
     */
    final public function get_signup() {
        return $this->signup;
    }

    /**
     * All signup state classes
     * return string[]
     */
    final public static function get_all_states() : array {
        $classes = \core_component::get_namespace_classes(
            'signup\state',
            'mod_facetoface\signup\state\state',
            'mod_facetoface'
        );
        return $classes;
    }

    /**
     * Get state class from code
     * @return string[]
     */
    final public static function from_code(int $code) : string {
        $allstates = self::get_all_states();
        foreach($allstates as $stateclass) {
            if ($stateclass::get_code() === $code) {
                return $stateclass;
            }
        }
        throw new signup_exception("Cannot find booking state with code: $code");
    }

    /**
     * Assess possbility to switch to any of the given classes
     * @param string[] ...$desiredstates
     * @return bool
     */
    final public function can_switch(string ...$desiredstates) : bool {
        try {
            $this->switch_to(...$desiredstates);
            return true;
        }
        catch (signup_exception $e) {
            return false;
        }
    }

    /**
     * Change current state to one of listed states if possible. State must be listed in order of preference.
     *
     * @param string ...$desiredstateclasses target state.
     * @return state first possible of $desiredstates
     * @throws signup_exception If not possbile to move
     */
    final public function switch_to(string ...$desiredstateclasses) : state {
        $desiredstateclasses = self::validate_state_classes($desiredstateclasses);

        // Iteratively search for desired state
        $map = $this->get_map();
        foreach ($desiredstateclasses as $desiredstateclass) {
            foreach ($map as $transition) {
                $actor = $this->signup->get_actor();
                /**
                 * @var transition $transition
                 */
                if ($transition->get_to() instanceof $desiredstateclass && $transition->possible($actor)) {
                    return $transition->get_to();
                }
            }
        }
        $fromclassname = get_class($this);
        throw new signup_exception("Cannot move from {$fromclassname} to any of requested states");
    }

    /**
     * Display an error message if one or more $desiredstateclasses are not found.
     *
     * @param string[] $desiredstateclasses array of state classes to check
     * @return string[] array of valid state classes ready to use
     */
    final public static function validate_state_classes(array $desiredstateclasses): array {
        $validstateclasses = [];
        
        // Ensure that all $desiredstateclasses are correctly imported
        foreach ($desiredstateclasses as $desiredstateclass) {
            if (class_exists($desiredstateclass)) {
                $validstateclasses[] = $desiredstateclass;
            } else {
                debugging("A desired state class '$desiredstateclass' does not exist.");
            }
        }

        return $validstateclasses;
    }

    /**
     * Get action label for getting into state.
     * E.g. "Join waitlist" for waitlisted
     * @return string
     */
    public function get_action_label() : string {
        return '';
    }

    /**
     * Get title of state
     *
     * @return string
     */
    public static function get_string() : string {
        return '';
    }

    /**
     * Is there any further transitions at all?
     * @return bool
     */
    public function is_final() : bool {
        return empty($this->get_map());
    }

    /**
     * Is current state means that signup either cancelled or declined only.
     * Note: if state means awaiting decision (like requested or requestedadmin) it doesn't mean that it is not happening.
     * @return bool
     */
    public function is_not_happening() : bool {
       // Override only for states that definitely mean signup is not happening.
        return false;
    }

    /**
     * Callback called on event when signup has switched to current state.
     */
    public function on_enter() {
        // Override if required.
    }

    /**
     * Message for user on entering the state
     * @return string
     */
    abstract public function get_message() : string;

    /**
     * Get the grade value associated with the state.
     * Override for graded states - no show, partially attended, fully attended.
     * @return int|null Graded states should return int. Non-graded states should return null.
     */
    public static function get_grade() : ?int {
        return null;
    }

    /**
     * Get conditions and validations of transitions from current state
     */
    abstract public function get_map() : array;

    /**
     * Code of status as it is stored in DB
     * Numeric statuses are backward compatible except not_set which was not meant to be written into DB.
     * Statuses don't have to follow particular order (except must be unique of course)
     */
    abstract public static function get_code() : int;
}
