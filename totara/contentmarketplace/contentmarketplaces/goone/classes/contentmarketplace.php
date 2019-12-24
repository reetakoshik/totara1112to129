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

namespace contentmarketplace_goone;

defined('MOODLE_INTERNAL') || die();

final class contentmarketplace extends \totara_contentmarketplace\local\contentmarketplace\contentmarketplace {

    public $name = 'goone';

    /**
     * Returns the URL for the plugin.
     *
     * @return string
     */
    public function url() {
        return 'https://www.go1.com';
    }

    /**
     * Returns the path to a page used to create the course(es), relative to the site root.
     *
     * @return string
     */
     public function course_create_page() {
         return "/totara/contentmarketplace/contentmarketplaces/goone/coursecreate.php";
     }

    /**
     * Returns a HTML snippet responsible for setting up the GO1 content marketplace data.
     * All related JavaScript has to be there as well.
     *
     * @param string $label
     * @return string Resulting HTML.
     */
    public function get_setup_html($label) {
        global $OUTPUT;
        $data = new \stdClass();
        $data->oauth_authorize_url = oauth::get_authorize_url(self::oauth_redirect_uri(), self::oauth_user_state())->out(false);
        $data->label = $label;
        return $OUTPUT->render_from_template("contentmarketplace_goone/setup", $data);
    }

    /**
     * Saves the portal data to LMS.
     *
     * @return void
     */
    public static function update_data() {
        $api = new api();
        self::update_portal_data($api);
        self::update_portal_configuration_data($api);
    }

    /**
     * @param \contentmarketplace_goone\api $api
     */
    private static function update_portal_data($api) {
        $account = self::load_account_data($api);
        if (!empty($account)) {
            set_config('account_portal_url', $account->url, 'contentmarketplace_goone');
            if (is_object($account->plan)) {
                set_config('account_plan_name', $account->plan->type, 'contentmarketplace_goone');
                set_config('account_plan_users_licensed', $account->plan->licensed_user_count, 'contentmarketplace_goone');
                set_config('account_plan_users_active', $account->plan->active_user_count, 'contentmarketplace_goone');
                set_config('account_plan_region', $account->plan->region, 'contentmarketplace_goone');
                set_config('account_plan_renewal_date', $account->plan->renewal_date, 'contentmarketplace_goone');
                set_config('account_plan_price', $account->plan->pricing->price, 'contentmarketplace_goone');
                if (!empty($account->plan->pricing->currency)) {
                    set_config('account_plan_currency', $account->plan->pricing->currency, 'contentmarketplace_goone');
                }
            }
        }
        $api->purge_all_caches();
    }

    /**
     * @param \contentmarketplace_goone\api $api
     */
    private static function update_portal_configuration_data($api) {
        $configuration = $api->get_configuration();
        if (!empty($configuration) && is_object($configuration) && isset($configuration->pay_per_seat)) {
            set_config('pay_per_seat', (bool) $configuration->pay_per_seat, 'contentmarketplace_goone');
        }
        $api->purge_all_caches();
    }

    /**
     * @param \stdClass $data
     */
    public static function save_content_settings_data(\stdClass $data) {
        set_config('content_settings_creators', $data->creators, 'contentmarketplace_goone');
        set_config('pay_per_seat', $data->pay_per_seat, 'contentmarketplace_goone');

        $apidata = ['pay_per_seat' => (bool) $data->pay_per_seat];
        $api = new api();
        $api->save_configuration($apidata);
    }

    /**
     * @return \moodle_url
     */
    public static function oauth_redirect_uri(): \moodle_url {
        return new \moodle_url("/totara/contentmarketplace/contentmarketplaces/goone/signin.php");
    }

    /**
     * @return array
     */
    private static function oauth_user_state() {
        global $USER, $CFG;

        require_once $CFG->dirroot . '/admin/registerlib.php';
        $regdata = get_registration_data();

        $state = [
            'full_name' => fullname($USER),
            'email' => $USER->email,
            'company' => $regdata['orgname'],
            'phone_number' => $USER->phone1,
            'country' => $USER->country,
            'customer_partner' => 'Totara Learn',
            'users_total' => $regdata['activeusercount'],
        ];

        return $state;
    }

    /**
     * @param \contentmarketplace_goone\api $api
     * @return mixed The account object.
     */
    public static function load_account_data($api) {
        $account = $api->get_account();
        return $account;
    }

    /**
     * Return listing of content availability options for the current user in the given context.
     *
     * @param \context $context
     * @return string[] Listing of availability options
     */
    public static function content_availability_options(\context $context) {
        if (has_capability('totara/contentmarketplace:config', $context)) {
            return ['all', 'subscribed', 'collection'];
        } elseif (has_capability('totara/contentmarketplace:add', $context)) {
            $content_settings = get_config('contentmarketplace_goone', 'content_settings_creators');
            switch ($content_settings) {
                case "all":
                    return ['all', 'subscribed', 'collection'];
                case "subscribed":
                    return ['subscribed', 'collection'];
            }
        }
        return [];
    }

}
