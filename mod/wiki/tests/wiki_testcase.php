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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package mod_wiki
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

abstract class wiki_testcase extends advanced_testcase {

    /**
     * Shorthand for data generator.
     *
     * @return testing_data_generator
     */
    protected function generator() {
        return $this->getDataGenerator();
    }

    /**
     * Shorthand for wiki generator.
     *
     * @return mod_wiki_generator
     */
    protected function wiki_generator() {
        return $this->generator()->get_plugin_generator('mod_wiki');
    }

    /**
     * Seed initial dummy data
     *
     * @param int $count How many items to create
     * @return array Generated data ID's See above for data structure
     */
    protected function seed($count = 2) {
        global $DB;
        $data = [];
        $student = $DB->get_record('role', ['shortname' => 'student'])->id;

        // Need to create a few course categories.
        // Then create a few different courses.

        for($i = 1; $i <= $count; $i++) {
            $user = $this->generator()->create_user();
            $data['users'][$user->id] = $user;
        }

        for($i = 1; $i <= $count; $i++) {
            $cat = $this->generator()->create_category();

            $data['cats'][$cat->id] = [];

            for($j = 1; $j <= $count; $j++) {
                $course = $this->generator()->create_course(['category' => $cat->id]);

                foreach ($data['users'] as $user) {
                    $this->generator()->enrol_user($user->id, $course->id, $student);
                }

                $data['cats'][$cat->id][$course->id] = [];

                // Some trickery needed as wiki save page from the generator uses the wiki api.
                // which checks for permissions while saving wiki page in the database.
                $this->setUser($data['users'][array_keys($data['users'])[0]]);
                // We will create 3 wiki instances, two individual and one collaborative
                [$wiki, $subwikis] = $this->create_and_populate_individual_wiki($course, array_keys($data['users']));
                $data['cats'][$cat->id][$course->id][$wiki->id] = $subwikis;


                [$wiki, $subwikis] = $this->create_and_populate_collaborative_wiki($course, array_keys($data['users']));
                $data['cats'][$cat->id][$course->id][$wiki->id] = $subwikis;
            }
        }

        $this->resetAfterTest();

        return $data;
    }

    /**
     * Create new wiki with given parameters.
     *
     * @param bool $individual Flag to create an individual wiki
     * @param array|int $params Array of parameters or course id as a shorthand.
     * @return stdClass
     */
    protected function create_wiki($individual = true, $params = []) {
        $default = [
            'wikimode' => $individual ? 'individual' : 'collaborative',
            'defaultformat' => 'html',
            'forceformat' => 0
        ];

        if (!is_array($params)) {
            $params = ['course' => $params];
        }

        $params = array_merge($default, $params);

        if (!isset($params['course'])) {
            throw new coding_exception('Course not specified');
        }

        return $this->wiki_generator()->create_instance($params);
    }

    /**
     * Create and populate collaborative wiki instance.
     *
     * @param stdClass $course Course id
     * @param array|int $user Array of user ids or a single id.
     * @return array [$wiki, $data]
     */
    protected function create_and_populate_collaborative_wiki($course, $user) {
        $wiki = $this->create_wiki(false, $course);

        $users = is_array($user) ? $user : [$user];

        $data = [];

        foreach ($users as $user) {
            $params = [
                'userid' => $user,
                'tags' => 'One',
            ];

            $titles = ['First', 'Second'];
            $subwikis = [];
            $nocomments = true;

            foreach ($titles as $title) {

                $linksto = 'This page links to [[' . ($title == 'First' ? 'Second' : 'First') . ']] page.';

                // Create page. That should create 2 revisions. 0 - empty and 1 with actual content.
                $page = $this->wiki_generator()
                    ->create_page($wiki, array_merge($params, ['title' => $title, 'content' => $linksto]), $user);

                // Create a second revision.
                $version = $this->wiki_generator()->update_page($page->id, "Revision 2 \n{$linksto}", $user);
                $versions[$version->id] = $version;

                $comments = [];

                if (!$nocomments) {
                    // Post 2 comments.
                    $comments[] = $this->wiki_generator()->post_comment($page->id, '<p>New comment</p>', $user);
                    $comments[] = $this->wiki_generator()->post_comment($page->id, '<p>Another comment</p>', $user);
                }

                $nocomments = false;

                $this->wiki_generator()->create_page_synonym($page->id, "{$page->title} synonym by {$user} at ". rand());
                $this->wiki_generator()->lock_page($page->id, $user, time() - rand(100000, 350000));

                if (!isset($subwikis[$page->subwikiid])) {
                    $subwikis[$page->subwikiid] = ['pages' => []];
                }

                $subwikis[$page->subwikiid]['pages'][$page->id] = [
                    'versions' => $versions,
                    'comments' => $comments,
                ];
            }

            $data[$user] = $subwikis;
        }

        // Adding files, here at the end to make sure all the sub-wikis have been properly created.
        $userid = $users[0];
        $subwiki = array_keys(array_values($data)[0])[0];
        $anothersubwiki = array_keys(array_values($data)[1])[0];
        $uploads = [
            'cute_kitten.jpg',
            'cute_puppy.jpg',
        ];

        foreach ($uploads as $upload) {
            $data[$userid][$subwiki]['files'] = $this->wiki_generator()->add_file($subwiki, $upload, $userid);
        }

        $data[$userid][$subwiki]['files'] = $this->wiki_generator()->add_file($subwiki, 'meaningful_text.txt', $users[1]);
        //$data[$userid][$anothersubwiki]['files'] = $this->wiki_generator()->add_file($anothersubwiki, 'meaningful_text.txt', $users[1]);

        return [$wiki, $data];
    }

    /**
     * Create and populate individual wiki instance.
     *
     * @param stdClass $course Course id
     * @param array|int $user Array of user ids or a single id.
     * @return array [$wiki, $data]
     */
    protected function create_and_populate_individual_wiki($course, $user) {
        $wiki = $this->create_wiki(true, $course);

        $users = is_array($user) ? $user : [$user];

        $data = [];

        foreach ($users as $user) {
            $params = [
                'userid' => $user,
                'tags' => 'One,Two,Three',
            ];

            $titles = ['First', 'Second'];
            $subwikis = [];

            foreach ($titles as $title) {

                $linksto = 'This page links to [[' . ($title == 'First' ? 'Second' : 'First') . ']] page.';

                // Create page. That should create 2 revisions. 0 - empty and 1 with actual content.
                $page = $this->wiki_generator()
                    ->create_page($wiki, array_merge($params, ['title' => $title, 'content' => $linksto]), $user);

                // Create a second revision.
                $version = $this->wiki_generator()->update_page($page->id, "Revision 2 \n{$linksto}", $user);
                $versions[$version->id] = $version;

                // Post 2 comments.
                $comments[] = $this->wiki_generator()->post_comment($page->id, '<p>New comment</p>', $user);
                $comments[] = $this->wiki_generator()->post_comment($page->id, '<p>Another comment</p>', $user);

                $this->wiki_generator()->create_page_synonym($page->id, "{$page->title} synonym");
                $this->wiki_generator()->lock_page($page->id, $user, time() - rand(100000, 350000));

                if (!isset($subwikis[$page->subwikiid])) {
                    $subwikis[$page->subwikiid] = ['pages' => []];
                }

                $subwikis[$page->subwikiid]['pages'][$page->id] = [
                    'versions' => $versions,
                    'comments' => $comments,
                ];
            }

            $data[$user] = $subwikis;
        }

        // Adding files, here at the end to make sure all the sub-wikis have been properly created.
        $userid = $users[0];
        $subwiki = array_keys(array_values($data)[0])[0];
        $uploads = [
            'cute_kitten.jpg',
            'cute_puppy.jpg',
        ];

        foreach ($uploads as $upload) {
            $data[$userid][$subwiki]['files'] = $this->wiki_generator()->add_file($subwiki, $upload, $userid);
        }

        return [$wiki, $data];
    }

    /**
     * Get all the versions for a wiki page.
     *
     * @param string $pagessql Normalized IDs returned by normalize_ids
     * @param int $user User id to filter versions by user, 0 to ignore.
     * @return array Array of wiki page versions
     */
    protected function get_versions($pagessql, $user = 0) {
        global $DB;

        $condition = "pageid {$pagessql}";

        if (($user = intval($user)) > 0) {
            $condition .= " AND userid = {$user}";
        }

        return $DB->get_records_sql("SELECT * FROM {wiki_versions} WHERE {$condition}");
    }

    /**
     * Get all the comments for a given wiki page.
     *
     * @param string $pagessql Normalized IDs returned by normalize_ids
     * @param int $userid User id to filter comments by or 0 to select all comments
     * @return array
     */
    protected function get_comments($pagessql, $userid = 0) {
        global $DB;

        $condition = "itemid {$pagessql}";

        if (($userid = intval($userid)) > 0) {
            $condition .= " AND userid = {$userid}";
        }

        return $DB->get_records_sql(
            "SELECT * FROM {comments}
                  WHERE component = 'mod_wiki' AND commentarea = 'wiki_page' AND {$condition}
                  ORDER BY timecreated");
    }

    /**
     * Filter only the required events by event component & target.
     *
     * @param phpunit_event_sink $sink
     * @param string $target Target of the event to filter
     * @param string $component Component to filter
     * @return array
     */
    protected function filter_events(phpunit_event_sink $sink, $target, $component = 'mod_wiki') : array {
        return array_filter($sink->get_events(), function($event) use ($target, $component) {
            /** @var $event \core\event\base */
            return $event->target == $target && $event->component == $component;
        });
    }

    /**
     * Normalize ids of array of data object to insert it into another query
     *
     * @param array $ids
     * @param bool $assqlstring Return as a chunk of safe sql code.
     * @return string
     */
    protected function normalize_ids(array $ids, $assqlstring = true) : string {
        $ids = array_map(function($item) {
            return intval($item->id);
        }, $ids);

        $ids = empty($ids) ? -1 : implode(', ', $ids);

        if ($assqlstring) {
            $ids = strpos($ids, ',') !== false ? "IN ({$ids})" : " = {$ids}";
        }

        return $ids;
    }

    /**
     * Convert timestamp to a human readable time string in the exported user timezone.
     *
     * @param int $timestamp Timestamp
     * @return string
     */
    protected function human_time($timestamp) : string {
        global $USER;
        // Fail safe if timestamp is not set, also prevents giving 1970.
        if (empty($timestamp)) {
            return '';
        }
        $date = new DateTime("@$timestamp");
        $date->setTimezone(new DateTimeZone(core_date::normalise_timezone($USER->timezone)));
        return $date->format('F j, Y, g:i a T');
    }

    /**
     * Returns the boolean representation of user id to obscure the real user who published page or a comment.
     *
     * This is needed to provide indication of the author of the item when exporting individual wiki, as it has
     * been decided to export revisions and comments made by trainer (admin) who has access to individual users wiki
     * and can create pages or post comments.
     *
     * @param int $id User id from the database record
     * @return bool
     */
    protected function is_published_by_user($id) : bool {
        global $USER;
        return $id == $USER->id;
    }

    /**
     * Helper to match that the IDs of two get_records from the database match.
     *
     * @param \stdClass[] $expected Expected database query result.
     * @param \stdClass[] $actual Actual database query result.
     * @param string $error Customized error message
     */
    protected function assertIdsMatch($expected, $actual, $error = '') {
        $expected = array_column($expected, 'id');
        $actual = array_column($actual, 'id');

        $this->assertEqualsCanonicalizing($expected, $actual, $error);
    }
}
