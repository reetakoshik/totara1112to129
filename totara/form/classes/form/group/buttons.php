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

use totara_form\form\element\action_button,
    totara_form\group,
    totara_form\item;

/**
 * Group of submission buttons in Totara forms.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
class buttons extends group {
    /**
     * Add item as child of this item.
     *
     * @throws \coding_exception if the item is not an action_button instance.
     * @param item $item
     * @param int $position null means the end, 0 is the first element, -1 means last
     * @return item $item
     */
    public function add(item $item, $position = null) {
        if (!($item instanceof action_button)) {
            throw new \coding_exception('Button group can contain action_buttons only!');
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

        $result = array(
            'form_item_template' => 'totara_form/group_buttons',
            'name__' . $this->get_name() => true,
            'name' => $this->get_name(),
            'id' => $this->get_id(),
            'items' => array(),
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
