<?php

function xmldb_local_compsync_install() {
    global $CFG, $DB;

    ///
    /// Add totarasync flag to element tables
    ///
    $dbman = $DB->get_manager();

    // comp
    $table = new xmldb_table('comp');
    $field = new xmldb_field('totarasync');
    $field->set_attributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', null);
    if (!$dbman->field_exists($table, $field)) {
        // Launch add field totarasync
        $dbman->add_field($table, $field);
    }
    $index = new xmldb_index('totarasync');
    $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('totarasync'));
    if (!$dbman->index_exists($table, $index)) {
        $dbman->add_index($table, $index);
    }

    return true;
}