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

use core\output\flex_icon;
use core\output\flex_icon_helper;
use totara_form\file_area;

/**
 * This is the base class for the default tile it can be an example of how to do this in the tile mods
 * Class default_tile
 * @package block_totara_featured_links
 */
class default_tile extends base{
    protected $used_fields = [
        'heading',                       // string The title for the tile.
        'textbody',                      // string The description for the tile.
        'url',                           // string The url that the tile links to.
        'background_color',              // string The hex value of the background color.
        'background_img',                // string The filename  for the background image.
        'alt_text',                      // string The text to go in the sr-only span in the anchor tag.
        'target',                        // string The target for the link either '_self' or '_blank'.
        'heading_location',              // string The location of the heading either 'top' or 'bottom'.
        'background_appearance',         // string The background size style 'cover' or 'contain' currently.
        'icon',
        'icon_size'
    ];
    protected $content_class = 'block-totara-featured-links-content';
    protected $content_template = 'block_totara_featured_links/content';

    private const COVER_BACKGROUND_APPEARANCE = 'cover';

    /**
     * Gets the name of the tile to display in the edit form
     *
     * @throws \coding_exception You must override this function.
     * @return string
     */
    public static function get_name(): string {
        return get_string('default_name', 'block_totara_featured_links');
    }

    /**
     * This does tile specific things when the tile is added
     * @return void
     */
    public function add_tile(): void {

    }

    /**
     * Copy the files for the tile to the new location for the new tile
     * @param base $new_tile the object of the new tile
     * @return void
     */
    public function copy_files(base &$new_tile): void {
        if (empty($this->data->background_img)) {
            return;
        }
        $fromcontext = \context_block::instance($this->blockid);
        $tocontext = \context_block::instance($new_tile->blockid);
        $fs = get_file_storage();
        // This extra check if file area is empty adds one query if it is not empty but saves several if it is.
        if (!$fs->is_area_empty($fromcontext->id, 'block_totara_featured_links', 'tile_background', $this->id, false)) {
            file_prepare_draft_area(
                $draftitemid,
                $fromcontext->id,
                'block_totara_featured_links',
                'tile_background',
                $this->id,
                ['subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1]
            );
            file_save_draft_area_files(
                $draftitemid,
                $tocontext->id,
                'block_totara_featured_links',
                'tile_background',
                $new_tile->id,
                ['subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1]
            );
        }
    }

    /**
     * Gets the data for the content form and loads the background image back into the draft area so its displayed
     * in the filemanager
     * @return \stdClass
     */
    public function get_content_form_data(): \stdClass {
        $dataobj = parent::get_content_form_data();
        // Move background file to the draft area.
        if (isset($this->data->background_img)) {
            $dataobj->background_img = new file_area(
                \context_block::instance($this->blockid),
                'block_totara_featured_links',
                'tile_background',
                $this->id
            );
        }
        if (!isset($dataobj->background_appearance)) {
            $dataobj->background_appearance = default_tile::COVER_BACKGROUND_APPEARANCE;
        }
        if (!isset($this->data->heading_location)) {
            $dataobj->heading_location = self::HEADING_TOP;
        }
        if (empty($dataobj->icon_size) && empty($this->icon_size)) {
            $dataobj->icon_size = 'large';
        }
        return $dataobj;
    }

    /**
     * Gets the data to be passed to the render_content function
     *
     * @return array
     */
    protected function get_content_template_data(): array {
        $notempty = false;
        if (!empty($this->data_filtered->heading) || !empty($this->data_filtered->textbody)) {
            $notempty = true;
        }
        return [
            'heading' => (empty($this->data_filtered->heading) ? '' : $this->data->heading),
            'textbody' => (empty($this->data_filtered->textbody) ? '' : $this->data->textbody),
            'content_class' => (empty($this->content_class) ? '' : $this->content_class),
            'heading_location' => (empty($this->data_filtered->heading_location) ? '' : $this->data_filtered->heading_location),
            'notempty' => $notempty
        ];
    }

    /**
     * Adds the data needed for the default tile type
     * @param \renderer_base $renderer
     * @param array $settings
     * @return array
     */
    protected function get_content_wrapper_template_data(\renderer_base $renderer, array $settings = []): array {
        global $PAGE, $CFG;
        $PAGE->requires->js_call_amd('block_totara_featured_links/icon_sizer', 'resize', [$this->id]);

        $data = parent::get_content_wrapper_template_data($renderer, $settings);

        $data['background_img'] = false;

        if (!empty($this->data_filtered->background_img)) {
            $context = \context_block::instance($this->blockid);
            $url = \moodle_url::make_pluginfile_url(
                $context->id,
                'block_totara_featured_links',
                'tile_background',
                $this->id,
                '/',
                $this->data_filtered->background_img
            );
            $data['background_img'] = $url->out();
        }

        $data['alt_text'] = $this->get_accessibility_text();
        $data['background_color'] = (!empty($this->data_filtered->background_color) ?
            $this->data_filtered->background_color :
            false);
        $data['url'] = (!empty($this->url_mod) ? $this->url_mod : false);
        $data['target'] = (!empty($this->data_filtered->target) ? $this->data_filtered : false);
        $data['background_appearance'] = (empty($this->data_filtered->background_appearance) ?
            'cover' :
            $this->data_filtered->background_appearance);

        $showicon = (isset($this->data_filtered->icon) &&
            isset(flex_icon_helper::get_icons($CFG->theme)[$this->data_filtered->icon]));

        if (!empty($this->data_filtered->icon) && $showicon) {
            $icon = new flex_icon($this->data_filtered->icon);
            $data['icon'] = [
                'template' => $icon->get_template(),
                'context' => $icon->export_for_template($renderer)
            ];
        } else {
            $data['icon'] = false;
        }

        if (!empty($this->data_filtered->icon_size)) {
            $data['icon_size'] = $this->data_filtered->icon_size;
        } else {
            $data['icon_size'] = 'large';
        }

        return $data;
    }

    /**
     * Adds the tile specific items to the data object.
     * @param \stdClass $data
     * @return void
     */
    public function save_content_tile($data): void {
        global $CFG;
        // Moves a file from the draft area to a defined area
        // Saves the Draft area.
        $draftitemid = file_get_submitted_draft_itemid('background_img');
        $blockcontext = \context_block::instance($this->blockid)->id;
        if (!empty($draftitemid)) {
            file_save_draft_area_files(
                $draftitemid,
                $blockcontext,
                'block_totara_featured_links',
                'tile_background',
                $this->id,
                ['subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1]
            );
        }

        // Gets the url to the new file.
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $blockcontext,
            'block_totara_featured_links',
            'tile_background',
            $this->id,
            '',
            false
        );

        if ($file = reset($files)) {
            $this->data->background_img = $file->get_filename();
        } else {
            $this->data->background_img = false;
        }

        /* Checks if the url starts with the wwwroot.
         * If it does it strips the wwwroot so it can be added back dynamically
         * Also checks if the url doesn't start with http:// https:// or a / then it adds https://
         * to stop people from using other protocols like FTP ect.
        */
        if (\core_text::substr($data->url, 0, 7) != 'http://'
            && \core_text::substr($data->url, 0, 8) != 'https://'
            && \core_text::substr($data->url, 0, 1) != '/') {
            $data->url = 'https://'.$data->url;
        }
        $wwwroot_chopped = preg_replace('/^(https:\/\/)|(http:\/\/)/', '', $CFG->wwwroot);
        if (\core_text::substr($data->url, 0, strlen($wwwroot_chopped)) == $wwwroot_chopped) {
            $data->url = \core_text::substr($data->url, strlen($wwwroot_chopped));
        }
        if (\core_text::substr($data->url, 0, strlen($CFG->wwwroot)) == $CFG->wwwroot) {
            $data->url = \core_text::substr($data->url, strlen($CFG->wwwroot));
        }
        if ($data->url == '') {
            $data->url = '/';
        }

        // Saves the rest of the data for the tile.
        if (isset($data->alt_text)) {
            $this->data->alt_text = $data->alt_text;
        }
        if (isset($data->url)) {
            $this->data->url = $data->url;
        }
        if (isset($data->heading)) {
            $this->data->heading = $data->heading;
        }
        if (isset($data->textbody)) {
            $this->data->textbody = $data->textbody;
        }
        if (isset($data->background_color)) {
            $this->data->background_color = $data->background_color;
        }
        if (isset($data->target)) {
            $this->data->target = $data->target;
        }
        if (isset($data->heading_location)) {
            $this->data->heading_location = $data->heading_location;
        }
        if (isset($data->background_appearance)) {
            $this->data->background_appearance = $data->background_appearance;
        }
        if (isset($data->icon)) {
            $this->data->icon = $data->icon;
        }
        if (isset($data->icon_size)) {
            $this->data->icon_size = $data->icon_size;
        }
        return;
    }

    /**
     * {@inheritdoc}
     * The static tile does not use any custom rules
     */
    public function is_visible_tile(): int {
        return 0;
    }

    /**
     * {@inheritdoc}
     * The static tile does not have any custom rules
     */
    public function save_visibility_tile($data): string{
        return '';
    }

    /**
     * Returns an array that the template will uses to put in text to help with accessibility
     * example
     *      [ 'sr-only' => string]
     * @return array
     */
    public function get_accessibility_text(): array {
        $sronly = '';
        if (!empty($this->data->alt_text)) {
            $sronly = $this->data->alt_text;
        } else if (!empty($this->data->heading)) {
            $sronly = $this->data->heading;
        } else if (!empty($this->data->textbody)) {
            $sronly = $this->data->textbody;
        }

        return [
            'sr-only' => $sronly
        ];
    }
}
