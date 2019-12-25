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
 * @author Courteney Brownie <courteney.brownie@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package tool_sitepolicy
 */

use \tool_sitepolicy\sitepolicy;
use \tool_sitepolicy\policyversion;
use \tool_sitepolicy\localisedpolicy;
use \tool_sitepolicy\url_helper;
use \tool_sitepolicy\userconsent;

defined('MOODLE_INTERNAL') || die();

/**
 * Class tool_sitepolicy_renderer
 *
 * The following methods pass through to the core renderer:
 * @method header()
 * @method heading($text)
 * @method footer()
 * @method single_button($url, $label, $method = 'post', array $options = null)
 * @method notification($message, $type = null)
 * @method help_icon($identifier, $component = 'moodle', $linktext = '')
 * @method box_start($classes, $id)
 * @method box_end()
 */
class tool_sitepolicy_renderer extends plugin_renderer_base {

    /**
     * Generates Site Policies table
     * @return string
     */
    public function manage_site_policy_table() {
        $out = $this->single_button(url_helper::sitepolicy_create(), get_string('policycreatenew', 'tool_sitepolicy'));

        $sitepolicylist = sitepolicy::get_sitepolicylist();

        $table = new html_table();
        $row = [];

        $numpolicies = count($sitepolicylist);
        if ($numpolicies < 1) {
            $table->head[] = (get_string('policiesempty', 'tool_sitepolicy'));
        } else {
            $table->head = [
                get_string('policieslabelname', 'tool_sitepolicy'),
                get_string('policieslabelrevisions', 'tool_sitepolicy'),
                get_string('policieslabelstatus', 'tool_sitepolicy'),
            ];
            foreach ($sitepolicylist as $entry) {
                $versionlisturl = url_helper::version_list($entry->id);
                $versionformurl = url_helper::version_edit($entry->localisedpolicyid, 'policies');
                $rowitems = [];

                // Title
                $title = format_string($entry->title, true, ['context' => context_system::instance()]);
                $rowitems[] = new html_table_cell(html_writer::link($versionlisturl, $title));

                // Status
                $status = get_string('policystatus'.$entry->status, 'tool_sitepolicy');
                $draft = (int)($entry->status == policyversion::STATUS_DRAFT);
                $draftlink = '';
                if ($draft) {
                    $draftlink = $this->help_icon('policystatusdraft', 'tool_sitepolicy') . ' ' .
                        html_writer::link($versionformurl, s(get_string('policiesrevisionnewdraft', 'tool_sitepolicy')));
                }

                // Revisions
                $rowitems[] = new html_table_cell($entry->numpublished . ' ' . $draftlink);
                $rowitems[] = new html_table_cell(s($status));
                $row[] = new html_table_row($rowitems);
            }
        }
        $table->data = $row;
        $out .= $this->output->render($table);
        return $out;
    }

    /**
     * Generates localised policy table
     * @param int $sitepolicyid
     * @return string
     */
    public function manage_version_policy_table(int $sitepolicyid) {
        $table = new html_table();
        $table->head = [
            get_string('versionslabelversion', 'tool_sitepolicy'),
            get_string('versionslabelstatus', 'tool_sitepolicy'),
            get_string('versionslabelnumtrans', 'tool_sitepolicy'),
            get_string('versionslabeldatepublish', 'tool_sitepolicy'),
            get_string('versionslabeldatearchive', 'tool_sitepolicy'),
            get_string('versionslabelactions', 'tool_sitepolicy')
        ];
        $row = [];
        $versionlist = policyversion::get_versionlist($sitepolicyid);
        $out = '';

        foreach ($versionlist as $entry) {
            $versionformurl = url_helper::version_edit($entry->primarylocalisedid, 'versions');
            $rowitems = [];

            // Version number.
            $rowitems[''] = new html_table_cell($entry->versionnumber);

            // Status.
            $status = $entry->status;
            $statusstr = get_string('versionstatus'. $entry->status, 'tool_sitepolicy');
            $rowitems[] = new html_table_cell($statusstr);

            // Number of translations.
            $translationlisturl = url_helper::localisedpolicy_list($entry->id);
            $incomplete = "";
            if ($status == policyversion::STATUS_DRAFT && $entry->cnt_translatedoptions != $entry->cnt_options) {
                $incomplete = $this->output->flex_icon('warning',
                    ['alt' => get_string('versionstatusincomplete', 'tool_sitepolicy')]
                );
            }

            $viewstr = get_string('versionstranslationsview', 'tool_sitepolicy');

            $a = new stdClass();
            $a->cnt = $entry->cnt_translations;
            $a->link = html_writer::link($translationlisturl, $viewstr);
            $a->incomplete = $incomplete;
            $cellentry = get_string('versionpolicycellentry', 'tool_sitepolicy', $a);

            $rowitems[] = new html_table_cell($cellentry);

            // Options
            $options = [];
            switch ($status) {
                case policyversion::STATUS_DRAFT:
                    $rowitems[] = new html_table_cell('-');
                    $rowitems[] = new html_table_cell('-');

                    $publishurl = url_helper::version_publish($entry->id);
                    $deleteurl = url_helper::version_delete($entry->id);

                    if (!empty($incomplete)) {
                        $options[] = $this->output->action_icon('#',
                            new pix_icon('a/logout', get_string('versionpublish', 'tool_sitepolicy'), 'moodle'), null, ['disabled' => 'disabled']);
                    } else {
                        $options[] = $this->output->action_icon($publishurl,
                            new pix_icon('a/logout', get_string('versionpublish', 'tool_sitepolicy'), 'moodle'));
                    }
                    $options[] = $this->output->action_icon($versionformurl, new pix_icon('i/manual_item', get_string('versionedit', 'tool_sitepolicy'), 'moodle'));

                    $options[] = $this->output->action_icon($deleteurl,
                        new pix_icon('i/delete', get_string('versiondelete', 'tool_sitepolicy'), 'moodle'));
                    $out = $this->single_button($versionformurl, get_string('versionscontinueedit', 'tool_sitepolicy'));
                    break;

                case policyversion::STATUS_PUBLISHED:
                    $rowitems[] = new html_table_cell(userdate($entry->timepublished));
                    $rowitems[] = new html_table_cell('-');

                    $archiveurl = url_helper::version_archive($entry->id);
                    $options[] = $this->output->action_icon($archiveurl,
                        new pix_icon('archive', get_string('versionarchive', 'tool_sitepolicy'),
                            'tool_sitepolicy'));
                    break;

                case policyversion::STATUS_ARCHIVED:
                    $rowitems[] = new html_table_cell(userdate($entry->timepublished));
                    $rowitems[] = new html_table_cell(userdate($entry->timearchived));
            }

            $rowitems[] = new html_table_cell(implode('', $options));
            $row[] = new html_table_row($rowitems);
        }

        $table->data = $row;
        $out .= $this->output->render($table);

        return $out;
    }

    /**
     * Generates Add translation single select
     *
     * @param policyversion $policyversion
     * @return string
     */
    public function add_translation_single_select(policyversion $policyversion) {
        /** @var core_string_manager_standard $sm */
        $syslanguages = get_string_manager()->get_list_of_translations();
        $verlanguages = $policyversion->get_languages();
        $options = array_diff_key($syslanguages, $verlanguages);

        $select = new \single_select(
            url_helper::version_create($policyversion->get_id(), null),
            'language',
            $options,
            '',
            ['' => get_string('translationsadd', 'tool_sitepolicy')],
            'addtranslationform'
        );
        $select->class = 'singleselect pull-right';

        // If there are no languages available to translate then disable the select box and add a meaningful title.
        if (empty($options)) {
            $select->disabled = true;
            $select->tooltip = get_string('morelanguagesrequired', 'tool_sitepolicy');
        }

        return $this->output->render($select);
    }

    /**
     * Generates Translations table
     *
     * @param policyversion $policyversion
     * @return string
     */
    public function manage_translation_table(policyversion $policyversion) {
        $table = new html_table();

        $table->head = [
            get_string('translationslabellanguage', 'tool_sitepolicy'),
            get_string('translationslabelstatus', 'tool_sitepolicy'),
            get_string('translationslabeloptions', 'tool_sitepolicy')
        ];

        $row = [];

        $policyversionsummary = $policyversion->get_summary();
        $out = '';

        foreach ($policyversionsummary as $entries => $entry) {
            $versionformurl = url_helper::version_edit($entry->id, 'translations');
            $translationformurl = url_helper::localisedpolicy_edit($entry->id);
            $deleteurl = url_helper::localisedpolicy_delete($entry->id);
            $translations = get_string_manager()->get_list_of_translations(true);
            if (isset($translations[$entry->language])) {
                $language = $translations[$entry->language];
            } else {
                // Hmmm not a known translation, I bet it is
                $language = get_string_manager()->get_list_of_languages($entry->primarylanguage)[$entry->language];
            }
            $rowitems = [];

            // Language.
            $languagestr = $language;
            if ($entry->isprimary == 1) {
                $languagestr = get_string('translationprimary', 'tool_sitepolicy', $language);

            }
            $viewpolicyurl = url_helper::sitepolicy_view($policyversion->get_id(), $entry->language, $policyversion->get_versionnumber());
            $rowitems[] = new html_table_cell(html_writer::link($viewpolicyurl, $languagestr));

            // Status.
            if (!empty($entry->timepublished)) {
                // Active or archived.
                $rowitems[] = new html_table_cell(get_string('translationstatuscomplete', 'tool_sitepolicy'));
                $rowitems[] = new html_table_cell('-');
            } else {
                // Draft.
                $status = get_string('translationstatuscomplete', 'tool_sitepolicy');
                if ($entry->incomplete) {
                    $status = get_string('translationstatusincomplete', 'tool_sitepolicy');
                }
                $rowitems[] = new html_table_cell($status);

                $option = [];
                if ($entry->isprimary == 1) {
                    $option[] = $this->output->action_icon($versionformurl, new pix_icon('i/manual_item', get_string('translationedit', 'tool_sitepolicy'), 'moodle'));
                } else {
                    $option[] = $this->output->action_icon($translationformurl, new pix_icon('i/manual_item', get_string('translationedit', 'tool_sitepolicy'), 'moodle'));
                    $option[] = $this->output->action_icon($deleteurl, new pix_icon('i/delete', get_string('translationdelete', 'tool_sitepolicy'), 'moodle'));
                }
                $rowitems[] = new html_table_cell(implode('', $option));
            }

            $row[] = new html_table_row($rowitems);
        }

        $table->data = $row;
        $out .= $this->output->render($table);

        return $out;
    }

    /**
     * Confirmation page for version actions
     * @param string $heading
     * @param string $message
     * @param single_button $continue
     * @param single_button $cancel
     * @return string
     */
    public function action_confirm(string $heading, string $message, single_button $continue, single_button $cancel): string {
        $output = $this->box_start('generalbox modal modal-dialog modal-in-page show', 'notice');
        $output .= $this->box_start('modal-content', 'modal-content');
        $output .= $this->box_start('modal-header', 'modal-header');
        $output .= html_writer::tag('h4', $heading);
        $output .= $this->box_end();
        $output .= $this->box_start('modal-body', 'modal-body');
        $output .= html_writer::tag('p', $message);
        $output .= $this->box_end();
        $output .= $this->box_start('modal-footer', 'modal-footer');
        $output .= html_writer::tag('div', $this->render($continue) . $this->render($cancel), ['class' => 'buttons']);
        $output .= $this->box_end();
        $output .= $this->box_end();
        $output .= $this->box_end();
        return $output;
    }

    /**
     * Generates User Consents table
     * @param int $userid
     * @return string
     */
    public function manage_userconsents_table(int $userid) {

        $consentresponse = userconsent::get_userconsenttable($userid);

        $table = new html_table();
        if (empty($consentresponse)) {
            $table->head[] = (get_string('userconsentlistempty', 'tool_sitepolicy'));
        } else {
            $table->head = [
                get_string('userconsentlistlabelpolicy', 'tool_sitepolicy'),
                get_string('userconsentlistlabelversion', 'tool_sitepolicy'),
                get_string('userconsentlistlabellanguage', 'tool_sitepolicy'),
                get_string('userconsentlistlabelstatement', 'tool_sitepolicy'),
                get_string('userconsentlistlabelresponse', 'tool_sitepolicy'),
                get_string('userconsentlistlabeldateconsented', 'tool_sitepolicy')

            ];
            $row = [];

            $previousid = 0;
            $translations = get_string_manager()->get_list_of_translations(true);
            $languages = get_string_manager()->get_list_of_languages();
            foreach ($consentresponse as $response) {
                $rowitems = [];

                //Policy Title and Version Number - if needed
                $rowitems[] = new html_table_cell('');
                $rowitems[] = new html_table_cell('');
                if ($response->policyversionid != $previousid) {
                    $myviewpolicyurl = url_helper::user_sitepolicy_version_view($userid, $response->policyversionid, $response->versionnumber, $response->language, 1, 1);
                    $rowitems[0] = new html_table_cell(html_writer::link($myviewpolicyurl, $response->title));
                    $rowitems[1] = new html_table_cell($response->versionnumber);
                }

                // Language.
                $language = $response->language;
                if (isset($translations[$language])) {
                    $language = $translations[$language];
                } else if (isset($languages[$language])) {
                    // Just a guess really, the translation has been uninstalled.
                    $language = $languages[$language];
                }
                $rowitems[] = new html_table_cell($language);

                // Consent Statement
                $rowitems[] = new html_table_cell($response->statement);

                // Consent Response
                $rowitems[] = new html_table_cell($response->response);

                // Date Consented
                $rowitems[] = new html_table_cell(userdate($response->timeconsented));
                $row[] = new html_table_row($rowitems);
                $previousid = $response->policyversionid;

            }
            $table->data = $row;
        }
        $out = '';
        $out .= $this->output->render($table);
        return $out;
    }

    /**
     * Renders a totara form.
     *
     * @param \totara_form\form $form
     * @return string
     */
    public function form(\totara_form\form $form) {
        return $form->render();
    }

    /**
     * Displays a notification to warn about incomplete translations.
     *
     * @param policyversion $policyversion
     * @return string
     */
    public function incomplete_language_translation_notification(policyversion $policyversion): string {
        $incompletelanguages = $policyversion->get_incomplete_language_translations();
        if (empty($incompletelanguages)) {
            return '';
        }

        $message = get_string('publishincompletedesc', 'tool_sitepolicy');
        $message .= html_writer::alist($incompletelanguages);
        $message .= get_string('publishincompleteaction', 'tool_sitepolicy');
        return $this->notification($message, \core\output\notification::NOTIFY_WARNING);
    }

    /**
     * Displays a publish version confirmation dialog.
     *
     * @param policyversion $policyversion
     * @return string
     */
    public function publish_version_confirmation(policyversion $policyversion): string {
        if (!$policyversion->is_complete()) {
            throw new \coding_exception('Policy versions cannot be published if there are incomplete localised versions.');
        }

        $message = $this->heading(get_string('publishpolicytitle', 'tool_sitepolicy', $policyversion->get_primary_title(true)));
        $message .= get_string('publishlistheading', 'tool_sitepolicy');

        $message .= html_writer::alist([
            get_string('publishlist1', 'tool_sitepolicy'),
            get_string('publishlist2', 'tool_sitepolicy'),
            get_string('publishlist3', 'tool_sitepolicy'),
            get_string('publishlist4', 'tool_sitepolicy'),
        ]);
        $message .= get_string('publishlangheading', 'tool_sitepolicy');
        $message .= html_writer::alist(array_keys($policyversion->get_languages(true)));

        $confirmurl = url_helper::version_publish($policyversion->get_id());
        $confirmurl->param('confirm', 1);
        $continue = new single_button($confirmurl, get_string('publishpublish', 'tool_sitepolicy'));

        $cancelurl = url_helper::version_list($policyversion->get_sitepolicy()->get_id());
        $cancel = new single_button($cancelurl, get_string('publishcancel', 'tool_sitepolicy'));
        return $this->action_confirm($this->page->heading, $message, $continue, $cancel);
    }

    /**
     * Displays a delete translation confirmation dialog.
     *
     * @param localisedpolicy $localisedpolicy
     * @return string
     */
    public function delete_translation_confirmation(localisedpolicy $localisedpolicy): string {
        $strparams = [
            'title' => $localisedpolicy->get_primary_title(true),
            'language' => $localisedpolicy->get_language(true)
        ];
        $message = $this->heading(get_string('deletetranslationtitle', 'tool_sitepolicy', $strparams));
        $message .= get_string('deletetranslationmessage', 'tool_sitepolicy');
        $deleteurl = url_helper::localisedpolicy_delete($localisedpolicy->get_id());
        $deleteurl->param('confirm', 1);
        $delete = new single_button($deleteurl, get_string('deletetranslationdelete', 'tool_sitepolicy'));

        $cancelurl = url_helper::localisedpolicy_list($localisedpolicy->get_policyversion()->get_id());
        $cancel = new single_button($cancelurl, get_string('deletetranslationcancel', 'tool_sitepolicy'));

        return $this->action_confirm($this->page->title, $message, $delete, $cancel);
    }

    /**
     * Displays a delete version confirmation
     *
     * @param policyversion $policyversion
     * @return string
     */
    public function delete_version_confirmation(policyversion $policyversion) {
        $primarypolicy = localisedpolicy::from_version($policyversion, ['isprimary' => localisedpolicy::STATUS_PRIMARY]);

        // Show confirmation.
        if ($policyversion->get_versionnumber() == 1) {
            $message = $this->heading(get_string('deletepolicytitle', 'tool_sitepolicy', $primarypolicy->get_primary_title(true)));
        } else {
            $strparams = [
                'title' => $primarypolicy->get_primary_title(true),
                'version' => $policyversion->get_versionnumber()
            ];
            $message = $this->heading(get_string('deleteversiontitle', 'tool_sitepolicy', $strparams));
        }
        $message .= get_string('deletelistheading', 'tool_sitepolicy');

        $policyversionlang = $policyversion->get_languages(true);
        $message .= html_writer::alist(array_keys($policyversionlang));

        $deleteurl = url_helper::version_delete($policyversion->get_id());
        $deleteurl->param('confirm', 1);
        $delete = new single_button($deleteurl, get_string('deleteversiondelete', 'tool_sitepolicy'));

        $cancelurl = url_helper::version_list($policyversion->get_sitepolicy()->get_id());
        $cancel = new single_button($cancelurl, get_string('deleteversioncancel', 'tool_sitepolicy'));

        return $this->action_confirm($this->page->heading, $message, $delete, $cancel);
    }

    /**
     * Displays an archive version confirmation.
     *
     * @param policyversion $policyversion
     * @return string
     */
    public function archive_version_confirmation(policyversion $policyversion) {
        // Show confirmation.
        $primarypolicy = localisedpolicy::from_version($policyversion, ['isprimary' => localisedpolicy::STATUS_PRIMARY]);
        $strparams = [
            'title' => $primarypolicy->get_title(true),
            'version' => $policyversion->get_versionnumber()
        ];
        $message = $this->heading(get_string('archivetitle', 'tool_sitepolicy', $strparams));
        $message .= get_string('archivemessage', 'tool_sitepolicy');

        $archiveurl = url_helper::version_archive($policyversion->get_id());
        $archiveurl->param('confirm', 1);
        $continue = new single_button($archiveurl, get_string('archivearchive', 'tool_sitepolicy'));

        $cancelurl = url_helper::version_list($policyversion->get_sitepolicy()->get_id());
        $cancel = new single_button($cancelurl, get_string('archivecancel', 'tool_sitepolicy'));

        return $this->action_confirm($this->page->heading, $message, $continue, $cancel);
    }
}
