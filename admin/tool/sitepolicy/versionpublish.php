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

use \tool_sitepolicy\policyversion,
    \tool_sitepolicy\url_helper;

$policyversionid = required_param('policyversionid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

admin_externalpage_setup('tool_sitepolicy-managerpolicies', '', null, url_helper::version_publish($policyversionid));

$version = new policyversion($policyversionid);
$sitepolicy = $version->get_sitepolicy();

$versionlisturl = url_helper::version_list($sitepolicy->get_id());
$primarypolicy = $version->get_primary_localisedpolicy();

if ($version->has_incomplete_language_translations()) {
    debugging('Policy versions with incomplete translations cannot be published.', DEBUG_DEVELOPER);
    redirect($versionlisturl);
}

// Perform action.
if ($confirm) {

    // You must have the correct sesskey.
    require_sesskey();

    $sitepolicy->switchversion($version);

    $message = get_string('publishsuccess', 'tool_sitepolicy', [
        'title' => $primarypolicy->get_title(true),
        'version' => $version->get_versionnumber()
    ]);
    redirect($versionlisturl, $message, null, \core\output\notification::NOTIFY_SUCCESS);
}

// Output.
$heading = get_string('publishheading', 'tool_sitepolicy', $primarypolicy->get_title(true));

$PAGE->set_title($heading);
$PAGE->set_heading($heading);
$PAGE->navbar->add($primarypolicy->get_title(false), $versionlisturl);
$PAGE->navbar->add(get_string('versionpublish', 'tool_sitepolicy'));

/** @var tool_sitepolicy_renderer $renderer */
$renderer = $PAGE->get_renderer('tool_sitepolicy');
echo $renderer->header();
echo $renderer->publish_version_confirmation($version);
echo $renderer->footer();
