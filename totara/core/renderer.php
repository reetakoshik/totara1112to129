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
 * @author Ciaran Irvine <ciaran.irvine@totaralms.com>
 * @package totara
 * @subpackage totara_core
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

/**
* Standard HTML output renderer for totara_core module
*/
class totara_core_renderer extends plugin_renderer_base {

    /**
     * Displays a count of the number of active users in the last year
     *
     * @param integer $activeusers Number of active users in the last year
     * @return string HTML to output.
     * @deprecated since 9.0.
     */
    public function totara_print_active_users($activeusers) {
        debugging('totara_print_active_users has been deprecated please use active_users', DEBUG_DEVELOPER);
        return $this->active_users($activeusers);
    }

    /**
    * Displays a count of the number of active users in the last year
    *
    * @param integer $activeusers Number of active users in the last year
    * @return string HTML to output.
    */
    public function active_users($activeusers, $activeusers3mth) {
        $data = new stdClass();

        $a = new \stdClass();
        $a->activeusers = $activeusers;
        $a->activeusers3mth = $activeusers3mth;
        return $this->notification(get_string('activeusercountstr', 'totara_core', $a), \core\output\notification::NOTIFY_INFO);
    }

    /**
     * Displays a link to download error log
     *
     * @deprecated since Totara 11
     * @param object $latesterror Object containing information about the last site error
     * @return string HTML to output.
     */
    public function errorlog_link($latesterror) {

        debugging(__METHOD__ . 'has been deprecated since Totara 11 and will be removed in a future release. There is no alternative.', DEBUG_DEVELOPER);

        $data = new stdClass();
        $data->timeoccured = userdate($latesterror->timeoccured);
        $data->lastoccuredstr = get_string('lasterroroccuredat', 'totara_core', $data->timeoccured);
        $data->downloadbutton = $this->output->single_button(new moodle_url('/admin/index.php', array('geterrors' => 1)), get_string('downloaderrorlog', 'totara_core'), 'post');
        $output = $this->render_from_template('totara_core/errorlog_link', $data);

        return $this->notification($output, \core\output\notification::NOTIFY_WARNING);
    }

    /**
     * Outputs a block containing totara copyright information
     *
     * @param string $totara_release A totara release version, for inclusion in the block
     *
     * @return string HTML to output.
     */
    public function totara_print_copyright($totara_release) {
        $output = '';
        $output .= html_writer::start_div('totara-copyright');
        $text = get_string('version') . ' ' . $totara_release;
        $url = new moodle_url('https://www.totaralearning.com');
        $attributes = array('href' => $url, 'target' => '_blank');
        $output .= html_writer::tag('a', $text, $attributes);

        // Inform the admin of the flavour they are using. If one has been set.
        $flavour = \totara_flavour\helper::get_active_flavour_definition();
        if ($flavour) {
            $output .= html_writer::empty_tag('br');
            $output .= html_writer::empty_tag('br');
            $text = markdown_to_html(get_string('description', 'totara_flavour', $flavour->get_name()));
            $output .= html_writer::tag('span', $text);
        }

        $output .= html_writer::empty_tag('br');
        $output .= html_writer::empty_tag('br');
        $output .= get_string('totaracopyright', 'totara_core', get_string('totaralearn', 'totara_core'));
        $output .= html_writer::end_div();
        return $output;
    }

    /**
     * Returns markup for displaying a progress bar for a user's course progress
     *
     * Optionally with a link to the user's profile if they have the correct permissions
     *
     * @deprecated since 9.0
     * @access  public
     * @param   $userid     int
     * @param   $courseid   int
     * @param   $status     int     COMPLETION_STATUS_ constant
     * @return  string html to display
     */
    public function display_course_progress_icon($userid, $courseid, $status) {
        debugging("display_course_progress_icon has been deprecated. Use course_progress_bar instead", DEBUG_DEVELOPER);
        return $this->course_progress_bar($userid, $courseid, $status);
    }

    /**
    * Returns markup for displaying a progress bar for a user's course progress
    *
    * Optionally with a link to the user's profile if they have the correct permissions
    *
    * @access  public
    * @param   $userid     int
    * @param   $courseid   int
    * @param   $status     int     COMPLETION_STATUS_ constant
    * @return  string html to display
    */
    public function course_progress_bar($userid, $courseid, $status) {
        global $COMPLETION_STATUS;

        // Display the course progress bar.
        $data = $this->export_course_progress_for_template($userid, $courseid, $status);
        return $this->output->render_from_template('totara_core/course_progress_bar', $data);
    }

    /**
    * Returns exported template data for displaying a progress bar of a user's course progress
    *
    * @access  public
    * @param   int $userid
    * @param   int $courseid
    * @param   int $status COMPLETION_STATUS_ constant
    * @return  stdClass with exported template data
    */
    public function export_course_progress_for_template($userid, $courseid, $status) {
        global $COMPLETION_STATUS, $OUTPUT;

        $data = new stdClass();

        if (!isset($status) || !array_key_exists($status, $COMPLETION_STATUS)){
            $data->statustext = get_string('statusnottracked', 'completion');
            return $data;
        }

        $completion = new completion_completion(['userid' => $userid, 'course' => $courseid]);
        $progressinfo = $completion->get_progressinfo();
        $percent = $progressinfo->get_percentagecomplete();
        if ($percent === false) {
            $customdata = $progressinfo->get_customdata();
            // enabled is only set if completion tracking is NOT enabled
            if (isset($customdata['enabled']) && $customdata['enabled'] === false) {
                $data->statustext = get_string('statusnottracked', 'completion');
                return $data;
            }

            // If there is no completion criteria and status != 'complete' and status != 'completeviarpl', show 'no criteria'
            if (!completion_criteria::course_has_criteria($courseid) && $status != COMPLETION_STATUS_COMPLETE && $status != COMPLETION_STATUS_COMPLETEVIARPL) {
                $data->statustext = get_string('statusnocriteria', 'completion');
                return $data;
            }
        }

        $data->statustext = get_string($COMPLETION_STATUS[$status], 'completion');
        $data->percent = $percent;
        $pbar = new \static_progress_bar('', '0');
        $pbar->set_progress((string)$percent);
        $detaildata = $completion->export_completion_criteria_for_template();
        if (!empty($detaildata)) {
            $pbar->add_popover(\core\output\popover::create_from_template('totara_core/course_completion_criteria', $detaildata));
        }

        $data->pbar = $pbar->export_for_template($OUTPUT);

        return $data;
    }

    /**
     * Print out the Totara My Team nav section.
     *
     * @deprecated since 9.0
     * @param integer $numteammembers The number of members in the team.
     * @return string HTML
     */
    public function print_my_team_nav($numteammembers) {
        debugging("print_my_team_nav has been deprecated. Please use my_team_nav instead.", DEBUG_DEVELOPER);
        return $this->my_team_nav($numteammembers);
    }

    /**
     * Use a template to generate the My Team nav markup.
     *
     * @param integer $numteammembers The number of members in the team.
     * @return string HTML
     */
    public function my_team_nav($numteammembers) {
        if (empty($numteammembers) || $numteammembers == 0) {
            return '';
        }

        if (!totara_feature_visible('myteam')) {
            return '';
        }

        $data = new stdClass();
        $data->numberinteam = $numteammembers;
        $data->numberinteamstring = get_string('numberofstaff', 'totara_core', $numteammembers);
        $data->href = (string) new moodle_url('/my/teammembers.php');

        return $this->output->render_from_template('totara_core/my_team_nav', $data);
    }

    /**
     * Print out the table of visible reports.
     *
     * @deprecated since 9.0
     * @param array $reports array of report objects visible to this user.
     * @param bool $canedit if this user is an admin with editing turned on.
     * @return string HTML
     */
    public function print_report_manager($reports, $canedit) {
        debugging("print_report_manager has been deprecated. Use report_list instead.", DEBUG_DEVELOPER);
        return $this->report_list($reports, $canedit);
    }

    /**
     * Use a template to generate a table of visible reports.
     *
     * @param array $reports array of report objects visible to this user.
     * @param bool $canedit if this user is an admin with editing turned on.
     * @return string HTML
     */
    public function report_list($reports, $canedit) {
        // If we've generated a report list, generate the mark-up.
        if ($report_list = $this->report_list_export_for_template($reports, $canedit)) {
            $data = new stdClass();
            $data->report_list = $report_list;

            return $this->output->render_from_template('totara_core/report_list', $data);
        } else {
            return '';
        }
    }

    /**
     * Generate the data required for the report_list template.
     *
     * @param array $reports array of report objects visible to this user.
     * @param bool $canedit if this user is an admin with editing turned on.
     * @return array List of reports.
     */
    public function report_list_export_for_template($reports, $canedit) {
        $report_list = array();
        $systemcontext = context_system::instance();

        foreach ($reports as $report) {

            $reportname = format_string($report->fullname, true, ['context' => $systemcontext]);

            // Check url property is set.
            if (!isset($report->url)) {
                debugging('The url property for report ' . $reportname . ' is missing, please ask your developers to check your code', DEBUG_DEVELOPER);
                continue;
            }

            // Escaping is done in the mustache template, so no need to do it in format string
            $report_data = [
                'name' => $reportname,
                'href' => $report->url
            ];

            if ($canedit) {
                $icon_params = array(
                    // Report name must be decoded, alt goes to an attribute
                    'alt' => get_string('edit', 'totara_reportbuilder')
                );
                $icon = \core\output\flex_icon::get_icon('t/edit', 'core', $icon_params);
                $report_data['icon'] = array(
                    'template' => $icon->get_template(),
                    'context' => $icon->export_for_template($this)
                );
                $report_data['edit_href'] = (string) new moodle_url('/totara/reportbuilder/general.php', array('id' => $report->id));
            }

            $report_list[] = $report_data;
        }

        return $report_list;
    }


    /**
     * Returns markup for displaying saved scheduled reports.
     *
     * @deprecated since 9.0.
     * @param array $scheduledreports List of scheduled reports.
     * @param boolean $showoptions boolean Show actions to edit or delete the scheduled report.
     * @return string HTML
     */
    public function print_scheduled_reports($scheduledreports, $showoptions=true) {
        debugging("print_scheduled_reports has been deprecated. Please use scheduled_reports_list instead.");
        return $this->scheduled_reports($scheduledreports, $showoptions);
    }


    /**
     * Uses a template to generate markup for displaying saved scheduled reports.
     *
     * @param array $scheduledreports List of scheduled reports.
     * @param boolean $showoptions boolean Show actions to edit or delete the scheduled report.
     * @return string HTML containing a form plus a table of scheduled reports or text.
     */
    public function scheduled_reports($scheduledreports, $showoptions=true, $addform = '') {
        global $OUTPUT;

        $dataobject = $this->scheduled_reports_export_for_template($scheduledreports, $showoptions, $addform);
        return $OUTPUT->render_from_template('totara_core/scheduled_reports', $dataobject);
    }


    /**
     * Uses a template to generate markup for displaying saved scheduled reports.
     *
     * @param array $scheduledreports List of scheduled reports.
     * @param boolean $showoptions Show actions to edit or delete the scheduled report.
     * @param string $addform form HTML to add another scheduled report.
     * @return object Table data object for the table template.
     */
    public function scheduled_reports_export_for_template($scheduledreports, $showoptions, $addform) {

        $table = new html_table();
        $table->id = 'scheduled_reports';
        $table->attributes['class'] = 'generaltable';
        $headers = array();
        $headers[] = get_string('reportname', 'totara_reportbuilder');
        $headers[] = get_string('savedsearch', 'totara_reportbuilder');
        $headers[] = get_string('format', 'totara_reportbuilder');
        if (get_config('reportbuilder', 'exporttofilesystem') == 1) {
            $headers[] = get_string('exportfilesystemoptions', 'totara_reportbuilder');
        }
        $headers[] = get_string('schedule', 'totara_reportbuilder');
        if ($showoptions) {
            $headers[] = get_string('options', 'totara_core');
        }
        $table->head = $headers;

        foreach ($scheduledreports as $sched) {
            $cells = array();
            $cells[] = new html_table_cell(format_string($sched->fullname));
            $cells[] = new html_table_cell($sched->data);
            $cells[] = new html_table_cell($sched->format);
            if (get_config('reportbuilder', 'exporttofilesystem') == 1) {
                $cells[] = new html_table_cell($sched->exporttofilesystem);
            }
            $cells[] = new html_table_cell($sched->schedule);
            if ($showoptions) {
                $text = get_string('edit');
                $icon = $this->output->flex_icon('settings', ['classes' => 'ft-size-100', 'alt' => $text]);
                $url = new moodle_url('/totara/reportbuilder/scheduled.php', array('id' => $sched->id));
                $attributes = array('href' => $url);
                $cellcontent = html_writer::tag('a', $icon, $attributes);
                $cellcontent .= ' ';
                $text = get_string('delete');
                $icon = $this->output->flex_icon('delete', ['classes' => 'ft-size-100', 'alt' => $text]);
                $url = new moodle_url('/totara/reportbuilder/deletescheduled.php', array('id' => $sched->id));
                $attributes = array('href' => $url);
                $cellcontent .= html_writer::tag('a', $icon, $attributes);
                $cell = new html_table_cell($cellcontent);
                $cell->attributes['class'] = 'options';
                $cells[] = $cell;
            }
            $row = new html_table_row($cells);
            $table->data[] = $row;
        }

        $dataobject = $table->export_for_template($this);
        $dataobject->scheduled_reports_count = count($scheduledreports);
        $dataobject->scheduled_report_form = $addform;
        $dataobject->scheduled_reports_visible = has_capability('totara/reportbuilder:createscheduledreports', \context_system::instance());

        return $dataobject;
    }

    /**
    * Render a set of toolbars (either top or bottom)
    *
    * @deprecated since 9.0
    * @param string $position 'top' or 'bottom'
    * @param int $numcolumns
    * @param array $toolbar array of left and right arrays
    *              eg. $toolbar[0]['left'] = <first row left content>
    *                  $toolbar[0]['right'] = <first row right content>
    *                  $toolbar[1]['left'] = <second row left content>
    */
    public function print_toolbars($position='top', $numcolumns, $toolbar) {
        debugging('print_toolbars has been deprecated please use table_toolbars', DEBUG_DEVELOPER);
        echo $this->table_toolbars($toolbar, $position);
    }

    /**
     * Render a set of toolbars (either top or bottom)
     *
     * @param array $toolbar array of left and right arrays
     *              eg. $toolbar[0]['left'] = <first row left content>
     *                  $toolbar[0]['right'] = <first row right content>
     *                  $toolbar[1]['left'] = <second row left content>
     * @param string $position 'top' or 'bottom'
     * @return string the rendered html template
     */
    public function table_toolbars($toolbar, $position='top') {

        ksort($toolbar);

        $data = new stdClass();
        $data->postion = $position;
        $data->toolbars_has_items = count($toolbar) > 0 ? true : false;
        $data->toolbars = array();

        foreach ($toolbar as $index => $row) {
            // don't render empty toolbars
            // if you want to render one, add an empty content string to the toolbar
            if (empty($row['left']) && empty($row['right'])) {
                continue;
            }

            $datarow = array(
                "left_content_has_items" => false,
                "left_content" => array(),
                "right_content_has_items" => false,
                "right" => array()
            );

            if (!empty($row['left'])) {
                $datarow['left_content_has_items'] = true;
                foreach ($row['left'] as $item) {
                    $datarow['left_content'][] = $item;
                }
            }

            if (!empty($row['right'])) {
                $datarow['right_content_has_items'] = true;
                foreach (array_reverse($row['right']) as $item) {
                    $datarow['right_content'][] = $item;
                }
            }
            $data->toolbars[] = $datarow;
        }
        return $this->render_from_template('totara_core/table_toolbars', $data);
    }

    /**
     * Generate markup for search box
     *
     * @deprecated since 9.0
     * @param string $action the form action
     * @param array $hiddenfields array of hidden field names and values
     * @param string $placeholder the form input placeholder text
     * @param string $value the form input value text
     * @param string $formid the form id
     * @param string $inputid the form input id
     * @return string the html form
     */
    public function print_totara_search($action, $hiddenfields = null, $placeholder = '', $value = '', $formid = null, $inputid = null) {
        debugging('print_totara_search has been deprecated please use totara_search', DEBUG_DEVELOPER);
        return $this->totara_search($action, $hiddenfields, $placeholder = '', $value = '', $formid, $inputid);
    }

    /**
     * Generate markup for search box.
     *
     * @param string $action the form action
     * @param array $hiddenfields array of hidden field names and values
     * @param string $placeholder the form input placeholder text
     * @param string $value the form input value text
     * @param string $formid the form id
     * @param string $inputid the form input id
     * @return string the html form
     */
    public function totara_search($action, $hiddenfields = null, $placeholder = '', $value = '', $formid = null, $inputid = null) {
        $data = new stdClass();
        $data->id = $formid;
        $data->action = $action;
        $data->value = $value;
        $data->placeholder = $placeholder;
        $data->alt = $placeholder;
        $data->inputid = $inputid;
        $data->hiddenfields = array();

        if (isset($hiddenfields)) {
            foreach ($hiddenfields as $fname => $fvalue) {
                $data->hiddenfields[] = array(
                    'name' => $fname,
                    'value' => $fvalue
                );
            }
        }

        return $this->render_from_template('totara_core/totara_search', $data);
    }

    /**
     * Generate markup for totara menu
     *
     * @deprecated since 9.0
     */
    public function print_totara_menu($menudata, $parent=null, $selected_items=array()) {
        debugging('print_totara_menu has been deprecated please use totara_menu', DEBUG_DEVELOPER);
        return $this->totara_menu($menudata, $parent, $selected_items);
    }

    /**
     * Generate markup for totara menu. This function is called recursively.
     *
     * @param $menudata array the menu data
     * @param $parent string the parent menu name
     * @param $selected_items array selected menu name items
     * @return string the html output
     */
    public function totara_menu($menudata, $parent=null, $selected_items=array()) {
        $menu = new totara_core\output\totara_menu($menudata, $parent, $selected_items);

        return $this->render($menu);
    }

    /**
     * Renders the totara_menu and returns the HTML to display it.
     *
     * @param totara_menu $totaramenu
     * @return string HTML fragment
     */
    protected function render_totara_menu(totara_core\output\totara_menu $totaramenu) {
        $contextdata = $totaramenu->export_for_template($this);

        return $this->render_from_template('totara_core/totara_menu', $contextdata);
    }

    /**
     * Displaying notices at top of page
     *
     * @deprecated since 9.0
     */
    public function print_totara_notifications() {
        debugging('print_totara_notifications has been deprecated please use totara_notifications', DEBUG_DEVELOPER);
        return $this->totara_notifications();
    }

    /**
     * Displaying notices at top of page
     */
    public function totara_notifications() {
        $output = '';
        // Display notifications set with totara_set_notification()
        $notices = totara_get_notifications();
        foreach ($notices as $notice) {
            if (isset($notice['class'])) {
                $output .= $this->output->notification($notice['message'], $notice['class']);
            } else {
                $output .= $this->output->notification($notice['message']);
            }
        }

        $data = new stdClass();
        $data->content = $output;
        return $this->render_from_template('totara_core/totara_notifications', $data);
    }

    /**
     * Displays relevant progress bar
     *
     * @deprecated since 9.0
     * @param $percent int a percentage value (0-100)
     * @param $size string large, medium...
     * @param $showlabel boolean show completion text label
     * @param $tooltip string required tooltip text
     * @return $out html string
     */
    public function print_totara_progressbar($percent, $size='medium', $showlabel=false, $tooltip='DEFAULTTOOLTIP') {
        debugging('print_totara_progressbar has been deprecated please use progressbar', DEBUG_DEVELOPER);
        return $this->progressbar($percent, $size, $showlabel, $tooltip);
    }

    /**
     * Displays relevant progress bar
     *
     * @param int $percent a percentage value (0-100)
     * @param string $size large, medium...
     * @param boolean $showlabel show completion text label
     * @param string $tooltip required tooltip text
     * @return html string
     */
    public function progressbar($percent, $size='medium', $showlabel=false, $tooltip='DEFAULTTOOLTIP') {
        $percent = round($percent);

        if ($percent < 0 || $percent > 100) {
            return 'progress bar error- invalid value...';
        }

        // Add more sizes if as neccessary :)!
        switch ($size) {
        case 'large' :
            $bar_foreground = 'progressbar-large';
            $pixelvalue = ($percent / 100) * 121;
            $pixeloffset = round($pixelvalue - 120);
            $class = 'totara_progress_bar_large';
            break;
        case 'medium' :
        default :
            $bar_foreground = 'progressbar-medium';
            $pixelvalue = ($percent / 100) * 61;
            $pixeloffset = round($pixelvalue - 60);
            $class = 'totara_progress_bar_medium';
            break;
        }

        if ($tooltip == 'DEFAULTTOOLTIP') {
            $tooltip = get_string('xpercent', 'totara_core', $percent);
        }

        // This is really unfortunate - in the future we should be using a dynamic progressbar model
        // rather than switching icons and setting a background position.
        $icon = $this->pix_icon($bar_foreground, $tooltip, 'totara_core', array('title' => $tooltip, 'style' => 'background-position: ' . $pixeloffset . 'px 0px;', 'class' => $class));

        $data = new stdClass();
        $data->icon = $icon;
        $data->percent = $percent;
        $data->percentcompletestr = get_string('xpercentcomplete', 'totara_core', $percent);

        if ($showlabel) {
            $data->showlabel = true;
        }

        return $this->render_from_template('totara_core/progressbar', $data);
    }

    /**
     * Renders a Totara-style HTML comment template to be used by the comments engine
     *
     * @return string Totara-style HTML comment template
     */
    public function comment_template() {
        return $this->render_from_template('totara_core/comment_template', null);
    }

    /**
     * Print list of icons.
     *
     * @deprecated since 9.0
     * @param string $type Choose the group of Totara icons to return
     * @return string HTML
     */
    public function print_icons_list($type = 'course') {
        debugging("print_icons_list has been deprecated. Please use icon_list instead.",DEBUG_DEVELOPER);
        return $this->icon_list($type);
    }

    /**
     * Print list of icons.
     *
     * @param string $type Choose the group of icons to return
     * @return string HTML
     */
    public function icon_list($type = 'course') {
        global $CFG;

        $icons = array();

        $fs = get_file_storage();
        $files = $fs->get_area_files(context_system::instance()->id, 'totara_core', $type, 0, 'itemid', false);

        // Custom icons.
        foreach ($files as $file) {
            $id = $file->get_itemid();
            $filename = $file->get_filename();
            // Create a name to be use in the akt and title parameters of the img tag.
            $name = preg_replace('/\.[^.\s]{1,}$/', '', $filename);
            $name = ucwords(strtr($name, array( '_' => ' ', '-' => ' ')));
            // Generate the full URL of the image.
            $src = (string) moodle_url::make_pluginfile_url($file->get_contextid(), 'totara_core',
                $file->get_filearea(), $id, $file->get_filepath(), $filename, true);

            // Build the icon data so we can use the core/pic_icon template.
            $attributes = array();
            $attributes[] = array ('name' => 'alt', 'value' => $name);
            $attributes[] = array ('name' => 'title', 'value' => $name);
            $attributes[] = array ('name' => 'class', 'value' => 'course_icon');
            $attributes[] = array ('name' => 'src', 'value' => $src);
            $icon = array('id' => $file->get_pathnamehash(), 'attributes' => $attributes);
            $icons[] = $icon;
        }

        // Totara icons.
        foreach (scandir("{$CFG->dirroot}/totara/core/pix/{$type}icons") as $icon) {
            if ($icon != '.' && $icon != '..') {
                $id = str_replace('.png', '', $icon);
                $name = ucwords(strtr($icon, array('.png' => '', '_' => ' ', '-' => ' ')));
                $src = $this->pix_url("{$type}icons/{$id}", 'totara_core');

                $attributes = array();
                $attributes[] = array ('name' => 'alt', 'value' => $name);
                $attributes[] = array ('name' => 'title', 'value' => $name);
                $attributes[] = array ('name' => 'class', 'value' => 'course_icon');
                $attributes[] = array ('name' => 'src', 'value' => $src);
                $icon = array('id' => $id, 'attributes' => $attributes);
                $icons[] = $icon;
            }
        }

        $template_data = new stdClass();
        $template_data->icons = $icons;

        return $this->output->render_from_template('totara_core/icon_list', $template_data);
    }

     /**
     * Render an appropriate message if registration is not complete.
     * @return string HTML to output.
     */
    public function is_registered() {
        global $CFG;

        // NOTE: do not change any config settings here, it is the job of admin/register.php page!!!

        if (empty($CFG->registrationenabled)) {
            $message = get_string('registrationisdisabled', 'admin', $CFG->wwwroot . '/admin/register.php');
            $level = \core\output\notification::NOTIFY_ERROR;
        } else if (empty($CFG->sitetype) or empty($CFG->registered)) {
            // This is displayed to non-admins only, admins get a register redirect on admin/index.php page now.
            $message = get_string('sitehasntregistered', 'admin', $CFG->wwwroot . '/admin/cron.php');
            $level = \core\output\notification::NOTIFY_ERROR;
        } else if ($CFG->registered < time() - 60 * 60 * 24 * 31) {
            $message = get_string('registrationoutofdate', 'admin');
            $level = \core\output\notification::NOTIFY_WARNING;
        } else {
            $message = get_string('registrationisenabled', 'admin');
            $level = \core\output\notification::NOTIFY_INFO;
        }

        return $this->notification($message, $level);
    }

    /**
     * Render Totara information on user profile.
     *
     * @param $userid ID of a user.
     * @return string HTML to output.
     */
    public function print_totara_user_profile($userid) {
        global $USER, $CFG;

        $currentuser = ($userid == $USER->id);
        $usercontext = context_user::instance($userid);
        // Display hierarchy information.
        profile_display_hierarchy_fields($userid);
        $canviewROL = has_capability('totara/core:viewrecordoflearning', $usercontext);
        // Record of learning.
        if ($currentuser || \totara_job\job_assignment::is_managing($USER->id, $userid) || $canviewROL) {
            $strrol = get_string('recordoflearning', 'totara_core');
            $urlrol = new moodle_url('/totara/plan/record/index.php', array('userid' => $userid));
            echo html_writer::tag('dt', $strrol);
            echo html_writer::tag('dd', html_writer::link($urlrol, $strrol));
        }

        // Learning plans.
        if (totara_feature_visible('learningplans') && dp_can_view_users_plans($userid)) {
            $strplans = get_string('learningplans', 'totara_plan');
            $urlplans = new moodle_url('/totara/plan/index.php', array('userid' => $userid));
            echo html_writer::tag('dt', $strplans);
            echo html_writer::tag('dd', html_writer::link($urlplans, $strplans));
        }
    }

    /**
     * Get a rule description.
     *
     * @param int $ruleid The rule's id.
     * @param $ruledefinition
     * @param int $ruleparamid Param id of the rule.
     * @return string Rule description of the rule.
     */
    public function get_rule_description($ruleid, $ruledefinition, $ruleparamid) {
        $ruledefinition->sqlhandler->fetch($ruleid);

        $ruledefinition->ui->setParamValues($ruledefinition->sqlhandler->paramvalues);

        return $ruledefinition->ui->getRuleDescription($ruleparamid, false);
    }

    /**
     * Render text broken rules in a HTML table.
     *
     * @return string $output HTML to output.
     */
    public function show_text_broken_rules($brokenrules = null) {
        $output = '';
        if (is_null($brokenrules)) {
            $brokenrules = totara_get_text_broken_rules();
        }

        if (!empty($brokenrules)) {
            $content = array();
            $warning = get_string('cohortbugneedfixing', 'totara_cohort');
            $output .= $this->container($warning, 'notifynotice');
            $table = new html_table();

            // Avoid duplicate rules. Display draft rules which contain the most recent changes.
            $brokenrules = array_filter($brokenrules, function ($objtofind) {
                return $objtofind->activecollectionid != $objtofind->rulecollectionid;
            });

            foreach ($brokenrules as $ruleparam) {
                $rule = cohort_rules_get_rule_definition($ruleparam->ruletype, $ruleparam->rulename);
                if (get_class($rule->ui) === 'cohort_rule_ui_text') {
                    $description = $this->get_rule_description($ruleparam->ruleid, $rule, $ruleparam->id);
                    $index = $ruleparam->cohortid . ',' . $ruleparam->cohortname;
                    if (!isset($content[$index])) {
                        $content[$index] = '';
                    }
                    $content[$index] .= html_writer::tag('div', $description);
                }
            }

            $table->head = array(get_string('cohorts', 'totara_cohort'), get_string('rules', 'totara_cohort'));
            foreach ($content as $key => $value) {
                list($id, $name) = explode(',', $key);
                $cohortlink = html_writer::link(new moodle_url('/totara/cohort/rules.php', array('id' => $id)), $name,
                    array('target' => '_blank'));
                $cells = array(new html_table_cell($cohortlink), new html_table_cell($value));
                $table->data[] = new html_table_row($cells);
            }
            $output .= $this->container(html_writer::table($table));
        }

        return $output;
    }

    /**
     * Render the tabs used when editing custom menu items.
     *
     * @param string $currenttab Name of the current tab.
     * @param integer $item The item for linking to.
     *
     * @return HTML to render the tabs.
     */
    public function totara_menu_tabs($currenttab, $item = null) {

        // Setup the top row of tabs.
        $toprow = array();

        $disabled = array();
        // Disable the access tab unless the menu item has custom visibility.
        if ($item->visibility != \totara_core\totara\menu\menu::SHOW_CUSTOM) {
            $disabled[] = 'rules';
        }

        $toprow[] = new tabobject('edit', new moodle_url('/totara/core/menu/edit.php', array('id' => $item->id)),
                get_string('menuitem:edit', 'totara_core'));

        $toprow[] = new tabobject('rules', new moodle_url('/totara/core/menu/rules.php', array('id' => $item->id)),
                get_string('menuitem:editaccess', 'totara_core'));

        return print_tabs(array($toprow), $currenttab, $disabled, null, true);
    }


    /**
     * The renderer for the My Reports page.
     */
    public function my_reports_page() {
        global $CFG;

        // This is required for scheduled_reports_add_form.
        require_once($CFG->dirroot . '/totara/reportbuilder/scheduled_forms.php');

        // Prepare the data for the list of reports.
        $reports = get_my_reports_list();
        $context = context_system::instance();
        $canedit = has_capability('totara/reportbuilder:managereports',$context);

        // Prepare the data for the list of scheduled reports.
        $scheduledreports = get_my_scheduled_reports_list();

        // Get the form that allow you to select a report to schedule.
        $mform = new scheduled_reports_add_form($CFG->wwwroot . '/totara/reportbuilder/scheduled.php', array());
        $addform = $mform->render();

        // Build the template data.
        $template_data = $this->scheduled_reports_export_for_template($scheduledreports, true, $addform);
        $template_data->report_list = $this->report_list_export_for_template($reports, $canedit);

        return $this->render_from_template('totara_core/myreports', $template_data);
    }

    /**
     * Generate text for additional copyrighted material acknowledgements on notifications page.
     *
     * @return string
     */
    public function acknowledgements() {
        return <<<EOF
The work of Open Source community themers distributed as part of <a href="https://github.com/bmbrands/theme_bootstrap">Theme Bootstrap</a><br />
Copyright &copy; 2015 Bas Brands, David Scotson and other contributors.<br />
<a href="http://www.gnu.org/licenses/gpl-3.0.en.html">GNU General Public License</a>
EOF;
    }
}
