<?php

// Edit course reminder settings.

require_once(dirname(__FILE__).'/../config.php');
require_once($CFG->dirroot.'/course/reminders_form.php');
require_once($CFG->libdir.'/reminderlib.php');
require_once($CFG->libdir.'/completionlib.php');

// Reminder we are currently editing.
$id = optional_param('id', 0, PARAM_INT); // Optional as id doesn't exist until the reminder is created.
$courseid = required_param('courseid', PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT); // Optional until the user deletes a reminder.

if ($courseid) {
    if($courseid == SITEID){
        // Don't allow editing of the site course.
        print_error('noeditsite', 'totara_coursecatalog');
    } else if (!$course = $DB->get_record('course', array('id' => $courseid))) {
        print_error('error:courseidincorrect', 'totara_core');
    }

    $coursecontext = context_course::instance($course->id);

    require_login($course->id);
    require_capability('moodle/course:managereminders', $coursecontext);
} else {
    require_login();
    print_error('error:courseidorcategory', 'totara_coursecatalog');
}

// Build the params for set_url so we can return to the page properly.
$params = array ('courseid' => $courseid);
if ($id) $params['id'] = $id;
if ($delete) $params['delete'] = $delete;

$PAGE->set_url('/course/reminders.php', $params);
$PAGE->set_context($coursecontext);
$PAGE->set_pagelayout('admin');

// Get all course reminders
$reminders = get_course_reminders($course->id);

// Check if we are deleting any reminders
if ($delete) {
    // Check reminder exists
    if (in_array($id, array_keys($reminders))) {
        $reminder = $reminders[$id];
    } else {
        redirect($CFG->wwwroot.'/course/reminders.php?courseid='.$course->id);
    }

    // Make sure we have a session key.
    require_sesskey();

    // Delete reminder
    $reminder->deleted = 1;
    $reminder->update();

    \totara_core\event\reminder_deleted::create_from_reminder($reminder)->trigger();

    $PAGE->set_title(get_string('editcoursereminders', 'totara_coursecatalog'));
    $PAGE->set_heading($course->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('deletedreminder', 'totara_coursecatalog', format_string($reminder->title)));
    echo $OUTPUT->continue_button(new moodle_url('/course/reminders.php', array('courseid' => $course->id)));
    echo $OUTPUT->footer();
    exit();
}

if (in_array($id, array_keys($reminders))) {
    // Edit a specific reminder.
    $reminder = $reminders[$id];
} else if (count($reminders) && $id === 0) {
    // No reminder selected so use the first one.
    $reminder = reset($reminders);
} else {
    // Create a new reminder.
    $reminder = new reminder();
    $reminder->courseid = $course->id;
}

// Load all form data
$formdata = $reminder->get_form_data();

// First create the form
$reminderform = new reminder_edit_form('reminders.php', compact('course', 'reminder'));
$reminderform->set_data($formdata);

// Process current action
if ($reminderform->is_cancelled()){
    redirect($CFG->wwwroot.'/course/view.php?id='.$course->id);
} else if ($data = $reminderform->get_data()) {
    $transaction = $DB->start_delegated_transaction();
    $config = array(
        'tracking' => $data->tracking,
        'requirement' => $data->requirement
    );
    // A special case hack for escalations to ensure we record when the escalation dontsend value is changed.
    if (!empty($reminder->id) && isset($formdata->escalationdontsend) !== isset($data->escalationdontsend)) {
        // The escalation setting has changed, record the time this changed.
        // We'll need this when sending escalation reminders.
        $config['escalationmodified'] = time();
    }

    // Create the reminder object
    $reminder->timemodified = time();
    $reminder->modifierid = $USER->id;
    $reminder->deleted = '0';
    $reminder->title = $data->title;
    $reminder->type = 'completion';
    $reminder->config = serialize($config);
    $reminder->timecreated = $reminder->timecreated ? $reminder->timecreated : $reminder->timemodified;

    if (empty($reminder->id)) {
        if (!$reminder->insert()) {
            print_error('error:createreminder', 'totara_coursecatalog');
        }
        \totara_core\event\reminder_created::create_from_reminder($reminder)->trigger();
    } else {
        if (!$reminder->update()) {
            print_error('error:updatereminder', 'totara_coursecatalog');
        }
        \totara_core\event\reminder_updated::create_from_reminder($reminder)->trigger();
    }

    // Create the messages
    foreach (array('invitation', 'reminder', 'escalation') as $mtype) {
        $nosend = "{$mtype}dontsend";
        $p = "{$mtype}period";
        $sm = "{$mtype}skipmanager";
        $s = "{$mtype}subject";
        $m = "{$mtype}message";

        $message = new reminder_message(
            array(
                'reminderid'    => $reminder->id,
                'type'          => $mtype,
                'deleted'       => 0
            )
        );

        // Do some unique stuff for escalation messages
        if ($mtype === 'escalation') {
            if (!empty($data->$nosend)) {
                // Delete any existing message
                if ($message->id) {
                    $message->deleted = 1;

                    if (!$message->update()) {
                        print_error('error:deletereminder', 'totara_coursecatalog');
                    }
                }

                // Do not create a new one
                continue;
            }
        }

        $message->period = $data->$p;
        $message->copyto = isset($data->$sm) ? $data->$sm : '';
        $message->subject = $data->$s;
        $message->message = $data->$m;
        $message->deleted = 0;

        if (empty($message->id)) {
            if (!$message->insert()) {
                print_error('errro:createreminder', 'totara_coursecatalog');
            }
        } else {
            if (!$message->update()) {
                print_error('error:updatereminder', 'totara_coursecatalog');
            }
        }
    }
    $transaction->allow_commit();
    redirect(new moodle_url("/course/reminders.php", array('courseid' => $course->id, 'id' => $reminder->id)));
}

// Print the page

// Generate the button HTML
$buttonhtml = '';
if ($reminder->id > 0) {
    $options = array(
        'courseid'  => $course->id,
        'id'        => $reminder->id,
        'delete'    => 1,
        'sesskey'   => sesskey()
    );

    $buttonhtml = $OUTPUT->single_button(
        new moodle_url('/course/reminders.php', $options),
        get_string('deletereminder', 'totara_coursecatalog', format_string($reminder->title)),
        'get'
    );
}

$streditcoursereminders = get_string('editcoursereminders', 'totara_coursecatalog');
$title = $streditcoursereminders;
$fullname = $course->fullname;

$PAGE->navbar->add($streditcoursereminders);
$PAGE->set_button($buttonhtml);
$PAGE->set_title($title);
$PAGE->set_heading($fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading($streditcoursereminders);

// Show tabs
$tabs = array();
$shownotice = false;
foreach ($reminders as $r) {
    $tabs[] = new tabobject($r->id, $CFG->wwwroot.'/course/reminders.php?courseid='.$course->id.'&id='.$r->id, $r->title);
    if (!empty($CFG->reminder_maxtimesincecompletion)) {
        if ($r->has_message_with_period_greater_or_equal($CFG->reminder_maxtimesincecompletion)) {
            // If the period value for any messages is greater than or equal to the global setting, we should
            // warn the user.
            $shownotice = true;
        }
    }
}

$tabs[] = new tabobject('new', $CFG->wwwroot.'/course/reminders.php?courseid='.$course->id.'&id=-1', get_string('new', 'totara_coursecatalog'));

if (!$reminder->id) {
    $selected_tab = 'new';
} else {
    $selected_tab = $reminder->id;
}

// Check if there are any activites we can use.
$completion = new completion_info($course);

// If no current reminders or creating a new reminder, and no activities - do not show form.
if (!$completion->is_enabled()) {
    echo $OUTPUT->box(get_string('noactivitieswithcompletionenabled', 'totara_coursecatalog'), 'generalbox adminerror boxwidthwide boxaligncenter');
    echo $OUTPUT->continue_button(new moodle_url('/course/view.php', array('id' => $course->id)));
} else if (!get_coursemodules_in_course('feedback', $course->id)) {
    echo $OUTPUT->notification(get_string('nofeedbackactivities', 'totara_coursecatalog'), 'notifynotice');
    echo $OUTPUT->continue_button(new moodle_url('/course/view.php', array('id' => $course->id)));
} else {
    if ($shownotice) {
        echo $OUTPUT->notification(get_string('maxdayshigherwarning', 'feedback', $CFG->reminder_maxtimesincecompletion), 'notifymessage');
    }
    // Display tabs and show form.
    echo $OUTPUT->tabtree($tabs, $selected_tab);
    $reminderform->display();
}

echo $OUTPUT->footer();
