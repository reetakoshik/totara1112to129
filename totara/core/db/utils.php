<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @author Ciaran Irvine <ciaran.irvine@totaralms.com>
 * @package totara
 * @subpackage totara_core
 */

/**
* Totara Module upgrade savepoint, marks end of Totara module upgrade blocks
* It stores module version, resets upgrade timeout
* @deprecated
* @param bool $result false if upgrade step failed, true if completed
* @param string|float $version plugin version
* @param string $modname full component name of module
* @return void
*/
function totara_upgrade_mod_savepoint($result, $version, $modname) {
    debugging('totara_upgrade_mod_savepoint() is deprecated, use upgrade_plugin_savepoint() instead', DEBUG_DEVELOPER);
    list($type, $plugin) = explode('_', $modname, 2);
    upgrade_plugin_savepoint($result, $version, $type, $plugin);
}

/**
 * Get cohort rules that were broken by expansion of options in Totara 2.4.8.
 *
 * @return  array List of rules.
 */
function totara_get_text_broken_rules() {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/totara/cohort/lib.php');

    $userrulenames = "('idnumber', 'username', 'email', 'firstname', 'lastname', 'city', 'institution', 'department')";

    // Create sql snippet for rules based on users.
    $sqluserrules = "cr.ruletype = 'user' AND cr.name IN $userrulenames";

    // Create sql snippet for rules based on positions.
    $sqlposrules = "cr.ruletype = 'pos' AND cr.name IN ('idnumber', 'name')";

    // Create sql snippet for rules based on organisations.
    $sqlorgrules = "cr.ruletype = 'org' AND cr.name = 'idnumber'";

    // Create sql snippet for rules based on customfields.
    $sqlcustomrules = "cr.ruletype = :usercustomfield";

    // Find all active and draft rules in dynamic cohorts that could be affected by the expansion of rule options change.
    $sql = "SELECT crp.id, crp.ruleid, crp.name, crp.value, crp.timecreated, crp.timemodified, cr.ruletype, cr.name as rulename,
                   c.id as cohortid, c.name as cohortname, c.activecollectionid, crc.id as rulecollectionid
            FROM {cohort} c
            INNER JOIN {cohort_rule_collections} crc ON crc.id IN (c.activecollectionid, c.draftcollectionid)
            INNER JOIN {cohort_rulesets} crs ON crs.rulecollectionid = crc.id
            INNER JOIN {cohort_rules} cr ON cr.rulesetid = crs.id
            INNER JOIN {cohort_rule_params} crp ON cr.id = crp.ruleid
            WHERE c.cohorttype = :cohorttype
              AND crp.name = :equal
              AND ($sqluserrules
               OR $sqlposrules
               OR $sqlorgrules
               OR $sqlcustomrules)
            ORDER BY c.id, crp.id";

    $params = array('cohorttype' => cohort::TYPE_DYNAMIC, 'equal' => 'equal', 'usercustomfield' => 'usercustomfields');

    $rules = $DB->get_records_sql($sql, $params);

    // We might still have some rules that don't need to be fixed, check each rule is a text rule
    // before including it.
    $brokenrules = array();
    foreach ($rules as $rule) {
        // This type of rule doesn't need to be fixed (only text rules are affected).
        $ruledef = cohort_rules_get_rule_definition($rule->ruletype, $rule->rulename);
        if (get_class($ruledef->ui) === 'cohort_rule_ui_text') {
            $brokenrules[] = $rule;
        }
    }

    return $brokenrules;
}

/**
 * Function call before upgrade.
 *
 * This is executed before each main upgrade and before plugins start upgrading.
 *
 * @return void.
 */
function totara_preupgrade() {
    global $CFG, $DB, $OUTPUT; // These are used from the pre upgrade scripts, do not remove!

    static $executed = false;
    if ($executed) {
        return;
    }
    $executed = true;

    if (during_initial_install()) {
        // This is a fresh new Totara install running right now, nothing to do here!
        return;
    }

    $totarainfo = totara_version_info();

    print_upgrade_part_start('', false, true);

    if (empty($CFG->totara_release)) {
        // This is a migration from vanilla Moodle site to Totara.
        $upgradefile = "{$CFG->dirroot}/totara/core/db/pre_moodle_upgrade.php";
        core_php_time_limit::raise(0);
        require($upgradefile);

    } else {
        // This is an upgrade of existing Totara site.
        $upgradefile = "{$CFG->dirroot}/totara/core/db/pre_totara_upgrade.php";
        core_php_time_limit::raise(0);
        require($upgradefile);

        // NOTE: 'previous_version' is a dangerous hack - watch out for interrupted upgrades and + in version numbers,
        //       always use $oldversion in totara/core/upgrade.php instead if possible!!!
        set_config('previous_version', $totarainfo->existingtotaraversion, 'totara_core');
    }

    print_upgrade_part_end('', false, true);
}
