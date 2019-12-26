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

namespace totara_catalog\form\element;

use totara_form\form\element\checkbox;

defined('MOODLE_INTERNAL') || die();

/**
 * Extension of totara form checkbox for styling adjustments.
 *
 * Class titled_checkbox
 * @package totara_catalog
 */
class titled_checkbox extends checkbox {

    /** @var string */
    private $template_name;

    /** @var string */
    private $checkbox_title;

    /**
     * @param string $name
     * @param string $label
     * @param string $checkbox_title
     * @param string $template_name
     */
    public function __construct($name, $label, $checkbox_title = 'enable_checkbox', $template_name = 'element_titled_checkbox') {
        parent::__construct($name, $label);
        $this->template_name = $template_name;
        $this->checkbox_title = $checkbox_title;
    }

    public function export_for_template(\renderer_base $output) {
        $result = parent::export_for_template($output);
        $result['form_item_template'] = 'totara_catalog/' . $this->template_name;
        $result['checkboxtitle'] = get_string($this->checkbox_title, 'totara_catalog');
        return $result;
    }
}
