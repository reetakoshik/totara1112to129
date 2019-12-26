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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_catalog
 */

namespace totara_catalog\form;

use totara_form\form\element\checkboxes;
use totara_form\form\group\section;

defined('MOODLE_INTERNAL') || die();

/**
 * Totara form class for catalog configuration: Tab "Contents".
 *
 * @package totara_catalog
 */
class config_contents extends base_config_form {

    public static function get_form_controller() {
        return new config_contents_controller();
    }

    protected function definition() {
        $params = $this->get_parameters();

        /** @var section $section */
        $section = $this->model->add(new section('contents', get_string('contents', 'totara_catalog')));
        $section->set_collapsible(false);

        // Include in catalogue
        $section->add(
            new checkboxes(
                'learning_types_in_catalog',
                get_string('include_in_catalog', 'totara_catalog'),
                $params['all_provider_names']
            )
        );

        $this->add_action_buttons();
    }
}
