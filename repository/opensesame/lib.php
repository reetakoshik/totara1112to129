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
 * @package repository_opensesame
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/repository/lib.php");

class repository_opensesame extends repository {

    public function check_login() {
        return true;
    }

    public function print_login() {
        return true;
    }

    public function logout() {
        return array();
    }

    public function get_listing($path = '', $page = '') {
        global $DB, $OUTPUT;

        $syscontext = context_system::instance();
        $fs = get_file_storage();

        $ret = array();
        $ret['list'] = array();
        $ret['manage'] = false;
        $ret['dynload'] = true;
        $ret['nosearch'] = true;
        $ret['nologin'] = true;
        $ret['path'] = array(
            array('name' => get_string('root', 'repository_opensesame'), 'path' => '')
        );

        $bundles = $DB->get_records('repository_opensesame_bdls', array(), 'name ASC');
        $foldericon = $OUTPUT->pix_url(file_folder_icon(90))->out(false);

        if ($path === '' or empty($bundles[$path])) {
            foreach ($bundles as $bundle) {
                if ($bundle->name === '') {
                    $name = get_string('nobundle', 'repository_opensesame');
                } else {
                    $name = $bundle->name;
                }
                $ret['list'][] = array(
                    'title' => $name,
                    'children' => array(),
                    'datecreated' => null,
                    'datemodified' => null,
                    'thumbnail' => $foldericon,
                    'path' => $bundle->id,
                );
            }

            return $ret;

        }

        // This must be a bundle then.

        $ret['path'][] = array('name' => $bundles[$path]->name, 'path' => $path);

        $sql = "SELECT p.*
                  FROM {repository_opensesame_pkgs} p
                 WHERE p.visible = 1
                       AND EXISTS (
                              SELECT bps.id
                                FROM {repository_opensesame_bps} bps
                               WHERE bps.packageid = p.id AND bps.bundleid = :bundleid)
          ORDER BY p.title ASC";
        $packages = $DB->get_records_sql($sql, array('bundleid' => $path));

        $scormicon = $OUTPUT->pix_url('icon', 'mod_scorm')->out(false);
        $osicon = $OUTPUT->pix_url('icon', 'repository_opensesame')->out(false);

        foreach ($packages as $package) {
            $file = $fs->get_file($syscontext->id, 'repository_opensesame', 'packages', $package->id, '/', $package->zipfilename);
            if (!$file) {
                continue;
            }

            $node = array(
                'title' => clean_filename($package->title) . '.zip',
                'source' => $package->id,
                'size' => $file->get_filesize(),
                'datecreated' => $package->timecreated,
                'datemodified' => $package->timecreated,
                'icon' => $osicon,
                'thumbnail' => $scormicon,
            );

            $ret['list'][] = $node;
        }

        return $ret;
    }

    public function file_is_accessible($source) {
        global $DB;

        // Access to this repository means users may use the package files.
        return $DB->record_exists('repository_opensesame_pkgs', array('id' => $source));
    }

    public function has_moodle_files() {
        // I am not crazy to deal with the file reference craziness here.
        return false;
    }

    public function get_file($source, $filename = '') {
        global $DB;

        $fs = get_file_storage();
        $syscontext = context_system::instance();

        $package = $DB->get_record('repository_opensesame_pkgs', array('id' => $source));
        $file = $fs->get_file($syscontext->id, 'repository_opensesame', 'packages', $package->id, '/', $package->zipfilename);

        $temppath = $this->prepare_file($filename);

        file_put_contents($temppath, $file->get_content());

        return array('path' => $temppath, 'url' => null);
    }

    public static function create($type, $userid, $context, $params, $readonly=0) {
        require_capability('moodle/site:config', context_system::instance());
        return parent::create($type, $userid, $context, $params, $readonly);
    }

    public static function type_config_form($mform, $classname = 'repository') {
        parent::type_config_form($mform, $classname);

        // The repository config code is dreadful!!!
        global $action; // Comes from admin/repository.php
        if (!has_capability('moodle/site:config', context_system::instance())) {
            // Bad luck, cannot configure the repo.
        } else if ($action == 'new') {
            $mform->addElement('static', 'opensesameaddhelp', '', get_string('addhelp', 'repository_opensesame'));
        } else {
            $url = new moodle_url('/repository/opensesame/register.php');
            if (get_config('repository_opensesame', 'tenantkey')) {
                $str = get_string('registrationlink', 'repository_opensesame');
            } else {
                $str = get_string('registerlink', 'repository_opensesame');
            }
            $link = html_writer::link($url, $str);
            $mform->addElement('static', 'opensesameaddhelp', '', $link);
        }
    }

    public static function get_type_option_names() {
        return array('pluginname');
    }

    public function supported_returntypes() {
        return FILE_INTERNAL;
    }

    public function contains_private_data() {
        return false;
    }
}

function repository_opensesame_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    require_login();

    if ($filearea !== 'packages') {
        send_file_not_found();
    }

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        send_file_not_found();
    }

    require_capability('repository/opensesame:managepackages', $context);

    $fs = get_file_storage();

    $fullpath = "/$context->id/repository_opensesame/$filearea/".implode('/', $args);

    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        send_file_not_found();
    }

    \core\session\manager::write_close();
    send_stored_file($file, 0, 0, true);
}
