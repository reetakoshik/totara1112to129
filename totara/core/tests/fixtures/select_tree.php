<?php
/*
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
 * @package totara_core
 */

require(__DIR__ . '/../../../../config.php');

$displaydebugging = false;
if (!defined('BEHAT_SITE_RUNNING') || !BEHAT_SITE_RUNNING) {
    if (debugging()) {
        $displaydebugging = true;
    } else {
        throw new coding_exception('Invalid access detected.');
    }
}
$title = '\totara_core\output\select_tree testing page';

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);
$PAGE->set_context($context);
$PAGE->set_url('/totara/core/tests/fixtures/select_tree.php');
$PAGE->set_pagelayout('noblocks');
$PAGE->set_title($title);

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

if ($displaydebugging) {
    // This is intentionally hard coded - this page is not in the navigation and should only ever be used by developers.
    $msg = 'This page only exists to facilitate acceptance testing, if you are here for any other reason please file an improvement request.';
    echo $OUTPUT->notification($msg, 'notifysuccess');
    // We display a developer debug message as well to ensure that this doesn't not get shown during behat testing.
    debugging('This is a developer resource, please contact your system admin if you have arrived here by mistake.', DEBUG_DEVELOPER);
}

$options1 = [
    (object)[
        'name' => 'All',
        'key' => 'all',
        'default' => 'true',
    ],
    (object)[
        'name' => 'Flooding',
        'key' => 'flooding',
        'children' => [
            (object)[
                'name' => 'Level 2',
                'key' => 'level2',
                'children' => [
                    (object)[
                        'name' => 'Level 3',
                        'key' => 'level3',
                        'children' => [
                            (object)[
                                'name' => 'Level 4',
                                'key' => 'level4',
                            ],
                            (object)[
                                'name' => 'Level 4b',
                                'key' => 'level4b',
                            ],
                        ],
                    ],
                ],
            ],
            (object)[
                'name' => 'Level 2b',
                'key' => 'level2b',
            ],
        ],
    ],
    (object)[
        'name' => 'Earthquake',
        'key' => 'earthquake'
    ],
    (object)[
        'name' => 'Self Combustion',
        'key' => 'selfcombustion'
    ],
    (object)[
        'name' => 'Plague',
        'key' => 'plague'
    ],
    (object)[
        'name' => 'Heatwave',
        'key' => 'heatwave'
    ],
    (object)[
        'name' => 'Fires',
        'key' => 'fires',
    ],
    (object)[
        'name' => 'Volcano',
        'key' => 'volcano'
    ],
];
$treelist1 = \totara_core\output\select_tree::create(
    'testtreelist1',
    'Test tree list title 1',
    false,
    $options1,
    'level4'
);

$options2 = [
    (object)[
        'name' => 'Earthquake',
        'key' => 'earthquake'
    ],
    (object)[
        'name' => 'Self Combustion',
        'key' => 'selfcombustion',
        'default' => 'true'
    ],
    (object)[
        'name' => 'Plague',
        'key' => 'plague'
    ],
];
$treelist2 = \totara_core\output\select_tree::create(
    'testtreelist2',
    'Test tree list title 2 single level',
    true,
    $options2,
    'selfcombustion',
    true
);

$options3 = [
    (object)[
        'name' => 'Heatwave',
        'key' => 'heatwave'
    ],
    (object)[
        'name' => 'Fires',
        'key' => 'fires',
    ],
    (object)[
        'name' => 'Volcano',
        'key' => 'volcano',
        'default' => 'true'
    ],
];
$treelist3 = \totara_core\output\select_tree::create(
    'testtreelist3',
    'Test tree list title 3 no selection',
    false,
    $options3
);

$options4 = [
    (object)[
        'name' => 'Red',
        'key' => 'red'
    ],
    (object)[
        'name' => 'Blue',
        'key' => 'blue',
    ],
    (object)[
        'name' => 'Rainbow',
        'key' => 'rainbow',
        'children' => [
            (object)[
                'name' => 'Green',
                'key' => 'green',
            ],
            (object)[
                'name' => 'Yellow',
                'key' => 'yellow',
            ],
        ],
    ],
];
$treelist4 = \totara_core\output\select_tree::create(
    'testtreelist4',
    'Which colour',
    false,
    $options4,
    '',
    false,
    false,
    'Please select colour'
);

echo $OUTPUT->render($treelist1);
echo $OUTPUT->render($treelist2);
echo $OUTPUT->render($treelist3);
echo $OUTPUT->render($treelist4);
echo $OUTPUT->footer();
