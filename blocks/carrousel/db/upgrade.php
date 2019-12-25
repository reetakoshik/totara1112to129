<?php

defined('MOODLE_INTERNAL') || die;

function xmldb_block_carrousel_upgrade($oldversion) 
{
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2016112501) {
        $table = new xmldb_table('block_carrousel');
        $field = new xmldb_field('textcolor');
        $field->set_attributes(XMLDB_TYPE_TEXT, 'small');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2016112501, 'carrousel');
    }

    if ($oldversion < 2016122101) {
        $settings = new xmldb_table('block_carrousel_settings');

        $settings->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $settings->add_field('block_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $settings->add_field('scroll_duration', XMLDB_TYPE_FLOAT, '10,2', null, XMLDB_NOTNULL, null, null);
        $settings->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $settings->add_key('block_id', XMLDB_KEY_FOREIGN, array('block_id'), 'forum', array('id'));
        if (!$dbman->table_exists($settings)) {
            $dbman->create_table($settings);
        }

        upgrade_block_savepoint(true, 2016122101, 'carrousel');
    }

    return true;
}

