<?php

// This file keeps track of upgrades to
// the totara_stats block
//

function xmldb_block_totara_stats_upgrade($oldversion, $block) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Totara 10 branching line.

    // Totara 12 branching line.

    if ($oldversion < 2018092600) {
        // Define index over columns used in several queries to improve query performance

        $table = new xmldb_table('block_totara_stats');
        
        $index = new xmldb_index('userid-eventtype-data2', XMLDB_INDEX_NOTUNIQUE, ['userid', 'eventtype', 'data2']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $index = new xmldb_index('eventtype-data2', XMLDB_INDEX_NOTUNIQUE, ['eventtype', 'data2']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Connect savepoint reached.
        upgrade_plugin_savepoint(true, 2018092600, 'block', 'totara_stats');
    }

    return true;
}
