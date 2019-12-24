<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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

namespace repository_opensesame\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Utility for managing of the repository instance.
 */
class util {
    public static function enable_repository() {
        global $DB, $CFG;
        require_once("$CFG->dirroot/repository/lib.php");

        $type = $DB->get_record('repository', array('type' => 'opensesame'));

        if ($type) {
            if (!$type->visible) {
                $repositorytype = \repository::get_type_by_typename('opensesame');
                if ($repositorytype) {
                    $repositorytype->update_visibility(true);
                }
            }
        } else {
            $type = new \repository_type('opensesame', array(), true);
            $type->create(true);
        }

        \core_plugin_manager::reset_caches();
    }

    public static function disable_repository() {
        global $CFG;
        require_once("$CFG->dirroot/repository/lib.php");

        $repositorytype = \repository::get_type_by_typename('opensesame');
        if ($repositorytype) {
            $repositorytype->update_visibility(false);
        }

        \core_plugin_manager::reset_caches();
    }

    public static function add_package_to_bundle($bundlename, $packageid) {
        global $DB;

        if ($bundlename === '') {
            if ($DB->record_exists('repository_opensesame_bps', array('packageid' => $packageid))) {
                // Do not add to "No bundle" if already in some other bundle.
                return;
            }
        }

        $bundle = $DB->get_record_select('repository_opensesame_bdls', "LOWER(name) = LOWER(:name)", array('name' => $bundlename));
        if (!$bundle) {
            $bundle = new \stdClass();
            $bundle->name = $bundlename;
            $bundle->timecreated = time();
            $bundle->id = $DB->insert_record('repository_opensesame_bdls', $bundle);
        }

        if (!$DB->record_exists('repository_opensesame_bps', array('bundleid' => $bundle->id, 'packageid' => $packageid))) {
            $record = new \stdClass();
            $record->bundleid = $bundle->id;
            $record->packageid = $packageid;
            $DB->insert_record('repository_opensesame_bps', $record);
        }

        if ($bundlename !== '') {
            // Make sure the package is removed from the "No bundle category".
            $nobundle = $DB->get_record('repository_opensesame_bdls', array('name' => ''));
            if ($nobundle) {
                $DB->delete_records('repository_opensesame_bps', array('bundleid' => $nobundle->id, 'packageid' => $packageid));
            }
        }
    }

    /**
     * Process downloaded content packages zip file.
     *
     * @param string $type - full or new
     * @return bool|int false on error, integer is number of packages downloaded
     */
    public static function fetch_packages($type) {
        global $DB;

        error_log('opensesame: starting import of packages');

        $request = opensesame_com::get_fetch_content_request($type);
        $options = opensesame_com::get_curl_options();

        $curl = new \curl();
        $zipcontents = $curl->post($request->get_normalized_http_url(), $request->to_postdata(), $options);

        if (strpos($zipcontents, 'PK') !== 0) {
            if (strpos($zipcontents, '"status":"success"') !== false) {
                error_log('opensesame: no packages downloaded');
                return 0;
            }
            error_log('opensesame: invalid zip contents downloaded');
            return false;
        }

        $tempsubdir = 'repository_opensesame/' . sha1(uniqid('', true));

        $tempdir = make_temp_directory($tempsubdir, true);
        $tempfile = make_temp_directory($tempsubdir, true) . '/mega.zip';
        $contentsdir = make_temp_directory($tempsubdir, true) . '/contents';
        $coursesdir = make_temp_directory($tempsubdir, true) . '/courses';

        file_put_contents($tempfile, $zipcontents);

        $fs = get_file_storage();
        $syscontext = \context_system::instance();
        $packer = get_file_packer('application/zip');

        $packer->extract_to_pathname($tempfile, $contentsdir);
        if (!file_exists($contentsdir . '/courses.zip') or !file_exists($contentsdir . '/metadata.json')) {
            remove_dir($tempdir, false);
            error_log('opensesame: invalid zip contents');
            return false;
        }

        $result = $packer->extract_to_pathname($contentsdir . '/courses.zip', $coursesdir);
        if ($result === false) {
            remove_dir($tempdir, false);
            error_log('opensesame: bundle unzip error');
            return false;
        }

        $metadatas = file_get_contents($contentsdir . '/metadata.json');
        $metadatas = json_decode($metadatas, true);
        $requiredmeta = array('zipFilename', 'title', 'bundleName', 'externalId');
        $optionalmeta = array('expirationDate', 'mobileCompatibility', 'description', 'duration');

        $count = 0;
        foreach ($metadatas as $metadata) {
            foreach ($requiredmeta as $req) {
                if (empty($metadata[$req])) {
                    error_log("opensesame: missing $req");
                    continue 2;
                }
            }
            foreach ($optionalmeta as $req) {
                if (!isset($metadata[$req])) {
                    $metadata[$req] = '';
                }
            }

            $pkg = new \stdClass();
            $pkg->visible = 1;
            $pkg->zipfilename = clean_param($metadata['zipFilename'], PARAM_FILE);
            $pkg->title = clean_param($metadata['title'], PARAM_NOTAGS);
            $pkg->expirationdate = clean_param($metadata['expirationDate'], PARAM_NOTAGS);
            $pkg->mobilecompatibility = clean_param($metadata['mobileCompatibility'], PARAM_NOTAGS);
            $pkg->externalid = clean_param($metadata['externalId'], PARAM_NOTAGS);
            $pkg->description = clean_param($metadata['description'], PARAM_CLEANHTML);
            $pkg->duration = clean_param($metadata['duration'], PARAM_NOTAGS);
            $pkg->timemodified = time();

            if ($pkg->duration === '99:59') {
                // This strange value means unknown most probably.
                $pkg->duration = '';
            }

            if (!file_exists($coursesdir . '/' . $pkg->zipfilename)) {
                error_log("opensesame: missing file $pkg->zipfilename");
                continue;
            }

            if ($old = $DB->get_record('repository_opensesame_pkgs', array('externalid' => $pkg->externalid))) {
                $pkg->id = $old->id;
                $DB->update_record('repository_opensesame_pkgs', $pkg);
                $fs->delete_area_files($syscontext->id, 'repository_opensesame', 'packages', $pkg->id);
                $added = false;
            } else {
                $pkg->timecreated = $pkg->timemodified;
                $pkg->id = $DB->insert_record('repository_opensesame_pkgs', $pkg);
                $added = true;
            }
            $pkg = $DB->get_record('repository_opensesame_pkgs', array('id' => $pkg->id), '*', MUST_EXIST);

            $file = array(
                'contextid' => $syscontext->id,
                'component' => 'repository_opensesame',
                'filearea' => 'packages',
                'itemid' => $pkg->id,
                'filepath' => '/',
                'filename' => $pkg->zipfilename,
            );
            $fs->create_file_from_pathname($file, $coursesdir . '/' . $pkg->zipfilename);

            $bundlename = clean_param($metadata['bundleName'], PARAM_NOTAGS);
            self::add_package_to_bundle($bundlename, $pkg->id);

            $count++;

            if ($added) {
                error_log("opensesame: added course $pkg->externalid package");
                \repository_opensesame\event\package_fetched::create_from_package($pkg)->trigger();
            } else {
                error_log("opensesame: updated course $pkg->externalid package");
            }
        }

        remove_dir($tempdir, false);

        error_log('opensesame: bundle request processed');
        return $count;
    }
}
