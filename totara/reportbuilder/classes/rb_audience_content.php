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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package totara_reportbuilder
 */

/*
 * Restrict the report content by a particular audience
 */
final class rb_audience_content extends rb_base_content {

    /**
     * Generate the SQL to apply this content restriction
     *
     * @param string  $field    SQL field to apply the restriction against
     * @param integer $reportid ID of the report
     *
     * @return array containing SQL snippet to be used in a WHERE clause, as well as array of SQL params
     */
    public function sql_restriction($field, $reportid) {

        $type = $this->get_type();
        $settings = reportbuilder::get_all_settings($reportid, $type);
        $cohortid = $settings['audience'] ?? 0;

        $restriction = " 1=1 ";
        $params = [];

        if (!empty($cohortid)) {
            $restriction = "(EXISTS (SELECT 1
                                       FROM {cohort_members} cm
                                       WHERE 
                                         cm.userid = {$field} 
                                         AND cm.cohortid = :audiencecontentaudienceid
                                     ))";
            $params = ['audiencecontentaudienceid' => $cohortid];
        }

        return [$restriction, $params];
    }

    /**
     * Generate a human-readable text string describing the restriction
     *
     * @param string  $title    Name of the field being restricted
     * @param integer $reportid ID of the report
     *
     * @return string Human readable description of the restriction
     */
    public function text_restriction($title, $reportid) {
        return get_string('reportbuilderaudience', 'totara_reportbuilder');
    }

    /**
     * Adds form elements required for this content restriction's settings page
     *
     * @param object  &$mform   Moodle form object to modify (passed by reference)
     * @param integer $reportid ID of the report being adjusted
     * @param string  $title    Name of the field the restriction is acting on
     */
    public function form_template(&$mform, $reportid, $title) {
        global $OUTPUT;

        $mform->addElement(
            'header', 'user_audience_header',
            get_string('reportbuilderaudience', 'totara_reportbuilder')
        );
        $mform->setExpanded('user_audience_header');

        if ($this->has_audience_capability()) {
            $type = $this->get_type();
            $cohortid = reportbuilder::get_setting($reportid, $type, 'audience');
            $enable = reportbuilder::get_setting($reportid, $type, 'enable');

            $mform->addElement(
                'checkbox', 'user_audience_enable', '',
                get_string('reportbuilderaudience', 'totara_reportbuilder')
            );
            $mform->setDefault('user_audience_enable', $enable);
            $mform->addElement(
                'select', 'user_audience',
                get_string('includerecordsfrom', 'totara_reportbuilder'),
                self::get_cohort_select_options()
            );
            $mform->setDefault('user_audience', $cohortid);

            $mform->disabledIf('user_audience', 'user_audience_enable', 'notchecked');
            $mform->disabledIf('user_audience_enable', 'contentenabled', 'eq', 0);
        } else {
            $settings = reportbuilder::get_all_settings($reportid, $this->get_type());
            $cohortid = $settings['audience'] ?? 0;

            $warning = get_string('reportbuilderaudiencenopermission', 'totara_reportbuilder');

            if (!empty($cohortid)) {
                $warning = get_string('reportbuilderaudiencenopermissionapplied', 'totara_reportbuilder');
            }
            $warning = $OUTPUT->notification($warning, 'warning');
            $mform->addElement('html', $warning);
        }

        $mform->addHelpButton('user_audience_header', 'reportbuilderaudience', 'totara_reportbuilder');
    }

    /**
     * Processes the form elements created by {@link form_template()}
     *
     * @param integer $reportid ID of the report to process
     * @param object  $fromform Moodle form data received via form submission
     *
     * @return boolean True if form was successfully processed
     */
    public function form_process($reportid, $fromform) {

        if ($this->has_audience_capability()) {
            $type = $this->get_type();

            $audience = $fromform->user_audience ?? 0;
            $audienceenable = $fromform->user_audience_enable ?? 0;

            if (!empty($audienceenable) && empty($audience)) {
                $link = new moodle_url('/totara/reportbuilder/content.php', ['id' => $reportid]);
                throw new moodle_exception('noaudienceselected', 'totara_reportbuilder', $link);
            }

            reportbuilder::update_setting($reportid, $type, 'audience', $audience);
            reportbuilder::update_setting($reportid, $type, 'enable', $audienceenable);
        }
    }

    /**
     * Check audience capability
     *
     * @return bool
     */
    private function has_audience_capability(): bool {
        return has_capability("moodle/cohort:view", context_system::instance());
    }

    /**
     * @return string
     */
    private function get_type(): string {
        // remove rb_ from start of classname
        return substr(get_class($this), 3);
    }

    /**
     * Update default audience restrictions
     *
     * @param int $reportid
     * @param int $cohortid
     */
    public function set_default_restriction(int $reportid, int $cohortid) {
        global $DB;

        $type = $this->get_type();

        // update content mode to ANY criteria
        $report = new stdClass();
        $report->id = $reportid;
        $report->contentmode = REPORT_BUILDER_CONTENT_MODE_ANY;
        $DB->update_record('report_builder', $report);

        // enable audience and update global settings
        reportbuilder::update_setting($reportid, $type, 'audience', $cohortid);
        reportbuilder::update_setting($reportid, $type, 'enable', 1);
    }

    /**
     * get all options for an audience/cohort select box
     *
     * @return array
     */
    public static function get_cohort_select_options(): array {
        global $DB;

        $sql = "SELECT * FROM {cohort} ORDER BY name ASC, idnumber ASC";
        $cohorts = $DB->get_records_sql($sql);

        $options = [];
        foreach ($cohorts as $cohort) {
            $options[$cohort->id] = format_string("{$cohort->name} ({$cohort->idnumber})");
        }

        return [ get_string('no_audience_defined', 'rb_source_user') ] + $options;
    }

}
