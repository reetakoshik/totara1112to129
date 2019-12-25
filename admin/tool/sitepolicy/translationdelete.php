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

$localisedpolicyid = required_param('localisedpolicy', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

$url = \tool_sitepolicy\url_helper::localisedpolicy_delete($localisedpolicyid);
admin_externalpage_setup('tool_sitepolicy-managerpolicies', '', null, $url);

$localisedpolicy = new \tool_sitepolicy\localisedpolicy($localisedpolicyid);
if ($localisedpolicy->is_primary()) {
    throw new coding_exception('Cannot delete primary policy version translation.');
}

$version = $localisedpolicy->get_policyversion();
if ($version->get_timepublished()) {
    throw new coding_exception('Cannot delete translation of published version.');
}

$sitepolicy = $version->get_sitepolicy();

// Perform action.
if ($confirm) {
    // Must have a valid sesskey!
    require_sesskey();

    $localisedpolicy->delete();

    redirect(
        \tool_sitepolicy\url_helper::localisedpolicy_list($version->get_id()),
        get_string('translationdeleted', 'tool_sitepolicy', $localisedpolicy->get_language(true)),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Output
$PAGE->set_title(get_string('deletetranslationheading', 'tool_sitepolicy'));
$PAGE->set_heading($PAGE->title);
$PAGE->navbar->add($localisedpolicy->get_primary_title(true), \tool_sitepolicy\url_helper::version_list($sitepolicy->get_id()));
$PAGE->navbar->add(get_string('translationheader', 'tool_sitepolicy'), \tool_sitepolicy\url_helper::localisedpolicy_list($version->get_id()));
$PAGE->navbar->add($localisedpolicy->get_language(true));
$PAGE->navbar->add($PAGE->title);

/** @var tool_sitepolicy_renderer $renderer */
$renderer = $PAGE->get_renderer('tool_sitepolicy');
echo $renderer->header();
echo $renderer->delete_translation_confirmation($localisedpolicy);
echo $renderer->footer();
