<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package repository_opensesame
 */

defined('MOODLE_INTERNAL') || die();

class rb_source_opensesame extends rb_base_source {

    public function __construct() {
        $this->base = '{repository_opensesame_pkgs}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = array();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_opensesame');
        $this->usedcomponents[] = 'repository_opensesame';

        parent::__construct();
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return false;
    }

    /**
     * Hide this source if site not registered.
     * @return bool
     */
    public static function is_source_ignored() {
        $key = get_config('repository_opensesame', 'tenantkey');
        return empty($key);
    }

    protected function define_joinlist() {
        $joinlist = array();

        $joinlist[] = new rb_join(
            'bundles',
            'INNER',
            "(SELECT bdls.name, bps.packageid
                FROM {repository_opensesame_bdls} bdls
                JOIN {repository_opensesame_bps} bps ON (bps.bundleid = bdls.id))",
            'base.id = bundles.packageid',
            REPORT_BUILDER_RELATION_MANY_TO_MANY
        );

        return $joinlist;
    }

    protected function define_columnoptions() {
        $columnoptions = array();

        $columnoptions[] = new rb_column_option(
            'opensesame',
            'title',
            get_string('metatitle', 'repository_opensesame'),
            'base.title',
            array(
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'displayfunc' => 'opensesame_course_title',
                'extrafields' => array('itemid' => 'base.id', 'zipfilename' => 'base.zipfilename', 'packageid' => 'base.id'),
            )
        );

        $columnoptions[] = new rb_column_option(
            'opensesame',
            'visible',
            get_string('packagevisible', 'repository_opensesame'),
            'base.visible',
            array(
                'dbdatatype' => 'boolean',
                'displayfunc' => 'opensesame_visibility',
                'extrafields' => array('itemid' => 'base.id'),
            )
        );

        $columnoptions[] = new rb_column_option(
            'opensesame',
            'expirationdate',
            get_string('metaexpirationdate', 'repository_opensesame'),
            'base.expirationdate',
            array(
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp',
            )
        );

        $columnoptions[] = new rb_column_option(
            'opensesame',
            'bundlename',
            get_string('metabundlename', 'repository_opensesame'),
            'bundles.name',
            array(
                'joins' => 'bundles',
                'dbdatatype' => 'char',
                'outputformat' => 'text',
            )
        );

        $columnoptions[] = new rb_column_option(
            'opensesame',
            'mobilecompatibility',
            get_string('metamobilecompatibility', 'repository_opensesame'),
            'base.mobilecompatibility',
            array(
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'displayfunc' => 'opensesame_mobile_compatibility',
            )
        );

        $columnoptions[] = new rb_column_option(
            'opensesame',
            'externalid',
            get_string('metaexternalid', 'repository_opensesame'),
            'base.externalid',
            array(
                'dbdatatype' => 'char',
                'outputformat' => 'text',
            )
        );

        $columnoptions[] = new rb_column_option(
            'opensesame',
            'description',
            get_string('metadescription', 'repository_opensesame'),
            'base.description',
            array(
                'displayfunc' => 'opensesame_shortdesc',
                'dbdatatype' => 'text',
            )
        );

        $columnoptions[] = new rb_column_option(
            'opensesame',
            'duration',
            get_string('metaduration', 'repository_opensesame'),
            'base.duration',
            array(
                'displayfunc' => 'duration',
                'dbdatatype' => 'char',
            )
        );

        $columnoptions[] = new rb_column_option(
            'opensesame',
            'timecreated',
            get_string('timeadded', 'repository_opensesame'),
            'base.timecreated',
            array(
                'displayfunc' => 'nice_datetime',
                'dbdatatype' => 'timestamp',
            )
        );

        return $columnoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array('type' => 'opensesame', 'value' => 'bundlename'),
            array('type' => 'opensesame', 'value' => 'title'),
            array('type' => 'opensesame', 'value' => 'mobilecompatibility'),
            array('type' => 'opensesame', 'value' => 'expirationdate'),
            array('type' => 'opensesame', 'value' => 'timecreated'),
        );
        return $defaultcolumns;
    }

    protected function define_filteroptions() {
        $filteroptions = array();

        $filteroptions[] = new rb_filter_option(
            'opensesame',
            'bundlename',
            get_string('metabundlename', 'repository_opensesame'),
            'text'
        );

        $filteroptions[] = new rb_filter_option(
            'opensesame',
            'title',
            get_string('metatitle', 'repository_opensesame'),
            'text'
        );

        $filteroptions[] = new rb_filter_option(
            'opensesame',
            'mobilecompatibility',
            get_string('metamobilecompatibility', 'repository_opensesame'),
            'select',
            array(
                'selectfunc' => 'mobile_compatibility',
                'attributes' => rb_filter_option::select_width_limiter(),
            )
        );

        $filteroptions[] = new rb_filter_option(
            'opensesame',
            'description',
            get_string('metadescription', 'repository_opensesame'),
            'text'
        );

        $filteroptions[] = new rb_filter_option(
            'opensesame',
            'duration',
            get_string('metaduration', 'repository_opensesame'),
            'text'
        );

        $filteroptions[] = new rb_filter_option(
            'opensesame',
            'visible',
            get_string('packagevisible', 'repository_opensesame'),
            'select',
            array(
                'selectchoices' => array(0 => get_string('no'), 1 => get_string('yes')),
                'simplemode' => true
            )
        );

        return $filteroptions;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'opensesame',
                'value' => 'bundlename',
                'advanced' => 0,
            ),
            array(
                'type' => 'opensesame',
                'value' => 'title',
                'advanced' => 0,
            ),
            array(
                'type' => 'opensesame',
                'value' => 'mobilecompatibility',
                'advanced' => 0,
            )
        );
        return $defaultfilters;
    }

    /**
     * Display course title with create course icon
     *
     * @deprecated Since Totara 12.0
     * @param $value
     * @param $row
     * @param bool $isexport
     * @return string
     */
    public function rb_display_coursetitle($value, $row, $isexport = false) {
        debugging('rb_source_opensesame::rb_display_coursetitle has been deprecated since Totara 12.0. Use repository_opensesame\rb\display\opensesame_course_title::display', DEBUG_DEVELOPER);
        global $OUTPUT;

        if ($isexport) {
            return $value;
        }
        $return = $value;
        $syscontext = context_system::instance();
        if (!has_capability('repository/opensesame:managepackages', $syscontext)) {
            return $value;
        }

        $createurl = new moodle_url('/repository/opensesame/create_course.php', array('id' => $row->packageid));
        $icon = new pix_icon('t/add', get_string('createcourse', 'totara_program'));
        $return .= ' ' . $OUTPUT->action_icon($createurl, $icon);

        /*
        // NOTE: Most likely downloading is not necessary and would only confuse users.
        $downloadurl = moodle_url::make_pluginfile_url($syscontext->id, 'repository_opensesame', 'packages', $row->itemid, '/', $row->zipfilename);
        $icon = new pix_icon('t/download', get_string('download'));
        $return .= $OUTPUT->action_icon($downloadurl, $icon);
        */

        return $return;
    }

    /**
     * Display visibility
     *
     * @deprecated Since Totara 12.0
     * @param $value
     * @param $row
     * @param bool $isexport
     * @return string
     */
    public function rb_display_visibility($value, $row, $isexport = false) {
        debugging('rb_source_opensesame::rb_display_visibility has been deprecated since Totara 12.0. Use repository_opensesame\rb\display\opensesame_visibility::display', DEBUG_DEVELOPER);
        global $OUTPUT;

        // This is very nasty, but I know no better way for 2.6!
        global $report;
        if (!$isexport and get_class($report) === 'reportbuilder') {
            if (empty($report->embedded)) {
                $reportid = $report->_id;
            } else {
                $reportid = 0;
            }
            $params = array('id' => $row->itemid, 'reportid' => $reportid, 'sesskey' => sesskey());
            if ($value) {
                $icon = new pix_icon('t/hide', get_string('hide'));
                $params['visible'] = 0;
            } else {
                $icon = new pix_icon('t/show', get_string('show'));
                $params['visible'] = 1;
            }

            $url = new moodle_url('/repository/opensesame/toggle_visible.php', $params);
            return $OUTPUT->action_icon($url, $icon);
        }

        if ($value) {
            return get_string('yes');
        } else {
            return get_string('no');
        }
    }

    /**
     * Display mobile compatibility
     *
     * @deprecated Since Totara 12.0
     * @param $value
     * @param $row
     * @param bool $isexport
     * @return mixed|string
     */
    public function rb_display_mobilecompatibility($value, $row, $isexport = false) {
        debugging('rb_source_opensesame::rb_display_mobilecompatibility has been deprecated since Totara 12.0. Use repository_opensesame\rb\display\opensesame_mobile_compatibility::display', DEBUG_DEVELOPER);
        $mobileoptions = array(
            'allDevices' => get_string('metamobilecompatibilityall', 'repository_opensesame'),
            'ios' => get_string('metamobilecompatibilityios', 'repository_opensesame'),
            'android' => get_string('metamobilecompatibilityandroid', 'repository_opensesame'),
        );

        if (isset($mobileoptions[$value])) {
            return $mobileoptions[$value];
        }
        return '';
    }

    /**
     * Display description
     *
     * @deprecated Since Totara 12.0
     * @param $value
     * @param $row
     * @param bool $isexport
     * @return string
     */
    public function rb_display_shortdesc($value, $row, $isexport = false) {
        debugging('rb_source_opensesame::rb_display_shortdesc has been deprecated since Totara 12.0. Use repository_opensesame\rb\display\opensesame_shortdesc::display', DEBUG_DEVELOPER);
        if ($isexport) {
            return $value;
        }
        $value = text_to_html($value, true, true, true);
        return $value;
    }

    /**
     * Display duration
     *
     * @deprecated Since Totara 12.0
     * @param int $value
     * @param stdClass $row
     * @param bool $isexport
     * @return int
     */
    public function rb_display_duration($value, $row, $isexport = false) {
        debugging('rb_source_opensesame::rb_display_duration has been deprecated since Totara 12.0', DEBUG_DEVELOPER);
        return $value;
    }

    function rb_filter_mobile_compatibility() {
        return array(
            'allDevices' => get_string('metamobilecompatibilityall', 'repository_opensesame'),
            'ios' => get_string('metamobilecompatibilityios', 'repository_opensesame'),
            'android' => get_string('metamobilecompatibilityandroid', 'repository_opensesame'),
            //'' => get_string('metamobilecompatibilitynone', 'repository_opensesame'),
        );
    }
}
