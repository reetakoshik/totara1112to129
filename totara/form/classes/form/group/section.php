<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_form
 */

namespace totara_form\form\group;

use totara_form\group,
    totara_form\item;

/**
 * Totara form section.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
class section extends group {
    /** @var string $legend */
    protected $legend;

    /** @var bool $collapsible */
    protected $collapsible = null;

    /** @var bool $expanded */
    protected $expanded = null;

    /**
     * Section constructor.
     *
     * @param string $name
     * @param string $legend
     */
    public function __construct($name, $legend) {
        $this->legend = $legend;
        parent::__construct($name);
    }

    /**
     * Add item as child of this item.
     *
     * @throws \coding_exception if you attempt to add one section into another.
     * @param item $item
     * @param int $position null means the end, 0 is the first element, -1 means last
     * @return item $item
     */
    public function add(item $item, $position = null) {
        if ($item instanceof section) {
            throw new \coding_exception('Section cannot be added to another section!');
        }
        return parent::add($item, $position);
    }

    /**
     * Get Mustache template data.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output) {
        $this->get_model()->require_finalised();
        $this->finalise_collapsible();

        $result = array(
            'form_item_template' => 'totara_form/group_section',
            'name__' . $this->get_name() => true,
            'name' => $this->get_name(),
            'id' => $this->get_id(),
            'legend' => $this->legend,
            'collapsible' => $this->get_collapsible(),
            'expanded' => $this->get_expanded(),
            'items' => array(),
            'amdmodule' => 'totara_form/form_group_section',
        );

        foreach ($this->get_items() as $item) {
            $detail = $item->export_for_template($output);
            if (debugging()) {
                if (isset($detail['elementtype'])) {
                    debugging('Form item parameter clash, elementtype is reserved.', DEBUG_DEVELOPER);
                }
                if (isset($detail['elementid'])) {
                    debugging('Form item parameter clash, elementid is reserved.', DEBUG_DEVELOPER);
                }
            }
            $detail['elementtype'] = get_class($item);
            $detail['elementid'] = $item->get_id();
            $detail['elementclassification'] = ($item instanceof group) ? 'group' : 'element';

            $result['items'][] = $detail;
        }

        // Add errors if found, tweak attributes by validators.
        $this->set_validator_template_data($result, $output);

        // Add help button data.
        $this->set_help_template_data($result, $output);

        return $result;
    }

    /**
     * Set collapsible.
     *
     * @param bool|null $value
     */
    public function set_collapsible($value) {
        if ($value !== null) {
            $value = (bool)$value;
        }
        $this->collapsible = $value;
    }

    /**
     * Is this section collapsible?
     *
     * @return bool|null
     */
    public function get_collapsible() {
        return $this->collapsible;
    }

    /**
     * Set expanded.
     *
     * @param bool|null $value
     */
    public function set_expanded($value) {
        if ($value !== null) {
            $value = (bool)$value;
        }
        $this->expanded = $value;
    }

    /**
     * Is this section expanded?
     *
     * @return bool|null
     */
    public function get_expanded() {
        return $this->expanded;
    }

    /**
     * Guess the proper collapsible/expanded state for this and optionally other sections.
     */
    protected function finalise_collapsible() {
        if ($this->get_parent() !== $this->get_model()) {
            // This should not happen, sections are supposed to be top containers only!
            $this->set_collapsible(false);
            $this->set_expanded(true);
            return;
        }

        if ($this->get_collapsible() === null) {
            $this->set_collapsible(true);
        }

        $sectiondata = $this->get_model()->get_raw_post_data($this->get_name());
        if ($sectiondata !== null and isset($sectiondata['expanded'])) {
            $this->set_expanded($sectiondata['expanded']);
        }

        if ($this->get_expanded() !== null) {
            // No need to do anything for this item.
            return;
        }

        // Now lets guess using the 'shortforms' logic.
        $expandedfound = false;

        /** @var section[] $guesssections */
        $guesssections = array();
        foreach ($this->get_model()->get_items() as $section) {
            if (!($section instanceof section)) {
                continue;
            }
            if ($section->get_collapsible() === null) {
                $section->set_collapsible(true);
            }
            if (!$section->get_collapsible()) {
                $section->set_expanded(true);
                $expandedfound = true;
                continue;
            }
            if ($section->get_expanded() === true) {
                $expandedfound = true;
                continue;
            }
            if ($section->get_expanded() === null) {
                if ($section->find(true, 'get_attributes', 'totara_form\item', true, array('required'))) {
                    $section->set_expanded(true);
                    $expandedfound = true;
                    continue;
                }
                $guesssections[] = $section;
                continue;
            }
        }

        foreach ($guesssections as $section) {
            if ($expandedfound) {
                // Set the rest of collapsible to expanded if nothing specified yet.
                $section->set_expanded(false);
            } else {
                // No section was expanded, find out if we can expand one by default.
                $section->set_expanded(true);
                $expandedfound = true;
            }
        }
    }
}
