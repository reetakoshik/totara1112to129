<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara
 * @subpackage totara_core
 */

/**
 * Fix incorrectly upgraded text columns.
 */
function totara_core_fix_old_upgraded_mssql() {
    global $CFG, $DB, $OUTPUT;

    if ($DB->get_dbfamily() !== 'mssql') {
        return;
    }

    $dbman = $DB->get_manager();

    // Changing the default of field laststatus on table backup_courses to 5.
    $table = new xmldb_table('backup_courses');
    $field = new xmldb_field('laststatus', XMLDB_TYPE_CHAR, '1', null, XMLDB_NOTNULL, null, '5', 'lastendtime');
    $dbman->change_field_default($table, $field);

    // All these text columns should be NOT NULL.
    $candidates = array(
        'appraisal_event_message' => array('content'),
        'assign' => array('intro'),
        'badge' => array('message', 'messagesubject'),
        'badge_issued' => array('message', 'uniquehash'),
        'facetoface_notification' => array('body'),
        'facetoface_notification_tpl' => array('body'),
        'feedback_value_history' => array('value'),
        'goal_scale_values' => array('name'),
        'qtype_randomsamatch_options' => array('correctfeedback', 'partiallycorrectfeedback', 'incorrectfeedback'),
        'config' => array('value'),
        'config_plugins' => array('value'),
        'course_request' => array('summary', 'reason'),
        'event' => array('description', 'name'),
        'cache_filters' => array('rawtext'),
        'cache_text' => array('formattedtext'),
        'log_queries' => array('sqltext'),
        'scale' => array('scale', 'description'),
        'scale_history' => array('scale', 'description'),
        'role' => array('description'),
        'user_info_field' => array('name'),
        'user_info_data' => array('data'),
        'question_categories' => array('info'),
        'question' => array('questiontext', 'generalfeedback'),
        'question_answers' => array('answer', 'feedback'),
        'question_hints' => array('hint'),
        'question_states' => array('answer'),
        'question_sessions' => array('manualcomment'),
        'mnet_host' => array('public_key'),
        'mnet_rpc' => array('help', 'profile'),
        'events_queue' => array('eventdata'),
        'grade_outcomes' => array('fullname'),
        'grade_outcomes_history' => array('fullname'),
        'tag_correlation' => array('correlatedtags'),
        'cache_flags' => array('value'),
        'comments' => array('content'),
        'blog_external' => array('url'),
        'backup_controllers' => array('controller'),
        'profiling' => array('data'),
        'qtype_match_options' => array('correctfeedback', 'partiallycorrectfeedback', 'incorrectfeedback'),
        'qtype_match_subquestions' => array('questiontext'),
        'question_multianswer' => array('sequence'),
        'qtype_multichoice_options' => array('correctfeedback', 'partiallycorrectfeedback', 'incorrectfeedback'),
        'assignment' => array('intro'),
        'assignment_submissions' => array('submissioncomment'),
        'book_chapters' => array('content'),
        'chat' => array('intro'),
        'chat_messages' => array('message'),
        'chat_messages_current' => array('message'),
        'choice' => array('intro'),
        'data' => array('intro'),
        'data_fields' => array('description'),
        'feedback' => array('intro', 'page_after_submit'),
        'feedback_item' => array('presentation'),
        'feedback_value' => array('value'),
        'feedback_valuetmp' => array('value'),
        'forum' => array('intro'),
        'forum_posts' => array('message'),
        'glossary' => array('intro'),
        'glossary_entries' => array('definition'),
        'label' => array('intro'),
        'lesson' => array('conditions'),
        'lesson_pages' => array('contents'),
        'lti' => array('toolurl'),
        'lti_types' => array('baseurl'),
        'quiz' => array('intro', 'questions'),
        'quiz_attempts' => array('layout'),
        'quiz_feedback' => array('feedbacktext'),
        'resource_old' => array('alltext', 'popup'),
        'scorm' => array('intro'),
        'scorm_scoes' => array('launch'),
        'scorm_scoes_data' => array('value'),
        'scorm_scoes_track' => array('value'),
        'survey' => array('intro'),
        'survey_answers' => array('answer1', 'answer2'),
        'survey_analysis' => array('notes'),
        'url' => array('externalurl'),
        'wiki_pages' => array('cachedcontent'),
        'wiki_versions' => array('content'),
        'block_rss_client' => array('title', 'description'),
        'block_quicklinks' => array('title'),
        'block_totara_stats' => array('data'),
        'mnetservice_enrol_courses' => array('summary'),
        'course_info_field' => array('fullname'),
        'errorlog' => array('details'),
        'comp_scale_values' => array('name'),
        'comp_template' => array('fullname'),
        'dp_priority_scale' => array('description'),
        'prog_message' => array('mainmessage'),
        'tool_customlang' => array('original'),
    );

    $totalcount = 0;
    foreach ($candidates as $table => $columns) {
        if (!$dbman->table_exists($table)) {
            unset($candidates[$table]);
            continue;
        }
        foreach ($columns as $column) {
            $totalcount++;
        }
    }

    $pbar = new progress_bar('mssqlfixextnulls', 500, true);

    $i = 0;
    foreach ($candidates as $table => $columns) {
        $existingcolumns = $DB->get_columns($table);
        foreach ($columns as $column) {
            if (isset($existingcolumns[$column])) {
                /** @var database_column_info $existing */
                $existing = $existingcolumns[$column];
                if ($existing->meta_type === 'X' and !$existing->not_null) {
                    $DB->execute("UPDATE {{$table}} SET $column = '' WHERE $column IS NULL");
                    $xmldbtable = new xmldb_table($table);
                    $xmldbcolumn = new xmldb_field($column, XMLDB_TYPE_TEXT, 'big', null, XMLDB_NOTNULL);
                    $dbman->change_field_notnull($xmldbtable, $xmldbcolumn);
                }
            }
            $i++;
            $pbar->update($i, $totalcount, "Fixed text columns in MS SQL database - $i/$totalcount.");
        }
    }
}

/**
 * Re-add changes to course completion for Totara
 *
 * Although these exist in lib/db/upgrade.php, anyone upgrading from Moodle 2.2.2 or above
 * would already have a higher version number so we need to apply them again:
 *
 * 1. when totara first is installed (to fix for anyone upgrading from 2.2.2+)
 * 2. in a totara core upgrade (to fix for anyone who has already upgraded from 2.2.2+)
 *
 * These changes will only be applied if they haven't been run previously so it's okay
 * to call this function multiple times
 */
function totara_readd_course_completion_changes() {
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    // Define index useridcourse (unique) to be added to course_completions
    $table = new xmldb_table('course_completions');
    $index = new xmldb_index('useridcourse', XMLDB_INDEX_UNIQUE, array('userid', 'course'));

    // Conditionally launch add index useridcourse
    if (!$dbman->index_exists($table, $index)) {
        // Clean up all instances of duplicate records
        // Add indexes to prevent new duplicates
        totara_upgrade_course_completion_remove_duplicates(
            'course_completions',
            array('userid', 'course'),
            array('timecompleted', 'timestarted', 'timeenrolled')
        );

        $dbman->add_index($table, $index);
    }

    // Define index useridcoursecriteraid (unique) to be added to course_completion_crit_compl
    $table = new xmldb_table('course_completion_crit_compl');
    $index = new xmldb_index('useridcoursecriteraid', XMLDB_INDEX_UNIQUE, array('userid', 'course', 'criteriaid'));

    // Conditionally launch add index useridcoursecriteraid
    if (!$dbman->index_exists($table, $index)) {
        totara_upgrade_course_completion_remove_duplicates(
            'course_completion_crit_compl',
            array('userid', 'course', 'criteriaid'),
            array('timecompleted')
        );

        $dbman->add_index($table, $index);
    }

    // Define index coursecriteratype (unique) to be added to course_completion_aggr_methd
    $table = new xmldb_table('course_completion_aggr_methd');
    $index = new xmldb_index('coursecriteriatype', XMLDB_INDEX_UNIQUE, array('course', 'criteriatype'));

    // Conditionally launch add index coursecriteratype
    if (!$dbman->index_exists($table, $index)) {
        totara_upgrade_course_completion_remove_duplicates(
            'course_completion_aggr_methd',
            array('course', 'criteriatype')
        );

        $dbman->add_index($table, $index);
    }

    require_once("{$CFG->dirroot}/completion/completion_completion.php");

    /// Define field status to be added to course_completions
    $table = new xmldb_table('course_completions');
    $field = new xmldb_field('status', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 0, 'reaggregate');
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);

        // Get all records
        $rs = $DB->get_recordset_sql('SELECT * FROM {course_completions}');
        foreach ($rs as $record) {
            // Update status column
            $status = completion_completion::get_status($record);
            if ($status) {
                $status = constant('COMPLETION_STATUS_'.strtoupper($status));
            }

            $record->status = $status;

            if (!$DB->update_record('course_completions', $record)) {
                break;
            }
        }
        $rs->close();
    }

}

/**
 * This function finds duplicate records (based on combinations of fields that should be unique)
 * and then programmatically generated a "most correct" version of the data, update and removing
 * records as appropriate
 *
 * It was originally a part of Moodle, but removed in Moodle 3.1 as part of their upgrade cleanup.
 * We copied it here as this is still potentially needed.
 * It will be removed from here when we clean up our installation process.
 *
 * Thanks to Dan Marsden for help
 *
 * @param   string  $table      Table name
 * @param   array   $uniques    Array of field names that should be unique
 * @param   array   $fieldstocheck  Array of fields to generate "correct" data from (optional)
 * @return  void
 */
function totara_upgrade_course_completion_remove_duplicates($table, $uniques, $fieldstocheck = array()) {
    global $DB;
    // Find duplicates
    $sql_cols = implode(', ', $uniques);
    $sql = "SELECT {$sql_cols} FROM {{$table}} GROUP BY {$sql_cols} HAVING (count(id) > 1)";
    $duplicates = $DB->get_recordset_sql($sql, array());
    // Loop through duplicates
    foreach ($duplicates as $duplicate) {
        $pointer = 0;
        // Generate SQL for finding records with these duplicate uniques
        $sql_select = implode(' = ? AND ', $uniques).' = ?'; // builds "fieldname = ? AND fieldname = ?"
        $uniq_values = array();
        foreach ($uniques as $u) {
            $uniq_values[] = $duplicate->$u;
        }
        $sql_order = implode(' DESC, ', $uniques).' DESC'; // builds "fieldname DESC, fieldname DESC"
        // Get records with these duplicate uniques
        $records = $DB->get_records_select(
            $table,
            $sql_select,
            $uniq_values,
            $sql_order
        );
        // Loop through and build a "correct" record, deleting the others
        $needsupdate = false;
        $origrecord = null;
        foreach ($records as $record) {
            $pointer++;
            if ($pointer === 1) { // keep 1st record but delete all others.
                $origrecord = $record;
            } else {
                // If we have fields to check, update original record
                if ($fieldstocheck) {
                    // we need to keep the "oldest" of all these fields as the valid completion record.
                    // but we want to ignore null values
                    foreach ($fieldstocheck as $f) {
                        if ($record->$f && (($origrecord->$f > $record->$f) || !$origrecord->$f)) {
                            $origrecord->$f = $record->$f;
                            $needsupdate = true;
                        }
                    }
                }
                $DB->delete_records($table, array('id' => $record->id));
            }
        }
        if ($needsupdate || isset($origrecord->reaggregate)) {
            // If this table has a reaggregate field, update to force recheck on next cron run
            if (isset($origrecord->reaggregate)) {
                $origrecord->reaggregate = time();
            }
            $DB->update_record($table, $origrecord);
        }
    }
}

/**
 * Uninstall Moodle plugins removed in 3.1 and 3.2 and Totara 10 plugins.
 */
function totara_core_upgrade_delete_moodle_plugins() {
    global $DB;

    // NOTE: this should match \core_plugin_manager::is_deleted_standard_plugin() data.

    $deleteplugins = array(
        // Moodle GDPR stuff.
        'tool_dataprivacy',
        'tool_policy',

        // Totara 10.0 removals.
        'theme_kiwifruitresponsive',
        'theme_customtotararesponsive',
        'theme_standardtotararesponsive',

        // Moodle merge removals - we do not want these!
        'block_lp',
        'editor_tinymce',
        'report_competency',
        'theme_boost',
        'theme_bootstrapbase',
        'theme_canvas',
        'theme_clean',
        'theme_more',
        'tinymce_ctrlhelp', 'tinymce_managefiles', 'tinymce_moodleemoticon', 'tinymce_moodleimage',
        'tinymce_moodlemedia', 'tinymce_moodlenolink', 'tinymce_pdw', 'tinymce_spellchecker', 'tinymce_wrap',
        'tool_cohortroles',
        'tool_installaddon',
        'tool_lp',
        'tool_lpimportcsv',
        'tool_lpmigrate',
        'tool_mobile',

        // Upstream Moodle 3.1 removals.
        'webservice_amf',

        // Upstream Moodle 3.2 removals.
        'auth_radius',
        'report_search',
        'repository_alfresco',
    );

    foreach ($deleteplugins as $deleteplugin) {
        list($plugintype, $pluginname) = explode('_', $deleteplugin, 2);
        $dir = core_component::get_plugin_directory($plugintype, $pluginname);
        if ($dir and file_exists("$dir/version.php")) {
            // This should not happen, this is not a standard distribution!
            continue;
        }
        if (!get_config($deleteplugin, 'version')) {
            // Not installed.
            continue;
        }
        if ($deleteplugin === 'tool_dataprivacy') {
            if ($DB->record_exists('tool_dataprivacy_request', array())) {
                continue;
            }
        }
        if ($deleteplugin === 'tool_policy') {
            if ($DB->record_exists('tool_policy', array())) {
                continue;
            }
        }
        if ($deleteplugin === 'auth_radius') {
            if ($DB->record_exists('user', array('auth' => 'radius', 'deleted' => 0))) {
                // Do not uninstall if users with this auth exist!
                continue;
            }
        }
        if ($deleteplugin === 'tool_mobile') {
            $service = $DB->get_record('external_services', array('shortname' => 'moodle_mobile_app'));
            if ($service) {
                $DB->delete_records('external_services_functions', array('externalserviceid' => $service->id));
                $DB->delete_records('external_services_users', array('externalserviceid' => $service->id));
                $DB->delete_records('external_tokens', array('externalserviceid' => $service->id));
                $DB->delete_records('external_services_functions', array('externalserviceid' => $service->id));
                $DB->delete_records('external_services', array('id' => $service->id));
            }
        }
        if ($deleteplugin === 'editor_tinymce') {
            $editors = get_config('core', 'texteditors');
            if ($editors) {
                $editors = explode(',', $editors);
                $editors = array_flip($editors);
                unset($editors['tinymce']);
                set_config('texteditors', implode(',', array_keys($editors)));
            }
            // NOTE: there is no need to update user preference, if editor is not found the default is used instead.
        }
        uninstall_plugin($plugintype, $pluginname);
    }

    // Delete all removed settings that are not linked to the plugins above.
    unset_config('disableupdatenotifications');
    unset_config('disableupdateautodeploy');
    unset_config('updateautodeploy');
    unset_config('updateautocheck');
    unset_config('updatenotifybuilds');
    unset_config('updateminmaturity');
    unset_config('updatenotifybuilds');
}

/**
 * Moodle developers incorrectly introduced multiple broken course backups areas,
 * they were always supposed to live in course context only!!!
 *
 * @internal
 * @param int $contextid
 */
function totara_core_migrate_bogus_course_backup_area($contextid) {
    global $SITE, $DB;
    $frontpagecontext = context_course::instance($SITE->id);
    $fs = get_file_storage();

    // Make sure we do all or nothing to prevent duplicate problems on rerun.
    $trans = $DB->start_delegated_transaction();
    $files = $fs->get_area_files($contextid, 'backup', 'course');
    foreach ($files as $file) {
        $newfile = array('contextid' => $frontpagecontext->id);
        if ($fs->file_exists($frontpagecontext->id, 'course', 'backup', 0, $file->get_filepath(), $file->get_filename())) {
            // The backup files must be unique, use some weird prefix to make sure we do not override anything.
            $newfile['filename'] = 'ctx' . $contextid . '_' . $file->get_filename();
        }
        $fs->create_file_from_storedfile($newfile, $file);
    }
    $fs->delete_area_files($contextid, 'backup', 'course');
    $trans->allow_commit();
}

/**
 * Move contents of all non-functional backup areas to frontpage and drop them.
 */
function totara_core_migrate_bogus_course_backup_areas() {
    global $DB;

    $syscontext = context_system::instance();
    totara_core_migrate_bogus_course_backup_area($syscontext->id);

    $sql = "SELECT DISTINCT c.id
              FROM {files} f
              JOIN {context} c ON c.id = f.contextid
             WHERE c.contextlevel <> :courselevel";
    $contexids = $DB->get_records_sql($sql, array('courselevel' => CONTEXT_COURSE));
    foreach ($contexids as $contextid => $unused) {
        totara_core_migrate_bogus_course_backup_area($contextid);
    }
}

/**
 * Makes sure that context related tables are up to date.
 *
 * NOTE: this must be called before upgrade starts executing.
 */
function totara_core_upgrade_context_tables() {
    global $DB;

    $dbman = $DB->get_manager();

    $updated = false;

    // Add parentid to context table.
    $table = new xmldb_table('context');
    $field = new xmldb_field('parentid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'depth');
    $index = new xmldb_index('parentid', XMLDB_INDEX_NOTUNIQUE, array('parentid'));
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
        $updated = true;
    }
    if (!$dbman->index_exists($table, $index)) {
        $dbman->add_index($table, $index);
    }

    // Remove fake context_temp table, real temp table is used in Totara.
    $table = new xmldb_table('context_temp');
    if ($dbman->table_exists($table)) {
        $dbman->drop_table($table);
    }

    // Add context_map table to be used for flattening context tree.
    $table = new xmldb_table('context_map');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('parentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('childid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
    $table->add_index('parentid_childid_ix', XMLDB_INDEX_UNIQUE, array('parentid', 'childid'));
    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
        $updated = true;
    }

    if ($updated) {
        // Add parentid to context and build context_map.
        $systemcontext = context_system::instance();
        $systemcontext->mark_dirty();
        upgrade_set_timeout(7200);
        \context_helper::build_all_paths(true, false);
    }
}

/**
 * The logic here is copied from blocks_add_default_course_blocks which is used during install.
 * The block API must be functioning, but to be safe only use the structures used there.
 *
 * @see blocks_add_default_course_blocks()
 */
function totara_core_migrate_frontpage_display() {
    global $CFG, $DB;

    $tryupgrade = true;

    if ($tryupgrade && !class_exists('moodle_page')) {
        // We need to be able to use moodle_page.
        $tryupgrade = false;
    }

    if ($tryupgrade && !defined('SITEID')) {
        // We don't know the siteid.
        $tryupgrade = false;
    }

    $course = $DB->get_record('course', ['id' => SITEID]);
    if ($tryupgrade && !$course) {
        // We don't have the site course.
        $tryupgrade = false;
    }

    if ($tryupgrade) {
        $blocks = [];

        if (!empty(get_config('core', 'courseprogress'))) {
            // Add an instance of block_course_progress_report
            $blocks[] = 'course_progress_report';
        }

        $frontpage = get_config('core', 'frontpageloggedin');
        $frontpagelayout = explode(',', $frontpage);

        foreach ($frontpagelayout as $widget) {
            switch ($widget) {
                // Display the main part of the front page.
                case '0': // FRONTPAGENEWS
                    // Add an instance of the news items block.
                    $blocks[] = 'news_items';
                    break;

                case '5': // FRONTPAGEENROLLEDCOURSELIST
                    // Add course_list
                    $blocks[] = 'course_list';
                    break;

                case '6': // FRONTPAGEALLCOURSELIST
                case '2': // FRONTPAGECATEGORYNAMES
                case '4': // FRONTPAGECATEGORYCOMBO
                    // Add frontpage_combolist block.
                    $blocks[] = 'frontpage_combolist';
                    break;
                case '7': // FRONTPAGECOURSESEARCH
                    // Add course_search block
                    $blocks[] = 'course_search';
                    break;
            }
        }

        if (!empty($blocks)) {
            $blocks = array_unique($blocks);

            foreach ($blocks as $key => $name) {
                // Ensure the block is visible, it needs to be so that we can add it.
                if (!$DB->record_exists('block', ['name' => $name])) {
                    // Likely the block is not installed yet.
                    $file = $CFG->dirroot . '/blocks/' . $name . '/version.php';
                    if (file_exists($file)) {
                        // OK, it's going to be installed later on.
                        set_config('frontpage_migration', 1, 'block_' . $name);
                    }
                    unset($blocks[$key]); // Remove it, we can't add it yet.
                }
            }

            $page = new moodle_page();
            $page->set_course($course);
            $page->blocks->add_blocks(['main' => $blocks], 'site-index');
        }
    }
}

/**
 * Migrate block title to the new way of storing it
 */
function totara_core_migrate_old_block_titles() {
    global $DB;

    $dbman = $DB->get_manager();

    $table = new xmldb_table('block_instances');
    $field = new xmldb_field('common_config', XMLDB_TYPE_TEXT);

    // Only proceed if the field doesn't exist.
    if ($dbman->field_exists($table, $field)) {
        return;
    }

    $dbman->add_field($table, $field);

    $instances = $DB->get_records_sql("SELECT id, configdata, blockname FROM {block_instances} WHERE configdata <> ''");

    foreach ($instances as $id => $instance) {

        // We upgrade border for all blocks and title only for those which had user-configurable title

        $title_upgrade_elegible = [
            'html',
            'totara_featured_links',
            'totara_report_graph',
            'totara_report_table',
            'totara_quick_links',
            'totara_program_completion',
            'tags',
            'tag_youtube',
            'tag_flickr',
            'rss_client',
            'mentees',
            'glossary_random',
            'blog_tags',
        ];

        $config = (array) unserialize(base64_decode($instance->configdata));

        // Explicitly converting config values to proper types below to avoid any confusions down the road as we
        // expect title to be a string.

        $common_config = [];

        if (isset($config['title']) && in_array($instance->blockname, $title_upgrade_elegible)) {

            // HTML block is a very special boy, it allows you to have an empty title, the rest just replace
            // it with default if title is not specified.
            if ($instance->blockname == 'html' || !empty($config['title'])) {
                $common_config['title'] = (string) $config['title'];
                $common_config['override_title'] = true;
            }
        }

        if (isset($config['display_with_border'])) {
            $common_config['show_border'] = (bool) $config['display_with_border'];
        }

        if (!empty($common_config)) {
            $DB->update_record('block_instances', (object) [
                'id' => $id,
                'common_config' => json_encode($common_config),
            ]);
        }
    }
}

/**
 * Add new 'course_navigation' block to all existing courses.
 * The block API must be functioning, but to be safe only use the structures used there.
 */
function totara_core_add_course_navigation() {
    global $CFG, $DB;

    if (!class_exists('moodle_page')) {
        // We need to be able to use moodle_page.
        return;
    }

    if (!$courses = $DB->get_records('course')) {
        // We don't have any courses yet.
        return;
    }

    // Ensure the block is visible, so that we can add it.
    if (!$DB->record_exists('block', ['name' => 'course_navigation'])) {
        // Likely the block is not installed yet.
        $file = $CFG->dirroot . '/blocks/course_navigation/version.php';
        if (file_exists($file)) {
            // OK, it's going to be installed later on.
            set_config('navigation_migration', 1, 'block_course_navigation');
        }
        return;
    }

    foreach ($courses as $course) {
        $page = new moodle_page();
        $page->set_course($course);
        $page->blocks->add_blocks(['side-pre' => ['course_navigation']], '*', null, true, -10);
    }
}

function totara_core_upgrade_course_defaultimage_config() {
    global $DB;

    $fs = get_file_storage();
    $context = context_system::instance();

    // If the file system has more than one files for setting 'defaultimage', then we will kinda assure that the
    // latest file is the used file for that specific setting.
    $files = $fs->get_area_files(
        $context->id,
        'course',
        'defaultimage',
        false,
        'timemodified DESC',
        false
    );

    if (!empty($files)) {
        $oldfile = reset($files);

        if (!$fs->file_exists($context->id, 'course', 'defaultimage', 0, '/', $oldfile->get_filename())) {
            // Start writing the old file to the file storage system. So that the admin settting is able to find it.
            // There is only one default image, and it must be a ZERO.
            $rc = [
                'contextid' => $context->id,
                'component' => 'course',
                'filearea' => 'defaultimage',
                'timemodified' => time(),
                'itemid' => 0,
                'source' => $oldfile->get_source(),
                'filepath' => '/',
                'filename' => $oldfile->get_filename()
            ];

            $fs->create_file_from_storedfile($rc, $oldfile);
            set_config('defaultimage', $oldfile->get_filepath() . $oldfile->get_filename(), 'course');

            // Just remove this old file, it is no longer being used.
            $oldfile->delete();
        }
    } else if (false !== get_config('course', 'defaultimage')) {
        // This seemed wrong that system admin was trying to use some random URL as their default image. But it is
        // really an edge case.
        unset_config('defaultimage', 'course');
    }

    // We need to remove pretty much all the course defaultimage that has itemid > zero. After the default image is
    // being set to zero at this point.
    $sql = "SELECT DISTINCT itemid FROM {files} WHERE itemid > 0 AND component = 'course' AND filearea = 'defaultimage'";
    $records = $DB->get_records_sql($sql);
    foreach ($records as $itemid => $unused) {
        $fs->delete_area_files($context->id, 'course', 'defaultimage', $itemid);
    }
}

/**
 * Upgrading course 'images' itemid to zero. Because course image should be found via context course id. Not item id.
 * @return void
 */
function totara_core_upgrade_course_images() {
    global $DB;

    $fs = get_file_storage();

    // For older version, itemid of course-images is being set as courseid.
    $sql = "SELECT DISTINCT itemid FROM {files} WHERE itemid > 0 AND component = 'course' AND filearea = 'images'";
    $records = $DB->get_records_sql($sql);

    foreach ($records as $itemid => $unused) {
        $ctx = context_course::instance($itemid, IGNORE_MISSING);
        if (!$ctx) {
            continue;
        }

        // The latest file should be the file that is being used for the course.
        $files = $fs->get_area_files($ctx->id, 'course', 'images', $itemid, 'timemodified DESC', false);

        if (!empty($files)) {
            $oldfile = reset($files);

            if (!$fs->file_exists($ctx->id, 'course', 'images', 0, '/', $oldfile->get_filename())) {
                $rc = [
                    'contextid' => $ctx->id,
                    'component' => 'course',
                    'filearea' => 'images',
                    'itemid' => 0,
                    'source' => $oldfile->get_source(),
                    'filepath' => '/',
                    'filename' => $oldfile->get_filename()
                ];

                $fs->create_file_from_storedfile($rc, $oldfile);
            }
        }

        // Does not really matter if the files are there or not. This will ensure we are removing unused files.
        $fs->delete_area_files($ctx->id, 'course', 'images', $itemid);
    }
}

/**
 * Upgrade to remove the invalid tags from system. Steps explaination of upgrading:
 * + Load the whole list of tag_instances
 * + Then start looking into each tag instance and checking if it is invalid with name or not (record with special
 * + characters encoded).
 * + Checking that if there are any original record for this invalid record
 * + If there is, then start the cleaning process. Luckily that the tag component itself does auto-clean up.
 *
 * @return void
 */
function totara_core_core_tag_upgrade_tags() {
    global $DB;

    $taginstances = $DB->get_records_sql(
        "SELECT ti.*
         FROM {tag_instance} ti
         INNER JOIN {tag} t ON t.id = ti.tagid
         WHERE t.isstandard = 0"
    );

    $tagstobedeleted = [];

    foreach ($taginstances as $taginstance) {
        // Current tag that is being used for instance mapping.
        $tag = $DB->get_record('tag', ['id' => $taginstance->tagid]);
        $name = $tag->name;

        // Detect whether tag name changes.
        $name_changed = false;

        while ($name !== htmlspecialchars_decode($name)) {
            // We only want it to go back to the very first decoded value (skipping the middle encoded value).
            $name = htmlspecialchars_decode($name);
            $name = clean_param($name, PARAM_TAG);
            $name_changed = true;
        }

        // If name didn't get encoded, then we don't need to do anything.
        if (!$name_changed) {
            continue;
        }

        $sql = "
            SELECT t.id FROM {tag} t
            INNER JOIN {tag_coll} tc ON tc.id = t.tagcollid
            INNER JOIN {tag_area} ta ON ta.tagcollid = tc.id
            WHERE t.name = :name
            AND ta.component = :component
            AND ta.itemtype = :type
        ";

        // Tag component does not allow us to have more than one tags that share a same name in a collection.
        // Therefore, it should have only one tag with specific '$name' and being a part of
        // '$component' and '$type'.
        $previoustag = $DB->get_record_sql(
            $sql,
            [
                'name' => $name,
                'component' => $taginstance->component,
                'type' => $taginstance->itemtype,
            ]
        );

        if ($previoustag) {
            if ($taginstance->tagid !== $previoustag->id) {
                // Previous record is being existed, we need to update this invalid into the previous one.
                $taginstance->tagid = $previoustag->id;
                $DB->update_record('tag_instance', $taginstance);

                if (!isset($tagstobedeleted[$tag->id])) {
                    $tagstobedeleted[$tag->id] = $tag->id;
                }
            }
        } else {
            // No standard tag matches this one, update this non-standard tag.
            $rawname = $tag->rawname;
            while ($rawname !== htmlspecialchars_decode($rawname)) {
                // We only want it to go back to the very first decoded value (skipping the middle encoded value).
                $rawname = htmlspecialchars_decode($rawname);
                $rawname = clean_param($rawname, PARAM_TAG);
            }
            $tag->name = $name;
            $tag->rawname = $rawname;
            $DB->update_record('tag', $tag);
        }
    }

    foreach ($tagstobedeleted as $tagid) {
        if ($DB->record_exists('tag_instance', ['tagid' => $tagid])) {
            // There are other instances that using these to-be-deleted tags
            continue;
        }

        $DB->delete_records('tag', ['id' => $tagid]);
    }
}