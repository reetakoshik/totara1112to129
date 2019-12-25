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
 * @author Yashco Systems <reeta.yashco@gmail.com>
 * @package totara
 * @subpackage block_compliance_training
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Certifications block
 *
 * Displays upcoming certifications
 */
class block_compliance_training extends block_base {

    public function init() {
        $this->title   = get_string('pluginname', 'block_compliance_training');
    }


    function instance_allow_multiple() {
        return true;
    }

    function get_content() {
        global $CFG, $USER, $DB;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass();

        // get all the mentees, i.e. users you have a direct assignment to
         $this->content->text ='';
        if(is_siteadmin()){
        $this->content->text .= '<span class="flex-icon ft-fw ft fa-cog" aria-hidden="true"></span> <a href="'.$CFG->wwwroot.'/blocks/compliance_training/questionnairesett.php">'.get_string('setting', 'block_compliance_training').'</a><br>';
         }
        $this->content->text .= '<span class="flex-icon ft-fw ft fas fa fa-map-marker" aria-hidden="true"> </span> <a href="'.$CFG->wwwroot.'/blocks/compliance_training/usermappinganalysis.php">'.get_string('analysisresult', 'block_compliance_training').'</a><br>';
        //$this->content->text .= '<span class="flex-icon ft-fw ft fas fa-exclamation-triangle" aria-hidden="true" ></span> <a href="'.$CFG->wwwroot.'/blocks/compliance_training/toprisksubcateogry.php"> '.get_string('toprisk', 'block_compliance_training').'</a><br>';
        //$this->content->text .= '<span class="flex-icon ft-fw ft fas fa fa-street-view" aria-hidden="true"></span> <a href="'.$CFG->wwwroot.'/blocks/compliance_training/reportsource1.php">'.get_string('usermapping', 'block_compliance_training').'</a><br>';
        $this->content->footer = '';

        return $this->content;
    }
}
