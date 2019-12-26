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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\output;

defined('MOODLE_INTERNAL') || die();

/**
 * @deprecated since 12.0
 */
class totara_menu implements \renderable, \templatable {

    public $menuitems = array();

    /**
     * @deprecated since 12.0
     */
    public function __construct($menudata, $parent=null, $selected_items=array()) {
        global $PAGE, $FULLME;

        debugging('Class totara_core\output\totara_menu has been deprecated since 12.0');

        // Gets selected items, only done first time
        if (!$selected_items && $PAGE->totara_menu_selected) {
            foreach ($menudata as $item) {
                if ($PAGE->totara_menu_selected == $item->name) {
                    $selected_items = array($item->name);
                }
            }
        }

        if (!$selected_items) {
            $urlcache = [];
            foreach ($menudata as $item) {
                $url = new \moodle_url($item->url);
                $urlcache[$item->name] = $url;
                if ($PAGE->url->compare($url)) {
                    $selected_items = array($item->name);
                    break;
                }
            }
            if (!$selected_items) {
                foreach ($menudata as $item) {
                    if ($urlcache[$item->name]->compare(new \moodle_url($FULLME), URL_MATCH_EXACT)) {
                        $selected_items = array($item->name);
                        break;
                    }
                }
            }
            unset($urlcache);
        }

        $currentlevel = array();
        foreach ($menudata as $menuitem) {
            if ($menuitem->parent == $parent) {
                $currentlevel[] = $menuitem;
            }
        }

        $numitems = count($currentlevel);

        $count = 0;
        if ($numitems > 0) {
            // Create Structure
            foreach ($currentlevel as $menuitem) {
                $class_isfirst = ($count == 0 ? true : false);
                $class_islast  = ($count == $numitems - 1 ? true : false);
                // If the menu item is known to be selected or it if its a direct match to the current pages URL.
                $class_isselected = (in_array($menuitem->name, $selected_items) ? true : false);

                $children = new self($menudata, $menuitem->name, $selected_items);
                $haschildren = ($children->has_children() ? true : false);
                $url = new \moodle_url($menuitem->url);
                $this->menuitems[] = array(
                    'class_name' => $menuitem->name,
                    'class_isfirst' => $class_isfirst,
                    'class_islast' => $class_islast,
                    'class_isselected' => $class_isselected,
                    'linktext' => $menuitem->linktext,
                    'url' => $url->out(false),
                    'target' => $menuitem->target,
                    'haschildren' => $haschildren,
                    'children' => $children->get_items()
                );
                $count++;
            }
        }
    }

    /**
     * Returns the menu item for this level of menu
     *
     * @return array Array of menu items
     *
     */
    private function get_items() {
        return $this->menuitems;
    }

    /**
     * Has this menu item got children
     *
     * @return bool Returns true if the item has children
     */
    private function has_children() {
        return !empty($this->menuitems);
    }

    /**
     * Export data to be used as the context for a mustache template to the menu.
     *
     * @param \renderer_base $output
     * @return \stdClass
     */
    public function export_for_template(\renderer_base $output) {

        $menudata = new \stdClass();
        $menudata->menuitems = $this->menuitems;

        return $menudata;
    }
}
