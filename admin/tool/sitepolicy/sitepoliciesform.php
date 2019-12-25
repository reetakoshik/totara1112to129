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

/**
 * Page to facilitate the creation of a new site policy.
 *
 * This is a management page.
 */

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use \tool_sitepolicy\sitepolicy;
use \tool_sitepolicy\policyversion;
use \tool_sitepolicy\localisedpolicy;
use \tool_sitepolicy\url_helper;

admin_externalpage_setup('tool_sitepolicy-managerpolicies', '', null, url_helper::sitepolicy_create());

/** @var \tool_sitepolicy\output\page_renderer $renderer */
$renderer = $PAGE->get_renderer('tool_sitepolicy', 'page');

$params = [
    'previewnotification' => $OUTPUT->notification(get_string('policyispreview', 'tool_sitepolicy'), \core\output\notification::NOTIFY_INFO),
];
$format = editors_get_preferred_format();
$form = new tool_sitepolicy\form\versionform(['versionnumber' => 1, 'policytextformat' => $format, 'preview' => ''], $params);

// We need to submit on preview button to ensure that the preview section contains the correct data
// No need to submit on 'continue editing'
if ($form->is_cancelled()) {
    redirect(url_helper::sitepolicy_list());
}
if ($formdata = $form->get_data()) {
    if ($formdata->submitbutton) {
        \tool_sitepolicy\sitepolicy::create_new_policy($formdata->title, $formdata->policytext, $formdata->statements, $formdata->language, null, (int)($formdata->policytextformat));
        $message = get_string('policynewsaved', 'tool_sitepolicy', $formdata->title);
        redirect(url_helper::sitepolicy_list(), $message, null, \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($formdata->previewbutton) {
        // Data have not been saved yet - thus need to stick to the original format
        $form = new tool_sitepolicy\form\versionform(['versionnumber' => 1, 'policytextformat' => $formdata->policytextformat, 'preview' => '1'], $params);
    }
}

$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('policyformheader', 'tool_sitepolicy'));
$PAGE->navbar->add($PAGE->title);

$PAGE->requires->js_call_amd('tool_sitepolicy/versionform', 'init');

echo $renderer->sitepolicy_create_new_policy($form);
