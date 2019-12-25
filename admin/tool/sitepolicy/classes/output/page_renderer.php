<?php
/**
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package tool_sitepolicy
 */

namespace tool_sitepolicy\output;

defined('MOODLE_INTERNAL') || die();

use \tool_sitepolicy\url_helper;
use \tool_sitepolicy\sitepolicy;
use \tool_sitepolicy\policyversion;
use \tool_sitepolicy\localisedpolicy;
use \tool_sitepolicy\userconsent;
use \tool_sitepolicy_renderer;
use \tool_sitepolicy\form\versionform;
use \tool_sitepolicy\form\translationform;
use \html_writer;

/**
 * This renderer produces pages for the site policy tool.
 */
class page_renderer extends \plugin_renderer_base {

    /**
     * Returns a site policy renderer.
     *
     * @return tool_sitepolicy_renderer
     */
    private function get_sitepolicy_renderer() {
        /** @var tool_sitepolicy_renderer $renderer */
        $renderer = $this->page->get_renderer('tool_sitepolicy');
        return $renderer;
    }

    /**
     * Displays a site policy list.
     *
     * @return string
     */
    public function sitepolicy_list() {
        $renderer = $this->get_sitepolicy_renderer();

        $html = $renderer->header();
        $html .= $renderer->heading($this->page->heading);
        $html .= $renderer->manage_site_policy_table();
        $html .= $renderer->footer();
        return $html;
    }

    /**
     * Displays the form to create a new policy.
     *
     * @param \tool_sitepolicy\form\versionform $form
     * @return string
     */
    public function sitepolicy_create_new_policy(versionform $form) {
        $renderer = $this->get_sitepolicy_renderer();

        $html = $renderer->header();
        $html .= $renderer->heading(get_string('policycreatenew', 'tool_sitepolicy'));
        $html .= $renderer->form($form);
        $html .= $renderer->footer();
        return $html;
    }

    /**
     * Displays a list of policy version translations.
     *
     * @param policyversion $policyversion
     * @return string
     */
    public function policyversion_translation_list(policyversion $policyversion) {
        $renderer = $this->get_sitepolicy_renderer();

        $html = $renderer->header();
        $html .= $renderer->heading($this->page->heading);

        $versionlisturl = url_helper::version_list($policyversion->get_sitepolicy()->get_id());
        $html .= html_writer::link($versionlisturl, get_string('translationsbacktoversions', 'tool_sitepolicy'));

        if ($policyversion->is_draft()) {
            $html .= $renderer->add_translation_single_select($policyversion);
        }

        $html .= $renderer->manage_translation_table($policyversion);
        $html .= $renderer->footer();
        return $html;
    }

    /**
     * Displays a policy version list.
     *
     * @param policyversion $policyversion
     * @return string
     */
    public function policyversion_list(policyversion $policyversion) {
        $renderer = $this->get_sitepolicy_renderer();

        $html = $renderer->header();
        if ($policyversion->has_incomplete_language_translations()) {
            // Print a notification about incomplete translations.
            $html .= $renderer->incomplete_language_translation_notification($policyversion);
        }
        $html .= $renderer->heading(get_string('versionsheading', 'tool_sitepolicy', $policyversion->get_primary_title(true)));
        if (!$policyversion->is_draft()) {
            // Show create new draft when latest version is published or achived.
            $newdrafturl = url_helper::version_list($policyversion->get_sitepolicy()->get_id());
            $newdrafturl->param('action', 'newdraft');
            $html .= $renderer->single_button($newdrafturl, get_string('versionscreatenew', 'tool_sitepolicy'));
        }
        $html .= $renderer->manage_version_policy_table($policyversion->get_sitepolicy()->get_id());
        $html .= $renderer->footer();
        return $html;
    }

    /**
     * Display a form to edit localised versions.
     *
     * @param policyversion $version
     * @param \tool_sitepolicy\form\versionform $form
     * @param bool $newpolicy
     * @return string
     */
    public function localisedversion_edit(policyversion $version, versionform $form, bool $newpolicy) {

        $renderer = $this->get_sitepolicy_renderer();

        $html = $renderer->header();

        $params = [
            'title' => $this->page->title
        ];
        if ($newpolicy) {
            $heading = get_string('versionformheadernew', 'tool_sitepolicy', $params);
        } else {
            $params['versionnumber'] = $version->get_versionnumber();
            $heading = get_string('versionformheader', 'tool_sitepolicy', $params);
        }

        $html .= $renderer->heading($heading);
        $html .= $renderer->form($form);
        $html .= $renderer->footer();
        return $html;
    }

    /**
     * Displays a form to edit translations.
     *
     * @param localisedpolicy $localisedpolicy
     * @param \tool_sitepolicy\form\translationform $form
     * @return string
     */
    public function localisedversion_translation_edit(localisedpolicy $localisedpolicy, translationform $form) {

        $heading = get_string('translationtolang', 'tool_sitepolicy', [
            'title' => $localisedpolicy->get_primary_title(true),
            'language' => $localisedpolicy->get_language(true)
        ]);

        $renderer = $this->get_sitepolicy_renderer();

        $html = $renderer->header();
        $html .= $renderer->heading($heading);
        $html .= $renderer->form($form);
        $html .= $renderer->footer();
        return $html;
    }

    /**
     * Previews a site policy.
     *
     * @param \tool_sitepolicy\form\versionform $form
     * @return string
     */
    public function sitepolicy_preview(versionform $form) {
        global $USER;

        $renderer = $this->get_sitepolicy_renderer();

        $html = $renderer->header();
        $html .= $renderer->form($form);
        $html .= $renderer->footer();

        return $html;
    }

    /**
     * Displays the user consent embedded report.
     *
     * @param \reportbuilder $report
     * @param int $debug
     * @param int $sid
     * @return string
     */
    public function consent_report(\reportbuilder $report, int $debug = 0, int $sid = 0) {

        // Prepare the required renderers.
        $renderer = $this->get_sitepolicy_renderer();
        /** @var \totara_reportbuilder_renderer $reportrenderer */
        $reportrenderer = $this->page->get_renderer('totara_reportbuilder');

        $html = $renderer->header();

        // This must be done after the header and before any other use of the report.
        list($tablehtml, $debughtml) = $reportrenderer->report_html($report, $debug);

        $strheading = get_string('embeddedtitle', 'rb_source_tool_sitepolicy');
        $heading = $strheading . ': ' . $reportrenderer->result_count_info($report);
        $html .= $renderer->heading($heading);

        $html .= $debughtml;

        ob_start();
        $report->display_search();
        $report->display_sidebar_search();
        $html .= ob_get_clean();

        $html .= $report->display_saved_search_options();

        $html .= $tablehtml;

        // Export button.
        ob_start();
        $reportrenderer->export_select($report, $sid);
        $html .= ob_get_clean();

        $html .= $renderer->footer();
        return $html;
    }

}
