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

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/facetoface/rb_sources/rb_facetoface_base_source.php');
require_once($CFG->dirroot . '/totara/customfield/field/location/define.class.php');

class rb_source_facetoface_asset extends rb_facetoface_base_source
{
    /**
     * Url of embedded report required for certain actions
     * @var string
     */
    protected $embeddedurl = '';

    /**
     * Report url params to pass through during actions
     * @var array
     */
    protected $urlparams = array();

    public function __construct(rb_global_restriction_set $globalrestrictionset = null) {

        $this->base = '{facetoface_asset}';
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_facetoface_asset');
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->paramoptions = $this->define_paramoptions();
        $this->add_customfields();

        parent::__construct();
    }

    protected function define_joinlist() {
        $joinlist = array();

        $joinlist[] = new rb_join(
            'assetdates',
            'LEFT',
            '{facetoface_asset_dates}',
            'assetdates.assetid = base.id',
            REPORT_BUILDER_RELATION_ONE_TO_MANY
        );

        $joinlist[] = new rb_join(
            'sessiondate',
            'LEFT',
            '{facetoface_sessions_dates}',
            'sessiondate.id = assetdates.sessionsdateid',
            REPORT_BUILDER_RELATION_ONE_TO_MANY
        );

        $joinlist[] = new rb_join(
            'assigned',
            'LEFT',
            '(SELECT assetid, COUNT(*) AS cntdates
              FROM {facetoface_asset_dates} fad
              INNER JOIN {facetoface_sessions_dates} fsd ON (fad.sessionsdateid = fsd.id)
              GROUP BY assetid)',
            'assigned.assetid = base.id',
            REPORT_BUILDER_RELATION_ONE_TO_MANY
        );


        return $joinlist;
    }

    protected function define_columnoptions() {
        $columnoptions = array();

        $this->add_assets_fields_to_columns($columnoptions, 'base');

        $columnoptions[] = new rb_column_option(
                'asset',
                'actions',
                get_string('actions'),
                'base.id',
                array(
                    'noexport' => true,
                    'nosort' => true,
                    'joins' => 'assigned',
                    'capability' => 'totara/core:modconfig',
                    'extrafields' => array('hidden' => 'base.hidden', 'cntdates' => 'assigned.cntdates'),
                    'displayfunc' => 'f2f_asset_actions',
                    'hidden' => false
                )
        );

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array();

        $this->add_assets_fields_to_filters($filteroptions);
        $filteroptions[] = new rb_filter_option(
            'asset',
            'assetavailable',
            get_string('assetavailable', 'rb_source_facetoface_asset'),
            'f2f_assetavailable',
            array(),
            'base.id'
        );

        return $filteroptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'asset',
                'value' => 'namelink'
            ),
            array(
                'type' => 'asset',
                'value' => 'description'
            ),
            array(
                'type' => 'asset',
                'value' => 'visible'
            ),
            array(
                'type' => 'asset',
                'value' => 'allowconflicts'
            )
        );

        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'asset',
                'value' => 'name'
            )
        );

        return $defaultfilters;
    }

    protected function define_paramoptions() {
        // this is where you set your hardcoded filters
        $paramoptions = array(
            new rb_param_option('custom', 'base.custom')
        );

        return $paramoptions;
    }

    protected function add_customfields() {
        $this->add_totara_customfield_component(
            'facetoface_asset',
            'base',
            'facetofaceassetid',
            $this->joinlist,
            $this->columnoptions,
            $this->filteroptions
        );
    }

    /**
     * Get the embeddedurl
     *
     * @return string
     */
    public function get_embeddedurl() {
        return $this->embeddedurl;
    }

    /**
     * Get the url params
     *
     * @return mixed
     */
    public function get_urlparams() {
        return $this->urlparams;
    }

    /**
     * Asset actions
     *
     * @deprecated Since Totara 12.0
     * @param int $assetid
     * @param stdClass $row
     * @param bool $isexport
     */
    public function rb_display_actions($assetid, $row, $isexport = false) {
        debugging('rb_source_facetoface_asset::rb_display_actions has been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_asset_actions::display', DEBUG_DEVELOPER);
        global $OUTPUT;

        if ($isexport) {
            return null;
        }

        $output = array();

        $output[] = $OUTPUT->action_icon(
            new moodle_url('/mod/facetoface/reports/assets.php', array('assetid' => $assetid)),
            new pix_icon('t/calendar', get_string('details', 'mod_facetoface'))
        );

        $output[] = $OUTPUT->action_icon(
            new moodle_url('/mod/facetoface/asset/edit.php', array('id' => $assetid)),
            new pix_icon('t/edit', get_string('edit'))
        );

        if ($row->hidden && $this->embeddedurl) {
            $params = array_merge($this->urlparams, ['action' => 'show', 'id' => $assetid, 'sesskey' => sesskey()]);
            $output[] = $OUTPUT->action_icon(
                new moodle_url($this->embeddedurl, $params),
                new pix_icon('t/show', get_string('assetshow', 'mod_facetoface'))
            );
        } else if ($this->embeddedurl) {
            $params = array_merge($this->urlparams, ['action' => 'hide', 'id' => $assetid, 'sesskey' => sesskey()]);
            $output[] = $OUTPUT->action_icon(
                new moodle_url($this->embeddedurl, $params),
                new pix_icon('t/hide', get_string('assethide', 'mod_facetoface'))
            );

        }
        if ($row->cntdates) {
            $output[] = $OUTPUT->pix_icon('t/delete_gray', get_string('currentlyassigned', 'mod_facetoface'), 'moodle', array('class' => 'disabled iconsmall'));
        } else {
            $output[] = $OUTPUT->action_icon(
                new moodle_url('/mod/facetoface/asset/manage.php', ['action' => 'delete', 'id' => $assetid]),
                new pix_icon('t/delete', get_string('delete'))
            );
        }

        return implode('', $output);
    }

    public function post_params(reportbuilder $report) {
        $this->embeddedurl = $report->embeddedurl;
        $this->urlparams = $report->get_current_url_params();
    }

    public function global_restrictions_supported() {
        return true;
    }
}
