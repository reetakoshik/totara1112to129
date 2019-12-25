<?php

use tool_policy\api;


     function list_policies_user() {
       global $DB, $USER, $PAGE;

       $cohortid = $DB->get_records_sql("SELECT cohortid FROM {cohort_members} WHERE userid = '".$USER->id."'");
      
       $policies=array();
        foreach ($cohortid as $value) {
          $policies[] = $DB->get_records_sql('SELECT pv.* FROM {tool_policy} AS p INNER JOIN {tool_policy_versions} AS pv ON pv.id = p.currentversionid WHERE  find_in_set('.$value->cohortid.',pv.relatedaudiences) ORDER BY p.sortorder DESC ');
       //$a=api::list_policies();
       //die();
      }
      return $policies;
     }

     function single_policy_details($versionid)
     {
       
      $policies=api::list_policies(null,true);
      foreach ($policies as $policy) {
            if ($policy->currentversionid == $versionid) {
                return $policy->currentversion;

            }

      }
    }
