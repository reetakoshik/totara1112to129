<?php
/**
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package block_totara_featured_links
 */

namespace block_totara_featured_links\tile;

defined('MOODLE_INTERNAL') || die();

use block_totara_featured_links\form\element\audience_list;
use block_totara_featured_links\form\validator\visibility_form_audience_validator;
use block_totara_featured_links\form\validator\visibility_form_custom_validator;
use block_totara_featured_links\form\validator\visibility_form_preset_rules_validator;
use totara_form\form\clientaction\hidden_if;
use totara_form\form\element\checkbox;
use totara_form\form\element\checkboxes;
use totara_form\form\element\hidden;
use totara_form\form\element\radios;
use totara_form\form\element\static_html;
use totara_form\form\group\section;

/**
 * Class base_form_visibility
 * This is the base form for the visibility option
 * This is the class that plugin tile types should extend
 * @package block_totara_featured_links
 */
abstract class base_form_visibility extends base_form {

    /**
     * returns whether or not to show the tile rules options
     * @return boolean
     */
    public abstract function has_custom_rules();

    /**
     * This defines the main part of the visibility form
     */
    public function definition () {
        global $PAGE, $CFG;
        require_once($CFG->dirroot . '/totara/cohort/lib.php');

        $tileid = $this->get_parameters()['tileid'];
        $blockid  = $this->get_parameters()['blockinstanceid'];

        /** @var section $group */
        $group = $this->model->add(new section('group', get_string('visibility_edit', 'block_totara_featured_links')));
        $group->set_collapsible(false);

        $visibility_options = [
            base::VISIBILITY_SHOW => get_string('visibility_show', 'block_totara_featured_links'),
            base::VISIBILITY_HIDE => get_string('visibility_hide', 'block_totara_featured_links'),
            base::VISIBILITY_CUSTOM => get_string('visibility_custom', 'block_totara_featured_links')
        ];

        $visibility = $group->add(new radios(
            'visibility',
            get_string('visibility_label', 'block_totara_featured_links'),
            $visibility_options)
        );
        $visibility->add_validator(new visibility_form_custom_validator());

        /** @var section $audience */
        $audience = $this->model->add(new section('audience', get_string('audience_title', 'block_totara_featured_links')));
        $audience->set_collapsible(true);
        $audience->set_expanded(true);

        if (has_capability('totara/coursecatalog:manageaudiencevisibility', \context_system::instance())) {
            $audience_checkbox = $audience->add(
                new checkbox('audience_showing',
                    get_string('audience_showing', 'block_totara_featured_links')
                )
            );

            $audiences_visible = $audience->add(new hidden('audiences_visible', PARAM_TEXT));
            $audiences_visible->set_frozen(false);

            $audiencelist = $audience->add(new audience_list('audience_visible_table', '&nbsp;', $tileid, $audiences_visible->get_data()['audiences_visible']));

            /** @var static_html $add_audience_button */
            $add_audience_button = $audience->add(
                new static_html('add_audience_button',
                    '&nbsp;',
                    '<input type="button" value="' . get_string('audience_add', 'block_totara_featured_links') . '" id="add_audience_id">'
                )
            );
            $add_audience_button->add_validator(new visibility_form_audience_validator());
            $add_audience_button->set_allow_xss(true);

            $audience_aggregation = $audience->add(new radios(
                'audience_aggregation',
                get_string('audience_aggregation_label', 'block_totara_featured_links'),
                [
                    base::AGGREGATION_ANY => get_string('audience_aggregation_any', 'block_totara_featured_links'),
                    base::AGGREGATION_ALL => get_string('audience_aggregation_all', 'block_totara_featured_links')
                ]
            ));
            $this->model->add_clientaction(new hidden_if($audiencelist))->is_empty($audience_checkbox)->is_empty($audiences_visible);
            $this->model->add_clientaction(new hidden_if($add_audience_button))->is_empty($audience_checkbox);
            $this->model->add_clientaction(new hidden_if($audience_aggregation))->is_empty($audience_checkbox);
        } else {
            $num_audience = $this->model->get_current_data('audiences_visible')['audiences_visible'] != '' ?
                count(explode(',', $this->model->get_current_data('audiences_visible')['audiences_visible'])) :
                0;
            $audience->add(new static_html('static',
                '',
                get_string('audience_hide', 'block_totara_featured_links', $num_audience)
            ));
        }

        /** @var section $presets */
        $presets = $this->model->add(new section('presets', get_string('preset_title', 'block_totara_featured_links')));
        $presets->set_collapsible(true);
        $presets->set_expanded(true);
        $preset_checkbox = $presets->add(
            new checkbox('preset_showing',
                get_string('preset_showing',
                    'block_totara_featured_links')
            )
        );
        $preset_checkboxes = $presets->add(new checkboxes('presets_checkboxes',
            get_string('preset_checkboxes_label', 'block_totara_featured_links'),
            [
                'loggedin' => get_string('preset_checkbox_loggedin', 'block_totara_featured_links'),
                'notloggedin' => get_string('preset_checkbox_notloggedin', 'block_totara_featured_links'),
                'guest' => get_string('preset_checkbox_guest', 'block_totara_featured_links'),
                'notguest' => get_string('preset_checkbox_notguest', 'block_totara_featured_links'),
                'admin' => get_string('preset_checkbox_admin', 'block_totara_featured_links')
            ]
        ));
        $preset_checkboxes->add_validator(new visibility_form_preset_rules_validator());
        $presets_aggregation = $presets->add(new radios(
            'presets_aggregation',
            get_string('preset_aggregation_label', 'block_totara_featured_links'),
            [
                base::AGGREGATION_ANY => get_string('preset_aggregation_any', 'block_totara_featured_links'),
                base::AGGREGATION_ALL => get_string('preset_aggregation_all', 'block_totara_featured_links')
            ]
        ));

        $this->model->add_clientaction(new hidden_if($preset_checkboxes))->is_empty($preset_checkbox);
        $this->model->add_clientaction(new hidden_if($presets_aggregation))->is_empty($preset_checkbox);

        if ($this->has_custom_rules()) {
            /** @var section $tile_rules */
            $tile_rules = $this->model->add(
                new section('tile_rules',
                    get_string('tilerules_title',
                        'block_totara_featured_links')));
            $tile_rules->set_collapsible(true);
            if (isset($this->model->get_current_data('tile_rules_showing')['tile_rules_showing'])
                && $this->model->get_current_data('tile_rules_showing')['tile_rules_showing']
            ) {
                $tile_rules->set_expanded(true);
            }
            $tile_rules_show = $tile_rules->add(
                new checkbox('tile_rules_showing',
                    get_string('tile_rules_show',
                        'block_totara_featured_links')
                )
            );
            $elements = $this->specific_definition($tile_rules);
            if (gettype($elements) != 'array') {
                throw new \coding_exception('The specific_definition must return an array of the elements it defines so they can be hidden appropriately');
            }
            foreach ($elements as $element) {
                $this->model->add_clientaction(new hidden_if($element))->is_empty($tile_rules_show);
            }
            $this->model->add_clientaction(new hidden_if($tile_rules))->not_equals($visibility, base::VISIBILITY_CUSTOM);
        }

        /** @var section $aggregation */
        $aggregation = $this->model->add(
            new section('aggregation',
                get_string('aggregation_title',
                    'block_totara_featured_links')
            )
        );
        $aggregation->set_collapsible(true);
        $aggregation->set_expanded(true);
        $aggregation->add(new radios(
            'overall_aggregation',
            get_string('aggregation_label', 'block_totara_featured_links'),
            [
                base::AGGREGATION_ANY => get_string('aggregation_any', 'block_totara_featured_links'),
                base::AGGREGATION_ALL => get_string('aggregation_all', 'block_totara_featured_links')
            ])
        );

        if (!empty($audience)) {
            $this->model->add_clientaction(new hidden_if($audience))->not_equals($visibility, base::VISIBILITY_CUSTOM);
        }
        $this->model->add_clientaction(new hidden_if($presets))->not_equals($visibility, base::VISIBILITY_CUSTOM);

        $PAGE->requires->js_call_amd('block_totara_featured_links/visibility_form', 'init', [$this->model->get_id_suffix()]);

        parent::definition();
    }

    /**
     * Gets the action url for the form this means that the blockid, tileid and return url
     * Are all passed back to the script
     * @return \moodle_url
     */
    public function get_action_url () {
        $blockinstanceid  = $this->get_parameters()['blockinstanceid'];
        $tileid = $this->get_parameters()['tileid'];
        $return_url = $this->get_parameters()['return_url'];
        return new \moodle_url(
            '/blocks/totara_featured_links/edit_tile_visibility.php',
            ['blockinstanceid' => $blockinstanceid, 'tileid' => $tileid, 'return_url' => $return_url]
        );
    }
}