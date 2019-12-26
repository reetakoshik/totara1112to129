

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
 require_once(dirname(__FILE__) . '/../../config.php');
 require_once($CFG->dirroot.'/blocks/compliance_training/locallib.php');
 global $DB, $OUTPUT, $PAGE, $USER, $CFG ;

 require_login();
 
 $PAGE->set_url('/blocks/compliance_training/usermappinganalysis.php');
 $PAGE->set_pagelayout('standard');
 $PAGE->set_title(get_string('analysisresult', 'block_compliance_training'));
 $PAGE->set_heading(get_string('analysisresult', 'block_compliance_training'));
 $PAGE->requires->jquery();
 //$PAGE->requires->js( new moodle_url($CFG->wwwroot .'/blocks/metrics_compliance/js/zingchart.min.js'), true);
 $PAGE->requires->css( new moodle_url($CFG->wwwroot .'/blocks/compliance_training/styles1.css'), true);
 $settingsnode = $PAGE->settingsnav->add(get_string('analysisresult', 'block_compliance_training'));
 $editurl1 = new moodle_url('/blocks/compliance_training/usermappinganalysis.php');
 $editnode = $settingsnode->add(get_string('analysisresult', 'block_compliance_training'), $editurl1);
 $editnode->make_active();
 $CFG->cachejs= false;
 $compid=$DB->get_record_sql("SELECT cc.id FROM {course_categories} cc WHERE cc.idnumber='compliance'");
$orgs = $DB->get_records('org', null, null, 'id, fullname, depthlevel, parentid');
$certifs = $DB->get_records_sql("SELECT p.id, p.fullname
      FROM {prog} p
      INNER JOIN {course_categories} cc ON cc.id = p.category
      WHERE p.certifid IS NOT NULL AND cc.path LIKE '/".$compid->id."/%'");

echo $OUTPUT->header();      

echo '<div class="panel with-nav-tabs panel-primary">
<div class="panel-heading headerTabLabel" >
    <ul class="nav nav-tabs" style="text-align:right;">
    <li class="active"><a href="#tab2primary" data-toggle="tab">'.get_string('toprisk', 'block_compliance_training').'</a></li> 
        <li ><a href="#tab1primary" data-toggle="tab">'.get_string('certiforgtext', 'block_compliance_training').'</a></li>                    
    </ul>
</div>';
       
$table = new html_table();
$table->attributes['class'] = 'table table-hover table-bordered table-striped';
$head = array(get_string('organisations', 'block_compliance_training'));
$tblsize = array('100px');
$tbldatacertif = array();
$conditionids = $DB->get_records_sql("SELECT conditionid FROM {block_questionnerie_setting}");
foreach($certifs as $certif) {
      $string = strip_tags($certif->fullname);
if (strlen($string) > 25) {
    // truncate string
    $stringCut = substr($string, 0, 25);
    $string = $stringCut.' ...';
}
    array_push($tblsize, '100px');
    $string1= '<span id="ctp_'.$certif->id.'" class="certiftooltip" title="'.$certif->fullname.'">'.$string.'</span>';
    array_push($head, $string1);
    array_push($tbldatacertif, $certif->id);
}

$true = '<p class="colGreen">&nbsp;</p>';
$table->head = $head;
$table->size = $tblsize;
$count = 0;
 $check='';
 $arraysubcatid = array();
 $sno=1;
foreach ($orgs as $org) {
    
    if ($sno % 2 == 0) {
     $fullname = "<span class='circleround'>".$sno."</span><span style='margin-left:$px'>".$org->fullname."</span>";
    }else{
     $fullname = "<span class='circleroundBg'>".$sno."</span><span style='margin-left:$px'>".$org->fullname."</span>";   
    }
    
    $a = array($fullname);
    
    foreach($tbldatacertif as $tcertif) {
        $check='';
        foreach($conditionids as $conditionid) {
            $cond1 = get_compliance_certifs_condition($conditionid->conditionid);
            $cond1 = explode(',', $cond1);
            $condorgids = get_compliance_orgids_condition($conditionid->conditionid);
            $condorgids = explode(',', $condorgids);
            foreach($cond1 as $cond) {
                if($cond == $tcertif && in_array($org->id, $condorgids)) { 
                  $compcond1 = compliance_condition($USER->id, $conditionid->conditionid);
                    if($compcond1 == '1') {
                        $check=$true;
                    }
                }
            }
        }
       
    array_push($a,$check);
    }
        
	$table->data[] = $a;
	$sno++;
}
echo '<div class="panel-body minHeightPanel">
                   <div class="tab-content">';
echo '<div class="tab-pane fade" id="tab1primary">
  <center><h3>'.get_string('analysisresult', 'block_compliance_training').'</h3></center> 
<div class="subheadBot"> <h4>'.get_string('mappingorgtext', 'block_compliance_training').'</h4> </div>';
echo '<div class="pcss3t" >
<div class="radioLftThree">
	
     <label for="tab4"><input type="radio" name="pcss3t" id="tab4" data-show="tabcontent4" class="radioButton"/>
     <span class="label-text"><small>'.get_string('notselected', 'block_compliance_training').'</small></span></label> 
    <label  for="tab1"><input type="radio" name="pcss3t" id="tab1" data-show="tabcontent1" class="radioButton"/>
    <span class="label-text"><small>'.get_string('organisation', 'block_compliance_training').' </small></span></label>                  
    <label for="tab2"><input type="radio" name="pcss3t" id="tab2" data-show="tabcontent2" class="radioButton" />
    <span class="label-text"><small>'.get_string('role', 'block_compliance_training').'</small></span></label>                    
    <label for="tab3"><input type="radio" name="pcss3t" id="tab3" data-show="tabcontent3" class="radioButton" />
    <span class="label-text"><small>'.get_string('name', 'block_compliance_training').'</small></span></label>
</div>
<div class="rhtColorGrid">
<label class="redBtn">'.get_string('incomplete', 'block_compliance_training').'</label>
<label class="yellowBtn">'.get_string('inprogress', 'block_compliance_training').'</label>
<label class="greenBtn">'.get_string('completed', 'block_compliance_training').'</label>
</div>	
    <ul class="organistionGrid">
        <li class="radiocontent tab-content-4" id="tabcontent4" runat="server">&nbsp; </li>
        <li class="radiocontent tab-content-1" id="tabcontent1" runat="server">';
        echo html_writer::table($table);
        echo '</li>
        <li class="radiocontent tab-content-2" id="tabcontent2" runat="server">
            <table></table>
        </li>
        <li class="radiocontent tab-content-3" id="tabcontent3" runat="server">';
        $jobassignmentusers = $DB->get_records_sql("SELECT ja.id, ja.userid, u.firstname, u.lastname, p.fullname, ja.organisationid
                               FROM {job_assignment} ja 
                               INNER JOIN {user} u ON u.id = ja.userid
                               INNER JOIN {pos} p ON p.id = ja.positionid
                               WHERE ja.managerjaid IS NULL");

foreach($jobassignmentusers as $user) {
    $staffusers = $DB->get_records_sql("SELECT ja.id, ja.userid, u.firstname, u.lastname, p.fullname, ja.organisationid
                               FROM {job_assignment} ja 
                               INNER JOIN {user} u ON u.id = ja.userid
                               INNER JOIN {pos} p ON p.id = ja.positionid
                               WHERE ja.managerjaid = ?",array($user->id));
    $record = new \stdClass();
    $record->id = $user->id;
    $record->userid = $user->userid;
    $record->firstname = $user->firstname;
    $record->lastname = $user->lastname;
    $record->fullname = $user->fullname;
    $record->organisationid = $user->organisationid;
    $record->usertype = 'Manager';
    $stusers[][] = $record;
    foreach($staffusers as $suser) {
        $record = new \stdClass();
        $record->id = $suser->id;
        $record->userid = $suser->userid;
        $record->firstname = $suser->firstname;
        $record->lastname = $suser->lastname;
        $record->fullname = $suser->fullname;
        $record->organisationid = $suser->organisationid;
        $record->usertype = 'Staff';
        $stusers[][] = $record;
    }
}

$admin = get_admin();
$table = new html_table();
$table->attributes['class'] = 'table table-hover table-bordered table-striped';
$head = array(get_string('fullname', 'block_compliance_training'), get_string('generalposition', 'block_compliance_training'));
$tblsize = array('100px','100px');
$compid=$DB->get_record_sql("SELECT cc.id FROM {course_categories} cc WHERE cc.idnumber= ?",array('compliance'));
$orgs = $DB->get_records('org', null, null, 'id, fullname, depthlevel, parentid');
$organisations = array();
foreach($orgs as $org) {
    $organisations[] = $org->id;
}
$certifs = $DB->get_records_sql("SELECT p.id, p.fullname
      FROM {prog} p
      INNER JOIN {course_categories} cc ON cc.id = p.category
      WHERE p.certifid IS NOT NULL AND cc.path LIKE '/".$compid->id."/%'");

$tbldatacertif = array();


foreach($certifs as $certif) {
    $string = strip_tags($certif->fullname);
if (strlen($string) > 25) {
    // truncate string
    $stringCut = substr($string, 0, 25);
    $string = $stringCut.' ...';
}
 $string1= '<span id="ctp_'.$certif->id.'" class="certiftooltip" title="'.$certif->fullname.'">'.$string.'</span>';
    array_push($tblsize, '100px');
    array_push($head,$string1);
    array_push($tbldatacertif, $certif->id);
}

array_push($head,get_string('enroll', 'block_compliance_training'));

$table->head = $head;
$table->size = $tblsize;
$count = 0;
$arraysubcatid = array();
$certifenrollink =  new moodle_url('/totara/program/manage.php', array('viewtype' => 'certification'));
$certifenrollbtn = '<div class="btnEnroll"><a href="'.$certifenrollink.'">'.get_string('enroll', 'block_compliance_training').'</a></div>';
$conditionids = $DB->get_records_sql("SELECT conditionid FROM {block_questionnerie_setting}");
$sno=1;
foreach ($stusers as $stmanager) {
    foreach($stmanager as $user) {
        if ($sno % 2 == 0) {
		$fullname='<span class="circleround">'.$sno.'</span>'.$user->firstname.' '. $user->lastname;
        }else{
        $fullname='<span class="circleroundBg">'.$sno.'</span>'.$user->firstname.' '. $user->lastname;  
        }
        $a = array($fullname, $user->usertype);
        
        $orgid = $user->organisationid;

    foreach($tbldatacertif as $tcertif) {
        $check='';
        foreach($conditionids as $conditionid) {
            $cond1 = get_compliance_certifs_condition($conditionid->conditionid);
            $cond1 = explode(',', $cond1);
            $condorgids = get_compliance_orgids_condition($conditionid->conditionid);
            $condorgids = explode(',', $condorgids);
            foreach($cond1 as $cond) {
                if($cond == $tcertif && in_array($orgid, $condorgids)) {
                    $colorclass = compliance_table_cell_color($cond, $user->userid);
                    $compcond1 = compliance_condition($admin->id, $conditionid->conditionid);
                    if($compcond1 == '1') { 
                        $check=$colorclass;
                    } 
                }
            }
        }
        
    array_push($a,$check);
    }
    array_push($a,$certifenrollbtn);
    $table->data[] = $a;
	$sno++;
    }
}

echo html_writer::table($table);

        echo '</li>
    </ul>
</div>';

echo '</div><div class="tab-pane fade in active" id="tab2primary">';
?>
<script src="https://cdn.zingchart.com/zingchart.min.js"></script>
  
<?php
$compid=$DB->get_record_sql("SELECT cc.id FROM {course_categories} cc WHERE cc.idnumber='compliance'");
$orgs = $DB->get_records('org', null, null, 'id, fullname, depthlevel, parentid');
$certifs = $DB->get_records_sql("SELECT p.id, p.fullname
      FROM {prog} p
      INNER JOIN {course_categories} cc ON cc.id = p.category
      WHERE p.certifid IS NOT NULL AND cc.path LIKE '/".$compid->id."/%'");

$count = 0;
$check='';
$arraysubcatid = array();
$arraysubcatidarr = array();
$conditionids = $DB->get_records_sql("SELECT conditionid FROM {block_questionnerie_setting}");
foreach($conditionids as $conditionid) {
    $cond1 = get_compliance_certifs_condition($conditionid->conditionid);
    $cond1 = explode(',', $cond1);
    foreach($cond1 as $cond) {
        $arraysubcatid[] = get_com_subcateogry_name($cond);
        $arraysubcatidarr[] = get_com_subcateogry_id($cond);
    }
}
$arrid = array_unique($arraysubcatidarr,SORT_REGULAR);
sort($arrid);
foreach($arrid as $b) {
  
$sql = "SELECT p.id AS certifid, COUNT(ja.userid) AS users  , p.fullname, p.availableuntil
FROM {job_assignment} ja 
INNER JOIN {prog_assignment} progas ON progas.assignmenttypeid = ja.organisationid
INNER JOIN {prog} p ON p.id = progas.programid 
INNER JOIN {org} o ON o.id = ja.organisationid 
INNER JOIN {course_categories} cc ON cc.id = p.category
WHERE p.certifid IS NOT NULL AND cc.id = ?
GROUP BY p.id HAVING COUNT(ja.userid) > 0
";
$certifcountpuser = $DB->get_records_sql($sql,array($b['id']));
$compliantpercent    = 0; 
$noncompliantpercent = 0;
$expirepercent       = 0;

if(!empty($certifcountpuser)) {
    $compliant=array();
    $noncompliant=array();
    $certificates=0;
    $expire=0;

    foreach ($certifcountpuser as  $value) {
        if($CFG->dbtype  == 'pgsql') {
            $sql1="SELECT COUNT(prog_comp.userid) AS certiftotusers
        FROM {job_assignment} ja 
        INNER JOIN {prog_completion} prog_comp ON prog_comp.userid = ja.userid 
        INNER JOIN {prog} p ON p.id = prog_comp.programid 
        INNER JOIN {org} o ON o.id = ja.organisationid 
        INNER JOIN {course_categories} cc ON cc.id = p.category 
        WHERE  prog_comp.status = ? AND cc.id = ? AND p.id = ?
        HAVING COUNT(prog_comp.userid) > 0";
        } else {
        
        $sql1="SELECT p.id, prog_comp.userid AS certiftotusers
        FROM {job_assignment} ja 
        INNER JOIN {prog_completion} prog_comp ON prog_comp.userid = ja.userid 
        INNER JOIN {prog} p ON p.id = prog_comp.programid 
        INNER JOIN {org} o ON o.id = ja.organisationid 
        INNER JOIN {course_categories} cc ON cc.id = p.category 
        WHERE  prog_comp.status = ? AND cc.id = ? AND p.id = ?
        GROUP BY p.id";
        }
        $certifcompuser = $DB->get_record_sql($sql1,array('3',$b['id'],$value->certifid));
       $singleperc = $certifcompuser->certiftotusers/$value->users*100; 
        if($singleperc >= 80.00){
            $compliant[$b['id']]['comp']++;
        }else{
            $noncompliant[$b['id']]['noncomp']++;
        }      
    }     
}
    $c = !empty($compliant[$b['id']]['comp']) ? $compliant[$b['id']]['comp'] : 0;
    $nc = !empty($noncompliant[$b['id']]['noncomp']) ? $noncompliant[$b['id']]['noncomp'] : 0;
    $subcategory[] = array('id'=>$b['id'],'name'=>$b['name'],'comp'=>$c,'noncomp'=>$nc);
}
  
$subcategory1=json_encode($subcategory);

$PAGE->requires->js_call_amd('block_compliance_training/usermapping', 'init', array($subcategory1));
echo '<div><center><h3>'.get_string('topriskreq', 'block_compliance_training').'</h3></center></div>';
echo '<div class="subheadBot"> <h4>'.get_string('topriskreqsubtitle', 'block_compliance_training').'</h4> </div>';
$count=0;
 echo '<div class="graphbody"><div class="row">';
foreach($subcategory as $value) {
    $courseurl = new moodle_url('/course/view.php', array('id' => 106));
   echo '<div class="col-xs-12 col-sm-6 col-md-4 col-lg-4"> 
         <div class="graphpanel">
          <div id="myChart'.$count.'"></div>
		  
          <div class="buttonCenter"><a href="'.$courseurl.'">'.get_string('mitigate', 'block_compliance_training').'</a></div> 
		  
		  </div></div>';
        $count++;
        }
        echo'</div></div>';

echo '</div></div>
                   </div>
               </div>
           </div>';
echo $OUTPUT->footer(); //display footer
?>