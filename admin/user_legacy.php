<?php

/*
 * @deprecated from Totara 10.0.
 *
 * The file is a working copy of the original Moodle implementation
 * of the Browse List of Users report. This has been replaced by a
 * report builder implementation at admin/user.php.
 */

// This script must be included from admin/user.php only!
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/authlib.php');
require_once($CFG->dirroot.'/user/filters/lib.php');
require_once($CFG->dirroot.'/user/lib.php');

$sort         = optional_param('sort', 'name', PARAM_ALPHANUM);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 30, PARAM_INT);        // how many per page
$ru           = optional_param('ru', '2', PARAM_INT);            // show remote users
$lu           = optional_param('lu', '2', PARAM_INT);            // show local users
$acl          = optional_param('acl', '0', PARAM_INT);           // id of user to tweak mnet ACL (requires $access)

admin_externalpage_setup('editusers');

$context = context_system::instance();
$site = get_site();

if (!has_capability('moodle/user:update', $context) and !has_capability('moodle/user:delete', $context)) {
    print_error('nopermissions', 'error', '', 'edit/delete users');
}

$stredit   = get_string('edit');
$strdelete = get_string('delete');
$strundelete = get_string('undelete', 'totara_core');
$strdeletecheck = get_string('deletecheck');
$strshowallusers = get_string('showallusers');
$strsuspend = get_string('suspenduser', 'admin');
$strunsuspend = get_string('unsuspenduser', 'admin');
$strunlock = get_string('unlockaccount', 'admin');
$strconfirm = get_string('confirm');

$returnurl = new moodle_url('/admin/user.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage, 'page'=>$page));

// The $user variable is also used outside of these if statements.
$user = null;

// force exclude deleted to true if user not permitted to see deleted users
if (has_capability('totara/core:seedeletedusers', $context)) {
    $excludedeleted = false;
} else {
    $excludedeleted = true;
}

// create the user filter form
$ufiltering = new user_filtering();
echo $OUTPUT->header();

// Carry on with the user listing
$extracolumns = get_extra_user_fields($context);
// Get all user name fields as an array.
$allusernamefields = get_all_user_name_fields(false, null, null, null, true);
$columns = array_merge($allusernamefields, $extracolumns, array('city', 'country', 'lastaccess'));

foreach ($columns as $column) {
    $string[$column] = get_user_field_name($column);
    if ($sort != $column) {
        $columnicon = "";
        if ($column == "lastaccess") {
            $columndir = "DESC";
        } else {
            $columndir = "ASC";
        }
    } else {
        $columndir = $dir == "ASC" ? "DESC":"ASC";
        if ($column == "lastaccess") {
            $columnicon = ($dir == "ASC") ? "sort-desc" : "sort-asc";
        } else {
            $columnicon = ($dir == "ASC") ? "sort-asc" : "sort-desc";
        }
        $columnicon = $OUTPUT->flex_icon($columnicon);

    }
    $$column = "<a href=\"user.php?sort=$column&amp;dir=$columndir\">".$string[$column]."</a>$columnicon";
}

// We need to check that alternativefullnameformat is not set to '' or language.
// We don't need to check the fullnamedisplay setting here as the fullname function call further down has
// the override parameter set to true.
$fullnamesetting = $CFG->alternativefullnameformat;
// If we are using language or it is empty, then retrieve the default user names of just 'firstname' and 'lastname'.
if ($fullnamesetting == 'language' || empty($fullnamesetting)) {
    // Set $a variables to return 'firstname' and 'lastname'.
    $a = new stdClass();
    $a->firstname = 'firstname';
    $a->lastname = 'lastname';
    // Getting the fullname display will ensure that the order in the language file is maintained.
    $fullnamesetting = get_string('fullnamedisplay', null, $a);
}

// Order in string will ensure that the name columns are in the correct order.
$usernames = order_in_string($allusernamefields, $fullnamesetting);
$fullnamedisplay = array();
foreach ($usernames as $name) {
    // Use the link from $$column for sorting on the user's name.
    $fullnamedisplay[] = ${$name};
}
// All of the names are in one column. Put them into a string and separate them with a /.
$fullnamedisplay = implode(' / ', $fullnamedisplay);
// If $sort = name then it is the default for the setting and we should use the first name to sort by.
if ($sort == "name") {
    // Use the first item in the array.
    $sort = reset($usernames);
}

list($extrasql, $params) = $ufiltering->get_sql_filter();
$users = get_users_listing($sort, $dir, $page*$perpage, $perpage, '', '', '',
        $extrasql, $params, $context, $excludedeleted);
$usercount = get_users(false, '', false, null, 'firstname ASC', '', '', '', '', '*', '', null, $excludedeleted);
$usersearchcount = get_users(false, '', false, null, "", '', '', '', '', '*', $extrasql, $params, $excludedeleted);

if ($extrasql !== '') {
    echo $OUTPUT->heading("$usersearchcount / $usercount ".get_string('users'));
    $usercount = $usersearchcount;
} else {
    echo $OUTPUT->heading("$usercount ".get_string('users'));
}

$strall = get_string('all');

$baseurl = new moodle_url('/admin/user.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage));
echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);

flush();


if (!$users) {
    $match = array();
    echo $OUTPUT->heading(get_string('nousersfound'));

    $table = NULL;

} else {

    $countries = get_string_manager()->get_list_of_countries(false);
    if (empty($mnethosts)) {
        $mnethosts = $DB->get_records('mnet_host', null, 'id', 'id,wwwroot,name');
    }

    foreach ($users as $key => $user) {
        if (isset($countries[$user->country])) {
            $users[$key]->country = $countries[$user->country];
        }
    }
    if ($sort == "country") {  // Need to resort by full country name, not code
        foreach ($users as $user) {
            $susers[$user->id] = $user->country;
        }
        asort($susers);
        foreach ($susers as $key => $value) {
            $nusers[] = $users[$key];
        }
        $users = $nusers;
    }

    $table = new html_table();
    $table->head = array ();
    $table->colclasses = array();
    $table->head[] = $fullnamedisplay;
    $table->attributes['class'] = 'admintable generaltable';
    foreach ($extracolumns as $field) {
        $table->head[] = ${$field};
    }
    $table->head[] = $city;
    $table->head[] = $country;
    $table->head[] = $lastaccess;
    $table->head[] = get_string('edit');
    $table->colclasses[] = 'centeralign';
    $table->head[] = "";
    $table->colclasses[] = 'centeralign';

    $table->id = "users";
    foreach ($users as $user) {
        $buttons = array();
        $lastcolumn = '';
        $actionurl = new moodle_url('/user/action.php', array('id' => $user->id, 'returnurl' => $returnurl->out_as_local_url(false)));

        // delete button
        if (has_capability('moodle/user:delete', $context)) {
            if (is_mnet_remote_user($user) or $user->id == $USER->id or is_siteadmin($user)) {
                // no deleting of self, mnet accounts or admins allowed
            } else {
                $buttons[] = html_writer::link(new moodle_url($actionurl, array('action'=>'delete')), $OUTPUT->flex_icon('delete', array('alt' => $strdelete)), array('title'=>$strdelete));
            }
        }

        // suspend button
        if (has_capability('moodle/user:update', $context)) {
            if (is_mnet_remote_user($user)) {
                // mnet users have special access control, they can not be deleted the standard way or suspended
                $accessctrl = 'allow';
                if ($acl = $DB->get_record('mnet_sso_access_control', array('username'=>$user->username, 'mnet_host_id'=>$user->mnethostid))) {
                    $accessctrl = $acl->accessctrl;
                }
                $changeaccessto = ($accessctrl == 'deny' ? 'allow' : 'deny');
                $buttons[] = " (<a href=\"?acl={$user->id}&amp;accessctrl=$changeaccessto&amp;sesskey=".sesskey()."\">".get_string($changeaccessto, 'mnet') . " access</a>)";

            } else {
                if ($user->suspended) {
                    $buttons[] = html_writer::link(new moodle_url($actionurl, array('action'=>'unsuspend', 'sesskey'=>sesskey())), $OUTPUT->flex_icon('show', array('alt' => $strunsuspend)), array('title'=>$strunsuspend));
                } else {
                    if ($user->id == $USER->id or is_siteadmin($user)) {
                        // no suspending of admins or self!
                    } else {
                        $buttons[] = html_writer::link(new moodle_url($actionurl, array('action'=>'suspend', 'sesskey'=>sesskey())), $OUTPUT->flex_icon('hide', array('alt' => $strsuspend)), array('title'=>$strsuspend));
                    }
                }

                if (login_is_lockedout($user)) {
                    $buttons[] = html_writer::link(new moodle_url($actionurl, array('action'=>'unlock', 'sesskey'=>sesskey())), $OUTPUT->flex_icon('unlock', array('alt' => $strunlock)), array('title'=>$strunlock));
                }
            }
        }

        // edit button
        if (has_capability('moodle/user:update', $context)) {
            // prevent editing of admins by non-admins
            if (is_siteadmin($USER) or !is_siteadmin($user)) {
                $buttons[] = html_writer::link(new moodle_url('/user/editadvanced.php', array('id'=>$user->id, 'course'=>$site->id, 'returnurl'=>$returnurl->out_as_local_url(false))), $OUTPUT->flex_icon('settings', array('alt' => $stredit)), array('title'=>$stredit));
            }
        }

        // the last column - confirm or mnet info
        if (is_mnet_remote_user($user)) {
            // all mnet users are confirmed, let's print just the name of the host there
            if (isset($mnethosts[$user->mnethostid])) {
                $lastcolumn = get_string($accessctrl, 'mnet').': '.$mnethosts[$user->mnethostid]->name;
            } else {
                $lastcolumn = get_string($accessctrl, 'mnet');
            }

        } else if ($user->confirmed == 0) {
            if (has_capability('moodle/user:update', $context)) {
                $lastcolumn = html_writer::link(new moodle_url($actionurl, array('action'=>'confirm', 'sesskey'=>sesskey())), $strconfirm);
            } else {
                $lastcolumn = "<span class=\"dimmed_text\">".get_string('confirm')."</span>";
            }
        }

        // Don't show any buttons, except undelete for deleted users, unless we do full delete now.
        if ($user->deleted and !is_mnet_remote_user($user)) {
            $buttons = array();
            $buttons[] = html_writer::link(new moodle_url($actionurl, array('action'=>'undelete')),
                $OUTPUT->flex_icon('recycle', array('alt' => $strundelete)),
                array('title' => $strundelete));
            if ($CFG->authdeleteusers !== 'partial' and $user->email and validate_email($user->email)) {
                $buttons[] = html_writer::link(new moodle_url($actionurl, array('action'=>'delete')),
                    $OUTPUT->flex_icon('delete', array('alt' => $strdelete)),
                    array('title' => $strdelete));
            }
            $lastcolumn = '';
        }

        if ($user->lastaccess) {
            $strlastaccess = format_time(time() - $user->lastaccess);
        } else {
            $strlastaccess = get_string('never');
        }
        $fullname = fullname($user, true);

        $row = array ();
        $row[] = "<a href=\"../user/view.php?id=$user->id&amp;course=$site->id\">$fullname</a>";
        foreach ($extracolumns as $field) {
            $row[] = $user->{$field};
        }
        $row[] = $user->city;
        $row[] = $user->country;
        $row[] = $strlastaccess;
        if ($user->suspended || $user->deleted) {
            foreach ($row as $k=>$v) {
                $row[$k] = html_writer::tag('span', $v, array('class'=>'usersuspended'));
            }
        }
        $row[] = implode(' ', $buttons);
        $row[] = $lastcolumn;
        $table->data[] = $row;
    }
}

// add filters
$ufiltering->display_add();
$ufiltering->display_active();

if (!empty($table)) {
    echo html_writer::start_tag('div', array('class'=>'no-overflow'));
    echo html_writer::table($table);
    echo html_writer::end_tag('div');
    echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);
}
if (has_capability('moodle/user:create', $context)) {
    $url = new moodle_url('/user/editadvanced.php', array('id' => -1, 'returnto'=>'allusers'));
    echo $OUTPUT->single_button($url, get_string('addnewuser'), 'get');
}

echo $OUTPUT->footer();
