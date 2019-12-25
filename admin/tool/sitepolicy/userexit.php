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

use \tool_sitepolicy\policyversion;
use \tool_sitepolicy\localisedpolicy;
use \tool_sitepolicy\userconsent;
use \tool_sitepolicy\url_helper;

// Check if the user is logged in rather than calling require_login.
// If they are not logged in then they must, and require_login will redirect them back here if required.
// If they are logged in then calling require_login would lead to a recursive redirect.
if (!isloggedin()) {
    require_login(null, false);
}

$policyversionid = required_param('policyversionid', PARAM_INT);
$language = required_param('language', PARAM_SAFEDIR); // We can't use PARAM_LANG here the language pack may have been uninstalled.
$currentcount = required_param('currentcount', PARAM_INT);
$totalcount = required_param('totalcount', PARAM_INT);
$consentdata = required_param('consentdata', PARAM_TEXT);
$confirm = optional_param('confirm', 0, PARAM_INT);

$currenturl = url_helper::user_sitepolicy_reject_confirmation($policyversionid, $language, $currentcount, $totalcount, $consentdata);

$PAGE->set_context(context_system::instance());
$PAGE->set_url($currenturl);

if ($confirm) {

    // Must have the correct sesskey.
    require_sesskey();

    if (empty($language)) {
        throw new \coding_exception("language should be passed with confirmation of user exit");
    }

    // Save consent data and log out
    $answers = explode(',', $consentdata);
    foreach ($answers as $answer) {
        $data = explode('-', $answer);
        $userconsent = new userconsent();
        $userconsent->set_userid($USER->id);
        $userconsent->set_consentoptionid($data[0]);
        $userconsent->set_hasconsented((bool)$data[1]);
        $userconsent->set_language($language);
        $userconsent->save();
    }

    redirect(new moodle_url(new moodle_url('/login/logout.php', ['sesskey' => sesskey()])));
}

if (empty($policyversionid)) {
    throw new \coding_exception("policyversionid should be passed to userexit.php");
}

$version = new policyversion($policyversionid);
if (!empty($language)) {
    $currentpolicy = localisedpolicy::from_version($version, ['language' => $language]);
} else {
    $currentpolicy = localisedpolicy::from_version($version, ['isprimary' => localisedpolicy::STATUS_PRIMARY]);
}

$heading = get_string('userexitheading', 'tool_sitepolicy');

$PAGE->set_title($heading);
$PAGE->set_heading($heading);

/** @var tool_sitepolicy_renderer $renderer */
$renderer = $PAGE->get_renderer('tool_sitepolicy');
echo $renderer->header();

$message = $renderer->heading(get_string('userexittitle', 'tool_sitepolicy'));
$message .= get_string('userexitmessage', 'tool_sitepolicy', $currentpolicy->get_title(true));
$backurl = url_helper::user_sitepolicy_version_view($USER->id, $policyversionid, $version->get_versionnumber(), $language, $currentcount, $totalcount);
$backurl->param('consentdata', $consentdata);
$backbutton = new single_button($backurl, get_string('userexitback', 'tool_sitepolicy'));
$logout = new single_button(new moodle_url($currenturl, ['confirm' => true]), get_string('userexitlogout', 'tool_sitepolicy'));

echo $renderer->action_confirm($heading, $message, $backbutton, $logout);
echo $renderer->footer();
