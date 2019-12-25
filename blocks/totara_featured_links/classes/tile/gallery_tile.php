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

use core\output\flex_icon;

/**
 * Class gallery_tile
 * The class that defines a tile type where multiple images can be uploaded and the they will be switched between when
 * the tile is loaded
 * @package block_totara_featured_links
 */
class gallery_tile extends base implements meta_tile {
    protected $used_fields = [
        'transition',
        'order',
        'controls',
        'autoplay',
        'interval',
        'repeat',
        'pauseonhover',
    ];
    protected $content_template = 'block_totara_featured_links/content';
    protected $content_wrapper_template = 'block_totara_featured_links/content_wrapper_gallery';
    protected $content_class = 'block-totara-featured-links-content-gallery';
    protected $content_form = '\block_totara_featured_links\tile\gallery_form_content';
    protected $visibility_form = '\block_totara_featured_links\tile\default_form_visibility';

    /** @var array $subtiles and array of {@link base}*/
    protected $subtiles = [];

    /** @var int The default interval (seconds) */
    const DEFAULT_INTERVAL = 4;

    const TRANSITION_SLIDE = 'slide';
    const TRANSITION_FADE = 'fade';
    const ORDER_RANDOM = 'random';
    const ORDER_SEQUENTIAL = 'sequential';
    const CONTROLS_ARROWS = 'arrows';
    const CONTROLS_POSITION = 'position_indicator';


    /**
     * Gets all the tiles that are contained by this tile.
     * @return array of {@link base} but none should be meta_tiles
     */
    public function get_subtiles(): array {
        return $this->subtiles;
    }

    /**
     * gallery_tile constructor.
     * @param \stdClass|null $tile
     */
    public function __construct($tile = null) {
        global $DB;
        parent::__construct($tile);

        if (!empty($this->id)) {
            $subtiles = $DB->get_records('block_totara_featured_links_tiles', ['parentid' => $this->id]);

            usort($subtiles, function ($tile1, $tile2) {
                if ($tile1->sortorder == $tile2->sortorder) {
                    assert(false, 'There was two tiles with the same sort order');
                    return 0;
                }
                return ($tile1->sortorder < $tile2->sortorder) ? -1 : 1;
            });

            foreach ($subtiles as $subtile) {
                list($plugin_name, $class_name) = explode('-', $subtile->type, 2);
                $type = "\\$plugin_name\\tile\\$class_name";
                $this->subtiles[] = new $type($subtile);
            }
        }
    }

    /**
     * Build the array of subtiles
     */
    private function build_subtiles(): void {
        global $DB;
        if (empty($this->id)) {
            throw new \coding_exception('The id on the gallery tile must be set before generating subtlies');
        }

        $subtiles = $DB->get_records('block_totara_featured_links_tiles', ['parentid' => $this->id]);

        usort($subtiles, function ($tile1, $tile2) {
            return ($tile2->sortorder - $tile1->sortorder);
        });

        foreach ($subtiles as $subtile) {
            list($plugin_name, $class_name) = explode('-', $subtile->type, 2);
            $type = "\\$plugin_name\\tile\\$class_name";
            $this->subtiles[] = new $type($subtile);
        }
    }

    /**
     * Gets the name of the tile to display in the edit form
     *
     * @throws \coding_exception You must override this function.
     * @return string
     */
    public static function get_name(): string {
        return get_string('multi_name', 'block_totara_featured_links');
    }

    /**
     * This does the tile defined add
     * Ie instantiates objects so they can be referenced later
     * @return void
     */
    public function add_tile(): void {
        return;
    }

    /**
     * gets the data for the content form
     * @return \stdClass
     */
    public function get_content_form_data(): \stdClass {
        $data_obj = parent::get_content_form_data();
        if (!isset($data_obj->transition)) {
            $data_obj->transition = self::TRANSITION_SLIDE;
        }
        if (!isset($data_obj->order)) {
            $data_obj->order = self::ORDER_SEQUENTIAL;
        }
        if (!isset($data_obj->controls)) {
            $data_obj->controls = [self::CONTROLS_ARROWS, self::CONTROLS_POSITION];
        }
        if (!isset($data_obj->autoplay)) {
            $data_obj->autoplay = 1;
        }
        if (!isset($data_obj->interval)) {
            $data_obj->interval = self::DEFAULT_INTERVAL;
        }
        if (!isset($data_obj->repeat)) {
            $data_obj->repeat = 1;
        }
        if (!isset($data_obj->pauseonhover)) {
            $data_obj->pauseonhover = 0;
        }

        return $data_obj;
    }

    /**
     * {@inheritdoc}
     * Also renders the subtiles and passes them through to the template.
     *
     * @param \renderer_base $renderer
     * @param array $settings
     * @return bool|string
     */
    public function render_content_wrapper(\renderer_base $renderer, array $settings) {
        $data = $this->get_content_wrapper_template_data($renderer, $settings);

        $subtilesettings = $settings;
        $subtilesettings['editing'] = false;
        $subtiles = '';
        foreach ($this->subtiles as $subtile) {
            if ($subtile->is_visible()) {
                $subtiles .= $subtile->render_content_wrapper($renderer, $subtilesettings);
            }
        }
        if ($subtiles == '' && (!isset($settings['editing']) || $settings['editing'] == false)) {
            return '';
        }
        $data['subtiles'] = $subtiles;
        $data = array_merge($data, $settings);
        return $renderer->render_from_template($this->content_wrapper_template, $data);
    }

    /**
     * Returns an array that the template will uses to put in text to help with accessibility
     * example
     *      [ 'sr-only' => 'value']
     * @return array
     */
    public function get_accessibility_text(): array {
        return ['sr-only' => get_string('multi_name', 'block_totara_featured_links')];
    }

    /**
     * This defines the saving process for the custom tile fields
     * This should modify the data variable rather than directly saving to the database.
     * If you do save to the db then what you save will get overridden when the tile is saved.
     * @param \stdClass $data
     * @return void
     */
    public function save_content_tile($data): void {
        if (isset($data->interval)) {
            if ($data->interval < 1 && $data->interval != 0) {
                $data->interval = 1;
            }
            $this->data->interval = $data->interval;
        }
        if (isset($data->transition)) {
            $this->data->transition = $data->transition;
        }
        if (isset($data->order)) {
            $this->data->order = $data->order;
        }
        if (isset($data->controls)) {
            $this->data->controls = $data->controls;
        }
        if (isset($data->autoplay)) {
            $this->data->autoplay = $data->autoplay;
        }
        if (isset($data->repeat)) {
            $this->data->repeat = $data->repeat;
        }
        if (isset($data->pauseonhover)) {
            $this->data->pauseonhover = $data->pauseonhover;
        }

        return;
    }

    /**
     * Saves the data for the custom visibility.
     * Should only modify the custom_rules variable so the reset of the visibility and tile options are left the same
     * when its saved to the database
     * @param \stdClass $data all the data from the form
     * @return string
     */
    public function save_visibility_tile($data): string {
        return '';
    }

    /**
     * Gets the data to be passed to the render_content function
     * @return array
     */
    protected function get_content_template_data(): array {
        $notempty = false;
        if (!empty($this->data_filtered->heading) || !empty($this->data_filtered->textbody)) {
            $notempty = true;
        }
        return [
            'content_class' => (empty($this->content_class) ? '' : $this->content_class),
            'notempty' => $notempty
        ];
    }

    /**
     * Gets the items that go into the edit menu for a tile.
     *
     * @param array $settings
     * @return array
     */
    protected function get_action_menu_items($settings = []): array {
        global $PAGE;
        $action_menu_items = [];
        $action_menu_items[] = new \action_menu_link_secondary(
            new \moodle_url(
                '/blocks/totara_featured_links/edit_tile_content.php',
                [
                    'blockinstanceid' => $this->blockid,
                    'tileid' => $this->id,
                    'return_url' => $PAGE->url->out_as_local_url()
                ]
            ),
            new flex_icon('edit'),
            get_string('parenttile_content_menu_title', 'block_totara_featured_links') .
            \html_writer::span(
                get_string(
                    'parenttile_content_menu_title_sr-only',
                    'block_totara_featured_links',
                    $this->get_accessibility_text()['sr-only']
                ),
                'sr-only'
            ),
            ['type' => 'edit']
        );

        $action_menu_items[] = new \action_menu_link_secondary(
            new \moodle_url(
                '/blocks/totara_featured_links/sub_tile_manage.php',
                ['tileid' => $this->id, 'return_url' => $PAGE->url->out_as_local_url()]
            ),
            new flex_icon('edit'),
            get_string('managesubtiles', 'block_totara_featured_links') .
            \html_writer::span(
                get_string(
                    'managesubtiles_sr-only',
                    'block_totara_featured_links',
                    $this->get_accessibility_text()['sr-only']
                ),
                'sr-only'
            ),
            ['type' => 'edit']
        );

        if ($this->is_visibility_applicable()) {
            $action_menu_items[] = new \action_menu_link_secondary(
                new \moodle_url('/blocks/totara_featured_links/edit_tile_visibility.php',
                    [
                        'blockinstanceid' => $this->blockid,
                        'tileid' => $this->id,
                        'return_url' => $PAGE->url->out_as_local_url()
                    ]
                ),
                new flex_icon('hide'),
                get_string('visibility_menu_title', 'block_totara_featured_links') .
                \html_writer::span(
                    get_string(
                        'visibility_menu_title_sr-only',
                        'block_totara_featured_links',
                        $this->get_accessibility_text()['sr-only']
                    ),
                    'sr-only'
                ),
                ['type' => 'edit_vis']);
        }

        $action_menu_items[] = new \action_menu_link_secondary(
            new \moodle_url('/'),
            new flex_icon('delete'),
            get_string('delete_menu_title', 'block_totara_featured_links') .
            \html_writer::span(
                get_string(
                    'delete_menu_title_sr-only',
                    'block_totara_featured_links',
                    $this->get_accessibility_text()['sr-only']
                ),
                'sr-only'
            ),
            ['type' => 'remove', 'blockid' => $this->blockid, 'tileid' => $this->id]);
        return $action_menu_items;
    }

    /**
     * Gets whether the tile is visible to the user by the custom rules defined by the tile.
     * This should only be used by the is_visible() function.
     * @return int (-1 = hidden, 0 = no rule, 1 = showing)
     */
    public function is_visible_tile(): int {
        return 0;
    }

    /**
     * initializes the switcher amd module
     */
    protected function get_requirements(): void {
        global $PAGE;

        $interval = $this->data->interval ?? self::DEFAULT_INTERVAL;

        $transition = $this->data_filtered->transition ?? self::TRANSITION_SLIDE;
        $order = $this->data_filtered->order ?? self::ORDER_SEQUENTIAL;
        $controls = $this->data_filtered->controls ?? [self::CONTROLS_POSITION, self::CONTROLS_ARROWS];
        $autoplay = $this->data_filtered->autoplay ?? '1';
        $repeat = $this->data_filtered->repeat ?? '1';
        $pauseonhover = $this->data_filtered->pauseonhover ?? '0';

        $PAGE->requires->js_call_amd(
            'block_totara_featured_links/switcher',
            'init', [
                $interval * 1000,
                'block-totara-featured-links-gallery-tile-'.$this->id,
                $transition,
                $order,
                $controls,
                $autoplay,
                $repeat,
                $pauseonhover
            ]
        );
    }
}