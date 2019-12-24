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

use \tool_sitepolicy\userconsent;
use \tool_sitepolicy\url_helper;

if (\core\session\manager::is_loggedinas()) {
    print_error('nopermissions', 'error', '', 'Site policy');
}

$language = optional_param('language', '', PARAM_SAFEDIR); // We can't use PARAM_LANG here the language pack may have been uninstalled.
$currentcount = optional_param('currentcount', 1, PARAM_INT);
$totalcount = optional_param('totalcount', 0, PARAM_INT);
$policyversionid = optional_param('policyversionid', 0, PARAM_INT);
$versionnumber = optional_param('versionnumber', 0, PARAM_INT);
$consentdata = optional_param('consentdata', '', PARAM_TEXT);

// Check if the user is logged in rather than calling require_login.
// If they are not logged in then they must, and require_login will redirect them back here if required.
// If they are logged in then calling require_login would lead to a recursive redirect.
if (!isloggedin()) {
    require_login(null, false);
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url("/{$CFG->admin}/tool/sitepolicy/userpolicy.php"));
$PAGE->set_popup_notification_allowed(false);

if (isset($SESSION->wantsurl)) {
    $wantsurl = $SESSION->wantsurl;
} else {
    $wantsurl = $CFG->wwwroot . '/';
}
$userid = $USER->id;

if (empty($policyversionid)) {
    $unanswered = \tool_sitepolicy\userconsent::get_unansweredpolicies($userid);
    if (count($unanswered) == 0) {
        $SESSION->tool_sitepolicy_consented = true;
        redirect($wantsurl);
    }

    if ($totalcount == 0 && count($unanswered) != 0) {
        $totalpolicyversion = [];
        foreach ($unanswered as $policy) {
            $totalpolicyversion[] = $policy->policyversionid;
        }
        $totalcount = count(array_unique($totalpolicyversion));
    }

    // This shouldn't happen, but just in case
    if ($totalcount == 0) {
        $SESSION->tool_sitepolicy_consented = true;
        redirect($wantsurl);
    }

    if (isguestuser()) {
        // For guest users all policies are always returned
        for ($i = 0; $i < $currentcount; $i++) {
            $current = array_shift($unanswered);
        }
    } else {
        $current = current($unanswered);
    }

    $policyversionid = $current->policyversionid;
    $version = new \tool_sitepolicy\policyversion($policyversionid);
} else {
    if ($totalcount == 0) {
        throw new coding_exception('Parameter totalcount is expected if policyversionid is given');
    }

    $version = new \tool_sitepolicy\policyversion($policyversionid);
}

$versionnumber = $version->get_versionnumber();
$availlanguages = get_string_manager()->get_list_of_translations();

if (empty($language)) {
    $language = \tool_sitepolicy\userconsent::get_user_consent_language($policyversionid, $userid, true);
}

$currentpolicy = \tool_sitepolicy\localisedpolicy::from_version($version, ['language' => $language]);

$currentdata = [
    'policyversionid' => $policyversionid,
    'versionnumber' => $versionnumber,
    'localisedpolicyid' => $currentpolicy->get_id(),
    'language' => $language,
    'currentcount' => $currentcount,
    'totalcount' => $totalcount,
    'title' => $currentpolicy->get_title(true),
    'policytext' => $currentpolicy->get_policytext(true),
    'whatsnew' => $currentpolicy->get_whatsnew(true),
];

$statements = $currentpolicy->get_statements(false);
$currentdata['statements'] = [];
foreach ($statements as $idx => $statement) {
    $dataid = abs($statement->dataid);        // get_statements return negative dataids for persisted statements
    $currentdata['statements'][] = [
        'dataid' => $dataid,
        'mandatory' => $statement->mandatory,
        'statement' => $statement->statement,
        'provided' => $statement->provided,
        'withheld' => $statement->withheld,
    ];
}

if (!empty($consentdata)) {
    $answers = explode(',', $consentdata);
    foreach ($answers as $answer) {
        $data = explode('-', $answer);
        $currentdata = array_merge($currentdata, ['option' . $data[0] => (int)$data[1]]);
    }
} else {
    // If user has consented before, show his previous answers
    $options = $currentdata['statements'];
    foreach ($options as $option) {
        if (userconsent::has_user_answered($option['dataid'], $userid)) {
            $hasconsent = userconsent::has_user_consented($option['dataid'], $userid);
            $currentdata = array_merge($currentdata, ['option' . $option['dataid'] => (int)$hasconsent]);
        }
    }
}

$params = ['hidden' => [
    'policyversionid' => PARAM_INT,
    'versionnumber' => PARAM_INT,
    'localisedpolicyid' => PARAM_INT,
    'language' => PARAM_ALPHANUMEXT,
    'currentcount' => PARAM_INT,
    'totalcount' => PARAM_INT,
]];

$form = new \tool_sitepolicy\form\userconsentform($currentdata, $params);

if ($form->is_cancelled()) {
    // Not allowing cancel here - this code is just a safety net
    redirect(url_helper::user_sitepolicy_consent($currentcount, $totalcount));

} elseif ($formdata = $form->get_data()) {

    $userconsent = new userconsent();
    $userconsent->set_userid($userid);

    $options = $currentdata['statements'];
    $mandatorywithhelds = array_filter($options, function($option) use ($formdata) {
        $optionkey = 'option' . $option['dataid'];
        $consent = $formdata->userconsent[$optionkey];
        $mandatory = $option['mandatory'];
        return (empty($consent) and $mandatory == true);
    });

    if (count($mandatorywithhelds) > 0) {
        // We need the consentoption data for saving on user as well as when user returns

        $answers = [];
        foreach ($options as $option) {
            $optionkey = 'option' . $option['dataid'];
            $consent = $formdata->userconsent[$optionkey];

            $answers[] = implode('-', [$option['dataid'], (int)$consent]);
        }

        $answers = implode(',', $answers);

        redirect(url_helper::user_sitepolicy_reject_confirmation($policyversionid, $language, $currentcount, $totalcount, $answers));
    }

    foreach ($options as $option) {
        $optionkey = 'option' . $option['dataid'];
        $consent = $formdata->userconsent[$optionkey];
        $mandatory = $option['mandatory'];

        $userconsent->set_hasconsented((int)$consent);
        $userconsent->set_consentoptionid($option['dataid']);
        $userconsent->set_language($language);
        $userconsent->save();
    }

    // Will only get here is $SESSION->tool_sitepolicy_consented not previously set
    // We set it here if user has consented to all to handle guests correctly and also
    // avoid uneccessary db queries
    if ($currentcount == $totalcount) {
        $SESSION->tool_sitepolicy_consented = true;
        redirect($wantsurl);
    } else {
        redirect(url_helper::user_sitepolicy_consent($currentcount + 1, $totalcount));
    }
}

$PAGE->set_title($currentpolicy->get_title(false));

// Navigation Bar
// Start the navigation off at the users branch.
if ($node = $PAGE->navigation->find('user' . $USER->id, navigation_node::TYPE_USER)) {
    $node->make_active();
}

/** @var tool_sitepolicy_renderer $renderer */
$renderer = $PAGE->get_renderer('tool_sitepolicy');

echo $renderer->header();
echo $renderer->heading(get_string('userconsentxofy', 'tool_sitepolicy', ['currentpolicy' => $currentcount, 'totalpolicies' => $totalcount]), 4);

//Langugae Selection Dropdown
$verlanguages = $version->get_languages();
$langarray = [];
$availlanguages = get_string_manager()->get_list_of_translations(true);
if (!array_key_exists($language, $availlanguages)) {
    // Handling case where language pack has been removed
    $languages = get_string_manager()->get_list_of_languages();
    if (isset($languages[$language])) {
        $langarray[$language] = $languages[$language];
    } else {
        $langarray[$language] = $language;
    }
}

foreach ($verlanguages as $lang => $row) {
    if (array_key_exists($lang, $availlanguages)) {
        $langarray[$lang] = $availlanguages[$lang];
    }
}

if (!empty($langarray)) {
    $langurl = url_helper::user_sitepolicy_version_view($userid, $policyversionid, $versionnumber, null, $currentcount, $totalcount);
    $select = new \single_select($langurl, 'language', $langarray, $language, [], 'userpolicy');
    $select->class = 'singleselect pull-right';
    echo $renderer->render($select);
}

echo $renderer->form($form);
echo $renderer->footer();

