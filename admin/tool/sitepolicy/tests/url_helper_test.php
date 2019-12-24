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
 * URL helper tests
 */
class tool_sitepolicy_url_helper_test extends \advanced_testcase {

    public function test_sitepolicy_list() {
        $url = url_helper::sitepolicy_list();
        self::assertInstanceOf(\moodle_url::class, $url);
        self::assertSame('/moodle/admin/tool/sitepolicy/index.php', $url->get_path());
        self::assertEmpty($url->params());
    }

    public function test_sitepolicy_create() {
        $url = url_helper::sitepolicy_create();
        self::assertInstanceOf(\moodle_url::class, $url);
        self::assertSame('/moodle/admin/tool/sitepolicy/sitepoliciesform.php', $url->get_path());
        self::assertEmpty($url->params());
    }

    public function test_sitepolicy_view() {
        $url = url_helper::sitepolicy_view('17', 'en');
        self::assertInstanceOf(\moodle_url::class, $url);
        self::assertSame('/moodle/admin/tool/sitepolicy/viewpolicy.php', $url->get_path());
        $params = $url->params();
        ksort($params);
        self::assertSame(['language' =>'en', 'policyversionid' => '17'], $params);

        $url = url_helper::sitepolicy_view('17', 'en', '2');
        self::assertInstanceOf(\moodle_url::class, $url);
        self::assertSame('/moodle/admin/tool/sitepolicy/viewpolicy.php', $url->get_path());
        $params = $url->params();
        ksort($params);
        self::assertSame(['language' =>'en', 'policyversionid' => '17', 'versionnumber' => '2'], $params);
    }

    /**
     * Returns a URL to delete a localised site policy version.
     */
    public function test_localisedpolicy_delete() {
        $url = url_helper::localisedpolicy_delete('17');
        self::assertInstanceOf(\moodle_url::class, $url);
        self::assertSame('/moodle/admin/tool/sitepolicy/translationdelete.php', $url->get_path());
        $params = $url->params();
        ksort($params);
        self::assertSame(['localisedpolicy' => '17'], $params);
    }

    /**
     * Returns a URL to view a list of localised site policies.
     */
    public function test_localisedpolicy_list() {
        $url = url_helper::localisedpolicy_list('17');
        self::assertInstanceOf(\moodle_url::class, $url);
        self::assertSame('/moodle/admin/tool/sitepolicy/translationlist.php', $url->get_path());
        $params = $url->params();
        ksort($params);
        self::assertSame(['policyversionid' => '17'], $params);
    }

    /**
     * Returns a URL to view a list of site policy versions.
     */
    public function test_version_list() {
        $url = url_helper::version_list('17');
        self::assertInstanceOf(\moodle_url::class, $url);
        self::assertSame('/moodle/admin/tool/sitepolicy/versionlist.php', $url->get_path());
        $params = $url->params();
        ksort($params);
        self::assertSame(['sitepolicyid' => '17'], $params);
    }

    /**
     * Returns a URL to create a localised site policy.
     */
    public function test_version_create() {
        $url = url_helper::version_create('17', 'fr');
        self::assertInstanceOf(\moodle_url::class, $url);
        self::assertSame('/moodle/admin/tool/sitepolicy/translationform.php', $url->get_path());
        $params = $url->params();
        ksort($params);
        self::assertSame(['language' => 'fr', 'policyversionid' => '17'], $params);
    }

    /**
     * Returns a URL to edit a localised site policy.
     */
    public function test_localisedpolicy_edit() {
        $url = url_helper::localisedpolicy_edit('17');
        self::assertInstanceOf(\moodle_url::class, $url);
        self::assertSame('/moodle/admin/tool/sitepolicy/translationform.php', $url->get_path());
        $params = $url->params();
        ksort($params);
        self::assertSame(['localisedpolicy' => '17'], $params);
    }

    /**
     * Returns a URL to archive a published site policy version.
     */
    public function test_version_archive() {
        $url = url_helper::version_archive('17');
        self::assertInstanceOf(\moodle_url::class, $url);
        self::assertSame('/moodle/admin/tool/sitepolicy/versionarchive.php', $url->get_path());
        $params = $url->params();
        ksort($params);
        self::assertSame(['policyversionid' => '17'], $params);
    }

    /**
     * Returns a URL to publish a draft site policy version.
     */
    public function test_version_publish() {
        $url = url_helper::version_publish('17');
        self::assertInstanceOf(\moodle_url::class, $url);
        self::assertSame('/moodle/admin/tool/sitepolicy/versionpublish.php', $url->get_path());
        $params = $url->params();
        ksort($params);
        self::assertSame(['policyversionid' => '17'], $params);
    }

    /**
     * Returns a URL to delete a draft site policy version.
     */
    public function test_version_delete() {
        $url = url_helper::version_delete('17');
        self::assertInstanceOf(\moodle_url::class, $url);
        self::assertSame('/moodle/admin/tool/sitepolicy/versiondelete.php', $url->get_path());
        $params = $url->params();
        ksort($params);
        self::assertSame(['policyversionid' => '17'], $params);
    }

    /**
     * Returns a URL to create a new localised version of a site policy.
     */
    public function test_localisedpolicy_create() {
        $url = url_helper::localisedpolicy_create('17', 'test');
        self::assertInstanceOf(\moodle_url::class, $url);
        self::assertSame('/moodle/admin/tool/sitepolicy/versionform.php', $url->get_path());
        $params = $url->params();
        ksort($params);
        self::assertSame(['localisedpolicy' => '17', 'newpolicy' => '1', 'ret' => 'test'], $params);
    }

    /**
     * Returns a URL to edit a localised site policy version.
     */
    public function test_version_edit() {
        $url = url_helper::version_edit('17', 'test');
        self::assertInstanceOf(\moodle_url::class, $url);
        self::assertSame('/moodle/admin/tool/sitepolicy/versionform.php', $url->get_path());
        $params = $url->params();
        ksort($params);
        self::assertSame(['localisedpolicy' => '17', 'ret' => 'test'], $params);
    }

    /**
     * Return a URL to view a list of site policies the user has submit consent selection to.
     */
    public function test_user_sitepolicy_list() {
        $url = url_helper::user_sitepolicy_list('17');
        self::assertInstanceOf(\moodle_url::class, $url);
        self::assertSame('/moodle/admin/tool/sitepolicy/userlist.php', $url->get_path());
        $params = $url->params();
        ksort($params);
        self::assertSame(['userid' => '17'], $params);
    }

    /**
     * Returns a URL to view the site policy version that a user has submit their consent selection to.
     */
    public function test_user_sitepolicy_version_view() {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        $url = url_helper::user_sitepolicy_version_view($user->id, '17', '3', 'es', 5, 10);
        self::assertInstanceOf(\moodle_url::class, $url);
        self::assertSame('/moodle/admin/tool/sitepolicy/viewpolicy.php', $url->get_path());
        $params = $url->params();
        ksort($params);
        self::assertSame([
            'currentcount' => '5',
            'language' =>'es',
            'policyversionid' => '17',
            'returntouser' => $user->id,
            'totalcount' => '10',
            'versionnumber' => '3',
        ], $params);

        $this->setUser($user);

        $url = url_helper::user_sitepolicy_version_view($user->id, '17', '3', 'es', 5, 10);
        self::assertInstanceOf(\moodle_url::class, $url);
        self::assertSame('/moodle/admin/tool/sitepolicy/userpolicy.php', $url->get_path());
        $params = $url->params();
        ksort($params);
        self::assertSame([
            'currentcount' => '5',
            'language' =>'es',
            'policyversionid' => '17',
            'totalcount' => '10',
            'versionnumber' => '3',
        ], $params);
    }

    /**
     * Returns a URL to view site policies the user is needs to review and consent to.
     */
    public function test_user_sitepolicy_consent() {
        $url = url_helper::user_sitepolicy_consent('17', 5);
        self::assertInstanceOf(\moodle_url::class, $url);
        self::assertSame('/moodle/admin/tool/sitepolicy/userpolicy.php', $url->get_path());
        $params = $url->params();
        ksort($params);
        self::assertSame(['currentcount' => '17', 'totalcount' => '5'], $params);
    }

    /**
     * Returns a URL that takes the user to a page confirming their intent to not accept one or more required consents.
     */
    public function test_user_sitepolicy_reject_confirmation() {
        $url = url_helper::user_sitepolicy_reject_confirmation('17', 'es', '5', '10', 'test');
        self::assertInstanceOf(\moodle_url::class, $url);
        self::assertSame('/moodle/admin/tool/sitepolicy/userexit.php', $url->get_path());
        $params = $url->params();
        ksort($params);
        self::assertSame([
            'consentdata' => 'test',
            'currentcount' => '5',
            'language' =>'es',
            'policyversionid' => '17',
            'totalcount' => '10',
        ], $params);
    }

}