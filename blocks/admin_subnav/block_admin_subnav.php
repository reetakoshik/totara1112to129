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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package block_admin_subnav
 */

defined('MOODLE_INTERNAL') || die();

final class block_admin_subnav extends block_base {

    private const COMPONENT = 'block_admin_subnav';
    private $settingsnav;

    /**
     * Initialise block
     */
    public function init() {
        $this->title = get_string('pluginname', self::COMPONENT);
    }

    /**
     * Set the applicable formats for this block.
     * @return array
     */
    public function applicable_formats() {
        return ['admin' => true];
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
     * Load Javascript required to expand and collapse sections of the tree
     */
    public function get_required_javascript() {
        parent::get_required_javascript();
        $arguments = array(
            'instanceid' => $this->instance->id,
        );
        $this->page->requires->js_call_amd(self::COMPONENT.'/subnavblock', 'init', $arguments);
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

        // Set title here, doing this in the specialization
        // function crashes the browser when moving the block
        $this->title = $node->text;
        if ($this->page->user_is_editing()) {
            $this->title = get_string('titlewhenediting', self::COMPONENT, $this->title);
        }

        /** @var \block_admin_subnav\output\renderer $renderer */
        $renderer = $this->page->get_renderer(self::COMPONENT);
        $this->content = new stdClass();
        $this->content->text = $renderer->admin_subnavigation($node->children);

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

        // Get top level node for subtree to display
        while (!empty($node->parent) && $node->parent->key !== 'root') {
            $node = $node->parent;
        }

        return $node;
    }
}
