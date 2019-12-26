<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @package    totara_program
 * @subpackage navigation
 * @author     Oleg Demeshev <oleg.demeshev@totaralms.com>
 */

namespace totara_program\totara\menu;

/**
 * Class to store, render and manage the Required Learning Node
 *
 * @package    totara_program
 * @subpackage navigation
 */
class requiredlearning extends \totara_core\totara\menu\item {
    private $progurl;

    private function get_prog_url() {
        global $CFG, $USER;

        if (!isset($this->progurl)) {
            require_once($CFG->dirroot . '/totara/program/lib.php');
            $this->progurl = prog_get_tab_link($USER->id);
        }

        return $this->progurl;
    }

    protected function get_default_title() {
        return get_string('requiredlearningmenu', 'totara_program');
    }

    protected function get_default_url() {
        $progurl = $this->get_prog_url();
        if ($progurl === false) {
            // This is just a hint for admin UI.
            return '/totara/program/required.php';
        } else {
            return $progurl;
        }
    }

    public function get_default_sortorder() {
        return 84000;
    }

    protected function check_visibility() {
        if (!isloggedin() or isguestuser()) {
            return false;
        }

        $progurl = $this->get_prog_url();
        if ($progurl === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Is this menu item completely disabled?
     *
     * @return bool
     */
    public function is_disabled() {
        return (totara_feature_disabled('programs') && totara_feature_disabled('certifications'));
    }

    protected function get_default_parent() {
        return '\totara_core\totara\menu\unused';
    }

    public function get_incompatible_preset_rules(): array {
        return ['can_view_required_learning'];
    }
}
