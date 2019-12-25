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
 * @copyright 2016 onwards Totara Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Joby Harding <joby.harding@totaralearning.com>
 * @package   theme_basis
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once("{$CFG->dirroot}/totara/core/renderer.php");

class theme_basis_totara_core_renderer extends totara_core_renderer {

    /**
     * Return the currently selected page's menu item or null.
     *
     * Note: This function may mutate the contextdata to ensure the
     * .selected class is consistently applied.
     *
     * @param $contextdata stdClass
     * @return array
     */
    protected function current_selected_item(&$contextdata) {

        $currentitem = null;
        $itemindex = -1;
        $childselected = false;

        foreach (array_values($contextdata->menuitems) as $i => $navitem) {

            if ($currentitem !== null) {
                break;
            }

            if ($navitem['class_isselected']) {
                if (empty($navitem['children'])) {
                    $currentitem = $navitem;
                    $itemindex = $i;
                }
            }

            foreach ($navitem['children'] as $childitem) {
                if ($childitem['class_isselected']) {
                    $currentitem = $navitem;
                    $itemindex = $i;
                    $childselected = true;
                }
            }
        }

        if ($currentitem !== null) {
            // the .selected class is not consistently added so normalize TL-10596
            $contextdata->menuitems[$itemindex]['class_isselected'] = true;
            // Add a class so we know it is the child item which is active.
            if ($childselected) {
                $contextdata->menuitems[$itemindex]['class_child_isselected'] = true;
            }
        }

        return $currentitem;
    }

    /**
     * Render menu.
     *
     * @param \totara_core\output\totara_menu $totaramenu
     * @return string
     */
    protected function render_totara_menu(totara_core\output\totara_menu $totaramenu) {

        global $OUTPUT;

        $contextdata = $totaramenu->export_for_template($this);
        $currentselected = $this->current_selected_item($contextdata);
        $contextdata->subnav = array();

        if ($currentselected !== null) {
            if (!empty($currentselected['children'])) {
                $contextdata->subnav = $currentselected['children'];
            }
        }

        $contextdata->subnav_has_items = !empty($contextdata->subnav);

        // If the first item is the default home link replace it with an icon.
        if (!empty($contextdata->menuitems)) {
            $firstitem = $contextdata->menuitems[0];
            if ($firstitem['class_name'] === 'home') {
                $icon = $OUTPUT->flex_icon('home', array('alt' => $firstitem['linktext']));
                $contextdata->menuitems[0]['linktext'] = $icon;
            }
        }

        return $this->render_from_template('totara_core/totara_menu', $contextdata);
    }

}
