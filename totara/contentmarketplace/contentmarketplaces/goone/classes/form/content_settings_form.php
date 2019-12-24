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

use totara_form\form\element\hidden;
use totara_form\form\element\radios;

defined('MOODLE_INTERNAL') || die();

final class content_settings_form extends \totara_form\form {

    public function get_action_url() {
        return new \moodle_url('/totara/contentmarketplace/marketplaces.php', array(
            'id' => 'goone',
            'tab' => 'content_settings',
        ));
    }

    protected function definition() {

        $explorecollectionurl = new \moodle_url('/totara/contentmarketplace/explorer.php', [
            'marketplace' => 'goone',
            'mode' => \totara_contentmarketplace\explorer::MODE_EXPLORE_COLLECTION,
        ]);
        $this->model->add(new radios(
            'creators',
            get_string('content_creators', 'contentmarketplace_goone'),
            array(
                'all' => s(get_string(
                    'all_content',
                    'contentmarketplace_goone',
                    $this->parameters['courses_all']
                )),
                'subscribed' => s(get_string(
                    'subscribed_content',
                    'contentmarketplace_goone',
                    $this->parameters['courses_subscribed']
                )),
                'collection' => s(get_string(
                    'collection_content',
                    'contentmarketplace_goone',
                    $this->parameters['courses_collection']
                )) . ' ' . \html_writer::link($explorecollectionurl, get_string('explore', 'totara_contentmarketplace')),
            )
        ))->add_help_button('content_creators', 'contentmarketplace_goone');

        $payperseat = new hidden('pay_per_seat', PARAM_INT);
        $this->model->add($payperseat);

        $this->model->add_action_buttons(false);
    }

}
