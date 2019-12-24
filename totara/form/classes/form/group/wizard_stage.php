<?php
/*
 * This file is part of Totara Learn
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
 * @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
 * @package totara_form
 */

namespace totara_form\form\group;

use totara_form\group;
use totara_form\item;

/**
 * Wizard Group stage.
 *
 * @author    Kevin Hottinger <kevin.hottinger@totaralearning.com>
 * @copyright 2017 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   totara_form
 */
class wizard_stage extends group {

    /**
     * Title of the stage
     *
     * @var string
     */
    private $title;


    /**
     * Flag indicating if the stage is hidden from view.
     *
     * @var bool
     */
    private $hidden;

    /**
     * Group constructor.
     *
     * @throws \coding_exception If the given name is not valid.
     * @param string $name
     * @param string $title
     */
    public function __construct(string $name, string $title) {
        parent::__construct($name);
        $this->title = $title;
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
        if ($item instanceof wizard_stage) {
            throw new \coding_exception('Wizard stages cannot be added to another stage.');
        }
        return parent::add($item, $position);
    }

    /**
     * Sets the parent wizard for this stage.
     *
     * @param item|null $parent
     * @throws \coding_exception when trying to add to a parent that is not a wizard.
     */
    public function set_parent(item $parent = null) {
        if (!$parent instanceof wizard) {
            throw new \coding_exception('Wizard stages can only be added to Wizards.');
        }
        return parent::set_parent($parent);
    }

    /**
     * Set stage hidden.
     *
     * @param bool $value
     */
    public function set_hidden(bool $value = true) {
        $this->hidden = $value;
    }

    /**
     * Is this stage hidden?
     *
     * @return bool
     */
    public function is_hidden() {
        return $this->hidden;
    }

    /**
     * Get Mustache template data.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output) {
        $this->get_model()->require_finalised();

        $result = array(
            'form_item_template' => 'totara_form/group_wizard_stage',
            'name' => $this->get_name(),
            'id' => $this->get_id(),
            'items' => array(),
            'title' => $this->title,
            'hidden' => $this->is_hidden(),
            'amdmodule' => '',
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

}
