<?php
 use tool_policy\policy_version;
 use tool_policy\api;
require_once(dirname(__FILE__) . '/../../config.php');
 require_once($CFG->dirroot.'/blocks/policyblock/locallib.php');
 global $DB, $OUTPUT, $PAGE, $USER, $CFG ;

 require_login();
 $versionid = required_param('id', PARAM_INT);
 //set page url
 $CFG->cachejs=false;
 $PAGE->set_url( new moodle_url('/blocks/policyblock/view.php', ['id' => $versionid]));
 $PAGE->set_pagelayout('standard');
 $PAGE->set_title(get_string('title', 'block_policyblock'));
 $PAGE->set_heading(get_string('viewpolicy', 'block_policyblock'));
 $settingsnode = $PAGE->settingsnav->add(get_string('pluginname', 'block_policyblock'));
 $PAGE->requires->jquery();
 
 $PAGE->requires->css( new moodle_url($CFG->wwwroot .'/blocks/policyblock/styles.css'), true);
 

echo $OUTPUT->header(); //display header
$policydata = $DB->get_record('tool_policy_versions', array('id' => $versionid));
if($policydata->type == 0) {
        	$policytype = 'Site Policy';
        } else if($policydata->type == 1) {
        	$policytype = 'Private Policy';
        } else if($policydata->type == 2) {
        	$policytype = 'Third Party Policyies';
        } else {
        	$policytype = 'Other Policyies';
        }
?>
<div class="headingTopHeader">
	<h2><?php echo $policydata->name ?></h2>
	<div class="rhtprint"><a href="#"><?php echo get_string('cstatus', 'block_policyblock')?></a> <a href="#"> <?php echo get_string('released', 'block_policyblock')?></a> <a href="#"><i class="fa fa-print onclickmyFunction"></i> </a></div>
	<div class=" clearfix"></div>
	</div>
	
	<div class="tdvPanel">
	<p><strong><?php echo get_string('policydoctype', 'block_policyblock')?></strong> <?php echo $policytype ?></p>
	<p><strong><?php echo get_string('documentnumber', 'block_policyblock')?></strong> <?php echo $policydata->docnumber ?></p>
	<p><strong><?php echo get_string('version', 'block_policyblock')?></strong> <?php echo $policydata->revision ?></p>
	</div>
<div class="policy_document_summary clearfix m-b-1 iphoneheight" >      <h3><b><?php echo get_string('policydocsummary', 'block_policyblock')?></b></h3>
        <?php echo $policydata->summary;?>
		<div style="clear:both;"></div>
    </div>
    <div class="policy_document_content m-t-2 iphoneheight">
     <h3><b><?php echo get_string('content', 'block_policyblock')?></b></h3>
        <?php echo $policydata->content;?>
		<div style="clear:both;"></div>
    </div>
<?php
if ($policydata->agreementstyle == policy_version::AGREEMENTSTYLE_OWNPAGE) {
            if (!api::is_user_version_accepted($USER->id, $versionid)) {
                
                $accepturl = (new moodle_url('/blocks/policyblock/accept.php', [
                    'listdoc[]' => $versionid,
                    'status'.$versionid => 1,
                    'submit' => 'accept',
                    'sesskey' => sesskey(),
                ]))->out(false);
                if ($policydata->optional == policy_version::AGREEMENT_OPTIONAL) {
                    $declineurl = (new moodle_url('/blocks/policyblock/accept.php', [
                        'listdoc[]' => $versionid,
                        'status'.$versionid => 0,
                        'submit' => 'decline',
                        'sesskey' => sesskey(),
                    ]))->out(false);
                }
                ?>
        <div>
        <a role="button" href="<?php echo $accepturl;?>" class="btn btn-primary"><?php echo get_string('iagree', 'block_policyblock',$policydata->name);?></a>
        <a role="button" href="{{declineurl}}" class="btn btn-link"><?php echo get_string('idontagree', 'block_policyblock',$policydata->name);?></a>
        </div>
 <?php       


            }
        }
        
echo $OUTPUT->footer();