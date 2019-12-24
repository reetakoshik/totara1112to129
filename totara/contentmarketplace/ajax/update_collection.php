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
 * @package totara_contentmarketplace
 */

use totara_contentmarketplace\plugininfo\contentmarketplace;

define('AJAX_SCRIPT', true);
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

$marketplace = required_param('marketplace', PARAM_ALPHA);
$action = required_param('action', PARAM_ALPHA);
$selection = optional_param_array('selection', [], PARAM_ALPHANUMEXT);

$context = context_system::instance();
$PAGE->set_context($context);

require_login();
require_capability('totara/contentmarketplace:add', $context);
require_sesskey();
\totara_contentmarketplace\local::require_contentmarketplace();

$mp = contentmarketplace::plugin($marketplace, false);
if ($mp === null || !$mp->is_enabled()) {
    echo $OUTPUT->header();
    echo json_encode(false);
    exit;
}

$collection = $mp->collection();
if ($action === 'add') {
    $collection->add($selection);
} else if ($action === 'remove') {
    $collection->remove($selection);
} else {
    throw new coding_exception('Invalid action provided', $action);
}

echo $OUTPUT->header();
echo json_encode(true);
