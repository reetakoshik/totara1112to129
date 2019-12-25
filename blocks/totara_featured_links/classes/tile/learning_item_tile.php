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
 * Class learning_item
 * a super class for tiles that represent a link to a group of organised learning
 * @package block_totara_featured_links
 */
abstract class learning_item_tile extends base {
    /** @var string The template used to render the content of the tile*/
    protected $content_template = 'block_totara_featured_links/content_learning_item';

    /**
     * Learning item tiles do not do anything when they are added by default.
     *
     * {@inheritdoc}
     */
    public function add_tile(): void {

    }

    /**
     * Gets the class_content and heading_location for the content template.
     *
     * {@inheritdoc}
     * @return array
     */
    protected function get_content_template_data(): array {
        return [
            'content_class' => (empty($this->content_class) ? '' : $this->content_class),
            'heading_location' => (empty($this->data_filtered->heading_location) ? '' : $this->data_filtered->heading_location),
            'notempty' => true
        ];
    }

    /**
     * Gets background_color and alt_text for the content_wrapper template.
     *
     * {@inheritdoc}
     * @param \renderer_base $renderer
     * @return array
     */
    protected function get_content_wrapper_template_data(\renderer_base $renderer, array $settings = []): array {
        $data = parent::get_content_wrapper_template_data($renderer, $settings);
        $data['background_color'] = (!empty($this->data_filtered->background_color) ?
            $this->data_filtered->background_color :
            false);
        $data['alt_text'] = $this->get_accessibility_text();
        return $data;
    }

    /**
     * Sets the heading location and background color into the data property
     * ready to be saved to the database.
     *
     * @param \stdClass $data
     * @return void
     */
    public function save_content_tile($data): void {
        if (isset($data->heading_location)) {
            $this->data->heading_location = $data->heading_location;
        }
        if (isset($data->background_color)) {
            $this->data->background_color = $data->background_color;
        }
        if (isset($data->progressbar)) {
            $this->data->progressbar = $data->progressbar;
        }
    }

    /**
     * learning items tile don't have any tile visibility rules by default.
     *
     * {@inheritdoc}
     * @return int 0
     */
    public function is_visible_tile(): int {
        return 0;
    }

    /**
     * Learning item tiles don't have visibiliy rules to save by default.
     *
     * {@inheritdoc}
     * @param \stdClass $data all the data from the form
     * @return string
     */
    public function save_visibility_tile($data): string {
        return '';
    }
}