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
 * @author Sergey Vidusov <sergey.vidusov@androgogic.com>
 * @package contentmarketplace_goone
 */

defined('MOODLE_INTERNAL') || die();

$tab = optional_param('tab', 'account', PARAM_ALPHAEXT);

$tabs = array();
$basepage = '/totara/contentmarketplace/marketplaces.php';
$pages = array(
    'account',
    'content_settings',
);

foreach ($pages as $page) {
    $tabs[] = new tabobject(
        $page,
        new moodle_url($basepage, array(
            'id' => 'goone',
            'tab' => $page,
        )),
        get_string($page, 'contentmarketplace_goone')
    );
}

echo $OUTPUT->heading('GO1 settings');
echo $OUTPUT->tabtree($tabs, $tab);

if (in_array($tab, $pages)) {
    \contentmarketplace_goone\contentmarketplace::update_data();
    include($CFG->dirroot . '/totara/contentmarketplace/contentmarketplaces/goone/'. $tab . '.php');
}
