<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package block_totara_quicklinks
 */

namespace block_totara_quicklinks\form;

defined('MOODLE_INTERNAL') || die();

/**
 * Add quick link.
 */
final class add extends \totara_form\form {
    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {
        $this->model->add(new \totara_form\form\element\hidden('blockinstanceid', PARAM_INT));
        $linktitle = new \totara_form\form\element\text('linktitle', get_string('linktitle', 'block_totara_quicklinks'), PARAM_TEXT);
        $linktitle->set_attributes(array('required'=> 1));
        $this->model->add($linktitle);
        $linkurl = new \totara_form\form\element\url('linkurl', get_string('url', 'block_totara_quicklinks'));
        $linkurl->set_attributes(['required' => true, 'size' => 60]);
        $this->model->add($linkurl);

        $this->model->add_action_buttons(false, get_string('addlink', 'block_totara_quicklinks'));
    }
}
