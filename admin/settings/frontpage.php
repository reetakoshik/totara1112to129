<?php

/**
 * This file defines everything related to frontpage
 *
 * @var admin_root $ADMIN
 */

// Not available during installation, or when the fulltree is not required.
if (!during_initial_install() && $ADMIN->fulltree) {
    $frontpagecontext = context_course::instance(SITEID);

    if ($hassiteconfig or has_capability('moodle/course:update', $frontpagecontext)) {

        // The frontpage page, this is hidden. In order to access it the user must search for the settings, or navigate to the
        // frontpage and select "Frontpage settings" from the "Frontpage administration" section of the settings block.
        $temp = new admin_settingpage('frontpagesettings', new lang_string('frontpagesettings','admin'), 'moodle/course:update', true, $frontpagecontext);

        $temp->add(new admin_setting_sitesettext('fullname', new lang_string('fullsitename'), '', NULL)); // no default
        $temp->add(new admin_setting_sitesettext('shortname', new lang_string('shortsitename'), '', NULL)); // no default
        $temp->add(new admin_setting_special_frontpagedesc());

        $temp->add(new admin_setting_sitesetcheckbox('numsections', new lang_string('sitesection'), new lang_string('sitesectionhelp','admin'), 1));
        $temp->add(new admin_setting_sitesetselect('newsitems', new lang_string('newsitemsnumber'), '', 3,
             array('0' => '0',
                   '1' => '1',
                   '2' => '2',
                   '3' => '3',
                   '4' => '4',
                   '5' => '5',
                   '6' => '6',
                   '7' => '7',
                   '8' => '8',
                   '9' => '9',
                   '10' => '10')));

        // front page default role
        $options = array(0=>new lang_string('none')); // roles to choose from
        $defaultfrontpageroleid = 0;
        $roles = role_fix_names(get_all_roles(), null, ROLENAME_ORIGINALANDSHORT);
        foreach ($roles as $role) {
            if (empty($role->archetype) or $role->archetype === 'guest' or $role->archetype === 'frontpage' or $role->archetype === 'student') {
                $options[$role->id] = $role->localname;
                if ($role->archetype === 'frontpage') {
                    $defaultfrontpageroleid = $role->id;
                }
            }
        }
        if ($defaultfrontpageroleid and (!isset($CFG->defaultfrontpageroleid) or $CFG->defaultfrontpageroleid)) {
            //frotpage role may not exist in old upgraded sites
            unset($options[0]);
        }
        $temp->add(new admin_setting_configselect('defaultfrontpageroleid', new lang_string('frontpagedefaultrole', 'admin'), '', $defaultfrontpageroleid, $options));
        $temp->add(new admin_setting_configcheckbox('frontpageaddcoursebutton', new lang_string('frontpageaddcoursebutton', 'admin'), '', 0));

        $ADMIN->add('navigationcat', $temp);
    }
}
