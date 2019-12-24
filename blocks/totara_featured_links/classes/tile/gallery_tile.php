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

use totara_form\file_area;

/**
 * Class gallery_tile
 * The class that defines a tile type where multiple images can be uploaded and the they will be switched between when
 * the tile is loaded
 * @package block_totara_featured_links
 */
class gallery_tile extends base {
    protected $used_fields = ['background_imgs', // array The filenames for all the background images.
        'textbody', // string The description for the tile.
        'heading', // string The title for the tile.
        'url', // string The url that the tile links to.
        'target', // string The target for the link either '_self' or '_blank'.
        'alt_text',  // string The text to go in the sr-only span in the anchor tag.
        'interval', // int The time in seconds between when the background image changes.
        'background_color', // string The hex value of the background color.
        'heading_location']; // string The location of the heading either 'top' or 'bottom'.
    protected $content_template = 'block_totara_featured_links/content';
    protected $content_wrapper_template = 'block_totara_featured_links/content_wrapper_gallery';
    protected $content_class = 'block-totara-featured-links-content-gallery';
    protected $content_form = '\block_totara_featured_links\tile\gallery_form_content';
    protected $visibility_form = '\block_totara_featured_links\tile\default_form_visibility';

    /** @var int The default interval (seconds) */
    protected $default_interval = 4;

    /**
     * {@inheritdoc}
     */
    public static function get_name() {
        return get_string('multi_name', 'block_totara_featured_links');
    }

    /**
     * {@inheritdoc}
     */
    public function add_tile() {
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function copy_files(base &$new_tile) {
        if (empty($this->data->background_imgs)) {
            return;
        }
        $fromcontext = \context_block::instance($this->blockid);
        $tocontext = \context_block::instance($new_tile->blockid);
        $fs = get_file_storage();
        // This extra check if file area is empty adds one query if it is not empty but saves several if it is.
        if (!$fs->is_area_empty($fromcontext->id, 'block_totara_featured_links', 'tile_backgrounds', $this->id, false)) {
            file_prepare_draft_area($draftitemid,
                $fromcontext->id,
                'block_totara_featured_links',
                'tile_backgrounds',
                $this->id,
                ['subdirs' => 0, 'maxbytes' => 0]);
            file_save_draft_area_files($draftitemid,
                $tocontext->id,
                'block_totara_featured_links',
                'tile_backgrounds',
                $new_tile->id,
                ['subdirs' => 0, 'maxbytes' => 0]);
        }
    }

    /**
     * gets the data for the content form
     * @return \stdClass
     */
    public function get_content_form_data() {
        $data_obj = parent::get_content_form_data();
        // Move background file to the draft area.
        if (isset($this->data->background_imgs)) {
            $data_obj->background_imgs = new file_area(\context_block::instance($this->blockid),
                'block_totara_featured_links',
                'tile_backgrounds',
                $this->id);
        }
        if (!isset($data_obj->interval)) {
            $data_obj->interval = 4;
        }
        if (!isset($this->data->heading_location)) {
            $data_obj->heading_location = self::HEADING_TOP;
        }
        return $data_obj;
    }

    /**
     * Returns an array that the template will uses to put in text to help with accessibility
     * example
     *      [ 'sr-only' => 'value']
     * @return array
     */
    public function get_accessibility_text() {
        $sronly = '';
        if (!empty($this->data_filtered->alt_text)) {
            $sronly = $this->data_filtered->alt_text;
        } else if (!empty($this->data_filtered->heading)) {
            $sronly = $this->data_filtered->heading;
        } else if (!empty($this->data_filtered->textbody)) {
            $sronly = $this->data_filtered->textbody;
        }
        return ['sr-only' => $sronly];
    }

    /**
     * This defines the saving process for the custom tile fields
     * This should modify the data variable rather than chang directly saving to the database cause if you don't
     * what you save will get overridden when the tile is saved to the database.
     * @param \stdClass $data
     * @return void
     */
    public function save_content_tile($data) {
        global $CFG;
        // Saves the Draft area.
        $draftitemid = file_get_submitted_draft_itemid('background_imgs');
        $blockcontext = \context_block::instance($this->blockid)->id;
        if (!empty($draftitemid)) {
            file_save_draft_area_files($draftitemid,
                $blockcontext,
                'block_totara_featured_links', 'tile_backgrounds',
                $this->id,
                ['subdirs' => 0, 'maxbytes' => 0]);
        }

        // Gets the url to the new file.
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $blockcontext,
            'block_totara_featured_links',
            'tile_backgrounds',
            $this->id,
            '',
            false);
        $this->data->background_imgs = [];
        foreach ($files as $file) {
            $this->data->background_imgs[] = $file->get_filename();
        }
        /* Checks if the url starts with the wwwroot.
         * If it does it strips the wwwroot so it can be added back dynamically
         * Also checks if the url doesn't start with http:// https:// or a / then it adds https://
         * to stop people from using other protocols like FTP ect.
        */
        if (\core_text::substr($data->url, 0, 7) != 'http://'
            && \core_text::substr($data->url, 0, 8) != 'https://'
            && \core_text::substr($data->url, 0, 1) != '/') {
            $data->url = 'http://'.$data->url;
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
        if (isset($data->interval)) {
            if ($data->interval < 1 && $data->interval != 0) {
                $data->interval = 1;
            }
            $this->data->interval = $data->interval;
        }
        if (isset($data->heading_location)) {
            $this->data->heading_location = $data->heading_location;
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
    public function save_visibility_tile($data) {
        return '';
    }

    /**
     * Gets the data to be passed to the render_content function
     * @return array
     */
    protected function get_content_template_data() {
        $notempty = false;
        if (!empty($this->data_filtered->heading) || !empty($this->data_filtered->textbody)) {
            $notempty = true;
        }
        return ['heading' => (empty($this->data_filtered->heading) ? '' : $this->data->heading),
            'textbody' => (empty($this->data_filtered->textbody) ? '' : $this->data->textbody),
            'content_class' => (empty($this->content_class) ? '' : $this->content_class),
            'heading_location' => (empty($this->data_filtered->heading_location) ? '' : $this->data_filtered->heading_location),
            'notempty' => $notempty
        ];
    }

    /**
     * Gets the data for the content wrapper
     * @param \renderer_base $renderer
     * @return array
     */
    protected function get_content_wrapper_template_data(\renderer_base $renderer) {
        global $PAGE;
        $data = parent::get_content_wrapper_template_data($renderer);

        // Build background urls.
        $backgrounds = [];
        if (!empty($this->data_filtered->background_imgs)) {
            foreach ($this->data_filtered->background_imgs as $image) {
                $backgrounds[] = (string)\moodle_url::make_pluginfile_url(\context_block::instance($this->blockid)->id,
                    'block_totara_featured_links',
                    'tile_backgrounds',
                    $this->id,
                    '/',
                    $image);
            }
        }

        $data['background_imgs'] = !empty($backgrounds) ? $backgrounds : false;
        $data['alt_text'] = $this->get_accessibility_text();
        $data['background_color'] = (!empty($this->data_filtered->background_color) ?
            $this->data_filtered->background_color :
            false);
        $data['url'] = (!empty($this->url_mod) ? $this->url_mod : false);
        $data['target'] = (!empty($this->data_filtered->target) ? $this->data->target : false);
        return $data;
    }

    /**
     * Gets whether the tile is visible to the user by the custom rules defined by the tile.
     * This should only be used by the is_visible() function.
     * @return int (-1 = hidden, 0 = no rule, 1 = showing)
     */
    public function is_visible_tile() {
        return 0;
    }

    /**
     * initializes the switcher amd module
     */
    protected function get_requirements() {
        global $PAGE;
        $PAGE->requires->js_call_amd('block_totara_featured_links/switcher',
            'init',
            [
                (!isset($this->data->interval) ? $this->default_interval*1000 : $this->data->interval*1000),
                'block-totara-featured-links-gallery-tile-'.$this->id]
        );
    }
}