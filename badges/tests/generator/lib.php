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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package core_badges
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class totara_userdata_generator
 */
class core_badges_generator extends component_generator_base {

    /**
     * @param $userid
     * @param array $properties
     * @return int the id of the new badge
     */
    public function create_badge($userid, array $properties = array()) {
        global $CFG, $DB;

        $now = time();

        $record = new stdClass();
        $record->id = null;
        $record->name = "Test badge";
        $record->description = "Testing badges";
        $record->timecreated = $now;
        $record->timemodified = $now;
        $record->usercreated = $userid;
        $record->usermodified = $userid;
        $record->issuername = "Test issuer";
        $record->issuerurl = "http://badgeissuer.example.com";
        $record->issuercontact = "issuer@example.com";
        $record->expiredate = null;
        $record->expireperiod = null;
        $record->type = BADGE_TYPE_SITE;
        $record->courseid = null;
        $record->messagesubject = "Test message subject";
        $record->message = "Test message body";
        $record->attachment = 1;
        $record->notification = 0;
        $record->status = BADGE_STATUS_ACTIVE;

        foreach ($properties as $key => $value) {
            if (!property_exists($record, $key)) {
                throw new \coding_exception('Invalid property provided to create_badge', $key);
            }
            $record->{$key} = $value;
        }

        $badgeid = $DB->insert_record('badge', $record, true);

        if (empty($record->courseid)) {
            $context = \context_system::instance();
        } else {
            $context = \context_course::instance($record->courseid);
        }

        // Trigger event, badge created.
        $eventparams = array('objectid' => $badgeid, 'context' => $context);
        $event = \core\event\badge_created::create($eventparams);
        $event->trigger();

        $temppath = make_temp_directory('badge_phpunit');
        $tempname = $temppath . '/logo.png';
        copy($CFG->dirroot . '/totara/core/pix/logo.png', $tempname);

        $newbadge = new badge($badgeid);
        badges_process_badge_image($newbadge, $tempname);

        return $badgeid;
    }

    /**
     * Creates a backpack connection and stores it in the database.
     */
    public function create_backpack_connection(\stdClass $user) {
        global $DB;

        // OK, time to be truthful. This can't be done legitimately.
        // In order to connect to a backpack an account on persona needs to exist.
        // Instead we are going to "fake" a backpack that serves only enough purpose to test userdata purging and export.

        // This record is created when you connect to a backpack.
        $backpackid = $DB->insert_record('badge_backpack', [
            'userid' => $user->id,
            'email' => $user->email,
            'backpackurl' => 'https://backpack.openbadges.org',
            'backpackuid' => $user->id,
            'password' => 'T0t@rA!'
        ]);

        // This record is created when you import collections from your backpack.
        $DB->insert_record('badge_external', [
            'backpackid' => $backpackid,
            'collectionid' => $user->id
        ]);
    }

    public function add_manual_badge_criteria($badgeid, $roleid = null) {
        global $DB;

        if ($roleid === null) {
            // Just grab one, any, it doesn't matter. If they didn't provide one they are probably testing admin.
            $roleid = $DB->get_field('role', 'id', ['archetype' => 'manager'], IGNORE_MULTIPLE);
        }

        $criteria_overall = award_criteria::build(array('criteriatype' => BADGE_CRITERIA_TYPE_OVERALL, 'badgeid' => $badgeid));
        $criteria_overall->save(array('agg' => BADGE_CRITERIA_AGGREGATION_ANY));
        $criteria_overall = award_criteria::build(array('criteriatype' => BADGE_CRITERIA_TYPE_MANUAL, 'badgeid' => $badgeid));
        $criteria_overall->save(array('agg' => BADGE_CRITERIA_AGGREGATION_ANY, 'role_1' => $roleid));
    }

    public function issue_badge_manually(\stdClass $awardto, \stdClass $awardedby, $badgeid) {
        global $CFG;

        require_once($CFG->dirroot . '/badges/lib/awardlib.php');

        $badge = new badge($badgeid);

        if (!isset($badge->criteria[BADGE_CRITERIA_TYPE_MANUAL])) {
            throw new coding_exception('This badge does not have the manual award criteria');
        }

        $acceptedroles = array_keys($badge->criteria[BADGE_CRITERIA_TYPE_MANUAL]->params);
        if (empty($acceptedroles)) {
            // @codeCoverageIgnoreStart
            throw new coding_exception('No role has been set for the manual award criteria');
            // @codeCoverageIgnoreEnd
        }

        $issuerrole = new stdClass();
        $issuerrole->roleid = $acceptedroles[0];

        if (process_manual_award($awardto->id, $awardedby->id, $issuerrole->roleid, $badgeid)) {
            // If badge was successfully awarded, review manual badge criteria.
            $data = new stdClass();
            $data->crit = $badge->criteria[BADGE_CRITERIA_TYPE_MANUAL];
            $data->userid = $awardto->id;
            badges_award_handle_manual_criteria_review($data);
        }
    }

    /**
     * Adds to the cache mock external connectedbackpacks and badge information.
     *
     * @param \stdClass $user
     * @return \stdClass
     */
    public function mock_external_badges_in_cache(\stdClass $user) {

        if (!badges_user_has_backpack($user->id)) {
            $this->create_backpack_connection($user);
        }

        $mockresult = new \stdClass;
        $mockresult->userId = $user->id;
        $mockresult->groupId = $user->id + 1;
        $mockresult->badges = array();

        $badge = new \stdClass;
        $badge->lastValidated = '2013-08-01T09:05:07.000Z';
        $badge->hostedUrl = 'https://badges.example.com/username/badges/'.$user->id;
        $badge->imageUrl = 'https://badges.example.com/username/badges/' . $user->id . '.png';
        $badge->assertion = new \stdClass();
        $badge->assertion->recipient = 'md5$' . md5($user->id);
        $badge->assertion->salt = md5($user->id);
        $badge->assertion->issued_on = '2013-08-01';
        $badge->assertion->badge = new \stdClass;
        $badge->assertion->badge->version = '0.5.0';
        $badge->assertion->badge->name = 'Totara example badge';
        $badge->assertion->badge->image = 'https://badges.example.com/username/badges/' . $user->id . '.png';
        $badge->assertion->badge->description = 'An example badge generated by mocking during unit tests.';
        $badge->assertion->badge->criteria = 'https://badges.example.com/username/badges/'.$user->id;
        $badge->assertion->badge->issuer = new \stdClass;
        $badge->assertion->badge->issuer->origin = 'https://badges.example.com';
        $badge->assertion->badge->issuer->name = 'Example badges';
        $badge->assertion->badge->issuer->contact = 'badges@example.com';

        $mockresult->badges[] = $badge;

        $badgescache = \cache::make('core', 'externalbadges');

        $out = new \stdClass();
        $out->backpackurl = 'https://badges.example.com/username/connectedbackpacks/' . $user->id;
        $out->totalcollections = 1;
        $out->totalbadges = 0;
        $out->badges = array();
        $out->badges = array_merge($out->badges, $mockresult->badges);
        $out->totalbadges += count($mockresult->badges);

        $badgescache->set($user->id, $out);
        return $out;
    }

}