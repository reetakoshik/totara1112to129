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

use totara_catalog\form\element\matrix;
use totara_form\form\element\static_html;

defined('MOODLE_INTERNAL') || die();

/**
 * Totara form class for catalog configuration: Tab "Filters".
 *
 * @package totara_catalog
 */
class config_filters extends base_config_form {

    public static function get_form_controller() {
        return new config_filters_controller();
    }

    protected function definition() {
        $params = $this->get_parameters();

        $this->model->add(
            new static_html(
                'filters_subheading',
                '',
                get_string('filters_subheading', 'totara_catalog')
            )
        );

        /** @var matrix $matrix */
        $matrix = $this->model->add(new matrix('filters', 'label_is_not_displayed'));
        $matrix->set_attribute('filters', $params['panel_filters']);
        $matrix->set_attribute('selected', $params['selected_panel_filters']);
        $matrix->set_optgroups($params['panel_filter_optgroups']);

        $this->add_action_buttons();
    }
}
