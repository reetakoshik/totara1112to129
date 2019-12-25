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

$newpolicy = (bool)optional_param('newpolicy', false, PARAM_BOOL);
$localisedpolicyid = required_param('localisedpolicy', PARAM_INT);
$sitepolicyid = optional_param('sitepolicyid', 0, PARAM_INT);
$returnpage = optional_param('ret', 'policies', PARAM_ALPHANUMEXT);

$url = url_helper::version_edit($localisedpolicyid);
admin_externalpage_setup('tool_sitepolicy-managerpolicies', '', null, $url);

$primarypolicy = new localisedpolicy($localisedpolicyid);

if (!$primarypolicy->is_primary()) {
    throw new coding_exception('Cannot edit non primary version as primary');
}

$version = $primarypolicy->get_policyversion();

$sitepolicyurl = url_helper::version_list($version->get_sitepolicy()->get_id());
$translationurl = url_helper::localisedpolicy_list($version->get_id());

switch ($returnpage) {
    case 'versions':
        $redirect = $sitepolicyurl;
        break;

    case 'translations':
        $redirect = $translationurl;
        break;

    default:
        $redirect = url_helper::sitepolicy_list();
        break;
}

// Prepare current data
$languages = get_string_manager()->get_list_of_translations();
if (!array_key_exists($primarypolicy->get_language(false), $languages)) {
    $primarypolicy->set_language('en');
}

[$currentdata, $params] = \tool_sitepolicy\form\versionform::prepare_current_data($primarypolicy, $newpolicy, $returnpage);
$form = new \tool_sitepolicy\form\versionform($currentdata, $params);

if ($form->is_cancelled()) {

    if ($newpolicy && confirm_sesskey()) {
        // It's important to note that sesskey is actually checked by the cancelled form.
        // However to be extra safe confirm that the user is intentionally doing this.
        // Only draft versions can be deleted like this.
        $version->delete();
    }
    redirect($redirect);

} elseif ($formdata = $form->get_data()) {

    if ($formdata->submitbutton) {
        if (!empty($version->get_timepublished())) {
            throw new coding_exception('Cannot edit published version.');
        }

        $primarypolicy->set_language($formdata->language);
        $primarypolicy->set_authorid($USER->id);
        $primarypolicy->set_title($formdata->title);
        $primarypolicy->set_policytext($formdata->policytext, $formdata->policytextformat);
        if (isset($formdata->whatsnew)) {
            $primarypolicy->set_whatsnew($formdata->whatsnew, $formdata->whatsnewformat);
        }
        $primarypolicy->set_statements($formdata->statements);
        $primarypolicy->save();

        $returnpage = !empty($returnpage) ? $returnpage : $formdata->ret;
        switch ($returnpage) {
            case 'versions':
            case 'translations':
                $successmsg = get_string('versionupdated', 'tool_sitepolicy', $version->get_versionnumber());
                break;

            default:
                $successmsg = get_string('policyupdated', 'tool_sitepolicy', $formdata->title);
                break;
        }

        redirect($redirect, $successmsg, null, \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($formdata->previewbutton) {
        // currentdata contains the 'defaults' for the form which at this point should be the persisted values although the user
        // have changed it already
        $currentdata['preview'] = '1';
        $currentdata['policytextformat'] = $formdata->policytextformat;
        $currentdata['whatsnewformat'] = $formdata->whatsnewformat ?? FORMAT_HTML;
        $params['previewnotification'] = $OUTPUT->notification(get_string('policyispreview', 'tool_sitepolicy'), \core\output\notification::NOTIFY_INFO);
        $form = new \tool_sitepolicy\form\versionform($currentdata, $params);
    }
}

$PAGE->set_title($primarypolicy->get_title(false));
$PAGE->navbar->add($primarypolicy->get_title(true), $sitepolicyurl);
if ($returnpage === 'translations') {
    $PAGE->navbar->add(get_string('translations', 'tool_sitepolicy'), $translationurl);
    $PAGE->navbar->add($primarypolicy->get_language(true));
}
$PAGE->navbar->add(get_string('versionedit', 'tool_sitepolicy'));
$PAGE->requires->js_call_amd('tool_sitepolicy/versionform', 'init');

/** @var \tool_sitepolicy\output\page_renderer $renderer */
$renderer = $PAGE->get_renderer('tool_sitepolicy', 'page');
echo $renderer->localisedversion_edit($version, $form, (bool)$newpolicy);
