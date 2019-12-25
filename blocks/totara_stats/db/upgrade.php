<?php

// This file keeps track of upgrades to
// the totara_stats block
//

function xmldb_block_totara_stats_upgrade($oldversion, $block) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Totara 10 branching line.

    return true;
}
