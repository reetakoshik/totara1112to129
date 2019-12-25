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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package block_totara_stats
 */

namespace tool_policy\task;
use tool_policy\api;

class sendnotificationtp_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('updatetoolpolicytask', 'tool_policy');
    }


    /**
     * Preprocess report groups
     */
    public function execute() { 
        global $CFG, $DB;
        //require_once($CFG->dirroot.'/admin/tool/policy/locallib.php');
         $policy=$DB->get_records_sql('SELECT DISTINCT pv.* FROM {tool_policy} AS p INNER JOIN {tool_policy_versions} AS pv ON p.currentversionid= pv.id WHERE pv.parentpolicy=0 ORDER BY p.sortorder ASC');
         if(!empty($policy)){
           foreach ($policy as $value) {
               if(!empty($value->policyexpdate)){
                echo date("Y-m-d", $value->policyexpdate);
                echo $days_ago = date('Y-m-d', strtotime('-7 days', $value->policyexpdate));
                  echo $current= date('Y-m-d');

                if($days_ago == $current)
                {
                    //echo $value->policyid;
                  api::send_policyexpiremsg_to_audience($value->policyid); 
                }
               }
           }
            
         }
        
        // $record = new \stdClass();
        // $record->policyid = 3;
        // $record->subject = 'test111';
        // $record->message = 'test222';
        // $record->sendmanager = 2;
        // $record->managersubject = 'test333';
        // $record->managermessage = 'test444';
        // $record->timecreated = time();
        // $record->timemodified = time();
        // $DB->insert_record('tool_policy_message', $record);
    }
}