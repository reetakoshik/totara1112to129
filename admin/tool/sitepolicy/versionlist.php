<?php
/**
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Courteney Brownie <courteney.brownie@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package tool_sitepolicy
 */

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use \tool_sitepolicy\localisedpolicy,
    \tool_sitepolicy\url_helper;

$sitepolicyid = required_param('sitepolicyid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHANUMEXT);

admin_externalpage_setup('tool_sitepolicy-managerpolicies', '', null, url_helper::version_list($sitepolicyid));

$sitepolicy = new \tool_sitepolicy\sitepolicy($sitepolicyid);

// Perform actions.
if ($action === 'newdraft') {

    // You must have the correct sesskey.
    require_sesskey();

    // Create a new draft version.
    $draft = $sitepolicy->create_new_draft_version();

    // Redirect to new version primary localised policy edit form
    redirect(url_helper::localisedpolicy_create($draft->get_primary_localisedpolicy()->get_id()));

} else if ($action !== '') {

    throw new coding_exception('Invalid action passed', $action);

}

$version = \tool_sitepolicy\policyversion::from_policy_latest($sitepolicy);

$PAGE->set_title(get_string('versionstitle', 'tool_sitepolicy'));
$PAGE->navbar->add($version->get_primary_title(true));

/** @var \tool_sitepolicy\output\page_renderer $renderer */
$renderer = $PAGE->get_renderer('tool_sitepolicy', 'page');
echo $renderer->policyversion_list($version);
