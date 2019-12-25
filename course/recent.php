<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Display all recent activity in a flexible way
 *
 * @copyright 1999 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package course
 */

require_once('../config.php');
require_once('lib.php');
require_once('recent_form.php');

$id = required_param('id', PARAM_INT);

$PAGE->set_url('/course/recent.php', array('id'=>$id));
$PAGE->set_pagelayout('report');

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error("That's an invalid course id");
}

require_login($course);
$context = context_course::instance($course->id);

\core\event\recent_activity_viewed::create(array('context' => $context))->trigger();

$lastlogin = time() - COURSE_MAX_RECENT_PERIOD;
if (!isguestuser() and !empty($USER->lastcourseaccess[$COURSE->id])) {
    if ($USER->lastcourseaccess[$COURSE->id] > $lastlogin) {
        $lastlogin = $USER->lastcourseaccess[$COURSE->id];
    }
}

$param = new stdClass();
$param->user   = 0;
$param->modid  = 'all';
$param->group  = 0;
$param->sortby = 'default';
$param->date   = $lastlogin;
$param->id     = $COURSE->id;

$mform = new recent_form();
$mform->set_data($param);
if ($formdata = $mform->get_data()) {
    $param = $formdata;
}

$userinfo = get_string('allparticipants');
$dateinfo = get_string('alldays');

if (!empty($param->user)) {
    if (!$u = $DB->get_record('user', array('id'=>$param->user))) {
        print_error("That's an invalid user!");
    }
    $userinfo = fullname($u);
}

$strrecentactivity = get_string('recentactivity');
$PAGE->navbar->add($strrecentactivity, new moodle_url('/course/recent.php', array('id'=>$course->id)));
$PAGE->navbar->add($userinfo);
$PAGE->set_title("$course->shortname: $strrecentactivity");
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($course->fullname) . ": $userinfo", 2);

$mform->display();

$modinfo = get_fast_modinfo($course);
$modnames = get_module_types_names();

if (has_capability('moodle/course:viewhiddensections', $context)) {
    $hiddenfilter = "";
} else {
    $hiddenfilter = "AND cs.visible = 1";
}
$sections = array();
foreach ($modinfo->get_section_info_all() as $i => $section) {
    if (!empty($section->uservisible)) {
        $sections[$i] = $section;
    }
}

if ($param->modid === 'all') {
    // ok

} else if (strpos($param->modid, 'mod/') === 0) {
    $modname = substr($param->modid, strlen('mod/'));
    if (array_key_exists($modname, $modnames) and file_exists("$CFG->dirroot/mod/$modname/lib.php")) {
        $filter = $modname;
    }

} else if (strpos($param->modid, 'section/') === 0) {
    $sectionid = substr($param->modid, strlen('section/'));
    if (isset($sections[$sectionid])) {
        $sections = array($sectionid=>$sections[$sectionid]);
    }
    // TOTARA CHANGES.
    $filtersectionid = $sections[$sectionid]->id;
    // END TOTARA CHANGES.

} else if (is_numeric($param->modid)) {
    $sectionnum = $modinfo->cms[$param->modid]->sectionnum;
    $filter_modid = $param->modid;
    $sections = array($sectionnum => $sections[$sectionnum]);
}

$activities = array();
$index = 0;

foreach ($sections as $sectionnum => $section) {

    $activity = new stdClass();
    $activity->type = 'section';
    if ($section->section > 0) {
        $activity->name = get_section_name($course, $section);
    } else {
        $activity->name = '';
    }

    $activity->visible = $section->visible;
    $activities[$index++] = $activity;

    if (empty($modinfo->sections[$sectionnum])) {
        continue;
    }

    foreach ($modinfo->sections[$sectionnum] as $cmid) {
        $cm = $modinfo->cms[$cmid];

        if (!$cm->uservisible) {
            continue;
        }

        if (!empty($filter) and $cm->modname != $filter) {
            continue;
        }

        if (!empty($filter_modid) and $cmid != $filter_modid) {
            continue;
        }

        // TOTARA CHANGES add headings for all course modules as they can have structural changes.gs
        $activity = new stdClass();
        $activity->type    = 'activity';
        $activity->cmid    = $cmid;
        $activities[$index++] = $activity;
        // END TOTARA CHANGES.

        $libfile = "$CFG->dirroot/mod/$cm->modname/lib.php";

        if (file_exists($libfile)) {
            require_once($libfile);
            $get_recent_mod_activity = $cm->modname."_get_recent_mod_activity";

            if (function_exists($get_recent_mod_activity)) {
                $get_recent_mod_activity($activities, $index, $param->date, $course->id, $cmid, $param->user, $param->group);
            }
        }
    }
}

// TOTARA changes add structural changes to recent activity.

$sql = "SELECT id, action, contextid, timecreated, userid, other, courseid, objectid
        FROM {logstore_standard_log} log
       WHERE log.timecreated > :starttime
         AND log.courseid = :courseid
         AND (log.action = 'created'
            OR log.action = 'deleted'
            OR log.action = 'updated')
        AND log.target = 'course_module'";

$records = $DB->get_records_sql($sql, ['starttime' => $param->date, 'courseid' => $course->id]);

// Gets the names for the modules.
$sqlcreations = "SELECT log.id, log.other
                   FROM {logstore_standard_log} log
                      INNER JOIN (
                        SELECT max(timecreated) as maxtime
                          FROM {logstore_standard_log} l2
                         WHERE (l2.action = 'created'
                            OR l2.action = 'updated')
                            AND l2.target = 'course_module'
                      GROUP BY objectid) AS maxtimetable
                        ON maxtimetable.maxtime = log.timecreated
                     WHERE log.courseid = :courseid
                       AND (log.action = 'created'
                          OR log.action = 'updated')
                       AND log.target = 'course_module'
                       And log.timecreated = maxtimetable.maxtime
                     GROUP BY log.id, log.other";

$creations = $DB->get_records_sql($sqlcreations, array('courseid' => $course->id));

$names = array();
foreach ($creations as $creation) {
    $data = unserialize($creation->other);
    $names[$data['modulename'] . $data['instanceid']] = $data['name'];
}

// Add removed heading.
$struct_heading = new stdClass();
$struct_heading->name = get_string('courseremovedmodules');
$struct_heading->type = 'section';
$activities[] = $struct_heading;

foreach ($records as $record) {
    // Extract data.
    $context = context::instance_by_id($record->contextid, IGNORE_MISSING);
    if ($context && isset($modinfo->cms[$context->instanceid])) {
        $record->cm = $modinfo->cms[$context->instanceid];

        $modname = $record->cm->modname;
        $modfullname = $record->cm->modfullname;
        $name = $record->cm->name;
        $url = $record->cm->url;
        $cmid = $record->cm->id;
        $cmsectionid = $record->cm->section;
    } else {
        $otherdata = unserialize($record->other);

        $modname = isset($otherdata['modulename']) ? $otherdata['modulename'] : '';
        $modfullname =  isset($otherdata['modulename'])
            ? get_string('modulename', $otherdata['modulename'])
            : get_string('unknownname');

        // Prioritise most recent name rather than name saved with log.
        if (isset($names[$otherdata['modulename'] . $otherdata['instanceid']])) {
            $name = $names[$otherdata['modulename'] . $otherdata['instanceid']];
        } else if (isset($otherdata['name'])) {
            $name = $otherdata['name'];
        } else {
            $name = get_string('unknownname');
        }
        $url = '';
        $cmid = $record->objectid;
        $cmsectionid = 0;
    }

    // Filter.
    if (!empty($filter_modid) && $cmid != $filter_modid) {
        continue;
    }
    if (!empty($filter) && $modname != $filter) {
        continue;
    }
    if (!empty($param->user) && $record->userid != $param->user) {
        continue;
    }
    if (!empty($filtersectionid) && (empty($cmsectionid) || $cmsectionid != $filtersectionid)) {
        continue;
    }

    // Mutate data.
    switch ($record->action) {
        case 'created':
            $record->link = empty($url) ? null : $url;
            $record->text = format_string($name, true);
            $record->extratext = get_string('added', 'moodle', '');
            break;
        case 'updated':
            $record->link = empty($url) ? null : $url;
            $record->text = format_string($name, true);
            $record->extratext = get_string('updated', 'moodle', '');
            break;
        case 'deleted':
            $record->link = null;
            $record->text = format_string($name, true);
            $record->extratext = get_string('deletedactivity', 'moodle', '');
            break;
        default:
            break; // The action was not recognised.
    }
    $record->user = $DB->get_record('user', array('id' => $record->userid));
    $record->type = 'structural';
    $record->timestamp = $record->timecreated;
    unset($record->timecreated);

    // Add Data.
    if ($param->sortby == 'default') {
        $added = false;
        // Find the heading and add it under.
        foreach ($activities as $key => $activity) {
            if ($activity->type == 'activity' && isset($activity->cmid) && $activity->cmid == $cmid) {
                array_splice($activities, $key + 1, 0, [$record]);
                $added = true;
                break;
            }
        }
        // If heading does not exist then add it at the end which is under the deleted heading and also need to add a deleted heading.
        if (!$added) {
            if (!empty($modinfo->cms[$cmid])) {
                $cm = $modinfo->cms[$cmid];
                if (!$cm->has_view() || !$cm->uservisible) {
                    continue;
                }
            }
            $activity = new \stdClass();
            $activity->modname = $modname;
            $activity->modfullname = $modfullname;
            $activity->name = $name;
            $activity->type = 'activity';
            $activity->cmid = $cmid;

            $activities[] = $activity;
            $activities[] = $record;
        }
    } else {
        $activities[] = $record;
    }
}

// Sort ascending in subheadings.
if ($param->sortby == 'default') {
    $activitystart = -1;
    foreach ($activities as $key => $activity) {
        if ($activitystart != -1
            && ($activity->type == 'activity' || $activity->type == 'section')) {

            $array = array_slice($activities, $activitystart, ($key - $activitystart));
            usort($array, 'compare_activities_by_time_asc');
            array_splice($activities, $activitystart, ($key - $activitystart), $array);
            $activitystart = -1;
        }
        if ($activity->type == 'activity') {
            $activitystart = $key + 1;
        }
    }
    if ($activitystart != -1 && (count($activities) - 1) != $activitystart) {
        $array = array_slice($activities, $activitystart, (count($activities) - $activitystart));
        usort($array, 'compare_activities_by_time_asc');
        array_splice($activities, $activitystart, (count($activities) - $activitystart), $array);
    }
}

// End TOTARA.

switch ($param->sortby) {
    case 'datedesc' : usort($activities, 'compare_activities_by_time_desc'); break;
    case 'dateasc'  : usort($activities, 'compare_activities_by_time_asc'); break;
    case 'default'  :
    default         : $param->sortby = 'default';
}

if (!empty($activities)) {

    $newsection   = true;
    $lastsection  = '';
    $newinstance  = true;
    $lastinstance = '';
    $inbox        = false;

    $section = 0;

    $activity_count = count($activities);
    $viewfullnames  = array();

    foreach ($activities as $key => $activity) {

        if ($activity->type == 'section') {
            if ($param->sortby != 'default') {
                continue; // no section if ordering by date
            }
            if ($activity_count == ($key + 1) or $activities[$key+1]->type == 'section') {
            // peak at next activity.  If it's another section, don't print this one!
            // this means there are no activities in the current section
                continue;
            }
        }

        if (($activity->type == 'section') && ($param->sortby == 'default')) {
            if ($inbox) {
                echo $OUTPUT->box_end();
                echo $OUTPUT->spacer(array('height'=>30, 'br'=>true)); // should be done with CSS instead
            }
            echo $OUTPUT->box_start();
            if (strval($activity->name) !== '') {
                echo html_writer::tag('h2', $activity->name);
            }
            $inbox = true;

        } else if ($activity->type == 'activity') {
            if ($param->sortby == 'default') {
                // TOTARA changes.
                // For when the module has being deleted.
                if (isset($modinfo->cms[$activity->cmid])) {
                    $cm = $modinfo->cms[$activity->cmid];

                    if ($cm->visible) {
                        $class = '';
                    } else {
                        $class = 'dimmed';
                    }
                    $name        = format_string($cm->name);
                    $modfullname = $modnames[$cm->modname];

                    $image = $OUTPUT->pix_icon('icon', $modfullname, $cm->modname, array('class' => 'icon smallicon'));
                    $link = html_writer::link(new moodle_url("/mod/$cm->modname/view.php",
                        array("id" => $cm->id)), $name, array('class' => $class));
                } else {
                    $modfullname = $activity->modfullname;
                    $image = $OUTPUT->pix_icon('icon', $activity->modfullname, $activity->modname, ['class' => 'icon smallicon']);
                    $link = html_writer::tag('span', format_string($activity->name), array('class' => $class));
                }
                // END TOTARA.
                echo html_writer::tag('h3', "$image $modfullname $link");
           }

        } else {

            if (!$inbox) {
                echo $OUTPUT->box_start();
                $inbox = true;
            }

            if (isset($activity->cmid) && !isset($viewfullnames[$activity->cmid])) {
                $cm_context = context_module::instance($activity->cmid);
                $viewfullnames[$activity->cmid] = has_capability('moodle/site:viewfullnames', $cm_context);
            }

            // TOTARA CHANGES.
            if (isset($activity->text)) {
                // Try new way if it doesnt work try old way
                print_recent_activity_note($activity->timestamp,
                    $activity->user,
                    $activity->text,
                    $activity->link,
                    false,
                    isset($activity->cmid) && isset($viewfullnames[$activity->cmid]) && $viewfullnames[$activity->cmid],
                    $course->id,
                    isset($activity->extratext) ? $activity->extratext : '');
            } else {
                $print_recent_mod_activity = $activity->type.'_print_recent_mod_activity';

                if (function_exists($print_recent_mod_activity)) {
                    debugging("The function {$print_recent_mod_activity} have being deprected since totara 11");
                    $print_recent_mod_activity($activity,
                        $course->id,
                        $param->sortby != 'default',
                        $modnames,
                        $viewfullnames[$activity->cmid]);
                }
            }
            // END TOTARA CHANGES.
        }
    }
    if ($inbox) {
        echo $OUTPUT->box_end();
    }
} else {
    echo html_writer::tag('h3', get_string('norecentactivity'), array('class' => 'mdl-align'));
}

echo $OUTPUT->footer();
