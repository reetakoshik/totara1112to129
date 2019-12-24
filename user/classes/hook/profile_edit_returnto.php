<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @package core_user
 */

namespace core_user\hook;

/**
 * This hook allows plugin to override the return url
 * of the user/edit.php and user/editadvanced.php scripts.
 */
class profile_edit_returnto extends \totara_core\hook\base {
    /** @var \stdClass $user user record before the update */
    public $user;

    /** @var string PARAM_ALPHANUMEXT parameter */
    public $returnto;

    /** @var \moodle_url the return url */
    public $returnurl;

    /**
     * The edit_form_definition_complete constructor.
     *
     * @param \stdClass $user
     * @param string $returnto
     * @param \moodle_url $returnurl
     */
    public function __construct(\stdClass $user, $returnto, \moodle_url $returnurl) {
        $this->user = $user;
        $this->returnto = $returnto;
        $this->returnurl = $returnurl;
    }
}