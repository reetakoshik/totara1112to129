<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 */
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;


/**
 * Definitions for handling auth approved plugin configuration.
 */
final class behat_auth_approved_config extends \behat_base{
    /**
     * Convenience function to loop through a Behat table and process each row
     * with a user defined function.
     *
     * @param Behat\Gherkin\Node\TableNode $raw input Behat table.
     * @param callable $handler function to execute on each row of the table.
     *        This takes the associative array holding row values and returns
     *        the steps to handle that row.
     *
     * @return array<array<string>> steps to handle rows in the input table.
     */
    private function parse_table(TableNode $raw, callable $handler) {
        return array_map(
            function (array $row) use ($handler) {
                return $handler($row);
            },

            $raw->getHash()
        );
    }

    /**
     * Converts a Behat "field" vs "value" table into an associative array. The
     * Behat table should be in this format:
     * And I ..:
     *   | field 1 | value 1 |
     *   | field 2 | value 2 |
     *   ...
     *
     * @param Behat\Gherkin\Node\TableNode $raw Behat table to convert.
     * @param array<array<string, mixed>> fields a list of (field name, default
     *        value) tuples to use when extracting the field values from the
     *        Behat table passed in.
     *
     * @return array<string=>mixed> the values.
     */
    private function parse_field_values(TableNode $raw, array $fields) {
        $values = array_reduce(
            $raw->getRows(),

            function (array $accumulated, array $row) {
                $no_of_cols = count($row);
                if ($no_of_cols !== 2) {
                    throw new ExpectationException(
                        "Expected 2 column Behat table; got $no_of_cols instead"
                    );
                }

                $name = $row[0];
                $accumulated[$name] = $row[1];
                return $accumulated;
            },

            []
        );

        return array_reduce(
            $fields,

            function (array $accumulated, array $tuple) {
                list($field, $def_value) = $tuple;
                if (!isset($accumulated[$field])) {
                    $accumulated[$field] = $def_value;
                }

                return $accumulated;
            },

            $values
        );
    }

    /**
     * Configures the plugin directly in the backend.
     *
     * This expects a 2 column ("field" vs "value") table with the following
     * fields:
     * - 'active': [OPTIONAL] whether the plugin is activated; accepts anything
     *   that PHP takes as boolean eg "1", "off", false, etc.
     *
     * - 'instructions: [OPTIONAL] signup instructions.
     *
     * - 'whitelist': [OPTIONAL] string of comma separated email domains to use
     *   for auto approving a signup.
     *
     * - 'mgr freeform': [OPTIONAL] whether users can key in a freeform manager
     *   name. Accepts anything PHP takes as boolean.
     *
     * - 'mgr pos fw': [OPTIONAL] comma separated position framework idnumbers
     *   from which the allowed list of managers is selected.
     *
     * - 'mgr org fw': [OPTIONAL] comma separated organisation framework
     *   idnumbers from which the allowed list of managers is selected.
     *
     * - 'org freeform': [OPTIONAL] whether users can supply a freeform
     *   organization name. Accepts anything that PHP takes as boolean.
     *
     * - 'org fw': [OPTIONAL] comma separated organisation framework idnumbers
     *   from which applicants choose an organisation.
     *
     * - 'pos freeform': [OPTIONAL] whether users can supply a freeform
     *   position name. Accepts anything that PHP takes as boolean.
     *
     * - 'pos fw': [OPTIONAL] comma separated position framework idnumbers from
     *   which applicants choose a position.
     *
     * @Given /^I set these auth approval plugin settings:$/
     *
     * @param Behat\Gherkin\Node\TableNode $raw configuration values.
     *
     * @return array the list of Behat givens to execute.
     */
    public function i_set_these_auth_approval_plugin_settings(TableNode $raw) {
        \behat_hooks::set_step_readonly(false);
        $fields = [
            ['active', true],
            ['instructions', ''],
            ['whitelist', ''],
            ['mgr freeform', false],
            ['mgr pos fw', ''],
            ['mgr org fw', ''],
            ['org freeform', false],
            ['org fw', ''],
            ['pos freeform', false],
            ['pos fw', '']
        ];
        $values = $this->parse_field_values($raw, $fields);

        list(
            $mgrposidnums, $mgrorgidnums, $orgidnums, $posidnums, $whitelist
        ) = array_map(
            function ($field) use ($values) {
                return array_map(
                    function ($item) use ($values) {
                        return trim($item);
                    },

                    explode(',', $values[$field])
                );
            },

            ['mgr pos fw', 'mgr org fw', 'org fw', 'pos fw', 'whitelist']
        );

        $hierarchies = [
            ['org_framework', $orgidnums],
            ['pos_framework', $posidnums],
            ['org_framework', $mgrorgidnums],
            ['pos_framework', $mgrposidnums]
        ];
        list($org, $pos, $mgrorg, $mgrpos) = array_map(
            function (array $hierarchy) {
                list($table, $idnums) = $hierarchy;
                return $this->hierarchies_for($table, $idnums);
            },

            $hierarchies
        );

        list($enabled, $mgrfreeform, $orgfreeform, $posfreeform) = array_map(
            function ($field) use ($values) {
                return filter_var($values[$field],  FILTER_VALIDATE_BOOLEAN);
            },

            ['active', 'mgr freeform', 'org freeform', 'pos freeform']
        );

        $instructions = isset($values['instructions'])
                        ? $values['instructions'] : '';

        $config_values = [
            ['requireapproval', $enabled],
            ['allowexternaldefaults', true],
            ['domainwhitelist', implode(' ', $whitelist)],
            ['instructions', $instructions],

            ['requiremanager', $mgrfreeform || $mgrorg || $mgrpos],
            ['allowmanager', !empty($mgrorg) || !empty($mgrpos)],
            ['allowmanagerfreetext', $mgrfreeform],
            ['managerorganisationframeworks', empty($mgrorg) ? '-1' : $mgrorg],
            ['managerpositionframeworks', empty($mgrpos) ? '-1' : $mgrpos],

            ['requireorganisation', $orgfreeform || $org],
            ['alloworganisation', !empty($org)],
            ['alloworganisationfreetext', $orgfreeform],
            ['organisationframeworks', empty($org) ? '-1' : $org],

            ['requireposition', $posfreeform || $pos],
            ['allowposition', !empty($pos)],
            ['allowpositionfreetext', $posfreeform],
            ['positionframeworks', empty($pos) ? '-1' : $pos]
        ];

        array_map(
            function ($tuple) {
                list($field, $value) = $tuple;
                \set_config($field, $value, 'auth_approved');
            },

            $config_values
        );

        $this->set_as_registration_plugin($enabled);
        return [];
    }

    /**
     * Returns the hierarchy ids corresponding to the idnumbers passed in.
     *
     * @param string $table hierarchy table to look up.
     * @param array<string> $idnumbers list of idnumbers to look up.
     *
     * @return string as string of comma separated ids or null if they do not
     *         exist.
     */
    private function hierarchies_for($table, array $idnumbers) {
        global $DB;
        if (empty($idnumbers)) {
            return null;
        }

        $ids = array_map(
            function (\stdClass $hierarchy) {
                return $hierarchy->id;
            },

            $DB->get_records_list($table, 'idnumber', $idnumbers)
        );

        return empty($ids) ? null : implode(',', $ids);
    }

    /**
     * Sets or unsets the approval plugin as the defacto registration plugin.
     *
     * @param bool $enabled true if the plugin is enabled.
     */
    private function set_as_registration_plugin($enabled) {
        global $CFG;
        $cfg = isset($CFG->auth) ? $CFG->auth : '';
        $plugins = explode(',', $cfg);

        $authapprovedplugin = 'approved';
        $active = array_diff($plugins, [$authapprovedplugin]);
        if ($enabled) {
            $active[] = $authapprovedplugin;
        }

        \set_config('auth', implode(',', $active));
        \set_config('registerauth', $enabled ? $authapprovedplugin : '');

        \core\session\manager::gc();
        \core_plugin_manager::reset_caches();
    }
}
