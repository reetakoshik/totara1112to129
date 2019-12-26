<?php

// searches for admin settings

require_once('../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/admin/admin_settings_search_form.php');

redirect_if_major_upgrade_required();

$query = trim(optional_param('query', '', PARAM_NOTAGS));  // Search string

$context = context_system::instance();
$PAGE->set_context($context);

admin_externalpage_setup('search', '', array('query' => $query)); // now hidden page
require_capability('moodle/site:config', $context); //Totara: this page is really for admins only!

if (moodle_needs_upgrading()) {
    redirect(new moodle_url('/admin/index.php'));
}

$adminroot = admin_get_root(); // need all settings here
$adminroot->search = $query; // So we can reference it in search boxes later in this invocation
$statusmsg = '';
$errormsg  = '';
$focus = '';

// now we'll deal with the case that the admin has submitted the form with changed settings
if ($data = data_submitted() and confirm_sesskey() and isset($data->action) and $data->action == 'save-settings') {
    require_capability('moodle/site:config', $context);
    $count = admin_write_settings($data);
    if (!empty($adminroot->errors)) {
        $errormsg = get_string('errorwithsettings', 'admin');
        $firsterror = reset($adminroot->errors);
        $focus = $firsterror->id;
    } else {
        // No errors. Did we change any setting? If so, then redirect with success.
        if ($count) {
            redirect($PAGE->url, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
        }
        redirect($PAGE->url);
    }
}

// and finally, if we get here, then there are matching settings and we have to print a form
// to modify them
echo $OUTPUT->header($focus);

echo $OUTPUT->heading(get_string('administrationsite'));

if ($errormsg !== '') {
    echo $OUTPUT->notification($errormsg);

} else if ($statusmsg !== '') {
    echo $OUTPUT->notification($statusmsg, 'notifysuccess');
}

$form = new admin_settings_search_form();
$form->display();
echo '<hr>';
if ($query) {
    echo admin_search_settings_html($query);
}

echo $OUTPUT->footer();
