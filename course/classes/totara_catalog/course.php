<?php
/*
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package core_course
 * @category totara_catalog
 */
namespace core_course\totara_catalog;

defined('MOODLE_INTERNAL') || die();

use core_course\workflow_manager\coursecreate;
use totara_catalog\provider;
use totara_contentmarketplace\workflow_manager\exploremarketplace;
use totara_customfield\totara_catalog\dataholder_factory as customfield_dataholder_factory;

class course extends provider {

    /**
     * @var [] Caches configuration for this provider.
     */
    private $config_cache = null;

    public static function is_plugin_enabled(): bool {
        return true;
    }

    public static function get_name(): string {
        return get_string('courses', 'moodle');
    }

    public static function get_object_type(): string {
        return 'course';
    }

    public function get_object_table(): string {
        return '{course}';
    }

    public function get_objectid_field(): string {
        return 'id';
    }

    public function can_see(array $objects): array {
        $results = [];

        $issiteadmin = is_siteadmin();

        foreach ($objects as $object) {
            if ($issiteadmin) {
                $results[$object->objectid] = true;
                continue;
            }

            $results[$object->objectid] = totara_course_is_viewable($object->objectid);
        }

        return $results;
    }

    public function get_data_holder_config(string $key) {

        if (is_null($this->config_cache)) {
            $this->config_cache = [
                'sort'        => [
                    'text' => 'fullname',
                    'time' => 'timemodified',
                ],
                'fts'         => [
                    'high'   => [
                        'fullname',
                        'shortname'
                    ],
                    'medium' => [
                        'summary_fts',
                        'ftstags',
                        'search_metadata'
                    ],
                    'low'    => array_merge(
                        customfield_dataholder_factory::get_fts_dataholder_keys('course', 'course', $this),
                        [
                            'idnumber',
                            'custom_section_titles',
                            'course_category_hierarchy',
                        ]
                    ),
                ],
                'image'       => 'image',
                'progressbar' => 'progressbar',
            ];
        }

        if (array_key_exists($key, $this->config_cache)) {
            return $this->config_cache[$key];
        }

        return null;
    }

    public function get_all_objects_sql(): array {

        $sql = "SELECT c.id AS objectid, 'course' AS objecttype, con.id AS contextid
                  FROM {course} c
                  JOIN {context} con
                    ON con.instanceid = c.id
                 WHERE c.id <> :sitecourseid
                   AND con.contextlevel = :coursecontextlevel";

        return [$sql, ['sitecourseid' => SITEID, 'coursecontextlevel' => CONTEXT_COURSE]];
    }

    public function get_manage_link(int $objectid) {
        global $CFG;

        require_once($CFG->dirroot . '/course/lib.php');

        $coursecontext = \context_course::instance($objectid);
        if (!has_capability('moodle/course:update', $coursecontext)) {
            return null;
        }

        $link = new \stdClass();
        $link->url = course_get_url($objectid)->out();
        $link->label = get_string('courselink', 'lti');

        return $link;
    }

    public function get_details_link(int $objectid) {
        global $CFG, $USER;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->libdir . '/enrollib.php');

        $coursecontext = \context_course::instance($objectid, MUST_EXIST);
        $alreadyenrolled = is_enrolled($coursecontext, $USER, '', true);

        $alreadycompleted = false;
        if ($alreadyenrolled) {
            $completion = new \completion_completion(['userid' => $USER->id, 'course' => $objectid]);
            $alreadycompleted = $completion->is_complete();
        }

        $link = new \stdClass();

        if ($alreadycompleted) {
            $link->description = get_string('catalog_already_completed', 'moodle');
            $link->button = new \stdClass();
            $link->button->url = course_get_url($objectid)->out();
            $link->button->label = get_string('catalog_view', 'moodle');
            return $link;
        }

        if ($alreadyenrolled) {
            $link->description = get_string('catalog_already_enrolled', 'moodle');
            $link->button = new \stdClass();
            $link->button->url = course_get_url($objectid)->out();
            $link->button->label = get_string('catalog_go_to_course', 'moodle');
            return $link;
        }

        $canselfenrol = !empty(enrol_selfenrol_available($objectid));
        if ($canselfenrol) {
            $link->description = get_string('catalog_can_enrol', 'moodle');
            $link->button = new \stdClass();
            $url = new \moodle_url('/enrol/index.php', ['id' => $objectid]);
            $link->button->url = $url->out();
            $link->button->label = get_string('catalog_enrol', 'moodle');
            return $link;
        }

        $link->description = get_string('catalog_not_enrolled', 'moodle');

        // We still have to include a link to the course in case there is auto-enrolment (e.g. programs, learning plans).
        // There is no easy way to figure that out for all enrolment plugins at this point.
        $link->button = new \stdClass();
        $link->button->url = course_get_url($objectid)->out();
        $link->button->label = get_string('catalog_go_to_course', 'moodle');

        return $link;
    }

    public function get_buttons(): array {
        global $CFG, $DB;

        $buttons = [];

        if (!empty($CFG->enablecourserequests)) {
            $systemcontext = \context_system::instance();

            if (!has_capability('moodle/course:create', $systemcontext) &&
                has_capability('moodle/course:request', $systemcontext)
            ) {
                $button = new \stdClass();
                $button->label = get_string('requestcourse');
                $button->url = (new \moodle_url('/course/request.php'))->out();
                $buttons[] = $button;
            }

            if (has_capability('moodle/site:approvecourse', $systemcontext) &&
                $DB->record_exists('course_request', [])
            ) {
                $button = new \stdClass();
                $button->label = get_string('pendingrequests');
                $button->url = (new \moodle_url('/course/pending.php'))->out();
                $buttons[] = $button;
            }
        }

        $wm = new exploremarketplace();
        if ($wm->workflows_available()) {
            $button = new \stdClass();
            $button->label = get_string('explore_totara_content', 'totara_contentmarketplace');
            $button->url = $wm->get_url()->out();
            $buttons[] = $button;
        }

        return $buttons;
    }

    public function get_create_buttons(): array {

        $buttons = [];

        $wm = new coursecreate();
        $categoryid = totara_get_categoryid_with_capability('moodle/course:create');
        if ($categoryid) {
            $wm->set_params(['category' => $categoryid]);
            if ($wm->workflows_available()) {
                $button = new \stdClass();
                $button->label = get_string('course');
                $button->url = $wm->get_url()->out();
                $buttons[] = $button;
            }
        }

        return $buttons;
    }
}
