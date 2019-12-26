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
 * @author Sergey Vidusov <sergey.vidusov@androgogic.com>
 * @package totara_contentmarketplace
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig or has_any_capability(array('totara/contentmarketplace:config', 'totara/contentmarketplace:add'), $systemcontext)) {

    /** @var admin_root $ADMIN */
    $ADMIN->add('root', new admin_category(
            'contentmarketplace',
            get_string('contentmarketplace', 'totara_contentmarketplace')
        ),
        'appearance'
    );

    $marketplaceenabled = \totara_contentmarketplace\local::is_enabled();
    // Check for explicit disable via config.php
    $forcedisabled = (!$marketplaceenabled && array_key_exists('enablecontentmarketplaces', $CFG->config_php_settings));
    // Check if enabled and configured.
    $alreadysetup = ($marketplaceenabled && !\totara_contentmarketplace\local::should_show_admin_setup_intro());

    // Hide if force disabled or already setup.
    $ADMIN->add('contentmarketplace', new admin_externalpage(
        'setup_content_marketplaces',
        get_string('setup_content_marketplaces', 'totara_contentmarketplace'),
        $CFG->wwwroot.'/totara/contentmarketplace/setup.php',
        'totara/contentmarketplace:config',
        ($forcedisabled || $alreadysetup)
    ));

    // Hide unless marketplaces are already setup.
    $ADMIN->add('contentmarketplace', new admin_externalpage(
        'manage_content_marketplaces',
        get_string('manage_content_marketplaces', 'totara_contentmarketplace'),
        $CFG->wwwroot.'/totara/contentmarketplace/marketplaces.php',
        'totara/contentmarketplace:config',
        !$alreadysetup
    ));

    $beforesibling = null;
    if (has_any_capability(['moodle/restore:restorefile', 'moodle/backup:downloadfile'], $systemcontext)) {
        $beforesibling = 'restorecourse';
    }
    $wm = new \totara_contentmarketplace\workflow_manager\exploremarketplace();
    $ADMIN->add(
        'courses',
        new admin_externalpage(
            'exploremarketplaces',
            new lang_string('explore_totara_content', 'totara_contentmarketplace'),
            $wm->get_url(),
            array('totara/contentmarketplace:add'),
            (!$marketplaceenabled || !$wm->workflows_available())
        ),
        $beforesibling
    );

    // Clean up after ourselves, the admin tree is big enough without us leaving things around.
    unset($wm);
    unset($marketplaceenabled);
    unset($forcedisabled);
    unset($alreadysetup);
    unset($beforesibling);
}
