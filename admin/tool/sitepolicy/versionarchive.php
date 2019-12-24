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

$policyversionid = required_param('policyversionid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

admin_externalpage_setup('tool_sitepolicy-managerpolicies', '', null, \tool_sitepolicy\url_helper::version_archive($policyversionid));

$version = new \tool_sitepolicy\policyversion($policyversionid);

if ($confirm) {

    // Must have the correct sesskey.
    require_sesskey();

    $time = time();
    $version->archive($time);

    $strparams = [
        'title' => $version->get_primary_title(true),
        'version' => $version->get_versionnumber()
    ];

    redirect(
        \tool_sitepolicy\url_helper::version_list($version->get_sitepolicy()->get_id()),
        get_string('archivesuccess', 'tool_sitepolicy', $strparams),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$PAGE->set_title(get_string('archiveheading', 'tool_sitepolicy', $version->get_primary_title(true)));
$PAGE->set_heading($PAGE->title);
$PAGE->navbar->add($version->get_primary_title(true));
$PAGE->navbar->add(get_string('deleteversionx', 'tool_sitepolicy', $version->get_versionnumber()));

/** @var tool_sitepolicy_renderer $renderer */
$renderer = $PAGE->get_renderer('tool_sitepolicy');
echo $renderer->header();
echo $renderer->archive_version_confirmation($version);
echo $renderer->footer();
