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
    \tool_sitepolicy\policyversion;

// Localised policy can be identified in two ways: by id and by version and language.
$language = optional_param('language', '', PARAM_SAFEDIR); // We cannot use PARAM_LANG here as the language pack may not be installed.
$policyversionid = optional_param('policyversionid', false,PARAM_INT);
$localisedpolicyid = optional_param('localisedpolicy', false, PARAM_INT);

if ($language) {
    if (!$policyversionid) {
        print_error('missingparam', '', '', 'policyversionid');
    }
    $url  = \tool_sitepolicy\url_helper::version_create($policyversionid, $language);
} else {
    if (!$localisedpolicyid) {
        print_error('missingparam', '', '', 'localisedpolicy');
    }
    $url  = \tool_sitepolicy\url_helper::localisedpolicy_edit($localisedpolicyid);
}
admin_externalpage_setup('tool_sitepolicy-managerpolicies', '', null, $url);

if ($policyversionid) {
    $version = new policyversion($policyversionid);
    if (localisedpolicy::exists($version,
                                ['language' => $language,
                                 'isprimary' => localisedpolicy::STATUS_NOTPRIMARY])) {
        $localisedpolicy = localisedpolicy::from_version($version,
            ['isprimary' => localisedpolicy::STATUS_NOTPRIMARY,
             'language' => $language]);
    } else {
        $localisedpolicy = localisedpolicy::from_data($version, $language);
    }
} else {
    $localisedpolicy = new localisedpolicy($localisedpolicyid);
}

$version = $localisedpolicy->get_policyversion();
$primarypolicy = localisedpolicy::from_version($version, ['isprimary' => localisedpolicy::STATUS_PRIMARY]);

[$currentdata, $params] = tool_sitepolicy\form\translationform::prepare_current_data($primarypolicy, $localisedpolicy);

$form = new \tool_sitepolicy\form\translationform($currentdata, $params);

$languagestr = $localisedpolicy->get_language(true);
$translationlisturl = \tool_sitepolicy\url_helper::localisedpolicy_list($version->get_id());

if ($form->is_cancelled()) {

    redirect($translationlisturl);

} else if ($formdata = $form->get_data()) {

    if ($formdata->submitbutton) {
        $time = time();

        if ($version->get_timepublished()) {
            throw new coding_exception('Cannot edit translation of published version.');
        }

        $localisedpolicy->set_authorid($USER->id);
        $localisedpolicy->set_title($formdata->title);
        $localisedpolicy->set_policytext($formdata->policytext, $formdata->policytextformat);

        if (!empty($formdata->whatsnew)) {
            $localisedpolicy->set_whatsnew($formdata->whatsnew, $formdata->whatsnewformat);
        }

        $localisedpolicy->set_statements($formdata->statements);
        $localisedpolicy->save();

        $successmsg = get_string('translationsaved', 'tool_sitepolicy', ['title' => $primarypolicy->get_title(true), 'language' => $languagestr]);
        redirect($translationlisturl, $successmsg, null, \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($formdata->previewbutton) {
        // currentdata contains the 'defaults' for the form which at this point should be the persisted values although the user
        // may have changed it already
        $currentdata['preview'] = '1';
        $currentdata['policytextformat'] = $formdata->policytextformat;
        $currentdata['whatsnewformat'] = $formdata->whatsnewformat ?? FORMAT_HTML;
        $params['previewnotification'] = $OUTPUT->notification(get_string('policyispreview', 'tool_sitepolicy'), \core\output\notification::NOTIFY_INFO);
        $form = new \tool_sitepolicy\form\translationform($currentdata, $params);
    }
}

$PAGE->set_title(get_string('translationheader', 'tool_sitepolicy'));
$PAGE->set_heading($PAGE->title);
$PAGE->navbar->add($localisedpolicy->get_primary_title(true), \tool_sitepolicy\url_helper::version_list($version->get_sitepolicy()->get_id()));
$PAGE->navbar->add(get_string('translationheader', 'tool_sitepolicy'), $translationlisturl);
$PAGE->navbar->add($languagestr);

$PAGE->requires->js_call_amd('tool_sitepolicy/translationform', 'init');

/** @var \tool_sitepolicy\output\page_renderer $renderer */
$renderer = $PAGE->get_renderer('tool_sitepolicy', 'page');
echo $renderer->localisedversion_translation_edit($localisedpolicy, $form);
