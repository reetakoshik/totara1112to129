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
 * @author Russell England <russell.england@catalyst-eu.net>
 * @package totara
 * @subpackage block_metrics_compliance
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Certifications block
 *
 * Displays upcoming certifications
 */
class block_policyblock extends block_base {

    public function init() {
        $this->title   = get_string('title', 'block_policyblock');
    }

    // only one instance of this block is required
    public function instance_allow_multiple() {
        return false;
    } //instance_allow_multiple

    public function preferred_width() {
        return 210;
    }

    /**
     * Default return is false - header will be shown
     * @return boolean
     */
     public function hide_header() {
        return false;
    }

    public function get_content() {
      global $CFG, $USER, $DB;
       
        require_once($CFG->dirroot . '/blocks/policyblock/locallib.php');
        if ($this->content !== NULL) {
            return $this->content;
        }
          
        $this->content = new stdClass();

        // get all the mentees, i.e. users you have a direct assignment to
        $this->content->text ='';
        $limit = 4;
        $table = new html_table();
        $table->attributes['class'] = 'policy_block_class';
        $table->head = $this->get_table_header();
        $policy =list_policies_user();
        //echo "<pre>";print_r($policy);
          $i=1;
          foreach ($policy as $valuep) {
         
            foreach ($valuep as  $value) {
             $plc =single_policy_details($value->id);
             if($i==$limit){
                break;
             }
             $progressarr = explode(" ", $plc->acceptancescounttext);
             //print_r($progressarr);
             $percent = ($progressarr[0] * 100)/$progressarr[2];
             $date =(!empty($plc->policyexpdate)) ? date("d.m.y", $plc->policyexpdate) :'No date';
             $a = '<div class="progress progress-striped active">
            <div id="pbar_5d47f6983cedc_bar" class="bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="'.round($percent).'" style="width:'.round($percent).'%">
                <span class="progressbar__text">'.round($percent).'%</span>
            </div>
        </div>';
        
          $policyreturnurl = new moodle_url('/blocks/policyblock/view.php', array('id' => $plc->id));
            $userviewurl = new moodle_url('/blocks/policyblock/view.php', array('id'=> $plc->id));
            
             $table->data[] = array($i,'<a href="'.$policyreturnurl.'">'.$plc->name.'</a>',$a,$date,'<a class="btn" href="'.$policyreturnurl.'">'.get_string("view", "block_policyblock").'</a>');

             $i++;
            }
            
             
        }
        
        
        // $table->data[] = array(1,'name','status','date','view');
		
        if(has_capability('block/policyblock:accept', \context_system::instance(),$USER->id)){
		$this->content->text .= html_writer::start_tag('div', array('class' => 'totaratable'));
        $this->content->text .= html_writer::table($table);
        $this->content->text .= '<div class="viewall"><a href="'.$CFG->wwwroot.'/blocks/policyblock/index.php"> '.get_string("viewall", "block_policyblock").' </a></div>';
		$this->content->text .= html_writer::end_tag('div');
        }
        $this->content->footer = '';
        return $this->content;
     
    }

     public function get_table_header() {

        $cells = array();

        $cells['cell0'] = new html_table_cell();
        $cells['cell0']->attributes['class'] = 'icon';
        $cells['cell1'] = new html_table_cell(get_string('str_title','block_policyblock'));
        $cells['cell1']->attributes['class'] = 'title';
        $cells['cell2'] = new html_table_cell(get_string('str_details','block_policyblock'));
        $cells['cell2']->attributes['class'] = 'details';
        $cells['cell3'] = new html_table_cell(get_string('str_duedate','block_policyblock'));
        $cells['cell3']->attributes['class'] = 'duedate';
        $cells['cell4'] = new html_table_cell();
        $cells['cell4']->attributes['class'] = 'actions';

        return right_to_left()
            ? array_reverse($cells)
            : $cells;
    }

}
