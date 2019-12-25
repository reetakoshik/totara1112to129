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

use \tool_sitepolicy\url_helper;

$policyversionid = required_param('policyversionid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

admin_externalpage_setup('tool_sitepolicy-managerpolicies', '', null, url_helper::version_delete($policyversionid));

$version = new \tool_sitepolicy\policyversion($policyversionid);
$sitepolicy = $version->get_sitepolicy();
$primarytitle = $version->get_primary_title(true);

if ($confirm) {
    // Must have the correct sesskey.
    require_sesskey();

    // Record this before we delete the version.
    $lastversion = ($version->get_versionnumber() == 1);

    // Delete the version, internally this uses a transaction.
    $version->delete();

    // Check if this was the last version number and delete the site policy if it was.
    if ($lastversion) {
        $sitepolicy->delete();

        $redirect_url = url_helper::sitepolicy_list();
        $redirect_message = get_string('deletepolicysuccess', 'tool_sitepolicy', $primarytitle);
    } else {
        $strparams = [
            'title' => $primarytitle,
            'version' => $version->get_versionnumber()
        ];
        $redirect_url = url_helper::version_list($sitepolicy->get_id());
        $redirect_message = get_string('deleteversionsuccess', 'tool_sitepolicy', $strparams);
    }
    redirect($redirect_url, $redirect_message, null, \core\output\notification::NOTIFY_SUCCESS);
}

$PAGE->set_title(get_string('deleteversionheading', 'tool_sitepolicy', $primarytitle));
$PAGE->set_heading($PAGE->title);
$PAGE->navbar->add($primarytitle, url_helper::version_list($sitepolicy->get_id()));
$PAGE->navbar->add(get_string('deleteversionx', 'tool_sitepolicy', $version->get_versionnumber()));

/** @var tool_sitepolicy_renderer $renderer */
$renderer = $PAGE->get_renderer('tool_sitepolicy');
echo $renderer->header();
echo $renderer->delete_version_confirmation($version);
echo $renderer->footer();
