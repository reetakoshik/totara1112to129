<?php
/*
 * This file is part of Totara LMS
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package contentmarketplace_goone
 */

defined('MOODLE_INTERNAL') || die;

/**
 * GO1 Content Marketplace plugin upgrade.
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool always true
 */
function xmldb_contentmarketplace_goone_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2018090800) {
        // This upgrade is only required for upgrades from backported versions (10 and 11).
        // Fresh installs receive these settings via install.php.

        // Enable GO1 course create workflow on upgrade.
        $workflow = contentmarketplace_goone\workflow\core_course\coursecreate\contentmarketplace::instance();
        $workflow->enable();
        // Enable goone Explore marketplace workflow on upgrade.
        $workflow = contentmarketplace_goone\workflow\totara_contentmarketplace\exploremarketplace\goone::instance();
        $workflow->enable();

        // Content Marketplace savepoint reached.
        upgrade_plugin_savepoint(true, 2018090800, 'contentmarketplace', 'goone');
    }

    if ($oldversion < 2018101900) {
        // Clean up old config settings if they exist.
        $settingchanges = [
            'contentmarketplace_goone\\workflow\\coursecreate\\contentmarketplace' => 'contentmarketplace_goone\\workflow\\core_course\\coursecreate\\contentmarketplace',
            'contentmarketplace_goone\\workflow\\exploremarketplace\\goone' => 'contentmarketplace_goone\\workflow\\totara_contentmarketplace\\exploremarketplace\\goone',
        ];
        foreach ($settingchanges as $oldclass => $newclass) {
            $oldsetting = get_config('totara_workflow', $oldclass);
            if (!is_null($oldsetting)) {
                if (!empty($oldsetting)) {
                    // If old setting was enabled, turn it on.
                    $workflow = $newclass::instance();
                    $workflow->enable();
                }
                // Remove old setting.
                unset_config($oldclass, 'totara_workflow');
            }
        }
        upgrade_plugin_savepoint(true, 2018101900, 'contentmarketplace', 'goone');
    }

    return true;
}
