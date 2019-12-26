<?php
function xmldb_local_contractor_webservice_upgrade($oldversion) {

    if ($oldversion < 2018122600) {
        global $DB;
        $dbman = $DB->get_manager();
        // Define field signupids to be added to contractor_service_history.
        $table = new xmldb_table('contractor_service_history');
        $field = new xmldb_field('signupids', XMLDB_TYPE_TEXT, null, null, null, null, null, 'status');

        // Conditionally launch add field signupids.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Contractor_webservice savepoint reached.
        upgrade_plugin_savepoint(true, 2018122600, 'local', 'contractor_webservice');
    }
    return true;
}

?>