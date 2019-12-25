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
 * @package block_totara_quicklinks
 */

function xmldb_block_totara_quicklinks_install() {
    global $DB;

    // Get the id of the default mymoodle page.
    $mypageid = $DB->get_field_sql('SELECT id FROM {my_pages} WHERE userid IS null AND private = 1');

    // A separate set up for a quicklinks block as it needs additional data to be added on install.
    $blockinstance = new stdClass();
    $blockinstance->blockname = 'totara_quicklinks';
    $blockinstance->parentcontextid = SITEID;
    $blockinstance->showinsubcontexts = 0;
    $blockinstance->pagetypepattern = 'my-index';
    $blockinstance->subpagepattern = $mypageid;
    $blockinstance->defaultregion = 'side-post';
    $blockinstance->defaultweight = 1;
    $blockinstance->configdata = '';
    $blockinstance->id = $DB->insert_record('block_instances', $blockinstance);

    // Ensure the block context is created.
    context_block::instance($blockinstance->id);

    // If the new instance was created, allow it to do additional setup.
    if ($block = block_instance('totara_quicklinks', $blockinstance)) {
        $block->instance_create();
    }
}

