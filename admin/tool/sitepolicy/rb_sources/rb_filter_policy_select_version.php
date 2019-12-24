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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package tool_sitepolicy
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/totara/reportbuilder/filters/select.php');

/**
 * Class rb_filter_policy_select_version
 *
 * This filter designed to work exclusively(!) in conjunction with rb_source_tool_sitepolicy.
 * It depends on the report having certain joins and cached columns.
 *
 * Due to peculiarities of the way filter options are instantiated we can not override several options in the
 * filter constructor, thus it is necessary to supply filter-options array with selectchoices => [],
 * failing to do so will generate an invalid debugging notice as selectchoices are populated directly in the filter.
 *
 * Please see the valid filter definition below:
 *
 * new rb_filter_option(
 * 'primarypolicy',
 *  'versionnumber',
 *   get_string('policyversion', 'rb_source_tool_sitepolicy'),
 *  'policy_select_version', [
 *      'selectchoices' => [],
 *   ]
 * ),
 */
class rb_filter_policy_select_version extends rb_filter_select {
    public function __construct($type, $value, $advanced, $region, $report, $defaultvalue) {
        parent::__construct($type, $value, $advanced, $region, $report, $defaultvalue);

        $this->options['simplemode'] = true;
        $this->options['attributes'] = []; // Attributes not supported
        $this->options['selectchoices'] = $this->get_options();
        $this->options['help'] = [
            'filter_version',
            'rb_source_tool_sitepolicy'
        ];
    }

    /**
     * @inheritdoc
     */
    function get_sql_filter($data) {
        global $DB;

        $value = explode(',', $data['value']);
        $query = $this->get_field();

        // Handle 'special cases':
        if (count($value) == 1) {
            switch ($value[0]) {

                // Any value is selected => ignoring the filter
                case '':
                    // return 1=1 instead of TRUE for MSSQL support
                    return [' 1=1 ', []];

                // Current version is selected, we need to substitute standard SQL query to our custom to
                // filter only current versions of the policy.
                case '0':
                    // Report caching support
                    $sql = $this->report->is_cached() ?
                        "primarypolicy_status = 'published'" :
                        "policyversion.timepublished IS NOT NULL AND policyversion.timearchived IS NULL";

                    return [$sql, []];
            }

        }

        // Standard get in or equal filter
        [$sql, $params] = $DB->get_in_or_equal($value, SQL_PARAMS_NAMED, rb_unique_param('fsequal_'));
        return ["{$query} {$sql}", $params];
    }

    /**
     * Get select options for the filter
     *
     * @return array
     */
    protected function get_options() {
        global $DB;

        // Get max version policy number or 1
        $max = $DB->get_field_sql('SELECT MAX(versionnumber) FROM {tool_sitepolicy_policy_version}
                                        WHERE timepublished IS NOT NULL') ?: 1;

        // Take care of
        $options = [
            '' => get_string('anyvalue', 'filters'),
            0 => get_string('policycurrentversion', 'rb_source_tool_sitepolicy'),
        ];

        // We need keys matching values.
        return array_merge($options, array_combine($range = range(1, $max), $range));
    }
}