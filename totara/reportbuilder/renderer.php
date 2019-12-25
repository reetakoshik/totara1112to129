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
 *
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage totara_reportbuilder
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

/**
* Output renderer for totara_reportbuilder module
*/
class totara_reportbuilder_renderer extends plugin_renderer_base {

    /**
     * Renders a table containing user-generated reports and options
     *
     * @deprecated since Totara 11.12
     *
     * @param array $reports array of report objects
     * @return string HTML table
     */
    public function user_generated_reports_table($reports=array()) {
        global $CFG;

        if (empty($reports)) {
            return get_string('noreports', 'totara_reportbuilder');
        }

        $tableheader = array(get_string('name', 'totara_reportbuilder'),
                             get_string('source', 'totara_reportbuilder'));
        if (!empty($CFG->enableglobalrestrictions)) {
            $tableheader[] = get_string('globalrestriction', 'totara_reportbuilder');
        }
        $tableheader[] = get_string('options', 'totara_reportbuilder');

        $data = array();

        $strsettings = get_string('settings', 'totara_reportbuilder');
        $strclone = get_string('clonereport', 'totara_reportbuilder');
        $strdelete = get_string('delete', 'totara_reportbuilder');
        $stryes = get_string('yes');
        $strno = get_string('no');

        foreach ($reports as $report) {
            try {
                $row = array();
                $viewurl = new moodle_url(reportbuilder_get_report_url($report));
                $editurl = new moodle_url('/totara/reportbuilder/general.php', array('id' => $report->id));
                $deleteurl = new moodle_url('/totara/reportbuilder/index.php', array('id' => $report->id, 'd' => 1));
                $cloneurl = new moodle_url('/totara/reportbuilder/clone.php', array('id' => $report->id));

                $row[] = html_writer::link($editurl, format_string($report->fullname)) . ' (' .
                    html_writer::link($viewurl, get_string('view')) . ')';

                $row[] = $report->sourcetitle;

                if (!empty($CFG->enableglobalrestrictions)) {
                    $grstatus = ''; // Report does not support GUR - do not show anything.
                    if ($report->sourceobject) {
                        if ($report->sourceobject->global_restrictions_supported()) {
                            if ($report->globalrestriction) {
                                $grstatus = $stryes;
                            } else {
                                $grstatus = $strno;
                            }
                        }
                    } else {
                        debugging('Missing $report->sourceobject!', DEBUG_DEVELOPER);
                    }
                    $row[] = $grstatus;
                }

                $settings = $this->output->action_icon($editurl, new pix_icon('/t/edit', $strsettings, 'moodle'), null,
                    array('title' => $strsettings));
                $delete = $this->output->action_icon($deleteurl, new pix_icon('/t/delete', $strdelete, 'moodle'), null,
                    array('title' => $strdelete));
                $cache = '';
                if (!empty($CFG->enablereportcaching) && !empty($report->cache)) {
                    $reportbuilder = reportbuilder::create($report->id);
                    if (empty($reportbuilder->get_caching_problems())) {
                        $cache = $this->cachenow_button($report->id, true);
                    }
                }
                $clone = $this->output->action_icon($cloneurl, new pix_icon('/t/copy', $strclone, 'moodle'), null,
                    array('title' => $strclone));
                $row[] = "{$settings}{$cache}{$clone}{$delete}";

                $data[] = $row;
            } catch (Exception $e) {
                $row = array();
                $deleteurl = new moodle_url('/totara/reportbuilder/index.php', array('id' => $report->id, 'd' => 1));
                $delete = $this->output->action_icon($deleteurl, new pix_icon('/t/delete', $strdelete, 'moodle'));
                $spacer = $this->output->spacer(array('width' => '11', 'height' => '11'));

                $row[] = format_string($report->fullname);
                $row[] = $e->getMessage();
                $row[] = "{$spacer}{$delete}";

                $data[] = $row;
            }
        }

        $reportstable = new html_table();
        $reportstable->summary = '';
        $reportstable->head = $tableheader;
        $reportstable->data = $data;

        return html_writer::table($reportstable);
    }


    /**
     * Renders a table containing embedded reports and options
     *
     * @deprecated since Totara 11.12
     *
     * @param array $reports array of report objects
     * @return string HTML table
     */
    public function embedded_reports_table($reports=array()) {
        global $CFG;

        if (empty($reports)) {
            return get_string('noembeddedreports', 'totara_reportbuilder');
        }

        $tableheader = array(get_string('name', 'totara_reportbuilder'),
                             get_string('source', 'totara_reportbuilder'));
        if (!empty($CFG->enableglobalrestrictions)) {
            $tableheader[] = get_string('globalrestriction', 'totara_reportbuilder');
        }
        $tableheader[] = get_string('options', 'totara_reportbuilder');

        $strsettings = get_string('settings', 'totara_reportbuilder');
        $strreload = get_string('restoredefaults', 'totara_reportbuilder');
        $strclone = get_string('clonereport', 'totara_reportbuilder');

        $embeddedreportstable = new html_table();
        $embeddedreportstable->summary = '';
        $embeddedreportstable->head = $tableheader;
        $embeddedreportstable->data = array();

        $stryes = get_string('yes');
        $strno = get_string('no');
        $data = array();
        foreach ($reports as $report) {
            $fullname = format_string($report->fullname);
            $viewurl = new moodle_url($report->url);
            $editurl = new moodle_url('/totara/reportbuilder/general.php', array('id' => $report->id));
            $reloadurl = new moodle_url('/totara/reportbuilder/index.php', array('id' => $report->id, 'em' => 1, 'd' => 1));
            $cloneurl = new moodle_url('/totara/reportbuilder/clone.php', array('id' => $report->id));

            $row = array();
            $row[] = html_writer::link($editurl, $fullname) . ' (' .
                html_writer::link($viewurl, get_string('view')) . ')';

            $row[] = $report->sourcetitle;

            if (!empty($CFG->enableglobalrestrictions)) {
                $grstatus = ''; // Report does not support GUR - do not show anything.
                if ($report->sourceobject) {
                    if ($report->sourceobject->global_restrictions_supported()) {
                        if ($report->globalrestriction) {
                            $grstatus = $stryes;
                        } else if (!isset($report->embedobj) || $report->embedobj->embedded_global_restrictions_supported()) {
                            $grstatus = $strno;
                        }
                    }
                } else {
                    debugging('Missing $report->sourceobject!', DEBUG_DEVELOPER);
                }
                $row[] = $grstatus;
            }

            $settings = $this->output->action_icon($editurl, new pix_icon('/t/edit', $strsettings, 'moodle'), null,
                    array('title' => $strsettings));
            $reload = $this->output->action_icon($reloadurl, new pix_icon('/t/reload', $strreload, 'moodle'), null,
                    array('title' => $strreload));
            $cache = '';
            if (!empty($CFG->enablereportcaching) && !empty($report->cache)) {
                $reportbuilder = reportbuilder::create($report->id);
                if (empty($reportbuilder->get_caching_problems())) {
                    $cache = $this->cachenow_button($report->id, true);
                }
            }
            $clone = $this->output->action_icon($cloneurl, new pix_icon('/t/copy', $strclone, 'moodle'), null,
                    array('title' => $strclone));
            $row[] = "{$settings}{$reload}{$cache}{$clone}";

            $data[] = $row;
        }
        $embeddedreportstable->data = $data;

        return html_writer::table($embeddedreportstable);
    }

    /**
     * Format records to view or restricted users.
     *
     * @param stdClass $records Records with group properties (cohorts, pos, org, users)
     * @return string $output Formatted records
     */
    public function format_records_to_view($records) {
        $output = '';

        foreach ($records as $group => $entries) {
            $str = new stdClass();
            $str->group = $group;
            $str->entries = implode(', ', $entries);
            if (strlen($str->entries) > 128) {
                $str->entries = strtok(wordwrap($str->entries, 128, "...\n"), "\n");
            }
            $output .= get_string('groupassignlist', 'totara_reportbuilder', $str);
            $output .= html_writer::empty_tag('br');
        }

        return $output;
    }

    /**
     * Renders a table containing global restrictions data
     *
     * @param array $globalrestrictions array of global restrictions objects
     * @return string HTML table
     */
    public function global_restrictions_table($globalrestrictions = array()) {

        if (empty($globalrestrictions)) {
            return get_string('noglobalrestrictionsfound', 'totara_reportbuilder');
        }

        $tableheader = array(get_string('name', 'totara_reportbuilder'),
            get_string('recordstoview', 'totara_reportbuilder'),
            get_string('restrictedusers', 'totara_reportbuilder'),
            get_string('options', 'totara_reportbuilder'));

        $stredit = get_string('edit');
        $strdelete = get_string('delete');
        $strmoveup = get_string('up');
        $strmovedown = get_string('down');

        $table = new html_table();
        $table->summary = '';
        $table->head = $tableheader;
        $table->data = array();
        $firstid = $lastid = 0;

        // Get the first and last record id from the records so we can manage the sort icons.
        if (count($globalrestrictions) > 1) {
            $globalrestrictionscopy = $globalrestrictions;
            $temp = array_shift($globalrestrictionscopy);
            $firstid = $temp->id;
            if ($globalrestrictionscopy) {
                $temp = array_pop($globalrestrictionscopy);
                $lastid = $temp->id;
            }
        }

        $data = array();
        $rowclasses = array();
        foreach ($globalrestrictions as $restriction) {
            $fullname = format_string($restriction->name);
            $baseurl = '/totara/reportbuilder/restrictions/index.php';
            $viewurl = new moodle_url($baseurl, array('id' => $restriction->id, 'action' => 'view', 'sesskey' => sesskey()));
            $editurl = new moodle_url('/totara/reportbuilder/restrictions/edit_general.php',
                    array('id' => $restriction->id, 'action' => 'edit'));
            $deleteurl = new moodle_url($baseurl, array('id' => $restriction->id, 'action' => 'delete', 'sesskey' => sesskey()));

            $row = array();
            $row[] = $fullname;

            if ($restriction->allrecords) {
                $row[] = get_string('restrictionallrecords', 'totara_reportbuilder');
            } else {
                $row[] = $this->format_records_to_view($restriction->recordstoview);
            }
            if ($restriction->allusers) {
                $row[] = get_string('restrictionallusers', 'totara_reportbuilder');
            } else {
                $row[] = $this->format_records_to_view($restriction->restrictedusers);
            }

            $editaction = $this->output->action_icon($editurl, new pix_icon('/t/edit', $stredit, 'moodle'), null,
                array('title' => $stredit));
            $deleteaction = $this->output->action_icon($deleteurl, new pix_icon('/t/delete', $strdelete, 'moodle'), null,
                array('title' => $strdelete));

            // Activate or deactivate actions.
            if ($restriction->active) {
                $tooltip = get_string('deactivateglobalrestriction', 'totara_reportbuilder');
                $icon = 't/hide';
                $params = array('id' => $restriction->id, 'action' => 'deactivate', 'sesskey' => sesskey());
                $rowclass = '';
            } else {
                $tooltip = get_string('activateglobalrestriction', 'totara_reportbuilder');
                $icon = 't/show';
                $params = array('id' => $restriction->id, 'action' => 'activate', 'sesskey' => sesskey());
                $rowclass = 'dimmed_text';
            }
            $activatedeactivateurl = new moodle_url($baseurl, $params);
            $disableaction = $this->output->action_icon($activatedeactivateurl, new pix_icon($icon, $tooltip, 'moodle'), null,
                array('title' => $tooltip));

            // Sort action.
            $upaction = '';
            $downaction = '';
            if ($restriction->id != $firstid && $firstid) {
                $params = array('id' => $restriction->id, 'action' => 'up', 'sesskey' => sesskey());
                $upaction = $this->output->action_icon(new moodle_url($baseurl, $params),
                    new pix_icon('t/up', $strmoveup), null, array('title' => $strmoveup));
            }

            if ($restriction->id != $lastid && $lastid) {
                $params = array('id' => $restriction->id, 'action' => 'down', 'sesskey' => sesskey());
                $downaction = $this->output->action_icon(new moodle_url($baseurl, $params),
                    new pix_icon('t/down', $strmovedown), null, array('title' => $strmovedown));
            }

            $row[] = "{$editaction}{$deleteaction}{$disableaction}{$upaction}{$downaction}";

            $data[] = $row;
            $rowclasses[] = $rowclass;
        }
        $table->data = $data;
        $table->rowclasses = $rowclasses;

        return html_writer::table($table);
    }

    /**
     * Output report delete confirmation message
     * @param reportbuilder $report Original report instance
     * @return string
     */
    public function confirm_delete(reportbuilder $report) {
        $type = empty($report->embedded) ? 'delete' : 'reload';

        $out = html_writer::tag('p', get_string('reportconfirm' . $type, 'totara_reportbuilder', $report->fullname));
        return $out;
    }

    /**
     * Output report clone confirmation message
     * @param reportbuilder $report Original report instance
     * @return string
     */
    public function confirm_clone(reportbuilder $report) {
        // Prepare list of supported clonable properties.
        $supportedproperties = array('clonereportfilters', 'clonereportcolumns', 'clonereportsearchcolumns',
            'clonereportsettings', 'clonereportgraph');
        if ($report->embedded) {
            $supportedproperties[] = 'clonereportaccessreset';
        }
        $strproperties = array();
        foreach ($supportedproperties as $propertyname) {
            $strproperties[] = get_string($propertyname, 'totara_reportbuilder');
        }
        $strpropertylist = html_writer::alist($strproperties);

        $out = '';
        if ($report->embedded){
            $out .= $this->output->notification(get_string('clonereportaccesswarning', 'totara_reportbuilder'), 'notifynotice');
        }

        $info = new stdClass();
        $info->origname = $report->fullname;
        $info->clonename = get_string('clonenamepattern', 'totara_reportbuilder', $report->fullname);
        $info->properties = $strpropertylist;

        $out .= html_writer::tag('p', get_string('clonedescrhtml', 'totara_reportbuilder', $info));

        return $out;
    }

    /** Prints select box and Export button to export current report.
     *
     * A select is shown if the global settings allow exporting in
     * multiple formats. If only one format specified, prints a button.
     * If no formats are set in global settings, no export options are shown
     *
     * for this to work page must contain:
     * if ($format != '') { $report->export_data($format);die;}
     * before header is printed
     *
     * @param integer|reportbuilder $report ID or instance of the report to exported
     * @param integer $sid Saved search ID if a saved search is active (optional)
     * @return No return value but prints export select form
     */
    public function export_select($report, $sid = 0) {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . '/totara/reportbuilder/export_form.php');

        if ($report instanceof reportbuilder) {
            $id = $report->_id;
            $url = $report->get_current_url();
        } else {
            $id = $report;
            $report = reportbuilder::create($id);
            if ($PAGE->has_set_url()) {
                $url = $PAGE->url;
            } else {
                $url = new moodle_url(qualified_me());
                foreach ($url->params() as $name => $value) {
                    if (in_array($name, array('spage', 'ssort', 'sid', 'clearfilters'))) {
                        $url->remove_params($name);
                    }
                }
            }
        }

        $extparams = array();
        foreach ($report->get_current_params() as $param) {
            $extparams[$param->name] = $param->value;
        }

        $export = new report_builder_export_form($url, compact('id', 'sid', 'extparams'), 'post', '', array('id' => 'rb_export_form'));
        $export->display();
    }

    /**
     * Returns a link that takes the user to a page which displays the report
     *
     * @param string $reporturl the url to redirect to
     * @return string HTML to display the link
     */
    public function view_report_link($reporturl) {

        $url = new moodle_url($reporturl);
        return html_writer::link($url, get_string('viewreport', 'totara_reportbuilder'));
    }

    /**
     * Returns message that there are changes pending cache regeneration or cache is being
     * regenerated since some time
     *
     * @param int|reportbuilder $reportid Report id or reportbuilder instance
     * @return string Rendered HTML
     */
    public function cache_pending_notification($report = 0) {
        global $CFG;
        if (empty($CFG->enablereportcaching)) {
            return '';
        }
        if (is_numeric($report)) {
            $report = reportbuilder::create($report);
        }
        $notice = '';
        if ($report instanceof reportbuilder) {
            //Check that regeneration is started
            $status = $report->get_cache_status();
            if ($status == RB_CACHE_FLAG_FAIL) {
                $notice = $this->container(get_string('cachegenfail','totara_reportbuilder'), 'notifyproblem clearfix');
            } else if ($status == RB_CACHE_FLAG_GEN) {
                $time = userdate($report->cacheschedule->genstart);
                $notice = $this->container(get_string('cachegenstarted','totara_reportbuilder', $time), 'notifynotice clearfix');
            } else if ($status == RB_CACHE_FLAG_CHANGED) {
                $context = context_system::instance();
                $capability = $report->embedded ? 'totara/reportbuilder:manageembeddedreports' : 'totara/reportbuilder:managereports';
                if ($report->_id > 0 && has_capability($capability, $context)) {
                    $button = html_writer::start_tag('div', array('class' => 'boxalignright rb-genbutton'));
                    $button .= $this->cachenow_button($report->_id);
                    $button .= html_writer::end_tag('div');
                } else {
                    $button = '';
                }
                $notice = $this->container(get_string('cachepending','totara_reportbuilder', $button),
                        'notifynotice clearfix', 'cachenotice_'.$report->_id);
            }
        }
        return $notice;
    }

    /**
     * Display cache now button
     *
     * @param int $reportid Report id
     * @param bool $icon Show icon instead of button
     */
    public function cachenow_button($reportid, $icon = false) {
        global $PAGE, $CFG;
        static $cachenowinit = false;
        static $strcache = '';

        if (!$cachenowinit) {
            $cachenowinit = true;
            require_once($CFG->dirroot.'/totara/core/js/lib/setup.php');
            $PAGE->requires->strings_for_js(array('cachenow_title'), 'totara_reportbuilder');
            $PAGE->requires->string_for_js('ok', 'moodle');
            $strcache = get_string('cachenow', 'totara_reportbuilder');
            local_js(array(TOTARA_JS_DIALOG));
            $PAGE->requires->js_call_amd('totara_reportbuilder/cachenow-lazy', 'init', array());
        }

        if ($icon) {
            $html = html_writer::start_tag('div', array('class' => 'show-cachenow-dialog', 'id' => 'show-cachenow-dialog-' . $reportid, 'data-id' => $reportid));
            $cacheicon = $this->output->flex_icon('cache', ['classes' => 'rb-genicon', 'alt' => get_string('cachereport', 'totara_reportbuilder')]);
            $html .= $cacheicon;
            $html .= html_writer::end_tag('div');
        } else {
            $html = html_writer::empty_tag('input', array('type' => 'button',
                'name' => 'rb_cachenow',
                'data-id' => $reportid,
                'class' => 'show-cachenow-dialog rb-hidden',
                'id' => 'show-cachenow-dialog-' . $reportid,
                'value' => $strcache
                ));
        }
        return $html;
    }

    /**
     * Returns a link back to the manage reports page called 'View all reports'
     *
     * Used when editing a single report
     *
     * @param boolean $embedded True to link to embedded reports, false to link to user reports.
     *
     * @return string The HTML for the link
     */
    public function view_all_reports_link($embedded = false) {
        $string = $embedded ? 'allembeddedreports' : 'alluserreports';
        $url = $embedded ? new moodle_url('/totara/reportbuilder/manageembeddedreports.php') : new moodle_url('/totara/reportbuilder/');
        return '&laquo; ' . html_writer::link($url, get_string($string, 'totara_reportbuilder'));
    }

    /**
     * Returns a button that when clicked, takes the user to a page where they can
     * save the results of a search for the current report
     *
     * @param reportbuilder $report
     * @return string HTML to display the button
     */
    public function save_button($report) {
        global $SESSION;

        $buttonsarray = optional_param_array('submitgroup', null, PARAM_TEXT);
        $search = (isset($SESSION->reportbuilder[$report->get_uniqueid()]) &&
                !empty($SESSION->reportbuilder[$report->get_uniqueid()])) ? true : false;
        // If a report has required url params then scheduled reports require a saved search.
        // This is because the user needs to be able to save the search with no filters defined.
        $hasrequiredurlparams = isset($report->src->redirecturl);
        if ($search || $hasrequiredurlparams) {
            $params = $report->get_current_url_params();
            $params['id'] = $report->_id;
            return $this->output->single_button(new moodle_url('/totara/reportbuilder/save.php', $params),
                    get_string('savesearch', 'totara_reportbuilder'), 'get');
        } else {
            return '';
        }
    }


    /**
     * Returns HTML for a button that lets users show and hide report columns
     * interactively within the report
     *
     * JQuery, dialog code and showhide.js.php should be included in page
     * when this is used (see code in report.php)
     *
     * @param int $reportid
     * @param string $reportshortname the report short name
     * @return string HTML to display the button
     */
    public function showhide_button($reportid, $reportshortname) {
        $js = "var id = {$reportid}; var shortname = '{$reportshortname}';";
        $html = html_writer::script($js);

        // hide if javascript disabled
        $html .= html_writer::start_tag('div', array('class' => 'rb-showhide'));
        $html .= html_writer::start_tag('form');
        $html .= html_writer::empty_tag('input', array('type' => 'button',
            'class' => 'rb-hidden',
            'name' => 'rb_showhide_columns',
            'id' => 'show-showhide-dialog',
            'value' => get_string('showhidecolumns', 'totara_reportbuilder')
        ));
        $html .= html_writer::end_tag('form');
        $html .= html_writer::end_tag('div');

        return $html;
    }

    /**
     * Returns HTML for a button that lets users show and hide report columns
     * interactively within the report
     *
     * JQuery, dialog code and showhide.js.php should be included in page
     * when this is used (see code in report.php)
     *
     * @param int $reportid
     * @param string $reportshortname the report short name
     * @return string HTML to display the button
     */
    public function expand_container($content) {
        $html = '';

        // We put the data in a container so that jquery can search inside it.
        $html .= html_writer::start_div('rb-expand-container');

        // We need to construct a table with one row and one column so that the row can be inserted into the existing table.
        $cell = new html_table_cell(html_writer::span($content));
        $cell->attributes['class'] = 'rb-expand-cell';

        $row = new html_table_row(array($cell));
        $row->attributes['class'] = 'rb-expand-row';

        $table = new html_table();
        $table->data = array($row);
        $html .= html_writer::table($table);

        // Close the container.
        $html .= html_writer::end_div();

        return $html;
    }

    /**
     * Returns HTML for a button that lets users see saved search
     *
     * JQuery, dialog code and searchlist.js should be included in page
     * when this is used (see code in report.php)
     *
     * @param int $report
     * @return string HTML to display the button
     */
    public function manage_search_button($report) {
        $html = html_writer::start_tag('div', array('class' => 'boxalignright'));
        $html .= html_writer::start_tag('form');
        $html .= html_writer::empty_tag('input', array('type' => 'button',
            'class' => 'boxalignright',
            'name' => 'rb_manage_search',
            'id' => 'show-searchlist-dialog-' . $report->_id,
            'value' => get_string('managesavedsearches', 'totara_reportbuilder')
        ));
        $html .= html_writer::end_tag('form');
        $html .= html_writer::end_tag('div');

        return $html;
    }

    /**
     * Print the description of a report
     *
     * @param string $description
     * @param integer $reportid ID of the report the description belongs to
     * @return string HTML
     */
    public function print_description($description, $reportid) {
        $sitecontext = context_system::instance();
        $description = file_rewrite_pluginfile_urls($description, 'pluginfile.php', $sitecontext->id, 'totara_reportbuilder', 'report_builder', $reportid);

        $out = '';
        if (isset($description) &&
            trim(strip_tags($description)) != '') {
            $out .= $this->output->box_start('generalbox reportbuilder-description');
            // format_text is HTML and multi language support for general and embedded reports.
            $out .= format_text($description);
            $out .= $this->output->box_end();
        }
        return $out;
    }

    /**
     * Returns HTML containing a string detailing the result count for the given report.
     *
     * @param reportbuilder $report
     * @return string
     */
    public function result_count_info(reportbuilder $report) {

        $filteredcount = $report->get_filtered_count();
        if ($report->can_display_total_count()) {
            $unfilteredcount = $report->get_full_count();
            $resultstr = ((int)$unfilteredcount === 1) ? 'record' : 'records';
            $a = new stdClass();
            $a->filtered = $filteredcount;
            $a->unfiltered = $unfilteredcount;
            $string = get_string('xofy' . $resultstr, 'totara_reportbuilder', $a);
        } else{
            $resultstr = ((int)$filteredcount === 1) ? 'record' : 'records';
            $string = get_string('x' . $resultstr, 'totara_reportbuilder', $filteredcount);
        }

        return html_writer::span($string, 'rb-record-count');
    }

    /**
     * Generates the report HTML and debug HTML if required.
     *
     * This method should always be called after the header has been output, before
     * the report has been used for anything, and before any other renderer methods have been called.
     * By doing this the report counts will be cached and you will avoid needing to run the count queries
     * which are nearly as expensive as the reports.
     *
     * @since Totara 9.9, 10
     * @param reportbuilder $report
     * @param int $debug
     * @return array The report html and the debughtml
     */
    public function report_html(reportbuilder $report, $debug = 0) {
        // Generate and output the debug HTML before we do anything else with the report.
        // This way if there is an error it we already have debug.
        $debughtml = ($debug > 0) ? $report->debug((int)$debug, true) : '';
        // Now generate the report HTML before anything else, this is optimised to cache counts.
        $reporthtml = $report->display_table(true);
        return array($reporthtml, $debughtml);
    }

    /**
     * Renders a table containing report saved searches
     *
     * @param array $searches array of saved searches
     * @param object $report report that these saved searches belong to
     * @return string HTML table
     */
    public function saved_searches_table($searches, $report) {
        $tableheader = array(get_string('name', 'totara_reportbuilder'),
                             get_string('publicsearch', 'totara_reportbuilder'),
                             get_string('options', 'totara_reportbuilder'));
        $data = array();
        $stredit = get_string('edit');
        $strdelete = get_string('delete', 'totara_reportbuilder');

        foreach ($searches as $search) {
            $editurl = new moodle_url('/totara/reportbuilder/savedsearches.php',
                array('id' => $search->reportid, 'action' => 'edit', 'sid' => $search->id));
            $deleteurl = new moodle_url('/totara/reportbuilder/savedsearches.php',
                array('id' => $search->reportid, 'action' => 'delete', 'sid' => $search->id));

            $actions = $this->output->action_icon($editurl, new pix_icon('/t/edit', $stredit, 'moodle')) . ' ';
            $actions .= $this->output->action_icon($deleteurl, new pix_icon('/t/delete', $strdelete, 'moodle'));

            $row = array();
            $row[] = $search->name;
            $row[] = ($search->ispublic) ? get_string('yes') : get_string('no');
            $row[] = $actions;
            $data[] = $row;
        }

        $table = new html_table();
        $table->summary = '';
        $table->head = $tableheader;
        $table->attributes['class'] = 'fullwidth generaltable';
        $table->data = $data;

        return html_writer::table($table);
    }

    /**
     * Renders a list of items for the email setting in schedule reports.
     *
     * @param object $item An item object which should contain id and name properties
     * @param string $filtername The filter name where the item belongs
     * @return string $out HTML output
     */
    public function schedule_email_setting($item, $filtername) {
        $name = (isset($item->name)) ? $item->name : $item->fullname;
        $strdelete = get_string('delete');
        $out = html_writer::start_tag('div', array('data-filtername' => $filtername,
            'id' => "{$filtername}_{$item->id}",
            'data-id' => $item->id,
            'class' => 'multiselect-selected-item audience_setting'));
        $out .= format_string($name);
        $out .= $this->output->action_icon('#', new pix_icon('/t/delete', $strdelete, 'moodle'), null,
            array('class' => 'action-icon delete'));

        $out .= html_writer::end_tag('div');

        return $out;
    }

    /**
     * Returns a table showing the currently assigned groups of users
     *
     * @param array $assignments group assignment info
     * @param int $itemid the id of the restriction object users are assigned to
     * @param string $suffix type of restriction (record or user)
     * @return string HTML
     */
    public function display_assigned_groups($assignments, $itemid, $suffix) {
        $tableheader = array(get_string('assigngrouptype', 'totara_core'),
                             get_string('assignsourcename', 'totara_core'),
                             get_string('assignincludechildrengroups', 'totara_core'),
                             get_string('assignnumusers', 'totara_core'),
                             get_string('actions'));
        if ($suffix === 'record') {
            $deleteurl = new moodle_url('/totara/reportbuilder/restrictions/edit_recordstoview.php',
                    array('id' => $itemid, 'sesskey' => sesskey()));
        } else if ($suffix === 'user') {
            $deleteurl = new moodle_url('/totara/reportbuilder/restrictions/edit_restrictedusers.php',
                    array('id' => $itemid, 'sesskey' => sesskey()));
        } else {
            $deleteurl = null;
        }

        $table = new html_table();
        $table->attributes['class'] = 'fullwidth generaltable';
        $table->summary = '';
        $table->head = $tableheader;
        $table->data = array();
        if (empty($assignments)) {
            $table->data[] = array(get_string('nogroupassignments', 'totara_core'));
        } else {
            foreach ($assignments as $assign) {
                $includechildren = ($assign->includechildren == 1) ? get_string('yes') : get_string('no');
                $row = array();
                $row[] = new html_table_cell($assign->grouptypename);
                $row[] = new html_table_cell($assign->sourcefullname);
                $row[] = new html_table_cell($includechildren);
                $row[] = new html_table_cell($assign->groupusers);

                if ($deleteurl) {
                    $delete = $this->output->action_icon(
                        new moodle_url($deleteurl, array('deleteid' => $assign->id)),
                        new pix_icon('t/delete', get_string('delete')));
                    $row[] = new html_table_cell($delete);
                } else {
                    $row[] = '';
                }

                $table->data[] = $row;
            }
        }
        $out = $this->output->container(html_writer::table($table), 'clearfix', 'assignedgroups');
        return $out;
    }

    /**
     * Returns the base markup for a paginated user table widget
     *
     * @return string HTML
     */
    public function display_user_datatable() {
        $table = new html_table();
        $table->id = 'datatable';
        $table->attributes['class'] = 'clearfix';
        $table->head = array(get_string('learner'), get_string('assignedvia', 'totara_core'));
        $out = $this->output->container(html_writer::table($table), 'clearfix', 'assignedusers');
        return $out;
    }

    /**
     * Renders the edit restictions header.
     *
     * @param rb_global_restriction $restriction
     * @param string $currenttab
     * @return string
     */
    public function edit_restriction_header(rb_global_restriction $restriction, $currenttab) {

        $html = $this->output->header();

        $url = new moodle_url('/totara/reportbuilder/restrictions/index.php');
        $html .= $this->output->container_start('reportbuilder-navlinks');
        $html .= html_writer::link($url, get_string('allrestrictions', 'totara_reportbuilder'));
        $html .= $this->output->container_end();

        if ($restriction->id) {
            $html .= $this->output->heading(get_string('editrestriction', 'totara_reportbuilder', $restriction->name));
        } else {
            $html .= $this->output->heading(get_string('newrestriction', 'totara_reportbuilder'));
        }

        $html .= $this->edit_restriction_tabs($restriction, $currenttab);
        return $html;
    }

    /**
     * Renders editing restriction tabs.
     *
     * @param rb_global_restriction $restriction
     * @param string $currenttab
     * @return string
     */
    public function edit_restriction_tabs(rb_global_restriction $restriction, $currenttab) {
        // Prepare the tabs.
        $tabgeneral = new tabobject(
            'general',
            new moodle_url('/totara/reportbuilder/restrictions/edit_general.php', array('id' => $restriction->id)),
            get_string('general')
        );
        $tabrecordstoview = new tabobject(
            'recordstoview',
            new moodle_url('/totara/reportbuilder/restrictions/edit_recordstoview.php', array('id' => $restriction->id)),
            get_string('recordstoview', 'totara_reportbuilder')
        );
        $tabrestrictedusers = new tabobject(
            'restrictedusers',
            new moodle_url('/totara/reportbuilder/restrictions/edit_restrictedusers.php', array('id' => $restriction->id)),
            get_string('restrictedusers', 'totara_reportbuilder')
        );

        // Set up the active and inactive tabs.
        if (!$restriction->id) {
            $tabgeneral->activated = true;
            $tabrecordstoview->inactive = true;
            $tabrestrictedusers->inactive = true;
        }

        $row = array(
            $tabgeneral,
            $tabrecordstoview,
            $tabrestrictedusers,
        );
        // Ensure the current tab is selected and activated.
        foreach ($row as $tab) {
            if ($tab->id === $currenttab) {
                $tab->activated = true;
                $tab->selected = true;
            }
        }
        return $this->output->tabtree($row);
    }
}
