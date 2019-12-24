<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_form
 */

namespace totara_form;

/**
 * Totara form item validator.
 *
 * This interface is used to construct and maintain the tree of things in form model.
 *
 * NOTE: Validators are usually not used on frozen elements.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
interface validator {
    /**
     * Inform validator that it was added to an item.
     *
     * This is expected to be used for sanity checks.
     *
     * @param item $item
     */
    public function added_to_item(item $item);

    /**
     * Validate previously added item.
     *
     * @return void
     */
    public function validate();

    /**
     * Add validator specific data to template data.
     *
     * @param array &$data item template data
     * @param \renderer_base $output
     * @return void $data argument is modified
     */
    public function set_validator_template_data(&$data, \renderer_base $output);
}
