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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_contentmarketplace
 */

/**
 * Content Marketplace install hook.
 */
function xmldb_totara_contentmarketplace_install() {
    global $CFG;

    // Check for explicit disable via config.php
    // To disable this notification add $CFG->enablecontentmarketplace = false; to config.php prior to upgrading.
    $forcedisabled = (!\totara_contentmarketplace\local::is_enabled() && array_key_exists('enablecontentmarketplaces', $CFG->config_php_settings));

    // Don't send notification if force disabled.
    if (!$forcedisabled) {

        // Don't generate welcome notifications when tests running.
        if (!PHPUNIT_TEST && !defined('BEHAT_SITE_RUNNING')) {
            // Queue tasks to notify admins about content marketplace.
            $task = new \totara_contentmarketplace\task\welcome_notification_task();
            \core\task\manager::queue_adhoc_task($task);
        }
    }
}
