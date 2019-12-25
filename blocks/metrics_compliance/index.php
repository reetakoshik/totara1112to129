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
 * @subpackage block_metrics_compliance
 */
 require_once(dirname(__FILE__) . '/../../config.php');
 global $DB, $OUTPUT, $PAGE, $USER, $CFG ;

 require_login();
 //set page url
 $CFG->cachejs=false;
 $PAGE->set_url('/blocks/metrics_compliance/index.php');
 $PAGE->set_pagelayout('standard');
 $PAGE->set_title(get_string('metricscomppage', 'block_metrics_compliance'));
 $PAGE->set_heading('Metrics Compliance');
 $settingsnode = $PAGE->settingsnav->add(get_string('pluginname', 'block_metrics_compliance'));
 $editurl = new moodle_url('/blocks/metrics_compliance/index.php');
 $editnode = $settingsnode->add(get_string('metricscompreport', 'block_metrics_compliance'), $editurl);
 $editnode->make_active();
 $PAGE->requires->jquery();
 //$PAGE->requires->js( new moodle_url($CFG->wwwroot .'/blocks/metrics_compliance/js/zingchart.min.js'), true);
 $PAGE->requires->css( new moodle_url($CFG->wwwroot .'/blocks/metrics_compliance/styles1.css'), true);
 ?>
<script src="https://cdn.zingchart.com/zingchart.min.js"></script>
 <?php
 $compid=$DB->get_record_sql("SELECT cc.id FROM {course_categories} cc WHERE cc.idnumber=?",array('compliance' ));
$sql = "SELECT COUNT(ja.userid) AS users, p.fullname, p.id AS certifid, p.availableuntil
FROM {job_assignment} ja 
INNER JOIN {prog_assignment} progas ON progas.assignmenttypeid = ja.organisationid
INNER JOIN {prog} p ON p.id = progas.programid 
INNER JOIN {org} o ON o.id = ja.organisationid 
INNER JOIN {course_categories} cc ON cc.id = p.category
WHERE cc.parent = ?
GROUP BY p.fullname, p.fullname, p.id, p.availableuntil";
$certifcountpuser = $DB->get_records_sql($sql, array($compid->id));
$compliantpercent    = 0; 
$noncompliantpercent = 0;
$expirepercent       = 0;
$sqlexp= "SELECT p.id, p.fullname FROM {prog} p 
INNER JOIN {course_categories} cc ON cc.id = p.category
WHERE cc.parent = ? AND p.available=?"; 
$certifexpired = $DB->get_records_sql($sqlexp,  array($compid->id,'0'));  
if(!empty($certifcountpuser)) {
    $compliant=0;
    $noncompliant=0;
    $certificates=0;
    $expire=0;
    foreach ($certifcountpuser as  $value) {
        $sql1="SELECT COUNT(prog_comp.userid) AS certiftotusers, p.fullname
        FROM {job_assignment} ja 
        INNER JOIN {prog_completion} prog_comp ON prog_comp.userid = ja.userid 
        INNER JOIN {prog} p ON p.id = prog_comp.programid 
        INNER JOIN {org} o ON o.id = ja.organisationid 
        INNER JOIN {course_categories} cc ON cc.id = p.category 
        WHERE prog_comp.status = ? AND cc.parent = ? AND p.id = ?
        GROUP BY p.fullname";
        $certifcompuser = $DB->get_record_sql($sql1, array('3',$compid->id,$value->certifid));
        
        $singleperc = $certifcompuser->certiftotusers/$value->users*100;
        if($singleperc >= 80.00){
            $compliant++;
        }else{
            $noncompliant++;
        }
        $certificates++;
        $certifdate =date('Y-m-d',$value->availableuntil);
        $currentdate =date('Y-m-d');
        $date1 = date_create($certifdate);
        $date2 = date_create($currentdate);
        $diff  = date_diff($date1,$date2);
        
        if(($diff->format('m')<3) && ($certifdate !='1970-01-01'))
        {
          $expire++;
        }
    }
    $compliantpercent    = round(($compliant /$certificates*100),2); 
    $noncompliantpercent = round(($noncompliant/$certificates*100),2);
    $expirepercent       = round(($expire/$certificates*100),2);
}


$PAGE->requires->js_call_amd('block_metrics_compliance/compliance_report', 'init', array($noncompliantpercent, $expirepercent, $compliantpercent));

?>
   <!-- <script src="https://cdn.zingchart.com/zingchart.min.js"></script> -->
   <style type="text/css">
       @media screen and (max-width: 1199px){
    .comp1{
        float: left !important;
        margin-right: 8px;
        margin-left: 12px;
    }
    .chartValue .comp1 {
    float: left;
    margin-right: 13px;
    margin-left: 2px;
}
    

}
@media screen and (max-width: 1024px){
    .comp2 {
        font-weight: bolder;
        font-size: 17px !important;
        }
    .comp3 {
        font-size: 12px !important;
        }
    .odograph .comp1 img{
        width:70%;
        margin-top: 6px;

    }    
    .chartValue{
    overflow: auto;
    text-align: center;
    width: 180px; margin:0 auto;
    }
}
   </style>
<?php
echo $OUTPUT->header(); //display header
            
  echo '<div class="container-fluid chartPanel">
  <div class="row ">
    <div class="col-xs-12 col-sm-4" >
        <div id="chartdiv"></div>
        <div class="chartValue">
        <div class="comp1"><img src="'.$CFG->wwwroot.'/blocks/metrics_compliance/images/non-complaint-icon.png" ></div>
        <div class="comp12">
            <div id="noncomppercent" class="comp2">80%</div>
            <div class="comp3">'.get_string('noncompliant', 'block_metrics_compliance').'</div>
            </div>
            </div>
    </div>
    <div class="col-xs-12 col-sm-4" >
       <div id="chartdiv2"></div>
       <div class="chartValue">
       <div class="comp1"><img src="'.$CFG->wwwroot.'/blocks/metrics_compliance/images/due-icon.png" ></div>
        
        <div class="comp12">
            <div id="expirepercent" class="comp2">0%</div>
            <div class="comp3">'.get_string('duetoexpire', 'block_metrics_compliance').'</div>
        </div>
    </div></div>
    <div class="col-xs-12 col-sm-4" >
        <div id="chartdiv3"></div>
         <div class="chartValue">
        <div class="comp1"><img src="'.$CFG->wwwroot.'/blocks/metrics_compliance/images/complaint-icon.png" ></div>
        
        <div class="comp12">
            <div id="comlpercent" class="comp2">20%</div>
            <div class="comp3">'.get_string('compliant', 'block_metrics_compliance').'</div>
        </div>
        </div>
    </div>
  </div>';
echo '</div><br><div class="clearfix"></div>';
$table = new html_table();
$table->head = array(get_string('certification', 'block_metrics_compliance'),get_string('status', 'block_metrics_compliance'), get_string('link', 'block_metrics_compliance'));
foreach ($certifexpired as $value) {
	$expireicon = '<img src="'.$CFG->wwwroot.'/blocks/metrics_compliance/images/expired-icon.png" > Expired';
	$editlink = '';
	$context = context_system::instance();
	
	if(has_capability('block/metrics_compliance:editcertification', $context)) {
		$editlink = '<a href="'.$CFG->wwwroot. '/totara/program/edit.php?id='.$value->id.'" target="_blank">'.get_string('edit', 'block_metrics_compliance').'</a>';
	}
	$table->data[] = array($value->fullname, $expireicon, $editlink);
}

echo html_writer::table($table);

echo $OUTPUT->footer(); //display footer
?>