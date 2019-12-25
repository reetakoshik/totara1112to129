<?php

if ($hassiteconfig) { // speedup for non-admins, add all caps used on this page

    $optionalsubsystems->add(new admin_setting_configcheckbox('enableoutcomes', new lang_string('enableoutcomes', 'grades'), new lang_string('enableoutcomes_help', 'grades'), 0));
    $optionalsubsystems->add(new admin_setting_configcheckbox('usecomments', new lang_string('enablecomments', 'admin'), new lang_string('configenablecomments', 'admin'), 1));
    $optionalsubsystems->add(new admin_setting_configtext('commentsperpage', new lang_string('commentsperpage', 'admin'), '', 15, PARAM_INT));

    $optionalsubsystems->add(new admin_setting_configcheckbox('usetags', new lang_string('usetags','admin'),new lang_string('configusetags', 'admin'), '1'));

    $optionalsubsystems->add(new admin_setting_configcheckbox('enablenotes', new lang_string('enablenotes', 'notes'), new lang_string('configenablenotes', 'notes'), 1));

    $optionalsubsystems->add(new admin_setting_configcheckbox('enableportfolios', new lang_string('enabled', 'portfolio'), new lang_string('enableddesc', 'portfolio'), 0));

    $optionalsubsystems->add(new admin_setting_configcheckbox('enablewebservices', new lang_string('enablewebservices', 'admin'), new lang_string('configenablewebservices', 'admin'), 0));

    $optionalsubsystems->add(new admin_setting_configcheckbox('messaging', new lang_string('messaging', 'admin'), new lang_string('configmessaging','admin'), 1));

    $optionalsubsystems->add(new admin_setting_configcheckbox('messaginghidereadnotifications', new lang_string('messaginghidereadnotifications', 'admin'), new lang_string('configmessaginghidereadnotifications','admin'), 0));

    $options = array(DAYSECS=>new lang_string('secondstotime86400'), WEEKSECS=>new lang_string('secondstotime604800'), 2620800=>new lang_string('nummonths', 'moodle', 1), 15724800=>new lang_string('nummonths', 'moodle', 6),0=>new lang_string('never'));
    $optionalsubsystems->add(new admin_setting_configselect('messagingdeletereadnotificationsdelay', new lang_string('messagingdeletereadnotificationsdelay', 'admin'), new lang_string('configmessagingdeletereadnotificationsdelay', 'admin'), 604800, $options));

    $optionalsubsystems->add(new admin_setting_configcheckbox('messagingallowemailoverride', new lang_string('messagingallowemailoverride', 'admin'), new lang_string('configmessagingallowemailoverride','admin'), 0));

    $optionalsubsystems->add(new admin_setting_configcheckbox('enablestats', new lang_string('enablestats', 'admin'), new lang_string('configenablestats', 'admin'), 0));

    $optionalsubsystems->add(new admin_setting_configcheckbox('enablerssfeeds', new lang_string('enablerssfeeds', 'admin'), new lang_string('configenablerssfeeds', 'admin'), 0));

    // Totara: blogs are disabled in Totara by default since 2.9.2.
    $optionalsubsystems->add(new admin_setting_configcheckbox('enableblogs', new lang_string('enableblogs', 'admin'), new lang_string('configenableblogs', 'admin'), 0));

    $options = array('off'=>new lang_string('off', 'mnet'), 'strict'=>new lang_string('on', 'mnet'));
    $optionalsubsystems->add(new admin_setting_configselect('mnet_dispatcher_mode', new lang_string('net', 'mnet'), new lang_string('configmnet', 'mnet'), 'off', $options));

    // Conditional activities: completion and availability
    $optionalsubsystems->add(new admin_setting_configcheckbox('enablecompletion',
        new lang_string('enablecompletion','completion'),
        new lang_string('configenablecompletion', 'completion'), 1));

    $options = array(
        1 => get_string('completionactivitydefault', 'completion'),
        0 => get_string('completion_none', 'completion')
    );
    $optionalsubsystems->add(new admin_setting_configselect('completiondefault', new lang_string('completiondefault', 'completion'),
            new lang_string('configcompletiondefault', 'completion'), 1, $options));

    $optionalsubsystems->add($checkbox = new admin_setting_configcheckbox('enableavailability',
            new lang_string('enableavailability', 'availability'),
            new lang_string('enableavailability_desc', 'availability'), 1));
    $checkbox->set_affects_modinfo(true);

    // Course RPL
    $optionalsubsystems->add(new admin_setting_configcheckbox('enablecourserpl', new lang_string('enablecourserpl', 'completion'), new lang_string('configenablecourserpl', 'completion'), 1));

    // Module RPLs
    // Get module list
    if ($modules = $DB->get_records("modules")) {
        // Default to all
        $defaultmodules = array();

        foreach ($modules as $module) {
            $strmodulename = new lang_string("modulename", "$module->name");
            // Deal with modules which are lacking the language string
            if ($strmodulename == '[[modulename]]') {
                $strmodulename = $module->name;
            }
            $modulebyname[$module->id] = $strmodulename;
            $defaultmodules[$module->id] = 1;
        }
        asort($modulebyname, SORT_LOCALE_STRING);

        $optionalsubsystems->add(new admin_setting_configmulticheckbox(
                        'enablemodulerpl',
                        new lang_string('enablemodulerpl', 'completion'),
                        new lang_string('configenablemodulerpl', 'completion'),
                        $defaultmodules,
                        $modulebyname
        ));
    }

    $optionalsubsystems->add(new admin_setting_configcheckbox('enableplagiarism', new lang_string('enableplagiarism','plagiarism'), new lang_string('configenableplagiarism','plagiarism'), 0));

    $optionalsubsystems->add(new admin_setting_configcheckbox('enablebadges', new lang_string('enablebadges', 'badges'), new lang_string('configenablebadges', 'badges'), 1));

/* Totara: moved to experimental section
    $optionalsubsystems->add(new admin_setting_configcheckbox('enableglobalsearch', new lang_string('enableglobalsearch', 'admin'),
        new lang_string('enableglobalsearch_desc', 'admin'), 0, 1, 0));
*/

    // Report caching and global restrictions.
    $optionalsubsystems->add(new admin_setting_configcheckbox('enablereportcaching', new lang_string('enablereportcaching','totara_reportbuilder'), new lang_string('configenablereportcaching','totara_reportbuilder'), 0));
    $optionalsubsystems->add(new admin_setting_configcheckbox('enableglobalrestrictions', new lang_string('enableglobalrestrictions', 'totara_reportbuilder'), new lang_string('enableglobalrestrictions_desc', 'totara_reportbuilder'), 0));

    // Audience visibility.
    $defaultenhanced = 0;
    $setting = new admin_setting_configcheckbox('audiencevisibility',
        new lang_string('enableaudiencevisibility', 'totara_cohort'),
        new lang_string('configenableaudiencevisibility', 'totara_cohort'),
        $defaultenhanced);
    $setting->set_updatedcallback('totara_rb_purge_ignored_reports');
    $optionalsubsystems->add($setting);

    $defaultenhanced = 0;
    $setting = new admin_setting_configcheckbox('enableconnectserver',
        new lang_string('enableconnectserver', 'totara_connect'),
        new lang_string('enableconnectserver_desc', 'totara_connect'),
        $defaultenhanced);
    $setting->set_updatedcallback('totara_rb_purge_ignored_reports');
    $optionalsubsystems->add($setting);

    // Content Marketplaces.
    $optionalsubsystems->add(new admin_setting_configcheckbox('enablecontentmarketplaces',
            new lang_string('enablecontentmarketplaces', 'totara_contentmarketplace'),
            new lang_string('enablecontentmarketplacesdesc', 'totara_contentmarketplace'), 1));

    // Dynamic Appraisals.
    $optionalsubsystems->add(new admin_setting_configcheckbox('dynamicappraisals',
        new lang_string('dynamicappraisals', 'totara_core'),
        new lang_string('configdynamicappraisals', 'totara_core'), 1));

    $optionalsubsystems->add(new admin_setting_configcheckbox('dynamicappraisalsautoprogress',
        new lang_string('dynamicappraisalsautoprogress', 'totara_core'),
        new lang_string('configdynamicappraisalsautoprogress', 'totara_core'), 1));

    // Show Hierarchy shortcodes.
    $optionalsubsystems->add(new admin_setting_configcheckbox('showhierarchyshortnames',
            new lang_string('showhierarchyshortnames', 'totara_hierarchy'),
            new lang_string('configshowhierarchyshortnames', 'totara_hierarchy'), 0));

    // Program extension request setting.
    $optionalsubsystems->add(new admin_setting_configcheckbox('enableprogramextensionrequests',
        new lang_string('enableprogramextensionrequests', 'totara_core'),
        new lang_string('enableprogramextensionrequests_help', 'totara_core'), 1));

    $optionalsubsystems->add(new admin_setting_configcheckbox('enablelegacyprogramassignments',
        new lang_string('enablelegacyprogramassignments', 'totara_program'),
        new lang_string('enablelegacyprogramassignments_help', 'totara_program'), 0));

    // If adding or removing the settings below, be sure to update the array in
    // totara_advanced_features_list() in totara/core/totara.php.

    $optionalsubsystems->add(new totara_core_admin_setting_feature('enablegoals',
        new lang_string('enablegoals', 'totara_hierarchy'),
        new lang_string('configenablegoals', 'totara_hierarchy'),
        TOTARA_SHOWFEATURE));

    $optionalsubsystems->add(new totara_core_admin_setting_feature('enablecompetencies',
        new lang_string('enablecompetencies', 'totara_hierarchy'),
        new lang_string('enablecompetencies_desc', 'totara_hierarchy'),
        TOTARA_SHOWFEATURE));

    $optionalsubsystems->add(new totara_core_admin_setting_feature('enableappraisals',
        new lang_string('enableappraisals', 'totara_appraisal'),
        new lang_string('configenableappraisals', 'totara_appraisal'),
        TOTARA_SHOWFEATURE));

    $optionalsubsystems->add(new totara_core_admin_setting_feature('enablefeedback360',
        new lang_string('enablefeedback360', 'totara_feedback360'),
        new lang_string('configenablefeedback360', 'totara_feedback360'),
        TOTARA_SHOWFEATURE));

    $optionalsubsystems->add(new totara_core_admin_setting_feature('enablelearningplans',
        new lang_string('enablelearningplans', 'totara_plan'),
        new lang_string('configenablelearningplans', 'totara_plan'),
        TOTARA_SHOWFEATURE,
        array('totara_menu_reset_all_caches', 'totara_rb_purge_ignored_reports',
            array('enrol_totara_learningplan_util', 'feature_setting_updated_callback'))));

    $optionalsubsystems->add(new totara_core_admin_setting_feature('enableprograms',
        new lang_string('enableprograms', 'totara_program'),
        new lang_string('configenableprograms', 'totara_program'),
        TOTARA_SHOWFEATURE,
        array('totara_menu_reset_all_caches', 'totara_rb_purge_ignored_reports',
            array('enrol_totara_program_util', 'feature_setting_updated_callback'))));

    $optionalsubsystems->add(new totara_core_admin_setting_feature('enablecertifications',
        new lang_string('enablecertifications', 'totara_program'),
        new lang_string('configenablecertifications', 'totara_program'),
        TOTARA_SHOWFEATURE));

    $optionalsubsystems->add(new totara_core_admin_setting_feature('enabletotaradashboard',
        new lang_string('enabletotaradashboard', 'totara_dashboard'),
        new lang_string('configenabletotaradashboard', 'totara_dashboard'),
        TOTARA_SHOWFEATURE));

    $optionalsubsystems->add(new totara_core_admin_setting_feature('enablereportgraphs',
        new lang_string('enablereportgraphs', 'totara_reportbuilder'),
        new lang_string('enablereportgraphsinfo', 'totara_reportbuilder'),
        TOTARA_SHOWFEATURE));

    $optionalsubsystems->add(new totara_core_admin_setting_feature('enablerecordoflearning',
        new lang_string('enablerecordoflearning', 'totara_plan'),
        new lang_string('enablerecordoflearninginfo', 'totara_plan'),
        TOTARA_SHOWFEATURE));

    $optionalsubsystems->add(new totara_core_admin_setting_feature('enablepositions',
        new lang_string('enablepositions', 'totara_hierarchy'),
        new lang_string('enablepositions_desc', 'totara_hierarchy'),
        TOTARA_SHOWFEATURE));

    $optionalsubsystems->add(new totara_core_admin_setting_feature('enablemyteam',
        new lang_string('enableteam', 'totara_core'),
        new lang_string('enableteam_desc', 'totara_core'),
        TOTARA_SHOWFEATURE));

    $defaultenhanced = 0;
    $setting = new admin_setting_configcheckbox('enableprogramcompletioneditor',
        new lang_string('enableprogramcompletioneditor', 'totara_program'),
        new lang_string('enableprogramcompletioneditor_desc', 'totara_program'),
        $defaultenhanced);
    $setting->set_updatedcallback('totara_rb_purge_ignored_reports');
    $optionalsubsystems->add($setting);

    $optionalsubsystems->add(new admin_setting_configcheckbox('totara_job_allowmultiplejobs',
        new lang_string('setting:allowmultiplejobs', 'totara_job'),
        new lang_string('setting:allowmultiplejobs_description', 'totara_job'),
        1));

    $optionalsubsystems->add(new admin_setting_configcheckbox('enablesitepolicies',
        new lang_string('enablesitepolicies', 'tool_sitepolicy'),
        new lang_string('configenablesitepolicies', 'tool_sitepolicy'), 0));

    // Catalog type.
    $defaultcatalogtype = 'totara';
    $options = [
        'moodle' => get_string('catalog_old', 'totara_catalog'),
        'enhanced' => get_string('catalog_enhanced', 'totara_catalog'),
        'totara' => get_string('catalog_totara', 'totara_catalog'),
    ];
    $setting = new admin_setting_configselect(
        'catalogtype',
        new lang_string('catalogtype', 'totara_catalog'),
        new lang_string('configcatalogtype', 'totara_catalog'),
        $defaultcatalogtype,
        $options
    );
    $setting->set_updatedcallback('totara_menu_reset_all_caches');
    $optionalsubsystems->add($setting);
}
