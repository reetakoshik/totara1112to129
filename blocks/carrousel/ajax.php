<?php
require_once('../../config.php');
require_once($CFG->dirroot.'/blocks/carrousel/locallib.php');

$blockid=required_param('blockid', PARAM_INT);
$sortorder=required_param('sortorder', PARAM_INT);

$audiences=optional_param('audiences', 0,PARAM_INT);

$hide=optional_param('hide',0, PARAM_INT);
$title=optional_param('title','', PARAM_TEXT);
$buttontext=optional_param('buttontext','', PARAM_TEXT);
$buttonurl=optional_param('buttonurl','', PARAM_TEXT);

$blockcontext = CONTEXT_BLOCK::instance($blockid);

require_login();

if(!is_siteadmin()){
    error('only site admin can do this action.');die;
}

//change/save file
//get draft data
$imageurl='';
$draftitemid = file_get_submitted_draft_itemid('private');
$sql='
SELECT *
FROM {files}
WHERE itemid='.$draftitemid.' AND filesize>0
';
$draftdb=$DB->get_records_sql($sql);
if(!empty($draftdb)){
    $draftdb=end($draftdb);
    $filename=$draftdb->filename;
    //move from draft to file context area
    file_save_draft_area_files($draftitemid, $blockcontext->id, 'block_carrousel', 'private', $sortorder);
    $fs = get_file_storage();
    $file = $fs->get_file($blockcontext->id, 'block_carrousel', 'private',$sortorder, '/', $filename);
    //create url
    $imageurl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
    $imageurl=$imageurl->out_as_local_url();
    echo $imageurl;
}
//update or create slide if dont exist
$dataobject=new stdClass;
$dataobject->blockid=$blockid;
$dataobject->sortorder=$sortorder;
$dataobject->hide=$hide;
$dataobject->title=$title;
$dataobject->buttontext=$buttontext;
$dataobject->buttonurl=$buttonurl;
if(!empty($imageurl)){
    $dataobject->imageurl=$imageurl;
}
$dataobject->timemodified=time();
$dataobject->modifierid=$USER->id;
if(!empty($audiences)){
    $audiences=implode(',', $audiences);
    $dataobject->audiences=$audiences;
}

if($slide=$DB->get_record('block_carrousel',array('blockid'=>$blockid,'sortorder'=>$sortorder))){
    $dataobject->id=$slide->id;
    if(!$DB->update_record('block_carrousel', $dataobject)){
        print_error(get_string('error_failed_update', 'block_carrousel'));die;
    }
}else if(!$DB->insert_record('block_carrousel', $dataobject)){
    print_error(get_string('error_failed_update', 'block_carrousel'));die;
}