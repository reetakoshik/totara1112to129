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

global $CFG, $OUTPUT;

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('userdatapurges', '', null, '', array('pagelayout'=>'noblocks'));

$tiles1 = [];
for ($i = 1; $i <= 7; $i++) {
    $tile = \totara_core\output\select_search_text::create('grid1tile' . $i, 'Grid 1 Tile ' . $i, true);
    $tiles1[] = $tile;
}
$grid1 = \totara_core\output\grid::create($tiles1);

$tiles2 = [];
for ($i = 1; $i <= 3; $i++) {
    $tile = \totara_core\output\select_search_text::create('grid2tile' . $i, 'Grid 2 Tile ' . $i, true);
    $tiles2[] = $tile;
}
$grid2 = \totara_core\output\grid::create($tiles2, true);

echo $OUTPUT->header();
echo $OUTPUT->render($grid1);
echo $OUTPUT->render($grid2);
echo $OUTPUT->footer();
