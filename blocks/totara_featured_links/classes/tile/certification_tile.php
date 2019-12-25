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

/**
 * Class certification_tile
 * The Class the represents a certification tile
 * @package block_totara_featured_links\tile
 */
class certification_tile extends program_tile {
    /** @var string Class for the visibility form */
    protected $visibility_form = '\block_totara_featured_links\tile\certification_form_visibility';
    /** @var string Class for the content form */
    protected $content_form = '\block_totara_featured_links\tile\certification_form_content';
    /** @var string The classes that get added to the content of the tile */
    protected $content_class = 'block-totara-featured-links-certification';

    /**
     * returns the name of the tile that will be displayed in the edit content form
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('certification_name', 'block_totara_featured_links');
    }

    /**
     * Gets the data for the learning item content form and adds the
     * certification name and id.
     *
     * {@inheritdoc}
     * @return \stdClass
     */
    public function get_content_form_data(): \stdClass {
        $dataobj = parent::get_content_form_data();
        if (!empty($this->get_program())) {
            $dataobj->certification_name = $this->get_program()->fullname;
        }
        if (isset($this->data_filtered->programid)) {
            $dataobj->certification_name_id = $this->data_filtered->programid;
        }
        return $dataobj;
    }

    /**
     * Sets the certification id into the data property
     *
     * @param \stdClass $data
     */
    public function save_content_tile($data): void {
        if (isset($data->certification_name_id)) {
            $this->data->programid = $data->certification_name_id;
        }
        parent::save_content_tile($data);
    }

    /**
     * {@inheritdoc}
     * @return array
     */
    public function get_accessibility_text(): array {
        return [
            'sr-only' => get_string(
                'certification_sr-only',
                'block_totara_featured_links',
                $this->get_program() ? $this->get_program()->fullname : ''
            )
        ];
    }

    /**
     * {@inheritdoc}
     *
     * We'll return that the certification was deleted if that is the case.
     *
     * @return string of text shown if a tile is hidden but being viewed in edit mode.
     */
    protected function get_hidden_text(): string {
        if (empty($this->get_program())) {
            return get_string('certification_has_been_deleted', 'block_totara_featured_links');
        } else {
            return parent::get_hidden_text();
        }
    }
}