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

use totara_form\form\element\hidden;
use \totara_form\form\element\select;
use \totara_form\form\element\number;
use \totara_form\form\group\section;
use \block_totara_featured_links\form\validator\is_subclass_of_tile_base;

/**
 * Class base_form_content
 * The base form for the content form
 * Plugin tile types should extend this form
 * @package block_totara_featured_links
 */
abstract class base_form_content extends base_form {

    /**
     * Defines the main part of the form
     * which basically includes ordering and tile type
     */
    public function definition () {
        global $DB;

        /** @var section $maingroup */
        $maingroup = $this->model->add(new section('maingroup', get_string('content_edit', 'block_totara_featured_links')));
        $maingroup->set_collapsible(false);

        /** @var base[] $classes */
        $classes = \core_component::get_namespace_classes('tile', 'block_totara_featured_links\tile\base');
        $class_options = [];
        foreach ($classes as $class_str) {
            $class_arr = explode('\\', $class_str);
            $plugin_name = $class_arr[0];
            $class_name = $class_arr[count($class_arr) - 1];
            $ismetatile = is_subclass_of($class_str, meta_tile::class);
            if (!empty($this->get_parameters()['parentid']) && $ismetatile) {
                continue;
            }
            $class_options[$plugin_name.'-'.$class_name] = $class_str::get_name();
        }

        $tile_types = $this->model->add(new select('type', get_string('tile_types', 'block_totara_featured_links'), $class_options));
        $tile_types->add_validator(new is_subclass_of_tile_base());

        $position = $this->model->add(new number('sortorder', get_string('tile_position', 'block_totara_featured_links')), PHP_INT_MAX);

        $parentid = isset($this->get_parameters()['parentid']) ? $this->get_parameters()['parentid'] : 0;
        $max = $DB->count_records(
            'block_totara_featured_links_tiles',
            [
                'blockid' => $this->get_parameters()['blockinstanceid'],
                'parentid' => $parentid
            ]
        );
        if ($DB->count_records('block_totara_featured_links_tiles', ['id' => $this->get_parameters()['tileid']]) == 0) {
            $max++;
        }
        $position->set_attribute('max', max(1, $max));
        $position->set_attribute('min', 1);
        $position->set_attribute('required', true);

        $this->model->add(new hidden('parentid', PARAM_INT));

        $this->specific_definition($maingroup);

        parent::definition();
    }

    /**
     * Overrides the get_action_url function so that the parameters can be added to the form even when its being submitted.
     * Needed as the php script requires that the block and tile ids are set
     * @return \moodle_url
     */
    public function get_action_url() {
        return new \moodle_url(
            '/blocks/totara_featured_links/edit_tile_content.php',
            $this->get_parameters()
        );
    }
}