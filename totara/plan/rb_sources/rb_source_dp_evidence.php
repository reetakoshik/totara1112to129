<?php
/*
 *
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
 *
 * @author Simon Coggins <simonc@catalyst.net.nz>
 * @author Russell England <russell.england@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/plan/lib.php');
require_once($CFG->dirroot . '/totara/plan/record/evidence/lib.php');

/**
 * A report builder source for DP Evidence
 */
class rb_source_dp_evidence extends rb_base_source {
    use \totara_job\rb\source\report_trait;

    private $dp_plans = array();

    /**
     * Constructor
     * @global object $CFG
     */
    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $global_restriction_join_e = $this->get_global_report_restriction_join('e', 'userid');

        $sql="
            (SELECT
                e.id,
                e.name,
                e.userid,
                e.readonly,
                e.timecreated,
                e.timemodified,
                e.evidencetypeid,
                et.name AS evidencetypename,
                CASE
                    WHEN linkedevidence.count IS NULL THEN 0
                    ELSE linkedevidence.count
                END AS evidenceinuse
            FROM {dp_plan_evidence} e
            {$global_restriction_join_e}
            LEFT JOIN {dp_evidence_type} et ON et.id = e.evidencetypeid
            LEFT JOIN
                (SELECT er.evidenceid,
                        COUNT(*) AS count
                FROM {dp_plan_evidence_relation} er
                GROUP BY er.evidenceid) linkedevidence ON linkedevidence.evidenceid = e.id)";

        $this->base = $sql;
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = array();
        $this->requiredcolumns = array();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_dp_evidence');
        $this->sourcewhere = $this->define_sourcewhere();
        $this->sourcejoins = $this->define_sourcejoins();
        $this->usedcomponents[] = 'totara_plan';
        parent::__construct();
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
    }

    protected function define_sourcejoins() {
        return array('auser');
    }

    protected function define_sourcewhere() {
        return ' (auser.deleted = 0) ';
    }

    /**
     * Creates the array of rb_join objects required for this->joinlist
     *
     * @global object $CFG
     * @return array
     */
    private function define_joinlist() {
        global $CFG;

        // to get access to position type constants
        require_once($CFG->dirroot . '/totara/reportbuilder/classes/rb_join.php');

        $joinlist = array();
        $joinlist[] = new rb_join(
            'dp_plan_evidence',
            'LEFT',
            '{dp_plan_evidence}',
            'dp_plan_evidence.id = base.id',
            REPORT_BUILDER_RELATION_ONE_TO_ONE
        );

        $this->add_core_user_tables($joinlist, 'base', 'userid');
        $this->add_totara_job_tables($joinlist, 'base', 'userid');

        return $joinlist;
    }

    /**
     * Creates the array of rb_column_option objects required for
     * $this->columnoptions
     *
     * @return array
     */
    protected function define_columnoptions() {
        $columnoptions = array();

        $columnoptions[] = new rb_column_option(
                'evidence',
                'name',
                get_string('name', 'rb_source_dp_evidence'),
                'base.name',
                array(
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string')
        );

        $columnoptions[] = new rb_column_option(
                'evidence',
                'namelink',
                get_string('namelink', 'rb_source_dp_evidence'),
                'base.name',
                array(
                    'defaultheading' => get_string('name'),
                    'displayfunc' => 'plan_evidence_name_link',
                    'extrafields' => array(
                        'evidence_id' => 'base.id',
                    ),
                )
        );

        $columnoptions[] = new rb_column_option(
            'evidence',
            'timecreated',
            get_string('timecreated', 'rb_source_dp_evidence'),
            'base.timecreated',
            array(
                'displayfunc' => 'nice_datetime'
            )
        );

        $columnoptions[] = new rb_column_option(
            'evidence',
            'timemodified',
            get_string('timemodified', 'rb_source_dp_evidence'),
            'base.timemodified',
            array(
                'displayfunc' => 'nice_datetime'
            )
        );

        $columnoptions[] = new rb_column_option(
            'evidence',
            'viewevidencelink',
            get_string('viewevidencelink', 'rb_source_dp_evidence'),
            'base.name',
            array(
                'defaultheading' => get_string('viewevidence', 'rb_source_dp_evidence'),
                'displayfunc' => 'plan_evidence_view_link',
                'extrafields' => array(
                    'evidence_id' => 'base.id',
                ),
            )
        );

        $columnoptions[] = new rb_column_option(
            'evidence',
            'evidencetypeid',
            get_string('evidencetype', 'rb_source_dp_evidence'),
            'base.evidencetypeid',
            array(
                'hidden' => true,
                'selectable' => false,
            )
        );

        $columnoptions[] = new rb_column_option(
            'evidence',
            'evidencetypename',
            get_string('evidencetype', 'rb_source_dp_evidence'),
            'base.evidencetypename',
            array('dbdatatype' => 'char',
                  'outputformat' => 'text',
                  'displayfunc' => 'format_string')
        );

        $columnoptions[] = new rb_column_option(
            'evidence',
            'evidenceinuse',
            get_string('evidenceinuse', 'rb_source_dp_evidence'),
            'base.evidenceinuse',
            array('displayfunc' => 'plan_evidence_in_use')
        );

        $columnoptions[] = new rb_column_option(
            'evidence',
            'actionlinks',
            get_string('actionlinks', 'rb_source_dp_evidence'),
            'base.id',
            array(
                'displayfunc' => 'plan_evidence_action_links',
                'extrafields' => array(
                    'userid' => 'base.userid',
                    'readonly' => 'base.readonly',
                ),
                'noexport' => true,
                'nosort' => true,
            )
        );

        $this->add_core_user_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);

        return $columnoptions;
    }

    /**
     * Creates the array of rb_filter_option objects required for $this->filteroptions
     * @return array
     */
    protected function define_filteroptions() {
        $filteroptions = array();

        $filteroptions[] = new rb_filter_option(
                'evidence',
                'name',
                get_string('evidencename', 'rb_source_dp_evidence'),
                'text'
        );

        $filteroptions[] = new rb_filter_option(
                'evidence',
                'evidencetypeid',
                get_string('evidencetype', 'rb_source_dp_evidence'),
                'select',
                array(
                    'selectfunc' => 'evidencetypes',
                )
        );

        $filteroptions[] = new rb_filter_option(
            'evidence',
            'timecreated',
            get_string('timecreated', 'rb_source_dp_evidence'),
            'date'
        );

        $filteroptions[] = new rb_filter_option(
            'evidence',
            'timemodified',
            get_string('timemodified', 'rb_source_dp_evidence'),
            'date'
        );

        $this->add_core_user_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions, 'base', 'userid');

        return $filteroptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'user',
                'value' => 'fullname',
            ),
            array(
                'type' => 'evidence',
                'value' => 'namelink',
            )
        );
        return $defaultcolumns;
    }

    /**
     * Creates the array of rb_content_option object required for $this->contentoptions
     * @return array
     */
    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        return $contentoptions;
    }

    protected function define_paramoptions() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/plan/lib.php');
        $paramoptions = array();

        $paramoptions[] = new rb_param_option(
                'userid',
                'base.userid',
                'base'
        );

        return $paramoptions;
    }

    /**
     * Generate the evidence link to the details page
     *
     * @deprecated Since Totara 12.0
     * @param string $evidence evidence name
     * @param object $row Object containing other fields
     * @return string
     */
    public function rb_display_viewevidencelink($evidence, $row) {
        debugging('rb_source_dp_evidence::rb_display_viewevidencelink has been deprecated since Totara 12.0. Use totara_plan\rb\display\plan_evidence_view_link::display', DEBUG_DEVELOPER);
        $url = new moodle_url('/totara/plan/record/evidence/view.php', array('id' => $row->evidence_id ));
        return html_writer::link($url, get_string('viewevidence', 'rb_source_dp_evidence'));
    }

    /**
     * Generate the evidence name with a link to the evidence details page
     *
     * @deprecated Since Totara 12.0
     * @global object $CFG
     * @param string $evidence evidence name
     * @param object $row Object containing other fields
     * @return string
     */
    public function rb_display_evidenceview($evidencename, $row, $isexport) {
        debugging('rb_source_dp_evidence::rb_display_evidenceview has been deprecated since Totara 12.0. Use totara_plan\rb\display\plan_evidence_name_link::display', DEBUG_DEVELOPER);
        if ($isexport) {
            return $evidencename;
        } else {
            $url = new moodle_url('/totara/plan/record/evidence/view.php', array('id' => $row->evidence_id ));
            $evidencename = empty($evidencename) ? '(' .get_string('viewevidence', 'rb_source_dp_evidence') . ')' : $evidencename;
            return html_writer::link($url, $evidencename);
        }
    }

    /**
     * Displays evidence name as html link
     *
     * @deprecated Since Totara 12.0
     * @param string $evidencelink
     * @param object Report row $row
     * @return string html link
     */
    public function rb_display_evidencelink($evidencelink, $row) {
        debugging('rb_source_dp_evidence::rb_display_evidencelink has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        global $OUTPUT;
        if (empty($evidencelink)) {
            return '';
        }
        return $OUTPUT->action_link(new moodle_url($evidencelink), $evidencelink);
    }

    /**
     * Display action links
     *
     * @deprecated Since Totara 12.0
     * @param $evidenceid
     * @param $row
     * @return string
     */
    public function rb_display_actionlinks($evidenceid, $row) {
        debugging('rb_source_dp_evidence::rb_display_actionlinks has been deprecated since Totara 12.0. Use totara_plan\rb\display\plan_evidence_action_links::display', DEBUG_DEVELOPER);
        global $USER, $OUTPUT;

        $out = '';

        if (can_create_or_edit_evidence($row->userid, !empty($evidenceid), $row->readonly)) {
            $out .= $OUTPUT->action_icon(
                        new moodle_url('/totara/plan/record/evidence/edit.php',
                                array('id' => $evidenceid, 'userid' => $row->userid)),
                        new pix_icon('t/edit', get_string('edit')));

            $out .= $OUTPUT->spacer(array('width' => 11, 'height' => 11, 'class' => 'iconsmall action-icon'));

            $out .= $OUTPUT->action_icon(
                        new moodle_url('/totara/plan/record/evidence/edit.php',
                                array('id' => $evidenceid, 'userid' => $row->userid, 'd' => '1')),
                        new pix_icon('t/delete', get_string('delete')));
        } else if ($row->readonly) {
            $out .= get_string('evidence_readonly', 'totara_plan');
        }

        return $out;
    }

    /**
     * Display evidence in use
     *
     * @deprecated Since Totara 12.0
     * @param $evidenceinuse
     * @param $row
     * @return string
     */
    public function rb_display_evidenceinuse($evidenceinuse, $row) {
        debugging('rb_source_dp_evidence::rb_display_evidenceinuse has been deprecated since Totara 12.0. Use totara_plan\rb\display\plan_evidence_in_use::display', DEBUG_DEVELOPER);
        return (empty($evidenceinuse)) ? get_string('no') : get_string('yes');
    }

    /**
     * Display evidence description
     *
     * @deprecated Since Totara 12.0
     * @param $description
     * @param $row
     * @return string
     */
    public function rb_display_description($description, $row) {
        debugging('rb_source_dp_evidence::rb_display_description has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        $description = file_rewrite_pluginfile_urls($description, 'pluginfile.php',
                context_system::instance()->id, 'totara_plan', 'dp_plan_evidence', $row->evidence_id );
        return(format_text($description, FORMAT_HTML));
    }

    public function rb_filter_evidencetypes() {
        global $DB;

        $types = $DB->get_records('dp_evidence_type', null, 'sortorder', 'id, name');
        $list = array();
        foreach ($types as $type) {
            $list[$type->id] = $type->name;
        }
        return $list;
    }

    /**
     * Check if the report source is disabled and should be ignored.
     *
     * @return boolean If the report should be ignored of not.
     */
    public static function is_source_ignored() {
        return !totara_feature_visible('recordoflearning');
    }
}
