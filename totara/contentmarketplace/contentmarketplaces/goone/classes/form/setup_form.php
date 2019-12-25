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

namespace contentmarketplace_goone\form;

use contentmarketplace_goone\contentmarketplace;
use contentmarketplace_goone\api;
use contentmarketplace_goone\config_session_storage;
use contentmarketplace_goone\string_manager;
use totara_form\form\element\radios;
use totara_form\form\element\hidden;
use totara_form\form\element\static_html;

defined('MOODLE_INTERNAL') || die();

final class setup_form extends \totara_form\form {

    public static function get_form_controller() {
        return new setup_wizard_default_controller();
    }

    protected function definition() {
        global $OUTPUT;

        $wizard = $this->model->add(new setup_wizard('setup'));

        $s1 = $wizard->add_stage(new setup_wizard_stage('stage_one', get_string('account', 'contentmarketplace_goone')));
        $api = new api(new config_session_storage());
        $data = contentmarketplace::load_account_data($api);
        self::format_account_data($data);
        $data = self::convert_account_data_for_template($data);
        $s1->add(new static_html(
            'account',
            null,
            $OUTPUT->render_from_template('contentmarketplace_goone/account', $data)
        ));

        $s2 = $wizard->add_stage(new setup_wizard_stage('stage_two', get_string('content_settings', 'contentmarketplace_goone')));
        $s2->add(new static_html(
            'creatorsdesc',
            '',
            get_string('content_settings_description', 'contentmarketplace_goone')
        ));
        $s2->add(new radios(
            'creators',
            get_string('content_creators', 'contentmarketplace_goone'),
            array(
                'all' => get_string('all_content', 'contentmarketplace_goone', $this->parameters['courses_all'] ?? null),
                'subscribed' => get_string('subscribed_content', 'contentmarketplace_goone', $this->parameters['courses_subscribed'] ?? null),
                'collection' => get_string('collection_content', 'contentmarketplace_goone', $this->parameters['courses_collection'] ?? null),
            )
        ))->add_help_button('content_creators', 'contentmarketplace_goone');

        $payperseat = new hidden('pay_per_seat', PARAM_INT);
        $s2->add($payperseat);

        $wizard->set_submit_label(get_string('saveandexplorego1', 'contentmarketplace_goone'));
        $wizard->finalise();
    }

    private static function format_account_data(&$data) {
        if (empty($data) || empty($data->plan)) {
            return;
        }

        if (!empty($data->plan->renewal_date)) {
            $data->plan->renewal_date = userdate(strtotime($data->plan->renewal_date), get_string('strftimedate', 'core_langconfig'));
        }
        if (!empty($data->plan->pricing->price) && !empty($data->plan->pricing->currency)) {
            $data->plan->pricing->price = \totara_contentmarketplace\local::format_money($data->plan->pricing->price, $data->plan->pricing->currency);
        }

        $stringmanager = new string_manager();
        $data->plan->region = $stringmanager->get_region($data->plan->region);

        if (!empty($data->plan->type)) {
            $data->plan->type = get_string('go1plantype', 'contentmarketplace_goone', \core_text::strtotitle($data->plan->type));
        }

        $courses = get_config('contentmarketplace_goone', 'learning_objects_subscribed');
        if (!empty($courses)) {
            $data->plan->type .= ' '.get_string(
                'courses_amount_label',
                'contentmarketplace_goone',
                number_format($courses, 0, '.', ',')
            );
        }
    }

    private static function convert_account_data_for_template($data) {
        global $USER;
        return (object) [
            'plan_name' => $data->plan->type ?? '',
            'plan_users_licensed' => $data->plan->licensed_user_count ?? '',
            'plan_users_active' => $data->plan->active_user_count ?? '',
            'plan_region' => $data->plan->region ?? '',
            'plan_renewal_date' => $data->plan->renewal_date ?? '',
            'plan_price' => $data->plan->pricing->price ?? '',
            'portal_url' => clean_param($data->url, PARAM_HOST),
            'enabled_on' => userdate(time(), get_string('strftimedate', 'core_langconfig')),
            'enabled_by' => fullname($USER),
        ];
    }
}
