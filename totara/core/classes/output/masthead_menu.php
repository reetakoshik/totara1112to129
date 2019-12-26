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

class masthead_menu implements \renderable, \templatable {

    public $menuitems = array();

    public function __construct($menudata, $parent=null) {

        $currentlevel = array();
        foreach ($menudata as $menuitem) {
            if ($menuitem->parent == $parent) {
                $currentlevel[] = $menuitem;
            }
        }

        $numitems = count($currentlevel);

        $count = 0;
        if ($numitems > 0) {
            // Create Structure.
            foreach ($currentlevel as $menuitem) {
                $class_isfirst = ($count == 0 ? true : false);
                $class_islast  = ($count == $numitems - 1 ? true : false);

                $children = new self($menudata, $menuitem->name);
                $haschildren = ($children->has_children() ? true : false);
                $externallink = ($menuitem->target == '_blank' ? true : false);
                $url = new \moodle_url($menuitem->url);
                $this->menuitems[] = array(
                    'class_name' => $menuitem->name,
                    'class_isfirst' => $class_isfirst,
                    'class_islast' => $class_islast,
                    'class_isselected' => $menuitem->is_selected,
                    'external_link' => $externallink,
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
     * Return the currently selected page's menu item or null.
     *
     * Note: This function may mutate the contextdata to ensure the
     * .selected class is consistently applied.
     *
     * @param $contextdata stdClass
     * @return array
     */
    protected function totara_menu_current_selected_item(&$contextdata) {
        return $this->totara_menu_node_selected($contextdata->menuitems);
    }

    /**
     * Recursively search an array for the current navigation item
     *
     * @param $menuitems array list of navigation items to search
     * @param $node array|null parent navigation node whose children are being searched
     * @return array|null current navigation item, or null if none is found
     */
    private function totara_menu_node_selected(&$menuitems, &$node = null) {

        $currentitem = null;

        foreach ($menuitems as $i => &$navitem) {

            if ($navitem['class_isselected']) {
                $currentitem = $navitem;
                break;
            }

            if (!empty($navitem['children'])) {
                $currentitem = $this->totara_menu_node_selected($navitem['children'], $navitem);
                if ($currentitem !== null) {
                    break;
                }
            }
        }

        if ($currentitem !== null && $node !== null) {
            // The .selected class is not consistently added so normalize TL-10596.
            $node['class_isselected'] = true;
            // Add a class so we know it is the child item which is active.
            $node['class_child_isselected'] = true;

            return $node;
        }

        return $currentitem;
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

        $currentselected = $this->totara_menu_current_selected_item($menudata);
        $menudata->subnav = array();

        if ($currentselected !== null) {
            if (!empty($currentselected['children'])) {
                $menudata->subnav = $currentselected['children'];
            }
        }

        $menudata->subnav_has_items = !empty($menudata->subnav);

        // If the first item is the default home link replace it with an icon.
        if (!empty($menudata->menuitems)) {
            $firstitem = $menudata->menuitems[0];
            if ($firstitem['class_name'] === 'home') {
                $icon = $output->flex_icon('home', array('alt' => $firstitem['linktext']));
                $menudata->menuitems[0]['homeicon'] = $icon;
            }

            $burgermenu = $output->flex_icon('bars', array(
                'alt' => get_string('togglenavigation', 'core'),
                'classes' => 'totaraNav--icon_burger'
            ));

            $closemenu = $output->flex_icon('close', array(
                'alt' => get_string('closebuttontitle', 'core'),
                'classes' => 'totaraNav--icon_close_menu'
            ));

            $externallink = $output->flex_icon('external-link', array(
                'alt' => get_string('openlinkinnewwindow', 'core'),
                'classes' => 'totaraNav--icon_link_external'
            ));

            $menudata->burger_icon = $burgermenu;
            $menudata->close_menu_icon = $closemenu;
            $menudata->external_link_icon = $externallink;
        }

        return $menudata;
    }
}
