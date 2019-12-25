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
use totara_form\form\element\textarea;
use totara_form\form\group\section;
use totara_form\form\group\buttons;
use totara_form\form\element\select;
use totara_form\form\element\hidden;
use totara_form\form\element\action_button;
use totara_form\form\element\editor;
use totara_form\form\element\static_html;
use totara_form\form\clientaction\hidden_if;

/**
 * Class versionform
 * This form manages primary localised policy, which defines original content and consent options for policy version
 */
class versionform extends form {
    protected function definition() {
        $model = $this->model;

        $params = $this->get_parameters();
        $previewonly = !empty($params['previewonly']);
        if (!$previewonly) {
            $previewfield = $model->add(new hidden('preview', PARAM_INT));
            ['preview' => $preview] = $this->model->get_current_data('preview');
            $ispreview = !empty($preview);

            $model->add(new hidden('versionnumber', PARAM_INT));

            if (isset($params['hidden'])) {
                foreach ($params['hidden'] as $name => $type) {
                    $model->add(new hidden($name, $type));
                }
            }

            ['versionnumber' => $versionnumber] = $this->model->get_current_data('versionnumber');

            // Edit section
            $editsection = $this->model->add(new section('edit_policyversion', ''));
            $editsection->set_collapsible(false);
            $editsection->set_expanded(true);

            $options = get_string_manager()->get_list_of_translations();
            $languageselect = $editsection->add(new select('language', get_string('policyprimarylanguage', 'tool_sitepolicy'), $options));

            $policytitle = $editsection->add(new text('title', get_string('policytitle', 'tool_sitepolicy'), PARAM_TEXT));
            $policytitle->set_attributes(['size' => 1335, 'required' => !$previewonly]);

            $policyeditor = $editsection->add(new editor('policytext', get_string('policystatement', 'tool_sitepolicy')));
            $policyeditor->set_attributes(['rows' => 20, 'required' => !$previewonly]);

            $statements = new element\statement('statements');
            $editsection->add($statements);
            $statements->set_attribute('required', !$previewonly);

            $model->add_clientaction(new hidden_if($editsection))->not_empty($previewfield);

            if ($versionnumber > 1) {
                $whatschangedsection = $this->model->add(new section('whatschanged', get_string('policyversionwhatschanged', 'tool_sitepolicy')));
                $whatschangedsection->set_collapsible(true);
                $whatschangedsection->set_expanded(true);

                $whatsnew = $whatschangedsection->add(new editor('whatsnew', get_string('policyversionchanges', 'tool_sitepolicy')));
                $whatsnew->set_attributes(['rows' => 5]);

                $model->add_clientaction(new hidden_if($whatschangedsection))->not_empty($previewfield);
            }
        }

        // Preview section
        $previewsection = $this->model->add(new section('preview_policyversion', ''));
        $previewsection->set_collapsible(false);
        $previewsection->set_expanded(true);

        if (!empty($params['previewnotification'])) {
            $previewnotification = $previewsection->add(new static_html('previewnotification', '', $params['previewnotification']));
        }

        // When in previewonly mode - use currentdata as the fields are not created
        if ($previewonly) {
            $data = $this->model->get_current_data(null);
            $versionnumber = $data['versionnumber'];
        } else {
            // Use the current field values
            // We can't use get_raw_post_data here as all field creation and population steps may not have completed yet
            $data = [];
            $data = array_merge($data, $policytitle->get_data());
            $data = array_merge($data, $policyeditor->get_data());
            $data = array_merge($data, $statements ->get_data());
            if ($versionnumber > 1) {
                $data = array_merge($data, $whatsnew ->get_data());
            }
        }

        // Use original format values for previewing editor data
        $options = [];
        $options['title'] = $data['title'];
        $options['policytext'] = $data['policytext'];
        $options['policytextformat'] = $data['policytextformat'] ?? FORMAT_HTML;
        $options['viewonly']  = true;
        if ($versionnumber > 1) {
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

        if (!$previewonly) {
            $model->add_clientaction(new hidden_if($previewsection))->is_empty($previewfield);

            // No action buttons on preview only
            $buttongroup = $model->add(new buttons('actionbuttonsgroup'), -1);

            $previewbutton = $buttongroup->add(new action_button('previewbutton', get_string('policypreview', 'tool_sitepolicy'), action_button::TYPE_SUBMIT));
            $continuebutton = $buttongroup->add(new action_button('continuebutton', get_string('policycontinueedit', 'tool_sitepolicy'), action_button::TYPE_SUBMIT));
            $model->add_action_buttons(true, get_string('policysave', 'tool_sitepolicy'));

            $model->add_clientaction(new hidden_if($previewbutton))->not_empty($previewfield);
            $model->add_clientaction(new hidden_if($continuebutton))->is_empty($previewfield);
        }
    }

    /**
     * Returns class responsible for form handling.
     * This is intended especially for ajax processing.
     *
     * @return null|\totara_form\form_controller
     */
    public static function get_form_controller() {
        return new versionform_controller();
    }

    /**
     * Prepares current data for this form given the localised policy.
     *
     * @param localisedpolicy $localisedpolicy
     * @param bool $newpolicy
     * @param string $returnpage
     * @return array[] currentdata and parameters
     */
    public static function prepare_current_data(localisedpolicy $localisedpolicy, bool $newpolicy, string $returnpage) {

        // Passing editor formats as strings as the totara_form/element/editor expects it as a string when determining currently selected
        $version = $localisedpolicy->get_policyversion();
        $currentdata = [
            'versionnumber' => $version->get_versionnumber(),
            'preview' => '',
            'language' => $localisedpolicy->get_language(false),
            'title' => $localisedpolicy->get_title(false),
            'policytext' => $localisedpolicy->get_policytext(false),
            'policytextformat' => (string) $localisedpolicy->get_policytextformat(),
            'whatsnew' => $localisedpolicy->get_whatsnew(false),
            'whatsnewformat' => (string) $localisedpolicy->get_whatsnewformat(),
            'statements' => $localisedpolicy->get_statements(false),
            'localisedpolicy' => $localisedpolicy->get_id(),
            'policyversionid' => $version->get_id(),
            'sitepolicyid' => $version->get_sitepolicy()->get_id(),
            'newpolicy' => $newpolicy,
            'ret' => $returnpage,
        ];

        $params = [
            'hidden' => [
                'localisedpolicy' => PARAM_INT,
                'policyversionid' => PARAM_INT,
                'sitepolicyid' => PARAM_INT,
                'newpolicy' => PARAM_BOOL,
                'ret' => PARAM_TEXT,
            ],
        ];

        return [$currentdata, $params];
    }
}