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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\source;

use moodle_url, html_writer, stdClass, core_component, core_date, customfield_file, customfield_define_location;

defined('MOODLE_INTERNAL') || die();

/**
 * Stuff deprecated in rb_base_source in T12, to be deleted in T13.
 */
trait base_deprecated_t12_trait {
    /** @var array auxiliary map of hierarchies */
    public $hierarchymap = array();

    /** @var mixed used for method depreciation only */
    private $bc_trait_instance;

    /**
     * Returns instance of base with all traits,
     * this is intended for deprecated methods only.
     *
     * @internal
     *
     * @return \core_user\rb\source\report_trait|\totara_cohort\rb\source\report_trait|\core_course\rb\source\report_trait|\totara_reportbuilder\rb\source\report_trait|\totara_program\rb\source\program_trait|\totara_certification\rb\source\certification_trait|\core_course\rb\source\report_trait|\totara_job\rb\source\report_trait|\core_tag\rb\source\report_trait|\totara_cohort\rb\source\report_trait
     */
    private function get_bc_trait_instance() {
        if (isset($this->bc_trait_instance)) {
            return $this->bc_trait_instance;
        }

        $this->bc_trait_instance = new class extends \rb_base_source {
            use \core_course\rb\source\report_trait,
                \core_tag\rb\source\report_trait,
                \totara_cohort\rb\source\report_trait,
                \totara_certification\rb\source\certification_trait,
                \totara_job\rb\source\report_trait,
                \totara_reportbuilder\rb\source\report_trait,
                \totara_program\rb\source\program_trait;

            public function __construct() {
                // Do not call parent constructor intentionally
                // because validation should not be done here.
            }

            public function extract_addeduserjoins() {
                $reflector = new \ReflectionObject($this);
                $joins = $reflector->getProperty('addeduserjoins');
                $joins->setAccessible(true);
                return $joins->getValue($this);
            }
        };

        $this->add_finalisation_method('finalise_bc_trait_instance');

        return $this->bc_trait_instance;
    }

    /**
     * BC hack.
     * @internal
     */
    private function finalise_bc_trait_instance() {
        // We cannot finalise the trait directly, so let's hack around it for now here.
        $trait = $this->get_bc_trait_instance();
        $addeduserjoins = $trait->extract_addeduserjoins();
        foreach ($addeduserjoins as $join => $info) {
            if (empty($info['groupname'])) {
                continue;
            }
            $trait->add_core_user_customfield($this->joinlist, $this->columnoptions, $this->filteroptions, $join, $info['groupname'], $info['addtypetoheading'], empty($info['filters']));
        }
    }

    //
    //
    // Generic grouping methods for aggregation
    //
    //

    /**
     * Get list of grouped columns.
     *
     * @deprecated since Totara 12
     * @return array of group select column values that are grouped
     */
    public function get_grouped_column_options() {
        $grouped = array();
        foreach ($this->columnoptions as $option) {
            if ($option->grouping !== 'none') {
                $grouped[] = $option->type . '-' . $option->value;
            }
        }
        return $grouped;
    }

    /**
     * @deprecated since Totara 12
     */
    function rb_group_count($field) {
        debugging('Column option grouping is deprecated since Totara 12, use subqueries instead', DEBUG_DEVELOPER);
        return "COUNT($field)";
    }

    /**
     * @deprecated since Totara 12
     */
    function rb_group_unique_count($field) {
        debugging('Column option grouping is deprecated since Totara 12, use subqueries instead', DEBUG_DEVELOPER);
        return "COUNT(DISTINCT $field)";
    }

    /**
     * @deprecated since Totara 12
     */
    function rb_group_sum($field) {
        debugging('Column option grouping is deprecated since Totara 12, use subqueries instead', DEBUG_DEVELOPER);
        return "SUM($field)";
    }

    /**
     * @deprecated since Totara 12
     */
    function rb_group_average($field) {
        debugging('Column option grouping is deprecated since Totara 12, use subqueries instead', DEBUG_DEVELOPER);
        return "AVG($field)";
    }

    /**
     * @deprecated since Totara 12
     */
    function rb_group_max($field) {
        debugging('Column option grouping is deprecated since Totara 12, use subqueries instead', DEBUG_DEVELOPER);
        return "MAX($field)";
    }

    /**
     * @deprecated since Totara 12
     */
    function rb_group_min($field) {
        debugging('Column option grouping is deprecated since Totara 12, use subqueries instead', DEBUG_DEVELOPER);
        return "MIN($field)";
    }

    /**
     * @deprecated since Totara 12
     */
    function rb_group_stddev($field) {
        debugging('Column option grouping is deprecated since Totara 12, use subqueries instead', DEBUG_DEVELOPER);
        return "STDDEV($field)";
    }

    /**
     * can be used to 'fake' a percentage, if matching values return 1 and
     * all other values return 0 or null
     * @deprecated since Totara 12
     */
    function rb_group_percent($field) {
        debugging('Column option grouping is deprecated since Totara 12, use subqueries instead', DEBUG_DEVELOPER);
        global $DB;

        return $DB->sql_round("AVG($field*100.0)", 0);
    }

    /**
     * This function calls the databases native implementations of
     * group_concat where possible and requires an additional $orderby
     * variable. If you create another one you should add it to the
     * $sql_functions array() in the get_fields() function in the rb_columns class.
     *
     * @deprecated since Totara 12
     * @param string $field         The expression to use as the select
     * @param string $orderby       The comma deliminated fields to order by
     * @return string               The native sql for a group concat
     */
    function rb_group_sql_aggregate($field, $orderby) {
        debugging('Column option grouping is deprecated since Totara 12, use subqueries instead', DEBUG_DEVELOPER);
        global $DB;

        return $DB->sql_group_concat($field, $this->uniquedelimiter, $orderby);
    }

    /**
     * return list as single field, separated by commas
     * @deprecated since Totara 12
     */
    function rb_group_comma_list($field) {
        debugging('rb_group_comma_list is deprecated since Totara 12, use $DB->sql_group_concat() instead', DEBUG_DEVELOPER);
        global $DB;

        return $DB->sql_group_concat($field, ', ');
    }

    /**
     * Return list as single field, without a separator delimiter.
     * @deprecated since Totara 12
     */
    function rb_group_list_nodelimiter($field) {
        debugging('rb_group_list_nodelimiter is deprecated since Totara 12, use $DB->sql_group_concat() instead', DEBUG_DEVELOPER);
        global $DB;

        return $DB->sql_group_concat($field, '');
    }

    /**
     * return unique list items as single field, separated by commas
     * @deprecated since Totara 12
     */
    function rb_group_comma_list_unique($field) {
        debugging('rb_group_comma_list_unique is deprecated since Totara 12, use $DB->sql_group_concat_unique() instead', DEBUG_DEVELOPER);
        global $DB;

        return $DB->sql_group_concat_unique($field, ', ');
    }

    /**
     * return list as single field, one per line
     * @deprecated since Totara 12
     */
    function rb_group_list($field) {
        debugging('rb_group_list is deprecated since Totara 12, use $DB->sql_group_concat() instead', DEBUG_DEVELOPER);
        global $DB;

        return $DB->sql_group_concat($field, html_writer::empty_tag('br'));
    }

    /**
     * return unique list items as single field, one per line
     * @deprecated since Totara 12
     */
    function rb_group_list_unique($field) {
        debugging('rb_group_list_unique is deprecated since Totara 12, use $DB->sql_group_concat_unique() instead', DEBUG_DEVELOPER);
        global $DB;

        return $DB->sql_group_concat_unique($field, html_writer::empty_tag('br'));
    }

    /**
     * return list as single field, separated by a line with - on (in HTML)
     * @deprecated since Totara 12
     */
    function rb_group_list_dash($field) {
        debugging('rb_group_list_dash is deprecated since Totara 12, use $DB->sql_group_concat() instead', DEBUG_DEVELOPER);
        global $DB;

        return $DB->sql_group_concat($field, html_writer::empty_tag('br') . '-' . html_writer::empty_tag('br'));
    }

    //
    //
    // Generic column display methods
    //
    //

    /**
     * Create a link that when clicked will display additional information inserted in a box below the clicked row.
     *
     * @deprecated Since Totara 12.0
     * @param string $columnvalue the value to display in the column
     * @param string $expandname the name of the function (prepended with 'rb_expand_') that will generate the contents
     * @param array $params any parameters that the content generator needs
     * @param string|moodle_url $alternateurl url to link to in case js is not available
     * @param array $attributes
     * @return string
     */
    protected function create_expand_link($columnvalue, $expandname, $params, $alternateurl = '', $attributes = array()) {
        debugging('rb_base_source::create_expand_link has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        global $OUTPUT;

        // Serialize the data so that it can be passed as a single value.
        $paramstring = http_build_query($params, '', '&');

        $class_link = 'rb-display-expand-link ';
        if (array_key_exists('class', $attributes)) {
            $class_link .=  $attributes['class'];
        }

        $attributes['class'] = 'rb-display-expand';
        $attributes['data-name'] = $expandname;
        $attributes['data-param'] = $paramstring;
        $infoicon = $OUTPUT->flex_icon('info-circle', ['classes' => 'ft-state-info']);

        // Create the result.
        $link = html_writer::link($alternateurl, format_string($columnvalue), array('class' => $class_link));
        return html_writer::div($infoicon . $link, 'rb-display-expand', $attributes);
    }


    /**
     * Reformat a timestamp into a time, showing nothing if invalid or null
     *
     * @deprecated Since Totara 12.0
     * @param integer $date Unix timestamp
     * @param object $row Object containing all other fields for this row
     *
     * @return string Time in a nice format
     */
    function rb_display_nice_time($date, $row) {
        debugging('rb_base_source::rb_display_nice_time has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        if ($date && is_numeric($date)) {
            return userdate($date, get_string('strftimeshort', 'langconfig'));
        } else {
            return '';
        }
    }

    /**
     * Reformat a timestamp and timezone into a datetime, showing nothing if invalid or null
     *
     * @deprecated Since Totara 12.0
     * @param integer $date Unix timestamp
     * @param object $row Object containing all other fields for this row (which should include a timezone field)
     *
     * @return string Date and time in a nice format
     */
    function rb_display_nice_datetime_in_timezone($date, $row) {
        debugging('rb_base_source::rb_display_nice_datetime_in_timezone has been deprecated since Totara 12.0', DEBUG_DEVELOPER);

        if ($date && is_numeric($date)) {
            if (empty($row->timezone)) {
                $targetTZ = core_date::get_user_timezone();
                $tzstring = get_string('nice_time_unknown_timezone', 'totara_reportbuilder');
            } else {
                $targetTZ = core_date::normalise_timezone($row->timezone);
                $tzstring = core_date::get_localised_timezone($targetTZ);
            }
            $date = userdate($date, get_string('strftimedatetime', 'langconfig'), $targetTZ) . ' ';
            return $date . $tzstring;
        } else {
            return '';
        }
    }

    /**
     * Display function for delimited date in timezone list
     *
     * @deprecated Since Totara 12.0
     * @param $data
     * @param $row
     * @return string
     */
    function rb_display_delimitedlist_date_in_timezone($data, $row) {
        debugging('rb_base_source::rb_display_delimitedlist_date_in_timezone has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        $format = get_string('strftimedate', 'langconfig');
        return $this->format_delimitedlist_datetime_in_timezone($data, $row, $format);
    }

    /**
     * Display function for delimited datetime in timezone list
     *
     * @deprecated Since Totara 12.0
     * @param $data
     * @param $row
     * @return string
     */
    function rb_display_delimitedlist_datetime_in_timezone($data, $row) {
        debugging('rb_base_source::rb_display_delimitedlist_datetime_in_timezone has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        $format = get_string('strftimedatetime', 'langconfig');
        return $this->format_delimitedlist_datetime_in_timezone($data, $row, $format);
    }

    /**
     * Helper function to concatenate the fields using $this->uniquedelimiter
     *
     * @deprecated Since Totara 12.0
     * @param $data
     * @param $row
     * @param $format
     * @return string
     */
    function format_delimitedlist_datetime_in_timezone($data, $row, $format) {
        debugging('rb_base_source::format_delimitedlist_datetime_in_timezone has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        $delimiter = $this->uniquedelimiter;
        $items = explode($delimiter, $data);
        $output = array();
        foreach ($items as $date) {
            if ($date && is_numeric($date)) {
                if (empty($row->timezone)) {
                    $targetTZ = core_date::get_user_timezone();
                    $tzstring = get_string('nice_time_unknown_timezone', 'totara_reportbuilder');
                } else {
                    $targetTZ = core_date::normalise_timezone($row->timezone);
                    $tzstring = core_date::get_localised_timezone($targetTZ);
                }
                $date = userdate($date, get_string('strftimedatetime', 'langconfig'), $targetTZ) . ' ';
                $output[] = $date . $tzstring;
            } else {
                $output[] = '-';
            }
        }

        return implode($output, "\n");
    }

    /**
     * Reformat two timestamps and timezones into a datetime, showing only one date if only one is present and
     * nothing if invalid or null.
     *
     * @deprecated Since Totara 12.0
     * @param integer $date Unix timestamp
     * @param object $row Object containing all other fields for this row (which should include a timezone field)
     *
     * @return string Date and time in a nice format
     */
    function rb_display_nice_two_datetime_in_timezone($startdate, $row) {
        debugging('rb_base_source::rb_display_nice_two_datetime_in_timezone has been deprecated since Totara 12.0', DEBUG_DEVELOPER);

        $finishdate = $row->finishdate;
        $startdatetext = $finishdatetext = $returntext = '';

        if (empty($row->timezone)) {
            $targetTZ = core_date::get_user_timezone();
            $tzstring = get_string('nice_time_unknown_timezone', 'totara_reportbuilder');
        } else {
            $targetTZ = core_date::normalise_timezone($row->timezone);
            $tzstring = core_date::get_localised_timezone($targetTZ);
        }

        if ($startdate && is_numeric($startdate)) {
            $startdatetext = userdate($startdate, get_string('strftimedatetime', 'langconfig'), $targetTZ) . ' ' . $targetTZ;
        }

        if ($finishdate && is_numeric($finishdate)) {
            $finishdatetext = userdate($finishdate, get_string('strftimedatetime', 'langconfig'), $targetTZ) . ' ' . $targetTZ;
        }

        if ($startdatetext && $finishdatetext) {
            $returntext = get_string('datebetween', 'totara_reportbuilder', array('from' => $startdatetext, 'to' => $finishdatetext));
        } else if ($startdatetext) {
            $returntext = get_string('dateafter', 'totara_reportbuilder', $startdatetext);
        } else if ($finishdatetext) {
            $returntext = get_string('datebefore', 'totara_reportbuilder', $finishdatetext);
        }

        return $returntext;
    }


    /**
     * Reformat a timestamp into a date and time (including seconds), showing nothing if invalid or null
     *
     * @deprecated Since Totara 12.0
     * @param integer $date Unix timestamp
     * @param object $row Object containing all other fields for this row
     *
     * @return string Date and time (including seconds) in a nice format
     */
    function rb_display_nice_datetime_seconds($date, $row) {
        debugging('rb_base_source::rb_display_nice_datetime_seconds has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        if ($date && is_numeric($date)) {
            return userdate($date, get_string('strftimedateseconds', 'langconfig'));
        } else {
            return '';
        }
    }

    /**
     * Display function to convert floats to 2 decimal places
     *
     * @deprecated Since Totara 12.0
     * @param $item
     * @param $row
     * @return string
     */
    function rb_display_round2($item, $row) {
        debugging('rb_base_source::rb_display_round2 has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        return ($item === null or $item === '') ? '-' : sprintf('%.2f', $item);
    }

    /**
     * Display function to convert numbers to percentage with 1 decimal place
     *
     * @deprecated Since Totara 12.0
     * @param $item
     * @param $row
     * @return string
     */
    function rb_display_percent($item, $row) {
        debugging('rb_base_source::rb_display_percent has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        return ($item === null or $item === '') ? '-' : sprintf('%.1f%%', $item);
    }

    /**
     * Displays a comma separated list of strings as one string per line.
     * Assumes you used "'grouping' => 'comma_list'", which concatenates with ', ', to construct the string.
     *
     * @deprecated Since Totara 12.0
     * @param $list
     * @param $row
     * @return string
     */
    function rb_display_list_to_newline($list, $row) {
        debugging('rb_base_source::rb_display_list_to_newline has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        $items = explode(', ', $list);
        foreach ($items as $key => $item) {
            if (empty($item)) {
                $items[$key] = '-';
            }
        }
        return implode($items, "\n");
    }

    /**
     * Displays a list of strings as one string per line.
     * Assumes you used a custom grouping with the $this->uniquedelimiter to concatenate the fields.
     *
     * @deprecated Since Totara 12.0
     * @param $list
     * @param $row
     * @return string
     */
    function rb_display_delimitedlist_to_newline($list, $row) {
        debugging('rb_base_source::rb_display_delimitedlist_to_newline has been deprecated since Totara 12.0. Use orderedlist_to_newline::display', DEBUG_DEVELOPER);
        $delimiter = $this->uniquedelimiter;
        $items = explode($delimiter, $list);
        $output = array();
        foreach ($items as $item) {
            $item = trim($item);
            if (empty($item) || $item === '-') {
                $output[] = '-';
            } else {
                $output[] = format_string($item);
            }
        }
        return implode($output, "\n");
    }

    /**
     * Displays a list of saved multi select strings as one string per line
     * Assumes you used a custom grouping with the $this->uniquedelimiter to concatenate the fields
     *
     * @deprecated Since Totara 12.0
     * @param $list
     * @param $row
     * @return string
     */
    function rb_display_delimitedlist_multi_to_newline($list, $row) {
        debugging('rb_base_source::rb_display_delimitedlist_multi_to_newline has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        $delimiter = $this->uniquedelimiter;
        $items = explode($delimiter, $list);
        $output = array();
        foreach ($items as $item) {
            $inline = array();
            $item = (array)json_decode($item);
            if ($item === '-' || empty($item)) {
                $output[] = '-';
            } else {
                foreach ($item as $option) {
                    $inline[] = format_string($option->option);
                }
                $output[] = implode($inline, ', ');
            }
        }
        return implode($output, "\n");
    }

    /**
     * Displays a list of url's as one string per line
     * Assumes you used a custom grouping with the $this->uniquedelimiter to concatenate the fields
     *
     * @deprecated Since Totara 12.0
     * @param $list
     * @param $row
     * @return string
     */
    function rb_display_delimitedlist_url_to_newline($list, $row) {
        debugging('rb_base_source::display_delimitedlist_url_to_newline has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        $delimiter = $this->uniquedelimiter;
        $items = explode($delimiter, $list);
        $output = array();
        foreach ($items as $item) {
            $item = json_decode($item);
            if ($item === '-' || empty($item)) {
                $output[] = '-';
            } else {
                $text = s(empty($item->text) ? $item->url : format_string($item->text));
                $target = isset($item->target) ? array('target' => '_blank', 'rel' => 'noreferrer') : null;
                $output[] = html_writer::link($item->url, $text, $target);
            }
        }
        return implode($output, "\n");
    }

    /**
     * Displays a list of pos files as one string per line
     * Assumes you used a custom grouping with the $this->uniquedelimiter to concatenate the fields
     *
     * @deprecated Since Totara 12.0
     * @param $data
     * @param $row
     * @param $isexport
     * @return string
     */
    function rb_display_delimitedlist_posfiles_to_newline($data, $row, $isexport = false) {
        debugging('rb_base_source::rb_display_delimitedlist_posfiles_to_newline has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        return $this->delimitedlist_files_to_newline($data, $row, 'position', $isexport);
    }

    /**
     * Displays a list of org files as one string per line
     * Assumes you used a custom grouping with the $this->uniquedelimiter to concatenate the fields
     *
     * @deprecated Since Totara 12.0
     * @param $data
     * @param $row
     * @param $isexport
     * @return string
     */
    function rb_display_delimitedlist_orgfiles_to_newline($data, $row, $isexport = false) {
        debugging('rb_base_source::rb_display_delimitedlist_orgfiles_to_newline has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        return $this->delimitedlist_files_to_newline($data, $row, 'organisation', $isexport);
    }

    /**
     * Assumes you used a custom grouping with the $this->uniquedelimiter to concatenate the fields.
     *
     * @deprecated Since Totara 12.0
     * @param $data
     * @param $row
     * @param $type
     * @param $isexport
     * @return string
     */
    function delimitedlist_files_to_newline($data, $row, $type, $isexport) {
        debugging('rb_base_source::delimitedlist_files_to_newline has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        global $CFG;
        require_once($CFG->dirroot . '/totara/customfield/field/file/field.class.php');

        $delimiter = $this->uniquedelimiter;
        $items = explode($delimiter, $data);
        $extradata = array(
            'prefix' => $type,
            'isexport' => $isexport
        );

        $output = array();
        foreach ($items as $item) {
            if ($item === '-' || empty($item)) {
                $output[] = '-';
            } else {
                $output[] = customfield_file::display_item_data($item, $extradata);
            }
        }
        return implode($output, "\n");
    }

    /**
     * Displays a list of locations as one string per line
     * Assumes you used a custom grouping with the $this->uniquedelimiter to concatenate the fields
     *
     * @deprecated Since Totara 12.0
     * @param $list
     * @param $row
     * @return string
     */
    function rb_display_delimitedlist_location_to_newline($list, $row) {
        debugging('rb_base_source::rb_display_delimitedlist_location_to_newline has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        $delimiter = $this->uniquedelimiter;
        $items = explode($delimiter, $list);
        $output = array();
        foreach ($items as $item) {
            $item = json_decode($item);
            if ($item === '-' || empty($item)) {
                $output[] = '-';
            } else {
                $location = trim(str_replace("\r\n", " ", $item->address));
                $output[] = $location;
            }
        }
        return implode($output, "\n");
    }

    /**
     * Displays a comma separated list of ints as one nice_date per line
     * Assumes you used "'grouping' => 'comma_list'", which concatenates with ', ', to construct the string
     *
     * @deprecated Since Totara 12.0
     * @param $datelist
     * @param $row
     * @return string
     */
    function rb_display_list_to_newline_date($datelist, $row) {
        debugging('rb_base_source::rb_display_list_to_newline_date has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        $items = explode(', ', $datelist);
        foreach ($items as $key => $item) {
            if (empty($item) || $item === '-') {
                $items[$key] = '-';
            } else {
                $items[$key] = is_numeric($item) && $item != 0 && $item != -1 ?
                    userdate($item, get_string('strfdateshortmonth', 'langconfig')) : '';
            }
        }
        return implode($items, "\n");
    }

    /**
     * Displays a delimited list of ints as one nice_date per line, based off nice_date_list
     * Assumes you used "'grouping' => 'sql_aggregate'", which concatenates with $uniquedelimiter to construct a pre-ordered string
     *
     * @deprecated Since Totara 12.0
     * @param $datelist
     * @param $row
     * @return string
     */
    function rb_display_orderedlist_to_newline_date($datelist, $row) {
        debugging('rb_base_source::rb_display_orderedlist_to_newline_date has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        $output = array();
        $items = explode($this->uniquedelimiter, $datelist);
        foreach ($items as $item) {
            if (empty($item) || $item === '-') {
                $output[] = '-';
            } else {
                $output[] = userdate($item, get_string('strfdateshortmonth', 'langconfig'));
            }
        }
        return implode($output, "\n");
    }

    /**
     * Assumes you used a custom grouping with the $this->uniquedelimiter to concatenate the fields.
     *
     * @deprecated Since Totara 12.0
     * @param $datelist
     * @param $row
     * @return string
     */
    function rb_display_delimitedlist_to_newline_date($datelist, $row) {
        debugging('rb_base_source::rb_display_delimitedlist_to_newline_date has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        $delimiter = $this->uniquedelimiter;
        $items = explode($delimiter, $datelist);
        $output = array();
        foreach ($items as $item) {
            if (empty($item) || $item === '-') {
                $output[] = '-';
            } else {
                $output[] = userdate($item, get_string('strfdateshortmonth', 'langconfig'));
            }
        }
        return implode($output, "\n");
    }

    /**
     * Display address from location stored as json object
     *
     * @deprecated Since Totara 12.0
     * @param string $location
     * @param stdClass $row
     * @param bool $isexport
     * @return string
     */
    public function rb_display_location($location, $row, $isexport = false) {
        debugging('rb_base_source::rb_display_location has been deprecated since Totara 12.0, please call customfield_location::display() instead.', DEBUG_DEVELOPER);
        global $CFG;
        require_once($CFG->dirroot . '/totara/customfield/field/location/define.class.php');
        $output = array();

        $location = customfield_define_location::convert_location_json_to_object($location);

        if (is_null($location)){
            return get_string('notapplicable', 'facetoface');
        }

        $output[] = $location->address;

        return implode('', $output);
    }

    /**
     * Display correct course grade via grade or RPL as a percentage string
     *
     * @deprecated Since Totara 12.0
     * @param string $item A number to convert
     * @param object $row Object containing all other fields for this row
     *
     * @return string The percentage with 1 decimal place
     */
    function rb_display_course_grade_percent($item, $row) {
        debugging('rb_base_source::rb_display_course_grade_percent has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        if ($row->status == COMPLETION_STATUS_COMPLETEVIARPL && !empty($row->rplgrade)) {
            // If RPL then print the RPL grade.
            return sprintf('%.1f%%', $row->rplgrade);
        } else if (!empty($row->maxgrade) && !empty($item)) {

            $maxgrade = (float)$row->maxgrade;
            $mingrade = 0.0;
            if (!empty($row->mingrade)) {
                $mingrade = (float)$row->mingrade;
            }

            // We can't have a divisor of zero, and a negative one doesn't make much sense either.
            if ($maxgrade - $mingrade <= 0) {
                return '-';
            }

            // Create a percentage using the max grade.
            $percent = ((($item - $mingrade) / ($maxgrade - $mingrade)) * 100);

            return sprintf('%.1f%%', $percent);
        } else if ($item !== null && $item !== '') {
            // If the item has a value show it.
            return $item;
        } else {
            // Otherwise show a '-'
            return '-';
        }
    }

    /**
     * A rb_column_options->displayfunc helper function for showing a user's name and links to their profile.
     * To pass the correct data, first:
     *      $usednamefields = totara_get_all_user_name_fields_join($base, null, true);
     *      $allnamefields = totara_get_all_user_name_fields_join($base);
     * then your "field" param should be:
     *      $DB->sql_concat_join("' '", $usednamefields)
     * to allow sorting and filtering, and finally your extrafields should be:
     *      array_merge(array('id' => $base . '.id'),
     *                  $allnamefields)
     * When exporting, only the user's full name is displayed (without link).
     *
     * @deprecated Since Totara 12.0
     * @param string $user Unused
     * @param object $row All the data required to display a user's name
     * @param boolean $isexport If the report is being exported or viewed
     * @return string
     */
    function rb_display_link_user($user, $row, $isexport = false) {
        debugging('rb_base_source::rb_display_link_user has been deprecated since Totara 12.0. Use user_link::display', DEBUG_DEVELOPER);
        // Process obsolete calls to this display function.
        if (isset($row->user_id)) {
            $fullname = $user;
        } else {
            $fullname = fullname($row);
        }

        // Don't show links in spreadsheet.
        if ($isexport) {
            return $fullname;
        }

        if ($row->id == 0) {
            // No user id means no link, most likely the fullname is empty anyway.
            return $fullname;
        }

        $url = new moodle_url('/user/view.php', array('id' => $row->id));
        if ($fullname === '') {
            return '';
        } else {
            return html_writer::link($url, $fullname);
        }
    }

    /**
     * A rb_column_options->displayfunc helper function for showing a user's profile picture, name and links to their profile.
     * To pass the correct data, first:
     *      $usednamefields = totara_get_all_user_name_fields_join($base, null, true);
     *      $allnamefields = totara_get_all_user_name_fields_join($base);
     * then your "field" param should be:
     *      $DB->sql_concat_join("' '", $usednamefields)
     * to allow sorting and filtering, and finally your extrafields should be:
     *      array_merge(array('id' => $base . '.id',
     *                        'picture' => $base . '.picture',
     *                        'imagealt' => $base . '.imagealt',
     *                        'email' => $base . '.email'),
     *                  $allnamefields)
     * When exporting, only the user's full name is displayed (without icon or link).
     *
     * @deprecated Since Totara 12.0
     * @param string $user Unused
     * @param object $row All the data required to display a user's name, icon and link
     * @param boolean $isexport If the report is being exported or viewed
     * @return string
     */
    function rb_display_link_user_icon($user, $row, $isexport = false) {
        debugging('rb_base_source::rb_display_link_user_icon has been deprecated since Totara 12.0. Use user_icon_link::display', DEBUG_DEVELOPER);
        global $OUTPUT;

        // Process obsolete calls to this display function.
        if (isset($row->userpic_picture)) {
            $picuser = new stdClass();
            $picuser->id = $row->user_id;
            $picuser->picture = $row->userpic_picture;
            $picuser->imagealt = $row->userpic_imagealt;
            $picuser->firstname = $row->userpic_firstname;
            $picuser->firstnamephonetic = $row->userpic_firstnamephonetic;
            $picuser->middlename = $row->userpic_middlename;
            $picuser->lastname = $row->userpic_lastname;
            $picuser->lastnamephonetic = $row->userpic_lastnamephonetic;
            $picuser->alternatename = $row->userpic_alternatename;
            $picuser->email = $row->userpic_email;
            $row = $picuser;
        }

        if ($row->id == 0) {
            return '';
        }

        // Don't show picture in spreadsheet.
        if ($isexport) {
            return fullname($row);
        }

        $url = new moodle_url('/user/view.php', array('id' => $row->id));
        return $OUTPUT->user_picture($row, array('courseid' => 1)) . "&nbsp;" . html_writer::link($url, $user);
    }

    /**
     * A rb_column_options->displayfunc helper function for showing a user's profile picture.
     * To pass the correct data, first:
     *      $usernamefields = totara_get_all_user_name_fields_join($base, null, true);
     *      $allnamefields = totara_get_all_user_name_fields_join($base);
     * then your "field" param should be:
     *      $DB->sql_concat_join("' '", $usednamefields)
     * to allow sorting and filtering, and finally your extrafields should be:
     *      array_merge(array('id' => $base . '.id',
     *                        'picture' => $base . '.picture',
     *                        'imagealt' => $base . '.imagealt',
     *                        'email' => $base . '.email'),
     *                  $allnamefields)
     * When exporting, only the user's full name is displayed (instead of picture).
     *
     * @deprecated Since Totara 12.0
     * @param string $user Unused
     * @param object $row All the data required to display a user's name and icon
     * @param boolean $isexport If the report is being exported or viewed
     * @return string
     */
    function rb_display_user_picture($user, $row, $isexport = false) {
        debugging('rb_base_source::rb_display_user_picture has been deprecated since Totara 12.0. Use user_icon::display', DEBUG_DEVELOPER);
        global $OUTPUT;

        // Process obsolete calls to this display function.
        if (isset($row->userpic_picture)) {
            $picuser = new stdClass();
            $picuser->id = $user;
            $picuser->picture = $row->userpic_picture;
            $picuser->imagealt = $row->userpic_imagealt;
            $picuser->firstname = $row->userpic_firstname;
            $picuser->firstnamephonetic = $row->userpic_firstnamephonetic;
            $picuser->middlename = $row->userpic_middlename;
            $picuser->lastname = $row->userpic_lastname;
            $picuser->lastnamephonetic = $row->userpic_lastnamephonetic;
            $picuser->alternatename = $row->userpic_alternatename;
            $picuser->email = $row->userpic_email;
            $row = $picuser;
        }

        // Don't show picture in spreadsheet.
        if ($isexport) {
            return fullname($row);
        } else {
            return $OUTPUT->user_picture($row, array('courseid' => 1));
        }
    }

    /**
     * A rb_column_options->displayfunc helper function for showing a user's name.
     * To pass the correct data, first:
     *      $usednamefields = totara_get_all_user_name_fields_join($base, null, true);
     *      $allnamefields = totara_get_all_user_name_fields_join($base);
     * then your "field" param should be:
     *      $DB->sql_concat_join("' '", $usednamefields)
     * to allow sorting and filtering, and finally your extrafields should be:
     *      $allnamefields
     *
     * @deprecated Since Totara 12.0
     * @param string $user Unused
     * @param object $row All the data required to display a user's name
     * @param boolean $isexport If the report is being exported or viewed
     * @return string
     */
    function rb_display_user($user, $row, $isexport = false) {
        debugging('rb_base_source::rb_display_user has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        return fullname($row);
    }

    /**
     * Convert a course name into an expanding link.
     *
     * @deprecated Since Totara 12.0
     * @param string $course
     * @param array $row
     * @param bool $isexport
     * @return string
     */
    public function rb_display_course_expand($course, $row, $isexport = false) {
        debugging('rb_base_source::rb_display_course_expand has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        if ($isexport) {
            return format_string($course);
        }

        $attr = array('class' => totara_get_style_visibility($row, 'course_visible', 'course_audiencevisible'));
        $alturl = new moodle_url('/course/view.php', array('id' => $row->course_id));
        return $this->create_expand_link($course, 'course_details', array('expandcourseid' => $row->course_id), $alturl, $attr);
    }

    /**
     * Convert a program/certification name into an expanding link.
     *
     * @deprecated Since Totara 12.0
     * @param string $program
     * @param array $row
     * @param bool $isexport
     * @return string
     */
    public function rb_display_program_expand($program, $row, $isexport = false) {
        debugging('rb_base_source::rb_display_program_expand has been deprecated since Totara 12.0. Use totara_program\rb\display\program_expand::display', DEBUG_DEVELOPER);
        if ($isexport) {
            return format_string($program);
        }

        $attr = array('class' => totara_get_style_visibility($row, 'prog_visible', 'prog_audiencevisible'));
        $alturl = new moodle_url('/totara/program/view.php', array('id' => $row->prog_id));
        return $this->create_expand_link($program, 'prog_details',
            array('expandprogid' => $row->prog_id), $alturl, $attr);
    }

    /**
     * Certification display the certification path as string.
     *
     * @deprecated Since Totara 12.0
     * @param string $certifpath    CERTIFPATH_X constant to describe cert or recert coursesets
     * @param array $row            The record used to generate the table row
     * @return string
     */
    function rb_display_certif_certifpath($certifpath, $row) {
        debugging('rb_base_source::rb_display_certif_certifpath has been deprecated since Totara 12.0. Use totara_certification\rb\display\certif_certifpath::display', DEBUG_DEVELOPER);
        global $CERTIFPATH;
        if ($certifpath && isset($CERTIFPATH[$certifpath])) {
            return get_string($CERTIFPATH[$certifpath], 'totara_certification');
        }
    }


    /**
     * Convert a course name into a link to that course
     *
     * @deprecated Since Totara 12.0
     * @param $course
     * @param $row
     * @param bool $isexport
     * @return string
     */
    function rb_display_link_course($course, $row, $isexport = false) {
        debugging('rb_base_source::rb_display_link_course has been deprecated since Totara 12.0. Use course_link::display', DEBUG_DEVELOPER);
        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');

        if ($isexport) {
            return format_string($course);
        }

        $courseid = $row->course_id;
        $attr = array('class' => totara_get_style_visibility($row, 'course_visible', 'course_audiencevisible'));
        $url = new moodle_url('/course/view.php', array('id' => $courseid));
        return html_writer::link($url, $course, $attr);
    }

    /**
     * Convert a course name into a link to that course and shows the course icon next to it
     *
     * @deprecated Since Totara 12.0
     * @param $course
     * @param $row
     * @param bool $isexport
     * @return string
     */
    function rb_display_link_course_icon($course, $row, $isexport = false) {
        debugging('rb_base_source::rb_display_link_course_icon has been deprecated since Totara 12.0. Use course_icon_link::display', DEBUG_DEVELOPER);
        global $CFG, $OUTPUT;
        require_once($CFG->dirroot . '/cohort/lib.php');

        if ($isexport) {
            return format_string($course);
        }

        $courseid = $row->course_id;
        $courseicon = !empty($row->course_icon) ? $row->course_icon : 'default';
        $cssclass = totara_get_style_visibility($row, 'course_visible', 'course_audiencevisible');
        $icon = html_writer::empty_tag('img', array('src' => totara_get_icon($courseid, TOTARA_ICON_TYPE_COURSE),
            'class' => 'course_icon', 'alt' => ''));
        $link = $OUTPUT->action_link(
            new moodle_url('/course/view.php', array('id' => $courseid)),
            $icon . $course, null, array('class' => $cssclass)
        );
        return $link;
    }

    // display an icon based on the course icon field

    /**
     * Display an icon based on the course icon field
     *
     * @deprecated Since Totara 12.0
     * @param $icon
     * @param $row
     * @param bool $isexport
     * @return string
     */
    function rb_display_course_icon($icon, $row, $isexport = false) {
        debugging('rb_base_source::rb_display_course_icon has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        if ($isexport) {
            return format_string($row->course_name);
        }

        $coursename = format_string($row->course_name);
        $courseicon = html_writer::empty_tag('img', array('src' => totara_get_icon($row->course_id, TOTARA_ICON_TYPE_COURSE),
            'class' => 'course_icon', 'alt' => $coursename));
        return $courseicon;
    }

    /**
     * Display an icon for the course type
     *
     * @deprecated Since Totara 12.0
     * @param $type
     * @param $row
     * @param bool $isexport
     * @return null|string
     */
    function rb_display_course_type_icon($type, $row, $isexport = false) {
        debugging('rb_base_source::rb_display_course_type_icon has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        global $OUTPUT;

        if ($isexport) {
            switch ($type) {
                case TOTARA_COURSE_TYPE_ELEARNING:
                    return get_string('elearning', 'rb_source_dp_course');
                case TOTARA_COURSE_TYPE_BLENDED:
                    return get_string('blended', 'rb_source_dp_course');
                case TOTARA_COURSE_TYPE_FACETOFACE:
                    return get_string('facetoface', 'rb_source_dp_course');
            }
            return '';
        }

        switch ($type) {
            case null:
                return null;
                break;
            case 0:
                $image = 'elearning';
                break;
            case 1:
                $image = 'blended';
                break;
            case 2:
                $image = 'facetoface';
                break;
        }
        $alt = get_string($image, 'rb_source_dp_course');
        $icon = $OUTPUT->pix_icon('/msgicons/' . $image . '-regular', $alt, 'totara_core', array('title' => $alt));

        return $icon;
    }

    /**
     * Display course type text
     *
     * @deprecated Since Totara 12.0
     * @param string $type
     * @param array $row
     * @param bool $isexport
     * @return string
     */
    public function rb_display_course_type($type, $row, $isexport = false) {
        debugging('rb_base_source::rb_display_course_type has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        $types = $this->rb_filter_course_types();
        if (isset($types[$type])) {
            return $types[$type];
        }
        return '';
    }

    /**
     * Convert a course category name into a link to that category's page
     *
     * @deprecated Since Totara 12.0
     * @param $category
     * @param $row
     * @param bool $isexport
     * @return string
     */
    function rb_display_link_course_category($category, $row, $isexport = false) {
        debugging('rb_base_source::rb_display_link_course_category has been deprecated since Totara 12.0. Use course_category_link::display', DEBUG_DEVELOPER);
        if ($isexport) {
            return format_string($category);
        }

        $catid = $row->cat_id;
        $category = format_string($category);
        if ($catid == 0 || !$catid) {
            return '';
        }
        $attr = (isset($row->cat_visible) && $row->cat_visible == 0) ? array('class' => 'dimmed') : array();
        $columns = array('coursecount' => 'course', 'programcount' => 'program', 'certifcount' => 'certification');
        foreach ($columns as $field => $viewtype) {
            if (isset($row->{$field})) {
                break;
            }
        }
        switch ($viewtype) {
            case 'program':
            case 'certification':
                $url = new moodle_url('/totara/program/index.php', array('categoryid' => $catid, 'viewtype' => $viewtype));
                break;
            default:
                $url = new moodle_url('/course/index.php', array('categoryid' => $catid));
                break;
        }
        return html_writer::link($url, $category, $attr);
    }

    /**
     * Display audience visibility
     *
     * @deprecated Since Totara 12.0
     * @param $visibility
     * @param $row
     * @param bool $isexport
     * @return mixed
     */
    public function rb_display_audience_visibility($visibility, $row, $isexport = false) {
        debugging('rb_base_source::rb_display_audience_visibility has been deprecated since Totara 12.0. Use totara_cohort\rb\display\cohort_visibility::display', DEBUG_DEVELOPER);
        global $COHORT_VISIBILITY;

        if (!isset($COHORT_VISIBILITY[$visibility])) {
            return $visibility;
        }

        return $COHORT_VISIBILITY[$visibility];
    }


    /**
     * Generate the plan title with a link to the plan
     *
     * @deprecated Since Totara 12.0
     * @param string $planname
     * @param object $row
     * @param boolean $isexport If the report is being exported or viewed
     * @return string
     */
    public function rb_display_planlink($planname, $row, $isexport = false) {
        debugging('rb_base_source::rb_display_planlink has been deprecated since Totara 12.0. Use totara_plan\rb\display\plan_link::display', DEBUG_DEVELOPER);

        // no text
        if (strlen($planname) == 0) {
            return '';
        }

        // invalid id - show without a link
        if (empty($row->plan_id)) {
            return $planname;
        }

        if ($isexport) {
            return $planname;
        }
        $url = new moodle_url('/totara/plan/view.php', array('id' => $row->plan_id));
        return html_writer::link($url, $planname);
    }


    /**
     * Display the plan's status (for use as a column displayfunc)
     *
     * @deprecated Since Totara 12.0
     * @global object $CFG
     * @param int $status
     * @param object $row
     * @return string
     */
    public function rb_display_plan_status($status, $row) {
        debugging('rb_base_source::rb_display_plan_status has been deprecated since Totara 12.0. Use totara_plan\rb\display\plan_status::display', DEBUG_DEVELOPER);
        global $CFG;
        require_once($CFG->dirroot . '/totara/plan/lib.php');

        switch ($status) {
            case DP_PLAN_STATUS_UNAPPROVED:
                return get_string('unapproved', 'totara_plan');
                break;
            case DP_PLAN_STATUS_PENDING:
                return get_string('pendingapproval', 'totara_plan');
                break;
            case DP_PLAN_STATUS_APPROVED:
                return get_string('approved', 'totara_plan');
                break;
            case DP_PLAN_STATUS_COMPLETE:
                return get_string('complete', 'totara_plan');
                break;
        }
    }


    /**
     * Column displayfunc to convert a plan item's status to a
     * human-readable string
     *
     * @deprecated Since Totara 12.0
     * @param int $status
     * @return string
     */
    public function rb_display_plan_item_status($status) {
        debugging('rb_base_source::rb_display_plan_item_status has been deprecated since Totara 12.0. Use totara_plan\rb\display\plan_item_status::display', DEBUG_DEVELOPER);
        global $CFG;
        require_once($CFG->dirroot . '/totara/plan/lib.php');

        switch($status) {
            case DP_APPROVAL_DECLINED:
                return get_string('declined', 'totara_plan');
            case DP_APPROVAL_UNAPPROVED:
                return get_string('unapproved', 'totara_plan');
            case DP_APPROVAL_REQUESTED:
                return get_string('pendingapproval', 'totara_plan');
            case DP_APPROVAL_APPROVED:
                return get_string('approved', 'totara_plan');
            default:
                return '';
        }
    }

    /**
     * Yes or no display
     *
     * @deprecated Since Totara 12.0
     * @param $item
     * @param $row
     * @return string
     */
    function rb_display_yes_no($item, $row) {
        debugging('rb_base_source::rb_display_yes_no has been deprecated since Totara 12.0. Please use yes_or_no::display', DEBUG_DEVELOPER);
        if ($item === null or $item === '') {
            return '';
        } else if ($item) {
            return get_string('yes');
        } else {
            return get_string('no');
        }
    }

    /**
     * Delimited list yes or no
     *
     * @deprecated Since Totara 12.0
     * @param $data
     * @param $row
     * @return string
     */
    function rb_display_delimitedlist_yes_no($data, $row) {
        debugging('rb_base_source::rb_display_delimitedlist_yes_no has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        $delimiter = $this->uniquedelimiter;
        $items = explode($delimiter, $data);
        $output = array();
        foreach ($items as $item) {
            if (!isset($item) || $item === '' || $item === '-') {
                $output[] = '-';
            } else if ($item) {
                $output[] = get_string('yes');
            } else {
                $output[] = get_string('no');
            }
        }
        return implode($output, "\n");
    }

    /**
     * Display duration in human readable format
     *
     * @deprecated Since Totara 12.0
     * @param integer $seconds
     * @param stdClass $row
     * @return string
     */
    public function rb_display_duration($seconds, $row) {
        debugging('rb_base_source::rb_display_duration has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        if (empty($seconds)) {
            return '';
        }
        return format_time($seconds);
    }

    // convert a 2 digit country code into the country name

    /**
     * Display country code
     *
     * @deprecated Since Totara 12.0
     * @param $code
     * @param $row
     * @return string
     */
    function rb_display_country_code($code, $row) {
        debugging('rb_base_source::rb_display_country_code has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        $countries = get_string_manager()->get_list_of_countries();

        if (isset($countries[$code])) {
            return $countries[$code];
        }
        return $code;
    }

    /**
     * Indicates if the user is deleted or not.
     *
     * @deprecated Since Totara 11.0.
     *
     * @param string $status User status value from the column data.
     * @param stdclass $row The data from the report row.
     * @return string Text denoting status.
     */
    function rb_display_deleted_status($status, $row) {
        debugging("The report builder display function 'deleted_status' has been deprecated. Please use 'user_status' instead.", DEBUG_DEVELOPER);

        switch($status) {
            case 1:
                return get_string('deleteduser', 'totara_reportbuilder');
            case 2:
                return get_string('suspendeduser', 'totara_reportbuilder');
            default:
                return get_string('activeuser', 'totara_reportbuilder');
        }
    }

    /**
     * Column displayfunc to show a hierarchy path as a human-readable string
     *
     * @deprecated Since Totara 12.0
     * @param string $path the path string of delimited ids e.g. 1/3/7
     * @param array $row data row
     * @return string
     */
    function rb_display_nice_hierarchy_path($path, $row) {
        debugging('rb_base_source::rb_display_nice_hierarchy_path has been deprecated since Totara 12.0. Use totara_hierarchy\rb\display\hierarchy_nice_path::display', DEBUG_DEVELOPER);
        global $DB;
        if (empty($path)) {
            return '';
        }
        $displaypath = '';
        $parentid = 0;
        // Make sure we know what we are looking for, and that the private var is populated (in source constructor).
        if (isset($row->hierarchytype) && isset($this->hierarchymap[$row->hierarchytype])) {
            $paths = explode('/', substr($path, 1));
            $map = $this->hierarchymap[$row->hierarchytype];
            foreach ($paths as $path) {
                if ($parentid !== 0) {
                    // Include ' > ' before name except on top element.
                    $displaypath .= ' &gt; ';
                }
                if (isset($map[$path])) {
                    $displaypath .= $map[$path];
                } else {
                    // Should not happen if paths are correct!
                    $displaypath .= get_string('unknown', 'totara_reportbuilder');
                }
                $parentid = $path;
            }
        }

        return $displaypath;
    }

    /**
     * Column displayfunc to convert a language code to a human-readable string
     *
     * @deprecated Since Totara 12.0
     * @param string $code Language code
     * @param array $row data row - unused in this function
     * @return string
     */
    function rb_display_language_code($code, $row) {
        debugging('rb_base_source::rb_display_language_code has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        return $this->language_code_to_name($code);
    }


    /**
     * Ordered list of emails as one per line
     *
     * @deprecated Since Totara 12.0
     * @param $list
     * @param $row
     * @param bool $isexport
     * @return string
     */
    public function rb_display_orderedlist_to_newline_email($list, $row, $isexport = false) {
        debugging('rb_base_source::rb_display_orderedlist_to_newline_email has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        $output = array();
        $emails = explode($this->uniquedelimiter, $list);
        foreach ($emails as $email) {
            if ($isexport) {
                $output[] = $email;
            } else if ($email === '!private!') {
                $output[] = get_string('useremailprivate', 'totara_reportbuilder');
            } else if ($email !== '-') {
                // Obfuscate email to avoid spam if printing to page.
                $output[] = obfuscate_mailto($email);
            } else {
                $output[] = '-';
            }
        }

        return implode($output, "\n");
    }

    /**
     * Display program icon with name and link.
     *
     * @deprecated Since Totara 12.
     * @param $program
     * @param $row
     * @param bool $isexport
     * @return mixed
     */
    function rb_display_link_program_icon($program, $row, $isexport = false) {
        debugging('rb_base_source::rb_display_link_program_icon has been deprecated since Totara 12.0. Use totara_program\rb\display\program_icon_link::display', DEBUG_DEVELOPER);
        global $OUTPUT;

        if ($isexport) {
            return $program;
        }

        $programid = $row->program_id;
        $programicon = !empty($row->program_icon) ? $row->program_icon : 'default';
        $programobj = (object) $row;
        $class = 'course_icon ' . totara_get_style_visibility($programobj, 'program_visible', 'program_audiencevisible');
        $icon = html_writer::empty_tag('img', array('src' => totara_get_icon($programid, TOTARA_ICON_TYPE_PROGRAM),
            'class' => $class, 'alt' => ''));
        $link = $OUTPUT->action_link(
            new moodle_url('/totara/program/view.php', array('id' => $programid)),
            $icon . $program, null, array('class' => $class)
        );
        return $link;
    }

    /**
     * Display grade along with passing grade if it is known
     *
     * @deprecated Since Totara 12.
     * @param $item
     * @param $row
     * @return string
     */
    function rb_display_grade_string($item, $row) {
        debugging('rb_base_source::rb_display_grade_string has been deprecated since Totara 12.0. Use course_grade_string::display', DEBUG_DEVELOPER);
        $passgrade = isset($row->gradepass) ? sprintf('%d', $row->gradepass) : null;

        $usergrade = (int)$item;
        $grademin = 0;
        $grademax = 100;
        if (isset($row->grademin)) {
            $grademin = $row->grademin;
        }
        if (isset($row->grademax)) {
            $grademax = $row->grademax;
        }

        // We can't have a divisor of zero, and a negative one doesn't make much sense either.
        if ($grademax - $grademin <= 0) {
            return '-';
        }

        $usergrade = sprintf('%.1f', ((($usergrade - $grademin) / ($grademax - $grademin)) * 100));

        if ($item === null or $item === '') {
            return '';
        } else if ($passgrade === null) {
            return "{$usergrade}%";
        } else {
            $a = new stdClass();
            $a->grade = $usergrade;
            $a->pass = sprintf('%.1f', ((($passgrade - $grademin) / ($grademax - $grademin)) * 100));
            return get_string('gradeandgradetocomplete', 'totara_reportbuilder', $a);
        }
    }

    /**
     * Display function for certification recertify date type
     *
     * @deprecated Since Totara 12.0
     * @param $recertifydatetype
     * @param $row
     * @return string
     */
    public function rb_display_recertifydatetype($recertifydatetype, $row) {
        debugging('rb_base_source::rb_rb_display_recertifydatetype has been deprecated since Totara 12.0. Use totara_certification\rb\display\certif_recertify_date_type::display', DEBUG_DEVELOPER);
        switch ($recertifydatetype) {
            case CERTIFRECERT_COMPLETION:
                return get_string('editdetailsrccmpl', 'totara_certification');
            case CERTIFRECERT_EXPIRY:
                return get_string('editdetailsrcexp', 'totara_certification');
            case CERTIFRECERT_FIXED:
                return get_string('editdetailsrcfixed', 'totara_certification');
        }
        return "Error - Recertification method not found";
    }

    //
    //
    // Generic select filter methods
    //
    //


    //
    // Wrapper functions to add columns/fields/joins in one go
    //
    //


    /**
     * Adds the user table to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     'user id' field
     * @param string $field Name of user id field to join on
     * @param string $alias Use custom user table alias
     * @return boolean True
     * @deprecated since Totara 12.0
     */
    protected function add_user_table_to_joinlist(&$joinlist, $join, $field, $alias = 'auser') {

        debugging('add_user_table_to_joinlist has been deprecated. Please use add_core_user_tables in \course\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_core_user_tables($joinlist, $join, $field, $alias);
    }


    /**
     * Adds some common user field to the $columnoptions array
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $join Name of the join that provides the 'user' table
     * @param string $groupname The group to add fields to. If you are defining
     *                          a custom group name, you must define a language
     *                          string with the key "type_{$groupname}" in your
     *                          report source language file.
     * @param boolean $$addtypetoheading Add the column type to the column heading
     *                          to differentiate between fields with the same name.
     *
     * @return True
     * @deprecated since Totara 12.0
     */
    protected function add_user_fields_to_columns(&$columnoptions, $join='auser', $groupname = 'user', $addtypetoheading = false) {

        debugging('add_user_fields_to_columns has been deprecated. Please use add_core_user_columns in \course\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_core_user_columns($columnoptions, $join, $groupname, $addtypetoheading);
    }


    /**
     * Adds some common user field to the $filteroptions array
     *
     * @param array &$filteroptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $groupname Name of group to filter. If you are defining
     *                          a custom group name, you must define a language
     *                          string with the key "type_{$groupname}" in your
     *                          report source language file.
     * @return True
     * @deprecated since Totara 12.0
     */
    protected function add_user_fields_to_filters(&$filteroptions, $groupname = 'user', $addtypetoheading = false) {

        debugging('add_user_fields_to_columns has been deprecated. Please use add_core_user_columns in \course\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_core_user_filters($filteroptions, $groupname, $addtypetoheading);
    }

    public function rb_filter_auth_options() {
        $authoptions = array();

        $auths = core_component::get_plugin_list('auth');

        foreach ($auths as $auth => $something) {
            $authinst = get_auth_plugin($auth);

            $authoptions[$auth] = get_string('pluginname', "auth_{$auth}");
        }

        return $authoptions;
    }


    /**
     * Adds the course table to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     'course id' field
     * @param string $field Name of course id field to join on
     * @param string $jointype Type of Join (INNER, LEFT, RIGHT)
     * @return boolean True
     * @deprecated since Totara 12.0
     */
    protected function add_course_table_to_joinlist(&$joinlist, $join, $field, $jointype = 'LEFT') {

        debugging('add_course_table_to_joinlist has been deprecated. Please use add_core_course_tables in \core_course\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_core_course_tables($joinlist, $join, $field, $jointype);
    }

    /**
     * Adds the course table to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     'course id' field
     * @param string $field Name of course id field to join on
     * @param int $contextlevel Name of course id field to join on
     * @param string $jointype Type of join (INNER, LEFT, RIGHT)
     * @return boolean True
     * @deprecated since Totara 12.0
     */
    protected function add_context_table_to_joinlist(&$joinlist, $join, $field, $contextlevel, $jointype = 'LEFT') {

        debugging('add_context_table_to_joinlist has been deprecated. Please use add_context_tables in \totara_reportbuilder\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_context_tables($joinlist, $join, $field, $contextlevel, $jointype);
    }


    /**
     * Adds some common course info to the $columnoptions array
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $join Name of the join that provides the 'course' table
     * @return True
     * @deprecated since Totara 12.0
     */
    protected function add_course_fields_to_columns(&$columnoptions, $join='course') {

        debugging('add_course_fields_to_columns has been deprecated. Please use add_core_course_columns in \course\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_core_course_columns($columnoptions, $join);
    }


    /**
     * Adds some common course filters to the $filteroptions array
     *
     * @param array &$filteroptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     * @return True
     * @deprecated since Totara 12.0
     */
    protected function add_course_fields_to_filters(&$filteroptions) {

        debugging('add_course_fields_to_filters has been deprecated. Please use add_core_course_filters in \core_course\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_core_course_filters($filteroptions);
    }

    /**
     * Adds the program table to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     'program id' field
     * @param string $field Name of table containing program id field to join on
     * @return bool True
     * @deprecated since Totara 12.0
     */
    protected function add_program_table_to_joinlist(&$joinlist, $join, $field) {

        debugging('add_program_table_to_joinlist is deprecated. Please use add_totara_program_tables in \totara_program\rb\source\program_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_totara_program_tables($joinlist, $join, $field);
    }


    /**
     * Adds some common program info to the $columnoptions array
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $join Name of the join that provides the 'program' table
     * @param string $langfile Source for translation, totara_program or totara_certification
     * @return bool True
     * @deprecated since Totara 12.0
     */
    protected function add_program_fields_to_columns(&$columnoptions, $join = 'program', $langfile = 'totara_program') {

        debugging('add_program_fields_to_columns is deprecated. Please use add_totara_program_columns in \totara_program\rb\source\program_trait instead');

        $trait = $this->get_bc_trait_instance();
        return $trait->add_totara_program_columns($columnoptions, $join);
    }

    /**
     * Adds some common program filters to the $filteroptions array
     *
     * @param array &$filteroptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $langfile Source for translation, totara_program or totara_certification
     * @return bool True
     * @deprecated since Totara 12.0
     */
    protected function add_program_fields_to_filters(&$filteroptions, $langfile = 'totara_program') {

        debugging('add_program_fields_to_filters is deprecated. Please use add_totara_program_filters in \totara_program\rb\source\program_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_totara_program_filters($filteroptions);
    }

    /**
     * Adds the certification table to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     'certif id' field
     * @param string $field Name of table containing program id field to join on
     * @return bool True
     * @deprecated since Totara 12.0
     */
    protected function add_certification_table_to_joinlist(&$joinlist, $join, $field) {

        debugging('add_certification_table_to_joinlist is deprecated. Please use add_totara_certification_tables in \totara_certification\rb\source\certification_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_totara_certification_tables($joinlist, $join, $field);
    }

    /**
     * Adds some common certification info to the $columnoptions array
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $join Name of the join that provides the 'program' table
     * @param string $langfile Source for translation, totara_program or totara_certification
     * @return bool True
     * @deprecated since Totara 12.0
     */
    protected function add_certification_fields_to_columns(&$columnoptions, $join = 'certif', $langfile = 'totara_certification') {

        debugging('add_certification_fields_to_columns is deprecated. Please use add_totara_certification_columns in \totara_certification\rb\source\certification_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_totara_certification_columns($columnoptions, $join);
    }

    /**
     * Adds some common certification filters to the $filteroptions array
     *
     * @param array &$filteroptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $langfile Source for translation, totara_program or totara_certification
     * @return bool True
     * @deprecated since Totara 12.0
     */
    protected function add_certification_fields_to_filters(&$filteroptions, $langfile = 'totara_certification') {

        debugging('add_certification_fields_to_filters is deprecated. Please use add_totara_certification_filters in \totara_certification\rb\source\certification_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_totara_certification_filters($filteroptions);
    }


    /**
     * Adds the course_category table to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include course_category
     * @param string $join Name of the join that provides the 'course' table
     * @param string $field Name of category id field to join on
     * @return boolean True
     * @deprecated since Totara 12.0
     */
    protected function add_course_category_table_to_joinlist(&$joinlist, $join, $field) {

        debugging('add_course_category_table_to_joinlist is deprecated. Please use add_core_course_category_tables in \core_course\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_core_course_category_tables($joinlist, $join, $field);
    }


    /**
     * Adds some common course category info to the $columnoptions array
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $catjoin Name of the join that provides the
     *                        'course_categories' table
     * @param string $coursejoin Name of the join that provides the
     *                           'course' table
     * @return True
     * @deprecated since Totara 12.0
     */
    protected function add_course_category_fields_to_columns(&$columnoptions, $catjoin='course_category', $coursejoin='course', $column='coursecount') {

        debugging('add_course_category_fields_to_columns is deprecated. Please use add_core_course_category_columns in \core_course\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_core_course_category_columns($columnoptions, $catjoin, $coursejoin, $column);
    }


    /**
     * Adds some common course category filters to the $filteroptions array
     *
     * @param array &$columnoptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     * @return True
     * @deprecated since Totara 12.0
     */
    protected function add_course_category_fields_to_filters(&$filteroptions) {

        debugging('add_course_category_fields_to_filters is deprecated. Please use add_core_course_category_filters in \core_course\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_core_course_category_filters($filteroptions);
    }

    /**
     * Adds the job_assignment, pos and org tables to the $joinlist array. All job assignments belonging to the user are returned.
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the 'user' table
     * @param string $field Name of user id field to join on
     * @return boolean True
     * @deprecated since Totara 12.0
     */
    protected function add_job_assignment_tables_to_joinlist(&$joinlist, $join, $field) {

        debugging('add_job_assignment_tables_to_joinlist is deprecated. Please use add_totara_job_tables in \totara_job\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_totara_job_tables($joinlist, $join, $field);
    }

    /**
     * Adds some common user manager info to the $columnoptions array,
     * assumes that the joins from add_job_assignment_tables_to_joinlist
     * have been added to the source.
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     * @return True
     * @deprecated since Totara 12.0
     */
    protected function add_job_assignment_fields_to_columns(&$columnoptions) {

        debugging('add_job_assignment_fields_to_columns is deprecated. Please use add_totara_job_columns in \totara_job\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_totara_job_columns($columnoptions);
    }

    /**
     * Adds some common user position filters to the $filteroptions array
     *
     * @param array &$filteroptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $userjoin Table name to join to which has the user's id
     * @param string $userfield Field name containing the user's id
     * @return True
     * @deprecated since Totara 12.0
     */
    protected function add_job_assignment_fields_to_filters(&$filteroptions, $userjoin = 'auser', $userfield = 'id') {

        debugging('add_job_assignment_fields_to_filters is deprecated. Please use add_totara_job_filters in \totara_job\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_totara_job_filters($filteroptions, $userjoin, $userfield);
    }


    /**
     * Generic function for adding custom fields to the reports
     * Intentionally optimized into one function to reduce number of db queries
     *
     * @deprecated since Totara 12.0
     *
     * @param string $cf_prefix     - prefix for custom field table e.g. everything before '_info_field' or
     *                              '_info_data'
     * @param string $join          - join table in joinlist used as a link to main query
     * @param string $joinfield     - joinfield in data table used to link with main table
     * @param array  $joinlist      - array of joins passed by reference
     * @param array  $columnoptions - array of columnoptions, passed by reference
     * @param array  $filteroptions - array of filters, passed by reference
     * @param string $suffix        - instead of custom_field_{$id}, column name will be custom_field_{$id}{$suffix}.
     *                              Use short prefixes to avoid hiting column size limitations
     * @param bool   $nofilter      - do not create filter for custom fields. It is useful when customfields are
     *                              dynamically added by column generator
     *
     * @return bool
     */
    protected function add_custom_fields_for($cf_prefix, $join, $joinfield,
                                             array &$joinlist, array &$columnoptions, array &$filteroptions, $suffix = '', $nofilter = false) {

        debugging(__FUNCTION__ . ' is deprecated. Please use add_totara_customfield_component in totara_customfield\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_totara_customfield_component($cf_prefix, $join, $joinfield, $joinlist, $columnoptions, $filteroptions, $suffix, $nofilter);
    }


    /**
     * Adds user custom fields to the report.
     *
     * @deprecated since Totara 12.0
     * @param array $joinlist
     * @param array $columnoptions
     * @param array $filteroptions
     * @param string $basejoin
     * @param string $groupname
     * @param bool $addtypetoheading
     * @param bool $nofilter
     * @return boolean
     */
    protected function add_custom_user_fields(array &$joinlist, array &$columnoptions,
                                              array &$filteroptions, $basejoin = 'auser', $groupname = 'user', $addtypetoheading = false, $nofilter = false) {

        debugging(__FUNCTION__ . ' is deprecated. Please use add_core_user_customfield in core_user\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_core_user_customfield($joinlist, $columnoptions, $filteroptions, $basejoin, $groupname, $addtypetoheading, $nofilter);
    }

    /**
     * @deprecated since Totara 12.0
     * @param array  $joinlist
     * @param array  $columnoptions
     * @param array  $filteroptions
     * @param string $basetable
     *
     * @return bool
     * @throws ReportBuilderException
     * @throws coding_exception
     */
    protected function add_custom_evidence_fields(array &$joinlist, array &$columnoptions,
                                                  array &$filteroptions, $basetable = 'dp_plan_evidence') {

        debugging(__FUNCTION__ . ' is deprecated. Please use add_totara_customfield_component in totara_customfield\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_totara_customfield_component('dp_plan_evidence',  $basetable, 'evidenceid', $joinlist, $columnoptions, $filteroptions);
    }

    /**
     * Adds course custom fields to the report
     *
     * @deprecated since Totara 12.0
     * @param array $joinlist
     * @param array $columnoptions
     * @param array $filteroptions
     * @param string $basetable
     * @return boolean
     */
    protected function add_custom_course_fields(array &$joinlist, array &$columnoptions,
                                                array &$filteroptions, $basetable = 'course') {

        debugging(__FUNCTION__ . ' is deprecated. Please use add_totara_customfield_component in totara_customfield\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_totara_customfield_component('course', $basetable, 'courseid', $joinlist, $columnoptions, $filteroptions);
    }

    /**
     * Adds course custom fields to the report
     *
     * @deprecated since Totara 12.0
     * @param array $joinlist
     * @param array $columnoptions
     * @param array $filteroptions
     * @param string $basetable
     * @return boolean
     */
    protected function add_custom_prog_fields(array &$joinlist, array &$columnoptions,
                                              array &$filteroptions, $basetable = 'prog') {

        debugging(__FUNCTION__ . ' is deprecated. Please use add_totara_customfield_component in totara_customfield\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_totara_customfield_component('prog', $basetable, 'programid', $joinlist, $columnoptions, $filteroptions);
    }

    /**
     * Adds custom organisation fields to the report
     *
     * Note: this wont work for users job assignments since they're all grouped.
     * but this would still be good for other base tables like the organisation source.
     *
     * @deprecated since Totara 12.0
     * @param array $joinlist
     * @param array $columnoptions
     * @param array $filteroptions
     * @return boolean
     */
    protected function add_custom_organisation_fields(array &$joinlist, array &$columnoptions,
                                                      array &$filteroptions) {

        debugging(__FUNCTION__ . ' is deprecated. Please use add_totara_customfield_component in totara_customfield\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_totara_customfield_component('org_type', 'organisation', 'organisationid', $joinlist, $columnoptions, $filteroptions);
    }

    /**
     * Adds custom position fields to the report.
     *
     * Note: this wont work for users job assignments since they're all grouped.
     * but this would still be good for other base tables like the organisation source.
     *
     * @deprecated since Totara 12.0
     * @param array $joinlist
     * @param array $columnoptions
     * @param array $filteroptions
     * @return boolean
     */
    protected function add_custom_position_fields(array &$joinlist, array &$columnoptions,
                                                  array &$filteroptions) {

        debugging(__FUNCTION__ . ' is deprecated. Please use add_totara_customfield_component in totara_customfield\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_totara_customfield_component('pos_type', 'position', 'positionid', $joinlist, $columnoptions, $filteroptions);
    }

    /**
     * Adds custom goal fields to the report
     *
     * @deprecated since Totara 12.0
     * @param array $joinlist
     * @param array $columnoptions
     * @param array $filteroptions
     * @return boolean
     */
    protected function add_custom_goal_fields(array &$joinlist, array &$columnoptions,
                                              array &$filteroptions) {

        debugging(__FUNCTION__ . ' is deprecated. Please use add_totara_customfield_component in totara_customfield\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_totara_customfield_component('goal_type', 'goal', 'goalid', $joinlist, $columnoptions, $filteroptions);
    }

    /**
     * Adds custom personal goal fields to the report
     *
     * @deprecated since Totara 12.0
     * @param array $joinlist
     * @param array $columnoptions
     * @param array $filteroptions
     * @return boolean
     */
    protected function add_custom_personal_goal_fields(array &$joinlist, array &$columnoptions,
                                                       array &$filteroptions) {

        debugging(__FUNCTION__ . ' is deprecated. Please use add_totara_customfield_component in totara_customfield\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_totara_customfield_component('goal_user', 'goal_personal', 'goal_userid', $joinlist, $columnoptions, $filteroptions);
    }

    /**
     * Adds custom competency fields to the report
     *
     * @deprecated since Totara 12.0
     * @param array $joinlist
     * @param array $columnoptions
     * @param array $filteroptions
     * @return boolean
     */
    protected function add_custom_competency_fields(array &$joinlist, array &$columnoptions,
                                                    array &$filteroptions) {

        debugging(__FUNCTION__ . ' is deprecated. Please use add_totara_customfield_component in totara_customfield\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_totara_customfield_component('comp_type', 'competency', 'competencyid', $joinlist, $columnoptions, $filteroptions);
    }

    /**
     * Adds the tag tables to the $joinlist array
     *
     * @param string $component component for the tag
     * @param string $itemtype tag itemtype
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     $type table
     * @param string $field Name of course id field to join on
     * @return boolean True
     * @deprecated since Totara 12.0
     */
    protected function add_core_tag_tables_to_joinlist($component, $itemtype, &$joinlist, $join, $field) {

        debugging('add_core_tag_tables_to_joinlist is deprecated. Please use add_core_tag_tables in \core_tag\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_core_tag_tables($component, $itemtype, $joinlist, $join, $field);
    }

    /**
     * Adds some common tag info to the $columnoptions array
     *
     * @param string $component component for the tag
     * @param string $itemtype tag itemtype
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $tagids name of the join that provides the 'tagids' table.
     * @param string $tagnames name of the join that provides the 'tagnames' table.
     *
     * @return True
     * @deprecated since Totara 12.0
     */
    protected function add_core_tag_fields_to_columns($component, $itemtype, &$columnoptions, $tagids='tagids', $tagnames='tagnames') {

        debugging('add_core_tag_fields_to_columns is deprecated. Please use add_core_tag_columns in \core_tag\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_core_tag_columns($component, $itemtype, $columnoptions, $tagids, $tagnames);
    }

    /**
     * Adds some common tag filters to the $filteroptions array
     *
     * @param string $component component for the tag
     * @param string $itemtype tag itemtype
     * @param array &$filteroptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     * @return True
     * @deprecated since Totara 12.0
     */
    protected function add_core_tag_fields_to_filters($component, $itemtype, &$filteroptions) {

        debugging('add_core_tag_fields_to_filters is deprecated. Please use add_core_tag_filters in \core_tag\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_core_tag_filters($component, $itemtype, $filteroptions);
    }

    /**
     * Adds the cohort user tables to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     'user' table
     * @param string $field Name of user id field to join on
     * @return boolean True
     * @deprecated since Totara 12.0
     */
    protected function add_cohort_user_tables_to_joinlist(&$joinlist, $join, $field, $alias = 'ausercohort') {

        debugging('add_cohort_user_tables_to_joinlist is deprecated, do not use it any more.', DEBUG_DEVELOPER);

        return true;
    }

    /**
     * Adds the cohort course tables to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     'course' table
     * @param string $field Name of course id field to join on
     * @return boolean True
     * @deprecated since Totara 12.0
     */
    protected function add_cohort_course_tables_to_joinlist(&$joinlist, $join, $field) {

        debugging('add_cohort_course_tables_to_joinlist is deprecated. Please use add_totara_cohort_course_tables in \totara_cohort\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_totara_cohort_course_tables($joinlist, $join, $field);
    }


    /**
     * Adds the cohort program tables to the $joinlist array
     *
     * @param array &$joinlist Array of current join options
     *                         Passed by reference and updated to
     *                         include new table joins
     * @param string $join Name of the join that provides the
     *                     table containing the program id
     * @param string $field Name of program id field to join on
     * @return boolean True
     * @deprecated since Totara 12.0
     */
    protected function add_cohort_program_tables_to_joinlist(&$joinlist, $join, $field) {

        debugging('add_cohort_program_tables_to_joinlist is deprecated. Please use add_totara_cohort_program_tables in \totara_cohort\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_totara_cohort_program_tables($joinlist, $join, $field);
    }


    /**
     * Adds some common cohort user info to the $columnoptions array
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $join Name of the join that provides the
     *                          'cohortuser' table.
     *
     * @return True
     * @deprecated since Totara 12.0
     */
    protected function add_cohort_user_fields_to_columns(&$columnoptions, $join='ausercohort', $groupname = 'user', $addtypetoheading = false) {

        debugging('add_cohort_user_fields_to_columns is deprecated, do not use it any more.', DEBUG_DEVELOPER);

        return true;
    }


    /**
     * Adds some common cohort course info to the $columnoptions array
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $cohortenrolledids Name of the join that provides the
     *                          'cohortenrolledcourse' table.
     *
     * @return True
     * @deprecated since Totara 12.0
     */
    protected function add_cohort_course_fields_to_columns(&$columnoptions, $cohortenrolledids='cohortenrolledcourse') {

        debugging('add_cohort_course_fields_to_columns is deprecated. Please use add_totara_cohort_course_columns in \totara_cohort\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_totara_cohort_course_columns($columnoptions, $cohortenrolledids);
    }


    /**
     * Adds some common cohort program info to the $columnoptions array
     *
     * @param array &$columnoptions Array of current column options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $cohortenrolledids Name of the join that provides the
     *                          'cohortenrolledprogram' table.
     *
     * @return True
     * @deprecated since Totara 12.0
     */
    protected function add_cohort_program_fields_to_columns(&$columnoptions, $cohortenrolledids='cohortenrolledprogram') {

        debugging('add_cohort_program_fields_to_columns is deprecated. Please use add_totara_cohort_program_columns in \totara_cohort\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_totara_cohort_program_columns($columnoptions, $cohortenrolledids);
    }

    /**
     * Adds some common user cohort filters to the $filteroptions array
     *
     * @param array &$columnoptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     * @return True
     * @deprecated since Totara 12.0
     */
    protected function add_cohort_user_fields_to_filters(&$filteroptions, $groupname = 'user', $addtypetoheading = false) {

        debugging('add_cohort_user_fields_to_filters is deprecated, do not use it any more.', DEBUG_DEVELOPER);

        return true;
    }

    /**
     * Adds some common course cohort filters to the $filteroptions array
     *
     * @param array &$columnoptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     * @return True
     * @deprecated since Totara 12.0
     */
    protected function add_cohort_course_fields_to_filters(&$filteroptions) {

        debugging('add_cohort_course_fields_to_filters is deprecated. Please use add_totara_cohort_course_filters in \totara_cohort\rb\source\report_trait instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_totara_cohort_course_filters($filteroptions);
    }


    /**
     * Adds some common program cohort filters to the $filteroptions array
     *
     * @param array &$columnoptions Array of current filter options
     *                              Passed by reference and updated by
     *                              this method
     * @param string $langfile Source for translation, totara_program or totara_certification
     *
     * @return True
     * @deprecated since Totara 12.0
     */
    protected function add_cohort_program_fields_to_filters(&$filteroptions, $langfile) {

        debugging('add_cohort_program_fields_to_filters is deprecated. Please use add_totara_cohort_program_filters in \totara_cohort\rb\source\report_trait instead instead', DEBUG_DEVELOPER);

        $trait = $this->get_bc_trait_instance();
        return $trait->add_totara_cohort_program_filters($filteroptions, $langfile);
    }

    /**
     * Populate the hierarchymap private variable to look up Hierarchy names from ids
     * e.g. when converting a hierarchy path from ids to human-readable form
     *
     * @param array $hierarchies array of all the hierarchy types we want to populate (pos, org, comp, goal etc)
     *
     * @return boolean True
     * @deprecated since Totara 12.0
     */
    function populate_hierarchy_name_map($hierarchies) {
        global $DB;

        debugging('populate_hierarchy_name_map is deprecated, use display caches instead', DEBUG_DEVELOPER);

        foreach ($hierarchies as $hierarchy) {
            $this->hierarchymap["{$hierarchy}"] = $DB->get_records_menu($hierarchy, null, 'id', 'id, fullname');
        }
        return true;
    }
}
