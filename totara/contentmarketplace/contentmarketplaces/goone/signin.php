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

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php');

use contentmarketplace_goone\contentmarketplace;
use contentmarketplace_goone\oauth;
use contentmarketplace_goone\config_session_storage;

$code = required_param('code', PARAM_RAW);
$client_id = required_param('client_id', PARAM_RAW);
$client_secret = required_param('client_secret', PARAM_RAW);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/totara/contentmarketplace/contentmarketplaces/goone/signin.php');

require_login();
require_capability('totara/contentmarketplace:config', $context);
\totara_contentmarketplace\local::require_contentmarketplace();

$PAGE->set_pagelayout('popup');

$oauth_client = new oauth(new config_session_storage());
$oauth_client->token_setup($client_id, $client_secret, $code, contentmarketplace::oauth_redirect_uri());

$redirect = new moodle_url('/totara/contentmarketplace/contentmarketplaces/goone/setup.php');
$PAGE->requires->js_init_code("window.opener.location.href = '$redirect'; window.close();");

echo $OUTPUT->header();
echo $OUTPUT->footer();
