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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_catalog
 */

use totara_catalog\local\config_form_helper;

defined('MOODLE_INTERNAL') || die();

class totara_catalog_renderer extends plugin_renderer_base {

    /**
     * @param string $currenttab
     * @return string
     */
    public function config_tabs($currenttab = 'general') {
        global $CFG;

        $tabs = [];
        $row = [];
        $activated = [];
        $inactive = [];

        if (has_capability('totara/catalog:configurecatalog', context_system::instance())) {
            foreach (config_form_helper::create()->get_form_keys() as $tab) {
                $row[] = new tabobject(
                    $tab,
                    $CFG->wwwroot . '/totara/catalog/config.php?tab=' . $tab,
                    get_string($tab, 'totara_catalog')
                );
            }
        }

        $tabs[] = $row;
        $activated[] = $currenttab;

        return print_tabs($tabs, $currenttab, $inactive, $activated, true);
    }
}
