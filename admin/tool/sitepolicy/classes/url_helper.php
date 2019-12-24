<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package tool_sitepolicy
 */

namespace tool_sitepolicy;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class
 */
final class url_helper {

    /**
     * Returns a URL to view a list of site policies.
     *
     * @return \moodle_url
     * @throws \moodle_exception
     */
    public static function sitepolicy_list(): \moodle_url {
        global $CFG;
        return new \moodle_url("/{$CFG->admin}/tool/sitepolicy/index.php");
    }

    /**
     * Returns a URL to create a site policy.
     *
     * @return \moodle_url
     */
    public static function sitepolicy_create(): \moodle_url {
        global $CFG;
        return new \moodle_url("/{$CFG->admin}/tool/sitepolicy/sitepoliciesform.php");
    }

    /**
     * Returns a URL to delete a localised site policy version.
     *
     * @param int $localisedpolicyid
     * @return \moodle_url
     */
    public static function localisedpolicy_delete($localisedpolicyid): \moodle_url {
        global $CFG;
        return new \moodle_url("/{$CFG->admin}/tool/sitepolicy/translationdelete.php", ['localisedpolicy' => $localisedpolicyid]);
    }

    /**
     * Returns a URL to view a list of localised site policies.
     *
     * @param int $policyversionid
     * @return \moodle_url
     */
    public static function localisedpolicy_list($policyversionid): \moodle_url {
        global $CFG;
        return new \moodle_url("/{$CFG->admin}/tool/sitepolicy/translationlist.php", ['policyversionid' => $policyversionid]);
    }

    /**
     * Returns a URL to view a list of site policy versions.
     *
     * @param int $sitepolicyid
     * @return \moodle_url
     */
    public static function version_list($sitepolicyid): \moodle_url {
        global $CFG;
        return new \moodle_url("/{$CFG->admin}/tool/sitepolicy/versionlist.php", ['sitepolicyid' => $sitepolicyid]);
    }

    /**
     * Returns a URL to create a localised site policy.
     *
     * @param int $policyversionid
     * @param string $language
     * @return \moodle_url
     */
    public static function version_create($policyversionid, $language): \moodle_url {
        global $CFG;
        return new \moodle_url ("/{$CFG->admin}/tool/sitepolicy/translationform.php", ['policyversionid' => $policyversionid, 'language' => $language]);
    }

    /**
     * Returns a URL to edit a localised site policy.
     *
     * @param int $localisedpolicyid
     * @return \moodle_url
     */
    public static function localisedpolicy_edit($localisedpolicyid): \moodle_url {
        global $CFG;
        return new \moodle_url ("/{$CFG->admin}/tool/sitepolicy/translationform.php", ['localisedpolicy' => $localisedpolicyid]);
    }

    /**
     * Returns a URL to archive a published site policy version.
     *
     * @param int $policyversionid
     * @return \moodle_url
     */
    public static function version_archive($policyversionid): \moodle_url {
        global $CFG;
        return new \moodle_url("/{$CFG->admin}/tool/sitepolicy/versionarchive.php", ['policyversionid' => $policyversionid]);
    }

    /**
     * Returns a URL to publish a draft site policy version.
     *
     * @param int $policyversionid
     * @return \moodle_url
     */
    public static function version_publish($policyversionid): \moodle_url {
        global $CFG;
        return new \moodle_url("/{$CFG->admin}/tool/sitepolicy/versionpublish.php", ['policyversionid' => $policyversionid]);
    }

    /**
     * Returns a URL to delete a draft site policy version.
     *
     * @param int $policyversionid
     * @return \moodle_url
     */
    public static function version_delete($policyversionid): \moodle_url {
        global $CFG;
        return new \moodle_url("/{$CFG->admin}/tool/sitepolicy/versiondelete.php", ['policyversionid' => $policyversionid]);
    }

    /**
     * Returns a URL to create a new localised version of a site policy.
     *
     * @param int $localisedpolicyid
     * @param string $return
     * @return \moodle_url
     */
    public static function localisedpolicy_create($localisedpolicyid, string $return = 'versions'): \moodle_url {
        global $CFG;
        return new \moodle_url(
            "/{$CFG->admin}/tool/sitepolicy/versionform.php",
            [
                'localisedpolicy' => $localisedpolicyid,
                'newpolicy' => 1,
                'ret' => $return
            ]
        );
    }

    /**
     * Returns a URL to edit a localised site policy version.
     *
     * @param int $localisedpolicyid
     * @param string $return
     * @return \moodle_url
     */
    public static function version_edit($localisedpolicyid, string $return = 'policies'): \moodle_url {
        global $CFG;
        return new \moodle_url("/{$CFG->admin}/tool/sitepolicy/versionform.php", ['localisedpolicy' => $localisedpolicyid, 'ret' => $return]);
    }

    /**
     * Returns a URL to view a site policy version.
     *
     * @param int $policyversionid
     * @param string $language
     * @param int|null $versionnumber
     * @return \moodle_url
     */
    public static function sitepolicy_view($policyversionid, string $language, $versionnumber = null): \moodle_url {
        global $CFG;
        $url = new \moodle_url(
            "/{$CFG->admin}/tool/sitepolicy/viewpolicy.php",
            [
                'policyversionid' => $policyversionid,
                'language' => $language
            ]
        );
        if ($versionnumber !== null) {
            $url->param('versionnumber', $versionnumber);
        }
        return $url;
    }

    /**
     * Return a URL to view a list of site policies the user has submit consent selection to.
     *
     * @param int $userid
     * @return \moodle_url
     */
    public static function user_sitepolicy_list($userid): \moodle_url {
        global $CFG;
        return new \moodle_url("/{$CFG->admin}/tool/sitepolicy/userlist.php",['userid' => $userid]);
    }

    /**
     * Returns a URL to view the site policy version that a user has submit their consent selection to.
     *
     * @param int $userid
     * @param int $policyversionid
     * @param int $versionnumber
     * @param string $language
     * @param int|null $currentcount Optional
     * @param int|null $totalcount Optional
     * @return \moodle_url
     */
    public static function user_sitepolicy_version_view($userid, $policyversionid, $versionnumber, $language, $currentcount = null, $totalcount = null): \moodle_url {
        global $CFG, $USER;
        $iscurrentuser = ($USER->id == $userid);
        if ($iscurrentuser) {
            $url = new \moodle_url("/{$CFG->admin}/tool/sitepolicy/userpolicy.php");
        } else {
            $url = new \moodle_url("/{$CFG->admin}/tool/sitepolicy/viewpolicy.php", ['returntouser' => $userid]);
        }
        $url->params(
            [
                'policyversionid' => $policyversionid,
                'versionnumber' => $versionnumber,
                'language' => $language
            ]
        );
        if ($currentcount) {
            $url->param('currentcount', $currentcount);
        }
        if ($totalcount) {
            $url->param('totalcount', $totalcount);
        }
        return $url;
    }

    /**
     * Returns a URL to view site policies the user is needs to review and consent to.
     *
     * @param int $currentcount
     * @param int $totalcount
     * @return \moodle_url
     */
    public static function user_sitepolicy_consent($currentcount, $totalcount): \moodle_url {
        global $CFG;
        return new \moodle_url("/{$CFG->admin}/tool/sitepolicy/userpolicy.php", ['currentcount' => $currentcount, 'totalcount' => $totalcount]);
    }

    /**
     * Returns a URL that takes the user to a page confirming their intent to not accept one or more required consents.
     *
     * @param int $policyversionid
     * @param string $language
     * @param int $currentcount
     * @param int $totalcount
     * @param mixed $answers
     * @return \moodle_url
     */
    public static function user_sitepolicy_reject_confirmation($policyversionid, $language, $currentcount, $totalcount, $answers): \moodle_url {
        global $CFG;
        return new \moodle_url(
            "/{$CFG->admin}/tool/sitepolicy/userexit.php",
            [
                'policyversionid' => $policyversionid,
                'language' => $language,
                'currentcount' => $currentcount,
                'totalcount' => $totalcount,
                'consentdata' => $answers
            ]
        );
    }
}