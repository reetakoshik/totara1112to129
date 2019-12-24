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

global $OUTPUT;

$data = new stdClass();
$data->plan_name = (string) get_config('contentmarketplace_goone', 'account_plan_name');
$data->plan_users_licensed = (string) get_config('contentmarketplace_goone', 'account_plan_users_licensed');
$data->plan_users_active = (string) get_config('contentmarketplace_goone', 'account_plan_users_active');
$data->plan_region = (string) get_config('contentmarketplace_goone', 'account_plan_region');
$data->plan_renewal_date = (string) get_config('contentmarketplace_goone', 'account_plan_renewal_date');
$data->plan_price = (string) get_config('contentmarketplace_goone', 'account_plan_price');
$data->plan_currency = (string) get_config('contentmarketplace_goone', 'account_plan_currency');
$data->portal_url = clean_param(get_config('contentmarketplace_goone', 'account_portal_url'), PARAM_HOST);

if (!empty($data->plan_renewal_date)) {
    $data->plan_renewal_date = userdate(strtotime($data->plan_renewal_date), get_string('strftimedate', 'core_langconfig'));
}
if (!empty($data->plan_price) && !empty($data->plan_currency)) {
    $data->plan_price = \totara_contentmarketplace\local::format_money($data->plan_price, $data->plan_currency);
}

$stringmanager = new \contentmarketplace_goone\string_manager();
$data->plan_region = $stringmanager->get_region($data->plan_region);

if (!empty($data->plan_name)) {
    $data->plan_name = get_string('go1planname', 'contentmarketplace_goone', \core_text::strtotitle($data->plan_name));
}

$courses = get_config('contentmarketplace_goone', 'learning_objects_subscribed');
if (!empty($courses) && !empty($data->plan_name)) {
    $data->plan_name .= ' '.get_string(
        'courses_amount_label',
        'contentmarketplace_goone',
        number_format($courses, 0, '.', ',')
    );
}

$data->enabled_by = get_config('contentmarketplace_goone', 'enabled_by');
$data->enabled_on = get_config('contentmarketplace_goone', 'enabled_on');

if (!empty($data->enabled_by)) {
    if ($data->enabled_by == -1) {
        // It was enabled by the system.
        $data->enabled_by = get_string('enabledbyunknown', 'contentmarketplace_goone');
    } else if (is_int($data->enabled_by)) {
        $enabledby = $DB->get_record('user', ['id' => $data->enabled_by], 'id, ' . get_all_user_name_fields());
        if (empty($enabledby)) {
            $data->enabled_by = get_string('enabledbyunknown', 'contentmarketplace_goone');
        } else {
            $data->enabled_by = fullname($enabledby);
        }
    }
}

if (!empty($data->enabled_on)) {
    $data->enabled_on = userdate($data->enabled_on, get_string('strftimedate', 'core_langconfig'));
}

echo $OUTPUT->render_from_template("contentmarketplace_goone/account", $data);
