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

require_once($CFG->dirroot . '/totara/program/program.class.php');

/**
 * Class program_tile
 * The Class the represents a program tile
 * @package block_totara_featured_links\tile
 */
class program_tile extends learning_item_tile {
    protected $used_fields = [
        'programid', // Int The id of the program that the tile links to.
        'background_color', // String The hex value of the background color.
        'heading_location', // String Where the heading is located 'top' or 'bottom'.
        'progressbar'
    ];

    /** @var string Class for the visibility form */
    protected $visibility_form = '\block_totara_featured_links\tile\program_form_visibility';
    /** @var string Class for the content form */
    protected $content_form = '\block_totara_featured_links\tile\program_form_content';
    /** @var string The classes that get added to the content of the tile */
    protected $content_class = 'block-totara-featured-links-program';

    /**
     * @var \program|false $program the database row of the program
     *
     * Call $this->get_program() to load this property.
     */
    protected $program = null;

    /**
     * returns the name of the tile that will be displayed in the edit content form
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('program_name', 'block_totara_featured_links');
    }

    /**
     * Gets the data for the learning item content form and adds the
     * program name and id.
     *
     * {@inheritdoc}
     * @return \stdClass
     */
    public function get_content_form_data(): \stdClass {
        $dataobj = parent::get_content_form_data();
        if (!empty($this->get_program())) {
            $dataobj->program_name = $this->get_program()->fullname;
        }
        if (isset($this->data_filtered->programid)) {
            $dataobj->program_name_id = $this->data_filtered->programid;
        }
        if (!isset($this->data->heading_location)) {
            $dataobj->heading_location = self::HEADING_TOP;
        }
        return $dataobj;
    }

    /**
     * Adds heading to the content data for a learning item tile.
     *
     * {@inheritdoc}
     * @return array
     */
    protected function get_content_template_data(): array {
        global $USER;
        if (empty($this->get_program())) {
            return [];
        }
        if (isset($this->data->progressbar) && $this->data->progressbar == '1') {
            $progressbar = prog_display_progress($this->data->programid, $USER->id);
        } else {
            $progressbar = false;
        }
        $data = parent::get_content_template_data();
        $data['heading'] = format_string($this->get_program()->fullname);
        $data['progress_bar'] = $progressbar;

        return $data;
    }

    /**
     * Gets the data for the content_wrapper template from {@learning_item}
     * and add the url to the program if the program can be retrieved.
     *
     * @param \renderer_base $renderer
     * @return array
     */
    protected function get_content_wrapper_template_data(\renderer_base $renderer, array $settings = []): array {
        global $CFG;
        $data = parent::get_content_wrapper_template_data($renderer, $settings);
        if (!empty($this->get_program())) {
            $programid = $this->get_program()->id;
            $data['url'] = $CFG->wwwroot.'/totara/program/view.php?id='.$programid;
            $data['background_img'] = false;

            // Get program tile image to use it as background.
            $image = $this->get_program()->get_image($programid);
            if ($image) {
                $data['background_img'] = $image;
            }
        }
        return $data;
    }

    /**
     * Sets the program id into the data property
     *
     * @param \stdClass $data
     */
    public function save_content_tile($data): void {
        if (isset($data->program_name_id)) {
            $this->data->programid = $data->program_name_id;
        }
        parent::save_content_tile($data);
    }

    /**
     * Checks if the user can see the program.
     *
     * @return bool
     */
    protected function user_can_view_content(): bool {
        return boolval($this->get_program());
    }

    /**
     * Returns the program this tile is associated with.
     *
     * @return \program|bool The program record or false if there is no associated program.
     */
    public function get_program($reload = false) {
        global $DB;
        if (empty($this->data->programid) || !$DB->record_exists('prog', ['id' => $this->data->programid])) {
            return false;
        }
        if ((!isset($this->program) or $reload)) {
            $program = new \program($this->data->programid);
            if ($program->is_viewable()) {
                $this->program = $program;
            } else {
                $this->program = false;
            }
        }
        return $this->program;
    }

    /**
     * {@inheritdoc}
     * @return array
     */
    public function get_accessibility_text(): array {
        return [
            'sr-only' => get_string('program_sr-only',
            'block_totara_featured_links',
            $this->get_program() ? $this->get_program()->fullname : '')
        ];
    }

    /**
     * {@inheritdoc}
     *
     * We'll return that the program was deleted if that is the case.
     *
     * @return string of text shown if a tile is hidden but being viewed in edit mode.
     */
    protected function get_hidden_text(): string {
        if (empty($this->get_program())) {
            return get_string('program_has_been_deleted', 'block_totara_featured_links');
        } else {
            return parent::get_hidden_text();
        }
    }
}