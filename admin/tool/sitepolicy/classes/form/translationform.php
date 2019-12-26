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

namespace tool_sitepolicy\form;

defined('MOODLE_INTERNAL') || die();

use tool_sitepolicy\localisedpolicy;
use tool_sitepolicy\element\sitepolicy;
use totara_form\form;
use totara_form\form\element\text;
use totara_form\form\element\static_html;
use totara_form\form\element\textarea;
use totara_form\form\element\hidden;
use totara_form\form\group\section;
use totara_form\form\group\buttons;
use totara_form\form\element\action_button;
use totara_form\form\element\editor;
use totara_form\form\clientaction\hidden_if;

/**
 * Class translationform
 * This form is responsible for localisation of the site policy version.
 */
class translationform extends form {
    protected function definition() {

        $model = $this->model;

        $previewfield = $model->add(new hidden('preview', PARAM_INT));
        ['preview' => $preview] = $this->model->get_current_data('preview');
        $ispreview = !empty($preview);

        $model->add(new hidden('localisedpolicy', PARAM_INT));
        $model->add(new hidden('language', PARAM_SAFEDIR)); // We can't use PARAM_LANG here as the language pack may have been uninstalled.
        $model->add(new hidden('policyversionid', PARAM_INT));

        // Edit section
        /** @var section $editsection */
        $editsection = $this->model->add(new section('edit_translation', ''));
        $editsection->set_collapsible(false);
        $editsection->set_expanded(true);

        /** @var static_html $primarytitle */
        $primarytitle = $editsection->add(new static_html('primarytitle', '&nbsp;', $this->parameters['primarytitle']));
        if ($this->parameters['preformatted']) {
            $primarytitle->set_allow_xss(true);
        }

        /** @var text $policytitle */
        $policytitle = $editsection->add(new text('title', get_string('policytitle', 'tool_sitepolicy'), PARAM_TEXT));
        $policytitle->set_attribute('size', 1335);
        $policytitle->set_attribute('required', true);

        /** @var static_html $primarypolicy */
        $primarypolicy = $editsection->add(new static_html('primarypolicytext', '&nbsp;', '<div class="primarypolicybox">' . $this->parameters['primarypolicytext'] . '</div>'));
        if ($this->parameters['preformatted']) {
            $primarypolicy->set_allow_xss(true);
        }
        /** @var editor $policyeditor */
        $policyeditor = $editsection->add(new editor('policytext', get_string('policystatement', 'tool_sitepolicy')));
        $policyeditor->set_attributes(['rows' => 20, 'required' => true]);

        $statements = new element\statement('statements', true);
        $model->add($statements);
        $statements->set_attribute('required', true);

        $model->add_clientaction(new hidden_if($editsection))->not_empty($previewfield);

        if ($this->parameters['versionnumber'] > 1) {
            /** @var section $whatschangedsection */
            $whatschangedsection = $model->add(new section('whatschanged', get_string('policyversionwhatschanged', 'tool_sitepolicy')));
            $whatschangedsection->set_collapsible(true);
            $whatschangedsection->set_expanded(true);

            /** @var static_html $primarywhatsnew */
            $primarywhatsnew = $whatschangedsection->add(new static_html('primarywhatsnew', '&nbsp;', '<div class="primarypolicybox">' . $this->parameters['primarywhatsnew'] . '</div>'));
            if ($this->parameters['preformatted']) {
                $primarywhatsnew->set_allow_xss(true);
            }
            /** @var editor $policywhatsnew */
            $policywhatsnew = $whatschangedsection->add(new editor('whatsnew', get_string('policyversionchanges', 'tool_sitepolicy')));
            $policywhatsnew->set_attributes(['rows' => 5]);

            $model->add_clientaction(new hidden_if($whatschangedsection))->not_empty($previewfield);
        }

        // Preview section
        /** @var section $previewsection */
        $previewsection = $this->model->add(new section('preview_translation', ''));
        $previewsection->set_collapsible(false);
        $previewsection->set_expanded(true);

        if (!empty($this->parameters['previewnotification'])) {
            $previewnotification = $previewsection->add(new static_html('previewnotification', '', $this->parameters['previewnotification']));
        }

        // Use the current field values
        // We can't use get_raw_post_data here as all field creation and population steps may not have completed yet
        $data = [];
        $data = array_merge($data, $policytitle->get_data());
        $data = array_merge($data, $policyeditor->get_data());
        $data = array_merge($data, $statements ->get_data());
        if ($this->parameters['versionnumber'] > 1) {
            $data = array_merge($data, $policywhatsnew->get_data());
        }

        $options = [];
        $options['title'] = $data['title'];
        $options['policytext'] = $data['policytext'];
        $options['policytextformat'] = $data['policytextformat'] ?? FORMAT_HTML;
        $options['viewonly']  = true;
        if (isset($data['whatsnew'])) {
            $options['whatsnew'] = $data['whatsnew'];
            $options['whatsnewformat'] = $data['whatsnewformat'] ?? FORMAT_HTML;
        }

        $options['statements'] = [];
        foreach ($data['statements'] as $idx => $statement) {
            if (empty($statement->removedstatement)) {
                $options['statements'][] = [
                    'mandatory' => $statement->mandatory,
                    'statement' => $statement->statement,
                    'provided' => $statement->provided,
                    'withheld' => $statement->withheld
                ];
            }
        }

        $previewsection->add(new element\sitepolicy('policypreview', $options));
        $model->add_clientaction(new hidden_if($previewsection))->is_empty($previewfield);

        $buttongroup = $model->add(new buttons('actionbuttonsgroup'), -1);
        $previewbutton = $buttongroup->add(new action_button('previewbutton', get_string('policypreview', 'tool_sitepolicy'), action_button::TYPE_SUBMIT));
        $continuebutton = $buttongroup->add(new action_button('continuebutton', get_string('policycontinueedit', 'tool_sitepolicy'), action_button::TYPE_SUBMIT));
        $model->add_action_buttons(true, get_string('translationsave', 'tool_sitepolicy'));

        $model->add_clientaction(new hidden_if($previewbutton))->not_empty($previewfield);
        $model->add_clientaction(new hidden_if($continuebutton))->is_empty($previewfield);
    }

    /**
     * Returns class responsible for form handling.
     * This is intended especially for ajax processing.
     *
     * @return null|\totara_form\form_controller
     */
    public static function get_form_controller() {
        return new translationform_controller();
    }

    /**
     * Prepares current data for this form, given the localised policy, and the primary localised policy.
     *
     * @param localisedpolicy $primarypolicy
     * @param localisedpolicy $localisedpolicy
     * @return array[] currentdata and parameters
     */
    public static function prepare_current_data(localisedpolicy $primarypolicy, localisedpolicy $localisedpolicy) {

        $primaryoptions = $primarypolicy->get_statements(false);
        $options = $localisedpolicy->get_statements(false);
        if (empty($options)) {
            $options = [];
            foreach ($primaryoptions as $option) {
                $option->primarystatement = $option->statement;
                $option->statement = '';
                $option->primaryprovided = $option->provided;
                $option->provided = '';
                $option->primarywithheld = $option->withheld;
                $option->withheld = '';

                $options[] = $option;
            }

        } else {
            // We need to merge the primary and localised options
            // Find all options that are only in one list
            $newprimary = array_diff_key($primaryoptions, $options);

            foreach ($options as $idx => $option) {
                if (isset($primaryoptions[$idx])) {
                    $primaryoption = $primaryoptions[$idx];
                    $option->primarystatement = $primaryoption->statement;
                    $option->primaryprovided = $primaryoption->provided;
                    $option->primarywithheld = $primaryoption->withheld;
                } else {
                    if ($idx < 0) {
                        $option[$idx]->removedstatement = true;
                    } else {
                        unset($option[$idx]);
                    }
                }
            }

            foreach ($newprimary as $idx) {
                $primaryoption = $primaryoptions[$idx];
                $option->primarystatement = $primaryoption->statement;
                $option->statement = '';
                $option->primaryprovided = $primaryoption->provided;
                $option->provided = '';
                $option->primarywithheld = $primaryoption->withheld;
                $option->withheld = '';

                $options[] = $option;
            }
        }

        // Passing editor formats as strings as the totara_form/element/editor expects it as a string when determining currently selected
        $currentdata = [
            'localisedpolicy' => $localisedpolicy->get_id(),
            'language' => $localisedpolicy->get_language(false),
            'policyversionid' => $localisedpolicy->get_policyversion()->get_id(),
            'title' => $localisedpolicy->get_title(false),
            'policytext' => $localisedpolicy->get_policytext(false),
            'policytextformat' => (string) $localisedpolicy->get_policytextformat(),
            'statements' => $options,
            'whatsnew' => $localisedpolicy->get_whatsnew(false),
            'whatsnewformat' => (string) $localisedpolicy->get_whatsnewformat(),
            'preview' => '',
        ];

        $params = [
            // Pass primary values as parameters
            'versionnumber' => $primarypolicy->get_policyversion()->get_versionnumber(),
            'primarytitle' => $primarypolicy->get_title(),
            'primarypolicytext' => $primarypolicy->get_policytext(),
            'primarywhatsnew' => $primarypolicy->get_whatsnew(),
            'preformatted' => true,
        ];
        return [$currentdata, $params];
    }
}
