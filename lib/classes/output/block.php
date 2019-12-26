<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @author Simon Player <simon.player@totaralms.com>
 * @package core
 * @subpackage output
 */

namespace core\output;

use renderable;
use block_contents;
use html_writer;


class block implements renderable {

    public $attributes = array();
    public $accessible_skip_from = array();
    public $accessible_skip_to = array();
    public $header = false;
    public $content;
    public $footer = false;
    public $annotation = false;

    /**
     * Builds the block data object
     *
     * @param object $bc The block_contents.
     * @param object  $output The renderer object.
     * @return object the data object.
     */
    public static function from_block_contents(block_contents $bc, \renderer_base $output) {
        if (empty($bc->blockinstanceid) || !strip_tags($bc->title)) {
            $bc->collapsible = block_contents::NOT_HIDEABLE;
        }
        if (!empty($bc->blockinstanceid)) {
            $bc->attributes['data-instanceid'] = $bc->blockinstanceid;
        }
        $skiptitle = strip_tags($bc->title);
        if ($bc->blockinstanceid && !empty($skiptitle)) {
            $bc->attributes['aria-labelledby'] = 'instance-'.$bc->blockinstanceid.'-header';
        } else if (!empty($bc->arialabel)) {
            $bc->attributes['aria-label'] = $bc->arialabel;
        }
        if ($bc->dockable) {
            $bc->attributes['data-dockable'] = 1;
        }
        if ($bc->collapsible == block_contents::HIDDEN) {
            $bc->add_class('hidden');
        }
        if (!empty($bc->controls)) {
            $bc->add_class('block_with_controls');
        }

        $block = new self;

        // Attributes.
        foreach ($bc->attributes as $name => $value) {
            $block->attributes[] = array(
                'name' => $name,
                'value' => $value
            );
        }

        if ($skiptitle) {
            $block->accessible_skip = array(
                'id' => $bc->skipid,
                'title' => $skiptitle,
                'skiptext' => get_string('skipa', 'access', $skiptitle)
            );

            // @deprecated since Totara 10
            $block->accessible_skip_from = array('href' => '#sb-' . $bc->skipid, 'title' => $skiptitle);
            $block->accessible_skip_to = array('id' => 'sb-' . $bc->skipid);
        }
        $title = array();
        if ($bc->title) {
            if ($bc->blockinstanceid) {
                $id = 'instance-'.$bc->blockinstanceid.'-header';
            } else {
                $id = html_writer::random_id('instance-').'-header';
            }
            $title = array(
                'text' => $bc->title,
                'id' => $id
            );
        }
        $blockid = null;
        if (isset($bc->attributes['id'])) {
            $blockid = $bc->attributes['id'];
        }
        $controls = array();
        if ($bc->controls) {
            $controls['control_output'] = $output->block_controls($bc->controls, $blockid);
        }

        $block->header = array(
            'title'    => false,
            'controls' => false,
            'no_header' => false
        );
        if ($title) {
            $block->header['title'] = $title;
        }
        if ($controls) {
            $block->header['controls'] = $controls;
        }

        $block->header['collapsible'] = $bc->header_collapsible ?? true;

        $block->dock_title = $bc->dock_title;

        $block->header['display'] = true;
        if (isset($bc->displayheader) && !$bc->displayheader) {
            unset($block->header);
        }

        if ($bc->noheader) {
            $block->header['no_header'] = true;
        }

        $block->content = $bc->content;

        if ($bc->footer) {
            $block->footer = array(
                'footer_content' => $bc->footer
            );
        }

        if ($bc->annotation) {
            $block->annotation = array(
                'annotation_content' => $bc->annotation
            );
        }
        return $block;
    }
}
