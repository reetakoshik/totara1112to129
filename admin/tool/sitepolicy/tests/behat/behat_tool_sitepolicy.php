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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package tool_sitepolicy
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

/**
 * Sitepolicy behat definitions.
 */
class behat_tool_sitepolicy extends behat_base {

    /**
     * HACK ALERT: This fakes a language installation. Don't use it outside of site policies.
     *
     * @Given /^I fake the French language pack is installed for site policies$/
     */
    public function i_fake_the_french_language_pack_is_installed_for_site_policies() {
        global $CFG;
        make_writable_directory($CFG->dataroot . '/lang/fr');
        copy(
            $CFG->dirroot . '/admin/tool/sitepolicy/tests/fixtures/langconfig_fr.php',
            $CFG->dataroot . '/lang/fr/langconfig.php'
        );
        cache_helper::purge_by_definition('core', 'langmenu');
    }

    /**
     * HACK ALERT: This fakes a language installation. Don't use it outside of site policies.
     *
     * @Given /^I fake the Dutch language pack is installed for site policies$/
     */
    public function i_fake_the_dutch_language_pack_is_installed_for_site_policies() {
        global $CFG;
        make_writable_directory($CFG->dataroot . '/lang/nl');
        copy(
            $CFG->dirroot . '/admin/tool/sitepolicy/tests/fixtures/langconfig_nl.php',
            $CFG->dataroot . '/lang/nl/langconfig.php'
        );
        cache_helper::purge_by_definition('core', 'langmenu');
    }

}
