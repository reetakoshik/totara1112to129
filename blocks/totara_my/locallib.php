<?php
/**
 *
 * @param object $course
 * @param completion_info $completions
 * @uses $COMPLETION_STATUS complition/complition_complition.php
 * @return html_table_row
 */
function block_totara_courses_get_row($course,$completions,$mylearning=false){
    global $USER,$OUTPUT,$COMPLETION_STATUS;

    $id = $course->id;
    $name = $course->fullname;
    $status = array_key_exists($id, $completions) ? $completions[$id]->status : null;
    $statusicon = '';
    if($status != null and array_key_exists((int)$status, $COMPLETION_STATUS)){
        $statusicon = $COMPLETION_STATUS[(int)$status];
    }elseif($course->enablecompletion){
        $statusicon = $COMPLETION_STATUS[COMPLETION_STATUS_NOTYETSTARTED];
    }
    if(($mylearning&&$statusicon=='complete')){
        return false;
    }
    $completion = block_totara_courses_get_icon($statusicon);
    $url = new moodle_url('/course/view.php', array('id' => $id));
    $link = html_writer::link($url, $name, array('title' => $name));

    $cell0 = new html_table_cell(block_totara_courses_get_icon());
    $cell0->attributes['class'] = 'icon';
    $cell1 = new html_table_cell($link);
    $cell1->attributes['class'] = 'course';
    $cell2 = new html_table_cell($completion);
    $cell2->attributes['class'] = 'status';
    $cell3 = new html_table_cell($OUTPUT->single_button($url, get_string('gotocourse','block_totara_my'),'get'));
    $cell3->attributes['class'] = 'gotocourse';

    $cels = array($cell0,$cell1, $cell2, $cell3);
    $tablerows = right_to_left() ? array_reverse($cels) : $cels;

    return new html_table_row($tablerows);
}
/**
 *
 * @param int $limit
 * @return multitype:
 */
function block_totara_courses_get_courses($limit=3){
    global $DB,$USER,$CFG;
    $sql = "SELECT c.id,c.fullname,c.format, MAX(ra.timemodified),c.coursetype,c.icon,c.enablecompletion
            FROM {role_assignments} ra
            INNER JOIN {context} cx
            ON ra.contextid = cx.id
            AND cx.contextlevel = " . CONTEXT_COURSE . "
                    LEFT JOIN {course} c
                    ON cx.instanceid = c.id
                    WHERE ra.userid = ?
                    AND ra.roleid = ?
                    GROUP BY c.id, c.fullname,c.format,c.coursetype,c.icon,c.enablecompletion
                    ORDER BY MAX(ra.timemodified) DESC";

    $courses = $DB->get_records_sql($sql, array($USER->id, $CFG->learnerroleid),0,$limit);

    return $courses;
}
/**
 *
 * @param object $course
 * @return string
 */
function block_totara_courses_get_icon($image = 'courseicon'){
    global $OUTPUT;
    if(!$image){
       return '';
    }
    $alt = get_string($image, 'block_totara_my');
    if ($image == 'courseicon') {
        return $OUTPUT->pix_icon('/'.$image, $alt, 'block_totara_my', array('title' => $alt));
    } else {
        $html=html_writer::start_tag('span',array('class'=>'coursecompletionstatus'));
        $html.=html_writer::start_tag('span',array('class'=>'completion-'.$image,'title' => $alt));
        $html.='Completion'; // this won't be shown, but a content is required for css
        $html.=html_writer::end_tag('span');
        $html.=html_writer::end_tag('span');
        return $html;
    }
 }

/**
 *
 * @param object $program
 * @param completion_info $completions
 * @return html_table_row
 */
function block_totara_programs_get_row($program,$mylearning=false){
    global $USER,$OUTPUT;

    $id = $program->id;
    $name = $program->fullname;
    
    if ($program->program_timedue != -1) {
        $duedate = userdate($program->program_timedue, '%d.%m.%y');
    } else {
        $duedate =  get_string('programwithoutduedate', 'block_totara_my');
    }
    $completion = block_totara_programs_get_complition_icon($id,$USER->id);
    $url = new moodle_url('/totara/program/view.php', array('id' => $id));
    $link = html_writer::link($url, $name, array('title' => $name));

    $cell0 = new html_table_cell(block_totara_programs_get_icon());
    $cell0->attributes['class'] = 'icon';
    $cell1 = new html_table_cell($link);
    $cell1->attributes['class'] = 'program';
    $cell2 = new html_table_cell($completion);
    $cell2->attributes['class'] = 'duedate';
    $cell3 = new html_table_cell($duedate);
    $cell3->attributes['class'] = 'status';
    $cell4 = new html_table_cell($OUTPUT->single_button($url, get_string('gotoprogram','block_totara_my'),'get'));
    $cell4->attributes['class'] = 'gotoprogram';

    $cels = array($cell0, $cell1, $cell2, $cell3, $cell4);
    $tablerows = right_to_left() ? array_reverse($cels) : $cels;

    return new html_table_row($tablerows);
}
/**
 *
 * @param int $limit
 * @return multitype:
 */
function block_totara_programs_get_programs($limit=3){
    global $DB,$USER,$CFG;
    $sql="
    SELECT      programs.assignmentid,
                programs.id,
                programs.fullname ,
                programs.program_id,
                programs.program_icon,
                programs.program_mandatory,
                programs.program_recurring,
                programs.userid,
                programs.program_timedue,
                programs.completionstatus,
                programs.program_completion_status,
                programs.programid,
                pc.pc_timeallowed
    FROM (SELECT p.id,
                p.fullname ,
                p.id AS program_id,
                p.icon AS program_icon,
                prog_user_assignment.id AS program_mandatory,
                prog_user_assignment.assignmentid,
                p.id AS program_recurring,
                program_completion.userid AS userid,
                program_completion.timedue AS program_timedue,
                program_completion.status AS completionstatus,
                program_completion.status AS program_completion_status,
                p.id AS programid
                FROM {prog} p
                INNER JOIN {prog_completion} program_completion
                ON p.id = program_completion.programid AND program_completion.coursesetid = 0
                LEFT JOIN {prog_user_assignment} prog_user_assignment
                ON program_completion.programid = prog_user_assignment.programid AND program_completion.userid = prog_user_assignment.userid
                WHERE ((p.certifid=0 OR p.certifid IS NULL) AND p.visible = 1 AND p.category != 0 AND program_completion.userid = ? AND
                CASE WHEN prog_user_assignment.exceptionstatus IN (0,3)
                THEN 0 ELSE 1 END = 0)) programs
                LEFT JOIN (SELECT pc.programid AS pc_programid,SUM(pc.timeallowed) AS pc_timeallowed
                FROM {prog_courseset} pc
                GROUP BY pc.programid) pc ON pc.pc_programid=programs.id
    ";


    $programs = $DB->get_records_sql($sql, array($USER->id));
    $programs_arr=array();
    if(!empty($programs)){
        foreach ($programs as $program) {
            if(!array_key_exists($program->id,$programs_arr)){
                $programs_arr[$program->id]=$program;
            }
        }
    }
    return $programs_arr;
}
/**
 *
 * @param string $image
 * @return string
 */
function block_totara_programs_get_icon($image='programicon'){
    global $OUTPUT;
    $alt = get_string($image, 'block_totara_my');
    if ($image=='programicon') {
        return '<i class="fa fa-cube" aria-hidden="true"></i>';
    } else {
        $html=html_writer::start_tag('span',array('class'=>'coursecompletionstatus'));
        $html.=html_writer::start_tag('span',array('class'=>'completion-'.$image,'title' => $alt));
        $html.='Completion'; // this won't be shown, but a content is required for css
        $html.=html_writer::end_tag('span');
        $html.=html_writer::end_tag('span');
        return $html;
    }
}

function block_totara_programs_get_complition_icon($programid, $userid)
{
    global $DB;
    global $PAGE;

    $program = new program($programid);
    $overall_progress = $program->get_progress($userid);
    $prog_completion = $DB->get_record(
        'prog_completion',
        [
            'programid'   => $programid,
            'userid'      => $userid,
            'coursesetid' => 0
        ]
    );

    $renderer = $PAGE->get_renderer('totara_core');
    return $renderer->progressbar($overall_progress, 'medium', false, 'DEFAULTTOOLTIP');
}
/**
 *
 * @param int $limit
 * @return multitype:
 */
function block_totara_cert_get_certs($limit=3){
    global $DB,$USER,$CFG;
    if($CFG->dbtype == 'sqlsrv' || $CFG->dbtype == 'pgsql') {
        $sql="
       SELECT certc.*,p.*, program_completion.*, p.fullname,p.id AS pid
       FROM {certif_completion} certc
       LEFT JOIN {prog} p ON p.certifid=certc.certifid
       LEFT JOIN {prog_completion} program_completion
           ON p.id = program_completion.programid
           AND certc.userid = program_completion.userid
           AND program_completion.coursesetid = 0
       WHERE certc.userid=? AND certc.status<=?
       GROUP BY certc.userid,certc.certifid, certc.id, p.id, program_completion.id,certc.userid,certc.certifpath,certc.status,certc.renewalstatus,certc.timeexpires ,certc.timewindowopens,certc.timecompleted,certc.timemodified, p.category,  p.sortorder,  p.fullname,  p.shortname,  p.idnumber,  p.summary,  p.endnote,  p.visible, p.availablefrom,  p.availableuntil,  p.available,  p.timecreated,
  p.timemodified,  p.usermodified,  p.icon,  p.exceptionssent,  p.audiencevisible,  p.certifid,
   p.assignmentsdeferred, p.allowextensionrequests, program_completion.programid,         program_completion.userid, program_completion.coursesetid, program_completion.status, program_completion.timestarted, program_completion.timecreated, program_completion.timedue, program_completion.timecompleted, program_completion.organisationid, program_completion.positionid ORDER BY certc.timemodified DESC
   ";
    } else {
    $sql="
        SELECT certc.*,p.*, program_completion.*, p.fullname,p.id AS pid
        FROM {certif_completion} certc
        LEFT JOIN {prog} p ON p.certifid=certc.certifid
        LEFT JOIN {prog_completion} program_completion 
            ON p.id = program_completion.programid 
            AND certc.userid = program_completion.userid
            AND program_completion.coursesetid = 0
        WHERE certc.userid=? AND certc.status<=?
        GROUP BY certc.certifid, certc.id, p.id, program_completion.id
        ORDER BY certc.timemodified DESC
    ";
    }

    $certs = $DB->get_records_sql($sql, array($USER->id,CERTIFSTATUS_EXPIRED));
    $certs_arr=array();
    if(!empty($certs)){
        foreach ($certs as $cert) {
            if(!array_key_exists($cert->certifid,$certs_arr)){
                $certs_arr[$cert->certifid]=$cert;
            }
        }
    }
    return $certs_arr;
}
function block_totara_cert_get_row($cert,$mylearning=false){
    global $USER,$OUTPUT;

    $id = $cert->pid;
    $name = $cert->fullname;
    $completion = block_totara_cert_get_complition_icon($cert);
    $url = new moodle_url('/totara/program/view.php', array('id' => $id));
    $link = html_writer::link($url, $name, array('title' => $name));
   
     if ($cert->timedue != -1) {
        $duedate = userdate($cert->timedue, '%d.%m.%y');
    } else {
        $duedate =  get_string('certwithoutduedate', 'block_totara_my');
    }

    $cell0 = new html_table_cell(block_totara_programs_get_icon());//we use the same icon as program
    $cell0->attributes['class'] = 'icon';
    $cell1 = new html_table_cell($link);
    $cell1->attributes['class'] = 'program';
    $cell2 = new html_table_cell($completion);
    $cell2->attributes['class'] = 'status';
    $cell3 = new html_table_cell($duedate);
    $cell3->attributes['class'] = 'status';
    $cell4 = new html_table_cell($OUTPUT->single_button($url, get_string('gotocert','block_totara_my'),'get'));
    $cell4->attributes['class'] = 'gotoprogram';

    $cels = array($cell0,$cell1, $cell2, $cell3, $cell4);
    $tablerows = right_to_left() ? array_reverse($cels) : $cels;

    return new html_table_row($tablerows);
}
/**
 *
 * @param string $image
 * @return string
 */
function block_totara_cert_get_icon($image='programicon'){
    global $OUTPUT;
    $alt = get_string($image, 'block_totara_my');
    if($image=='programicon'){
        return $OUTPUT->pix_icon('/'.$image, $alt, 'block_totara_my', array('title' => $alt));
    }else{
        $html=html_writer::start_tag('span',array('class'=>'coursecompletionstatus'));
        $html.=html_writer::start_tag('span',array('class'=>'completion-'.$image,'title' => $alt));
        $html.='Completion'; // this won't be shown, but a content is required for css
        $html.=html_writer::end_tag('span');
        $html.=html_writer::end_tag('span');
        return $html;
    }
}

function block_totara_cert_get_complition_icon($cert)
{
    global $DB;
    global $USER;

    $program = $DB->get_record('prog', array('certifid' => $cert->certifid));

    return block_totara_programs_get_complition_icon($program->id, $USER->id);
}
/**
 *
 * @param object $course
 * @param completion_info $completions
 * @return html_table_row
 */
function block_totara_scheduled_facetoface_get_row($signup,$mylearning=false){
    global $USER,$OUTPUT;
    $facetofaceid = $signup->facetofaceid;
    $courseid = $signup->courseid;
    $name = $signup->name;
    $date = userdate($signup->timestart, '%d.%m.%y %H:%M');
    if ($signup->timefinish) {
        $finishdate = userdate($signup->timefinish, '%d.%m.%y');
    }
    else {
        $finishdate = get_string('facetofacewithoutduedate', 'block_totara_my');
    }
   
    
    if($date==userdate(time(), '%d.%m.%y')){
        $date=get_string('today');
    }
    $url = new moodle_url('/course/view.php', array('id' => $courseid));
    $link = html_writer::link($url, $name, array('title' => $name));

    $cell0 = new html_table_cell(block_totara_scheduled_facetoface_get_icon());
    $cell0->attributes['class'] = 'icon';
    $cell1 = new html_table_cell($link);
    $cell1->attributes['class'] = 'signup';
    $cell2 = new html_table_cell($date);
    $cell2->attributes['class'] = 'date';
    $cell3 = new html_table_cell($finishdate);
    $cell3->attributes['class'] = 'date';
    $cell4 = new html_table_cell($OUTPUT->single_button($url, get_string('gotofacetoface','block_totara_my'),'get'));
    $cell4->attributes['class'] = 'gotofacetoface';

    $cels = array($cell0,$cell1, $cell2, $cell3, $cell4);
    $tablerows = right_to_left() ? array_reverse($cels) : $cels;

    return new html_table_row($tablerows);
}
/**
 *
 * @param int $limit
 * @return multitype:
 */
function block_totara_scheduled_facetoface_get_facetofaces($limit=3){
    global $DB,$USER,$CFG;
    $time = time();
    // Get all Face-to-face signups from the DB
    $sql = "SELECT d.id, c.id as courseid, c.fullname AS coursename, f.name,
            f.id as facetofaceid, s.id as sessionid, 0,
            d.timestart, d.timefinish, d.sessiontimezone, su.userid, ss.statuscode as status
            FROM {facetoface_sessions_dates} d
            JOIN {facetoface_sessions} s ON s.id = d.sessionid
            JOIN {facetoface} f ON f.id = s.facetoface
            JOIN {facetoface_signups} su ON su.sessionid = s.id
            JOIN {facetoface_signups_status} ss ON su.id = ss.signupid AND ss.superceded = 0
            JOIN {course} c ON f.course = c.id
            WHERE su.userid = ? AND ss.statuscode >=".MDL_F2F_STATUS_BOOKED."
            ORDER BY d.timestart ";
    $signups_list = $DB->get_records_sql($sql, array($USER->id));
    $signups=array();
    foreach ($signups_list as $signup) {
        if($signup->timestart>=$time){
            $signups[]=$signup;
        }else if(userdate($time, '%d.%m.%y')==userdate($signup->timestart, '%d.%m.%y')){
            $signups[]=$signup;
        }
    }
//     $show_location = add_location_info($signups);
    $futuresessions = false;
    if ($signups and count($signups > 0)) {
        $groupeddates = group_session_dates($signups);
        // out of the results separate out the future sessions
        $futuresessions = future_session_dates($groupeddates);
    }
    return $futuresessions;
}
/**
 *
 * @param string $image
 * @return string
 */
function block_totara_scheduled_facetoface_get_icon($image='facetofaceicon'){
    global $OUTPUT;
    $alt = get_string($image, 'block_totara_my');

    return '<i class="fa fa-cube" aria-hidden="true"></i>';
    
    // return $OUTPUT->pix_icon('/'.$image, $alt, 'block_totara_my', array('title' => $alt));
}

?>
