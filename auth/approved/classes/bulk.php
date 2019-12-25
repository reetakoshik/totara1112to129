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
 *
 * @package auth_approved
 */

namespace auth_approved;

/**
 * General bulk processing stuff.
 */
final class bulk {
    /**
     * Returns list of all bulk action classes.
     *
     * @return bulk\base[] List of actions in format (shortname => classname)
     */
    public static function get_all_actions() {
        $actions = array();
        /** @var bulk\base[] $classes */
        $classes = \core_component::get_namespace_classes('bulk', 'auth_approved\bulk\base', 'auth_approved');
        foreach ($classes as $class) {
            $actions[$class::get_name()] = $class;
        }
        // Allow partners to override standard and add more custom actions.
        $classes = \core_component::get_namespace_classes('bulk', 'auth_approved\bulk\base', 'local_approved');
        foreach ($classes as $class) {
            $actions[$class::get_name()] = $class;
        }
        return $actions;
    }

    /**
     * Returns a localised menu of bulk actions available for current user.
     *
     * @return string[] list of bulk actions (name => localised name)
     */
    public static function get_actions_menu() {
        $actions = array();
        $classes = self::get_all_actions();
        foreach ($classes as $name => $classname) {
            if (!$classname::is_available()) {
                continue;
            }
            $actions[$name] = $classname::get_fullname();
        }
        \core_collator::asort($actions);
        return $actions;
    }

    /**
     * Execute a bulk action.
     *
     * @codeCoverageIgnore Can't be tested as it redirects.
     * @throws \coding_exception If the action is not valid or available
     * @param string $bulkaction
     * @param \reportbuilder $report
     * @param int $bulktime the cut off time for actions, this excludes later modifications and new requests
     * @return void exits or redirects
     */
    public static function execute_action($bulkaction, \reportbuilder $report, $bulktime) {
        // This may take a long time, prevent interruptions!
        ignore_user_abort(true);
        \core_php_time_limit::raise(60*10);

        $actions = self::get_all_actions();
        if (!isset($actions[$bulkaction])) {
            throw new \coding_exception('Invalid bulk action');
        }

        /** @var bulk\base $classname */
        $classname = $actions[$bulkaction];
        if (!$classname::is_available()) {
            throw new \coding_exception('Invalid bulk action');
        }

        /** @var \auth_approved\bulk\base $action */
        $action = new $classname($report, $bulktime);
        $action->execute();

        // We should not get here, but if we do let's go back to the report.
        redirect($report->get_current_url());
    }
}