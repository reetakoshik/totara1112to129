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
 use tool_policy\policy_version;
 require_once(dirname(__FILE__) . '/../../config.php');
 require_once($CFG->dirroot.'/blocks/policyblock/locallib.php');
 global $DB, $USER, $CFG;

 require_login();
 //set page url
 // $CFG->cachejs=false;
 $PAGE->set_url(new moodle_url('/blocks/policyblock/index.php'));
 $PAGE->set_pagelayout('standard');
 $PAGE->set_title(get_string('title', 'block_policyblock'));
 $PAGE->set_heading(get_string('viewpolicy', 'block_policyblock'));
 // $settingsnode = $PAGE->settingsnav->add(get_string('pluginname', 'block_policyblock'));
 $PAGE->requires->jquery();
 
 $PAGE->requires->css( new moodle_url($CFG->wwwroot .'/blocks/policyblock/styles.css'), true);
 

echo $OUTPUT->header(); //display header

        
        $policy =list_policies_user();
       
            ?>
            <div class="dataTablePolicies">
<table id="tool-policy-managedocs-wrapper firstpolicy" class="generaltable fullwidth listpolicydocuments">
    <thead>
        <tr>
            <th scope="col" width="3%">&nbsp;</th>
            <th scope="col" width="18%"><?php echo get_string('policydocname','block_policyblock');?></th>
            <th scope="col" width="14%;"><?php echo get_string('policyrevision', 'block_policyblock')?></th>
            <th scope="col" width="11%;"><?php echo get_string('policylanguage', 'block_policyblock')?></th>
            <th scope="col" width="16%;"><?php echo get_string('status', 'block_policyblock')?></th>
            <th scope="col" width="21%;"><?php echo get_string('policylastmodify', 'block_policyblock')?> </th>
            <th scope="col" width="21%;" style="text-align:center;"><?php echo get_string('policycompletion', 'block_policyblock')?></th>
           
              
            
            
        </tr>
    </thead>
<tbody > 
<?php
    foreach ($policy as $valuep) { 
        foreach ($valuep as  $value) { 
             $plc =single_policy_details($value->id);

$percentages = $plc->acceptancescounttext;
        $val = explode("(",$percentages);
        $val_explode = explode(")",$val[1]);
        $percentages = $val_explode[0];
        $val1 = explode("%",$val_explode[0]);
        $percentagesVal=$val1[0];
        if($percentagesVal<=50){
         $graphcolor = 'bar-r';   
        }else if(($percentagesVal>50) && ($percentagesVal<=80)){
         $graphcolor = 'bar-y';
        }else if($percentagesVal > 80)
        {
          $graphcolor='bar-g';  
        }
        $status = $plc->status;
        $statustext = get_string('status' . $status, 'block_policyblock');
        
        if ($status == policy_version::STATUS_ACTIVE) {
            $statustext = html_writer::tag('button',$statustext,array("class"=>"btn btn-primay btn-green btnwidth"));
           
        } else if ($status == policy_version::STATUS_DRAFT) {
            $statustext = html_writer::tag('button',$statustext,array("class"=>"btn btn-default btn-gray btnwidth"));
        } else {
            $statustext = html_writer::tag('button',$statustext,array("class"=>"btn  btn-danger btn-red  btnwidth"));
        }

?>
<tr data-policy-name="<?php echo $plc->name?>" data-policy-revision="<?php echo $plc->revision?>" id="<?php echo $plc->sortorder?>">
        <td colspan="9" style="margin:0; padding:0;">
         <table width="100%" cellpadding="0" cellspacing="0" border="0" class="policyRowOne">
         <tr>
         <td width="4%;" align="center"><span class="flex-icon ft-fw ft ft-fw ft fa-arrows"></span></td>
            <td width="18%;">
                <div>
                    <div><a href="<?php echo $CFG->wwwroot?>/blocks/policyblock/view.php?id=<?php echo $plc->id ?>" class="decornone"><?php echo $plc->name?></a></div>
                    <div class="text-muted, muted"><small><?php echo $plc->typetext, $plc->audiencetext, $plc->optionaltext;?></small></div>
                </div>
            </td>
            <td width="14%;">
            <?php echo $plc->revision?>
            </td>
            <td width="11%" >
             <?php echo $plc->primarylang?>
            </td>
            <td width="16%">
           <?php echo $statustext ?>    
            </td>
            <td width="21%">
                <div class="text-muted, muted">
                    <small>
                        <time title="<?php echo get_string('lastmodified', 'core')?>" datetime="">
                            <?php echo date( 'm/d/Y H:i:s',$plc->timemodified) ?>
                        </time>
                    </small>
                </div>
            </td>
            <td width="21%">
                <div class="clearfix">
                    <div class="c100 p<?php echo $percentagesVal ?> small" style="margin: 0 auto;">
                        <span><?php echo $percentages ?></span>
                        <div class="slice">
                            <div class="bar <?php echo $graphcolor ?>" ></div>
                            <div class="fill" ></div>
                        </div>
                    </div>
                </div>
            </td>
          </tr>
          </table>
          </td>
          </tr>
<?php

        } 
    }
?>
</tbody>
</table>
</div>
<?php
// if(has_capability('block/policyblock:accept', \context_system::instance(),$USER->id)){
// 	echo get_string('title', 'block_policyblock');
  
// }
echo $OUTPUT->footer(); //display footer
?>