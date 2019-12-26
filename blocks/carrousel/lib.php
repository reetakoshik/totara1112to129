<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once($CFG->dirroot.'/admin/tool/uploaduser/locallib.php');
require_once($CFG->dirroot.'/user/externallib.php');
require_once($CFG->dirroot.'/totara/core/lib.php');
require_once($CFG->dirroot.'/totara/cohort/lib.php');

/**
 * Serves the slider background images. Implements needed access control ;-)
 *
 * @package  block_carrousel
 * @category files
 * @param stdClass $course course object
 * @param stdClass $birecord_or_cm block instance record
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 */
function block_carrousel_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {

    if ($context->contextlevel != CONTEXT_BLOCK) {
        //send_file_not_found();
    }

     $fs = get_file_storage();

    $filename = array_pop($args);
    $entryid = array_pop($args);
    $filepath = '/';
    if (!$file = $fs->get_file($context->id, 'block_carrousel', $filearea, $entryid, $filepath, $filename) or $file->is_directory()) {
        return false;
    }
    // send the file
    send_stored_file($file,86400,0,false); // download MUST be forced - security!
}

/*
function block_carrousel_get_cohorts() {
    global $DB;
    $cohorts = $DB->get_records('cohort');
    $cohorts_by_id=array();
    foreach ($cohorts as $cohort) {
        $cohorts_by_id[$cohort->id]=$cohort->name;
    }
    return $cohorts_by_id;
}*/


function block_carrousel_get_slides($blockid, $showwithhidden = false) {
    global $DB, $USER;

    $block = $DB->get_record_sql("SELECT * FROM {block_instances} WHERE id = $blockid");
    $context = context::instance_by_id($block->parentcontextid);
    $canEdit = has_capability('block/carrousel:manage', $context);
    
    $result = [];
    $usercohorts = totara_cohort_get_user_cohorts($USER->id);
    $where = '';
    if (!$showwithhidden) {
        $where .= " AND hide = 0";
    }
  
    $where .= " ";
    if (empty($usercohorts) && !$canEdit) {
        $where .= " AND cohorts = ''";
    }

    $sql = "SELECT * FROM {block_carrousel} WHERE blockid = " . $blockid . $where
            . " ORDER BY sortorder,timemodified";

    if (empty($usercohorts) || $canEdit) {
        return $DB->get_records_sql($sql);
    } else {
         $records = $DB->get_records_sql($sql);
         foreach ($records as $record) {
             $slide_cohorts = explode(',', $record->cohorts);
             foreach ($slide_cohorts as $cohort){
                 if (in_array($cohort, $usercohorts)) {
                     $result[] = $record;
                     break;
                 }
             }
         }
    }
   
    return $result;   
}

function block_carrousel_get_cohorts($ids){
    global $DB;
    if (empty($ids)) {
        return array();
    } 
    $sql = "SELECT * FROM {cohort} WHERE id IN (". $ids . ")";
    return $DB->get_records_sql($sql, array());
}



function block_carrousel_create_slide ($blockid, $slideid = NULL){
    global $DB;
    
    if ($slideid) {
        return block_carrousel_get_slide($slideid);
    }
    
    $maxsortorderrecord = $DB->get_record_sql('SELECT MAX(sortorder) maxsort FROM {block_carrousel}');
    
    $slide = new stdClass;
    $slide->blockid = $blockid;
    $slide->sortorder = $maxsortorderrecord->maxsort + 1;
    $slide->hide = 0;
    $slide->title = '';
    $slide->buttontext = '';
    $slide->buttonurl = '';
    $slide->imageurl = '';
    $slide->cohorts = '';
    
    return $slide;
}


function block_carrousel_is_first($slide) {
    global $DB;
    $record = $DB->get_record_sql('SELECT MIN(sortorder) minsort FROM {block_carrousel}');
    if ($record->minsort == $slide->sortorder) {
        return true;
    }
    return false;
}

function block_carrousel_is_last($slide) {
    global $DB;
    $record = $DB->get_record_sql('SELECT MAX(sortorder) minsort FROM {block_carrousel}');
    if ($record->minsort == $slide->sortorder) {
        return true;
    }
    return false;
}

function block_carrousel_get_slide($slideid) {
    global $DB;
   
    $sql = "SELECT * FROM {block_carrousel}
         WHERE id = $slideid";
    return $DB->get_record_sql($sql);
}

function block_carrousel_get_user_audiences() {
    global $DB,$USER;
    $sql="SELECT * FROM {cohort_members}
        WHERE userid=".$USER->id. "";
    $cohorts=$DB->get_records_sql($sql);
    $cohorts_arr = array();
    foreach ($cohorts as $cohort) {
        $cohorts_arr[$cohort->cohortid]=$cohort->cohortid;
    }
    return;
}

  /**
 * Remove slide from DB
 */
  function block_carrousel_delete_slide($id) {
    global $DB;
    // Reorder it to last.
    db_reorder($id, -1, 'block_carrousel');
    // Delete slide.
    $DB->delete_records('block_carrousel', array('id' => $id));
}

/**
 * Remove slide from DB
 */
function block_carrousel_slide_move_up($id) {
    global $DB;
    $slide = block_carrousel_get_slide($id);
    db_reorder($id, $slide->sortorder - 1, 'block_carrousel');
}

function block_carrousel_slide_move_down($id) {
    global $DB;
    $slide = block_carrousel_get_slide($id);
    db_reorder($id, $slide->sortorder + 1, 'block_carrousel');
}

function block_carrousel_slide_publish($id) {
    global $DB;
    $slide = block_carrousel_get_slide($id);
    $slide->hide = 0;
    if (!$DB->update_record('block_carrousel', $slide, false)) {
        print_error(get_string('error_failed_update', 'block_carrousel'));
        die;
    }
}

function block_carrousel_slide_unpublish($id) {
    global $DB;
    $slide = block_carrousel_get_slide($id);
    $slide->hide = 1;
    if (!$DB->update_record('block_carrousel', $slide, false)) {
        print_error(get_string('error_failed_update', 'block_carrousel'));
        die;
    }
}

function block_carrousel_process_form_submition($slide, $fromform) {
    global $DB, $CFG;

    $context = CONTEXT_BLOCK::instance($slide->blockid);

    $slide->title = (isset($fromform->title)) ? $fromform->title : '' ;
    $slide->buttontext = (isset($fromform->buttontext)) ? $fromform->buttontext : '' ; 
    $slide->buttonurl = (isset($fromform->buttonurl)) ? $fromform->buttonurl : '' ;  
    $slide->cohorts = (isset($fromform->cohorts)) ? $fromform->cohorts : '' ;  ;
    $slide->textcolor = (isset($fromform->textcolor)) ? $fromform->textcolor : 'white' ;

    if (!isset($slide->id)) {
        if(!($slide->id = $DB->insert_record('block_carrousel', $slide))) {
            print_error(get_string('error_failed_insert', 'block_carrousel'));
            die;
        } 
    }

    $slide = file_postupdate_standard_filemanager(
        $slide, 
        'private',
        [
            'subdirs'        => 0, 
            'maxbytes'       => 50000000, 
            'maxfiles'       => 1,
            'accepted_types' => ['.png', '.jpg', '.gif']
        ], 
        $context,
        'block_carrousel',
        'private',
        $slide->id
    );

    $files = get_file_storage()->get_area_files(
        $context->id,
        'block_carrousel',
        'private',
        $slide->id,
        '',
        false
    );

    $file = reset($files);

    if ($file) {
        $slide->imageurl = moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        )->out_as_local_url();
    }

    if (!$DB->update_record('block_carrousel', $slide, false)) {
        print_error('Failed to update slide');
        die;
    }

    return $slide;
}
