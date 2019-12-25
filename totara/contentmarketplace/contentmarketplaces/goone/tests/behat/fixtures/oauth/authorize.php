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
 * @author Michael Dunstan <michael.dunstan@androgogic.com>
 * @package contentmarketplace_goone
 */

require_once('../../../../../../../../config.php');

if (!defined('BEHAT_SITE_RUNNING') || !BEHAT_SITE_RUNNING) {
    throw new coding_exception('Invalid access.');
}

$authorize = optional_param('authorize', 0, PARAM_BOOL);
if ($authorize) {
    $redirect_uri = required_param('redirect_uri', PARAM_URL);
    $url = new moodle_url($redirect_uri, array(
        'code' => '--CODE--',
        'client_id' => '--CLIENT-ID--',
        'client_secret' => '--CLIENT-SECRET--',
    ));
    redirect($url);

} else {
    $state = required_param('state', PARAM_RAW_TRIMMED);
    $redirect_uri = required_param('redirect_uri', PARAM_URL);

    $context = context_system::instance();

    $PAGE->set_url(new moodle_url('/totara/contentmarketplace/contentmarketplaces/goone/tests/behat/fixtures/oauth/authorize.php'));
    $PAGE->set_context($context);
    echo $OUTPUT->heading('Allow Totara to access GO1');

    $state = json_decode(base64_decode($state));
    $table = new html_table();
    $table->id = 'state';
    $table->data = array();
    foreach ($state as $key => $value) {
        $table->data[] = array($key, $value);
    }
    echo $OUTPUT->render($table);

    $params = array(
        "authorize" => "yes",
        "redirect_uri" => $redirect_uri,
    );
    echo $OUTPUT->single_button(new moodle_url($PAGE->url, $params), 'Authorize Totara');
}
