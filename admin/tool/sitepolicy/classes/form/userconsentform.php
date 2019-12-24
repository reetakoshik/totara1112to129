<?php
/**
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Courteney Brownie <courteney.brownie@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package tool_sitepolicy
 */

namespace tool_sitepolicy\form;

defined('MOODLE_INTERNAL') || die();

use totara_form\form;
use totara_form\form\element\hidden;
use tool_sitepolicy\element\sitepolicy;

class userconsentform extends form {
    protected function definition() {
        $model = $this->model;

        if (!empty($this->parameters['hidden'])) {
            foreach ($this->parameters['hidden'] as $name => $type) {
                $model->add(new hidden($name, $type));
            }
        }

        $data = $this->model->get_current_data(null);
        $model->add(new element\sitepolicy('userconsent', $data));

        $model->add_action_buttons(false, get_string('userconsentsubmit', 'tool_sitepolicy'));
    }
}

