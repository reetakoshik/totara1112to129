<?php
/*
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package block_admin_related_pages
 */

defined('MOODLE_INTERNAL') || die();

/**
 * The simple admin related pages block class.
 */
final class block_admin_related_pages extends block_base {

    /**
     * This blocks component.
     */
    private const COMPONENT = 'block_admin_related_pages';

    /**
     * Keeps admin navigation.
     */
    private $settingsnav;

    /**
     * Initialise block
     */
    public function init() {
        $this->title = get_string('related', self::COMPONENT);
    }

    /**
     * There is no configuration for this block
     *
     * @return bool
     */
    public function instance_allow_config() {
        return false;
    }

    /**
     * Set the applicable formats for this block.
     * @return array
     */
    public function applicable_formats() {
        return ['admin' => true];
    }

    /**
     * Set block content
     *
     * @return stdClass
     */
    public function get_content() {
        // First check if we have already generated, don't waste cycles
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';

        $node = $this->get_active_node();
        if ($node === null) {
            // Nothing to see here.
            return $this->content;
        }

        $items = \block_admin_related_pages\helper::get_related_pages($node->key);
        if (empty($items)) {
            return $this->content;
        }

        $component = \block_admin_related_pages\output\itemlist::from_items($items);
        $output = $this->page->get_renderer('core');
        $this->content->text = $output->render($component);

        return $this->content;
    }

    /**
     * Finds the active admin navigation_node, if there is one.
     *
     * This method uses the page settings navigation in order to ensure that the settings navigation tree is only
     * ever generated once, and is then available for all.
     * It's also been around a long time and knows exactly how to find the active node, if there is one.
     *
     * @return navigation_node|null
     */
    private function get_active_node(): ?navigation_node {
        if (!$this->page->settingsnav->is_admin_tree_needed()) {
            // If it ain't needed don't display it!
            return null;
        }

        // Get current admin node
        $root = $this->page->settingsnav->get('root', settings_navigation::TYPE_SITE_ADMIN);
        if (!$root) {
            // Root not found.
            return null;
        }
        $node = $root->find_active_node();
        if (!$node) {
            // It's not an admin page.
            return null;
        }

        return $node;
    }
}
