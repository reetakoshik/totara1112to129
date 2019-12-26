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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_reportbuilder
 */
class totara_reportbuilder_rb_global_restriction_set_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    /**
     * @var rb_global_restriction Normal restriction with restricted user (1).
     */
    protected $restr1 = null;

    /**
     * @var rb_global_restriction Normal restriction with restricted user (2).
     */
    protected $restr2 = null;

    /**
     * @var rb_global_restriction Restriction allowed for all users.
     */
    protected $restrallusers = null;

    /**
     * @var rb_global_restriction Restriction gives access to all data.
     */
    protected $restrallrecords = null;

    /**
     * @var rb_global_restriction Restriction without restricted users.
     */
    protected $restrnotassign = null;

    /**
     * @var rb_global_restriction Inactive restriction.
     */
    protected $restroff = null;

    /**
     * @var stdClass user that has restrictions.
     */
    protected $user = null;

    /**
     * @var stdClass user that has no restrictions.
     */
    protected $usernone = null;

    /**
     * @var reportbuilder report
     */
    protected $report = null;

    /**
     * @var stdClass report record
     */
    protected $reportrecord = null;

    /**
     * @var string Testing query
     */
    protected $query = "SELECT base.id,
     base.instanceid AS ef_419295f028_insid,
     base.instancetype AS ef_419295f028_type
    FROM {cohort_visibility} base
    INNER JOIN (SELECT p.id, p.id AS instanceid,
            p.fullname AS name, p.icon,  CASE WHEN p.certifid > ? THEN ? ELSE ? END AS instancetype, p.audiencevisible
            FROM {prog} p ) associations
        ON base.instancetype = associations.instancetype AND base.instanceid = associations.instanceid
 WHERE ef_419295f028_insid = ? AND ef_419295f028_type = ?";

    public static function setUpBeforeClass() {
        global $CFG;
        parent::setUpBeforeClass();
        require_once("$CFG->dirroot/totara/reportbuilder/classes/rb_global_restriction_set.php");
    }

    protected function tearDown() {
        $this->restr1 = null;
        $this->restr2 = null;
        $this->restrallusers = null;
        $this->restrallrecords = null;
        $this->restrnotassign = null;
        $this->restroff = null;
        $this->user = null;
        $this->usernone = null;
        $this->report = null;
        $this->reportrecord = null;
        $this->query = null;
        parent::tearDown();
    }

    protected function setUp() {
        global $DB, $CFG;
        parent::setUp();

        $this->resetAfterTest();
        $this->setAdminUser();

        $CFG->enableglobalrestrictions = 1;

        /** @var totara_reportbuilder_generator $reportgen */
        $reportgen = $this->getDataGenerator()->get_plugin_generator('totara_reportbuilder');

        $this->user = $this->getDataGenerator()->create_user();
        $this->usernone = $this->getDataGenerator()->create_user();

        $this->restr1 = $reportgen->create_global_restriction(array('active' => 1));
        $this->restr2 = $reportgen->create_global_restriction(array('active' => 1));
        $this->restrallusers = $reportgen->create_global_restriction(array('active' => 1, 'allusers' => 1));
        $this->restrallrecords = $reportgen->create_global_restriction(array('active' => 1, 'allrecords' => 1));
        $this->restrnotassign = $reportgen->create_global_restriction(array('active' => 1));
        $this->restroff = $reportgen->create_global_restriction(array('active' => 0, 'allusers' => 1));

        // Assign users to restriction 1, 2, and inactive.
        $reportgen->assign_global_restriction_user(array('restrictionid' => $this->restr1->id, 'prefix'=> 'user', 'itemid' => $this->user->id));
        $reportgen->assign_global_restriction_user(array('restrictionid' => $this->restr2->id, 'prefix'=> 'user', 'itemid' => $this->user->id));
        $reportgen->assign_global_restriction_user(array('restrictionid' => $this->restroff->id, 'prefix'=> 'user', 'itemid' => $this->user->id));
        $reportgen->assign_global_restriction_user(array('restrictionid' => $this->restrallrecords->id, 'prefix'=> 'user', 'itemid' => $this->user->id));

        // Create report and enable global restrictions.
        $rid = $this->create_report('user', 'Test user report 1');
        $DB->set_field('report_builder', 'globalrestriction', '1', array('id' => $rid));

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'id', null, null, null, 0);

        $this->report = reportbuilder::create($rid);
        $this->reportrecord = $DB->get_record('report_builder', array('id' => $this->report->_id));

        // Just in case reset the caches.
        rb_global_restriction_set::get_user_all_restrictions($this->user->id, true);
        rb_global_restriction_set::get_user_all_restrictions($this->usernone->id, true);
    }

    public function test_create_from_selected_all() {
        global $SESSION, $DB, $CFG;

        // Show all records if no restriction selected.
        set_config('noactiverestrictionsbehaviour', rb_global_restriction_set::NO_ACTIVE_ALL, 'reportbuilder');

        // Try to create instance with one allowed restriction.
        $this->setUser($this->user);
        unset($SESSION->rb_global_restriction);
        $_GET['globalrestrictionids'] = $this->restr1->id;
        $_GET['sesskey'] = sesskey();
        $one = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertCount(1, $one->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $one->get_current_restriction_ids());
        $this->assertEquals(array($this->restr1->id), $SESSION->rb_global_restriction);

        unset($_GET['globalrestrictionids']);
        unset($_GET['sesskey']);
        $one = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertCount(1, $one->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $one->get_current_restriction_ids());
        $this->assertEquals(array($this->restr1->id), $SESSION->rb_global_restriction);

        // Make sure CSRF is not possible.
        // Try to create instance with one allowed restriction.
        $this->setUser($this->user);
        unset($SESSION->rb_global_restriction);
        $_GET['globalrestrictionids'] = $this->restr1->id;
        $_GET['sesskey'] = 'xxx';
        try {
            rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
            $this->fail('Exception expected when sesskey incorrect');
        } catch (moodle_exception $e) {
            $this->assertSame('invalidsesskey', $e->errorcode);
        }
        unset($_GET['sesskey']);
        try {
            rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
            $this->fail('Exception expected when sesskey missing');
        } catch (moodle_exception $e) {
            $this->assertSame('missingparam', $e->errorcode);
        }

        // Try to create instance with two allowed restrictions.
        $this->setUser($this->user);
        unset($SESSION->rb_global_restriction);
        $_GET['globalrestrictionids'] = $this->restr1->id . ',' . $this->restr2->id;
        $_GET['sesskey'] = sesskey();
        $both = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertCount(2, $both->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $both->get_current_restriction_ids());
        $this->assertContains($this->restr2->id, $both->get_current_restriction_ids());
        $this->assertEquals(array($this->restr1->id, $this->restr2->id), $SESSION->rb_global_restriction);

        unset($_GET['globalrestrictionids']);
        unset($_GET['sesskey']);
        $both = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertCount(2, $both->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $both->get_current_restriction_ids());
        $this->assertContains($this->restr2->id, $both->get_current_restriction_ids());
        $this->assertEquals(array($this->restr1->id, $this->restr2->id), $SESSION->rb_global_restriction);

        // Deal with deleted instances in session.
        $SESSION->rb_global_restriction[] = -1;
        $both = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertCount(2, $both->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $both->get_current_restriction_ids());
        $this->assertContains($this->restr2->id, $both->get_current_restriction_ids());
        $this->assertEquals(array($this->restr1->id, $this->restr2->id), $SESSION->rb_global_restriction);

        // Try to create instance with allowed for all restrictions.
        $this->setUser($this->user);
        unset($SESSION->rb_global_restriction);
        $_GET['globalrestrictionids'] = $this->restr1->id . ',' . $this->restrallusers->id;
        $_GET['sesskey'] = sesskey();
        $both = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertCount(2, $both->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $both->get_current_restriction_ids());
        $this->assertContains($this->restrallusers->id, $both->get_current_restriction_ids());
        $this->assertEquals(array($this->restr1->id, $this->restrallusers->id), $SESSION->rb_global_restriction);

        unset($_GET['globalrestrictionids']);
        unset($_GET['sesskey']);
        $both = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertCount(2, $both->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $both->get_current_restriction_ids());
        $this->assertContains($this->restrallusers->id, $both->get_current_restriction_ids());
        $this->assertEquals(array($this->restr1->id, $this->restrallusers->id), $SESSION->rb_global_restriction);

        // Try restriction that gives access to all records.
        $this->setUser($this->user);
        unset($SESSION->rb_global_restriction);
        $_GET['globalrestrictionids'] = $this->restr1->id . ',' . $this->restrallrecords->id;
        $_GET['sesskey'] = sesskey();
        $null = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertNull($null);
        $this->assertEquals(array($this->restr1->id, $this->restrallrecords->id), $SESSION->rb_global_restriction);

        unset($_GET['globalrestrictionids']);
        unset($_GET['sesskey']);
        $null = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertNull($null);
        $this->assertEquals(array($this->restr1->id, $this->restrallrecords->id), $SESSION->rb_global_restriction);

        // Try to create instance with one non-allowed restriction, first should be returned.
        $this->setUser($this->user);
        unset($SESSION->rb_global_restriction);
        $_GET['globalrestrictionids'] = $this->restrnotassign->id;
        $_GET['sesskey'] = sesskey();
        $one = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertCount(1, $one->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $one->get_current_restriction_ids());
        $this->assertEquals(array($this->restr1->id), $SESSION->rb_global_restriction);

        unset($_GET['globalrestrictionids']);
        unset($_GET['sesskey']);
        $one = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertCount(1, $one->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $one->get_current_restriction_ids());
        $this->assertEquals(array($this->restr1->id), $SESSION->rb_global_restriction);

        // First should be assigned to user with restriction as default.
        $this->setUser($this->user);
        unset($SESSION->rb_global_restriction);
        unset($_GET['globalrestrictionids']);
        unset($_GET['sesskey']);
        $one = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertCount(1, $one->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $one->get_current_restriction_ids());
        $this->assertEquals(array($this->restr1->id), $SESSION->rb_global_restriction);

        // Try to create instance with inactive allowed restriction, first active should be returned.
        $this->setUser($this->user);
        unset($SESSION->rb_global_restriction);
        $_GET['globalrestrictionids'] = $this->restroff->id;
        $_GET['sesskey'] = sesskey();
        $one = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertCount(1, $one->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $one->get_current_restriction_ids());
        $this->assertEquals(array($this->restr1->id), $SESSION->rb_global_restriction);

        unset($_GET['globalrestrictionids']);
        unset($_GET['sesskey']);
        $one = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertCount(1, $one->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $one->get_current_restriction_ids());
        $this->assertEquals(array($this->restr1->id), $SESSION->rb_global_restriction);

        // Nothing should not be assigned to user with no restrictions automatically.
        $DB->delete_records('report_builder_global_restriction', array('id' => $this->restrallusers->id));
        rb_global_restriction_set::get_user_all_restrictions($this->usernone->id, true);
        rb_global_restriction_set::get_user_all_restrictions($this->user->id, true);

        $this->setUser($this->usernone);
        unset($SESSION->rb_global_restriction);
        unset($_GET['globalrestrictionids']);
        unset($_GET['sesskey']);
        $null = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertNull($null);
        $this->assertEquals(array(), $SESSION->rb_global_restriction);

        // First should be picked automatically if restriction deleted.
        $this->setUser($this->user);
        unset($_GET['globalrestrictionids']);
        unset($_GET['sesskey']);
        $SESSION->rb_global_restriction = array($this->restrallusers->id);
        $one = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertCount(1, $one->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $one->get_current_restriction_ids());
        $this->assertEquals(array($this->restr1->id), $SESSION->rb_global_restriction);

        // Disable report restriction.
        $this->reportrecord->globalrestriction = 0;
        $CFG->enableglobalrestrictions = 1;

        $this->setUser($this->user);
        $_GET['globalrestrictionids'] = $this->restr1->id;
        $_GET['sesskey'] = sesskey();
        $null = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertNull($null);

        // Disable the whole thing.
        $this->reportrecord->globalrestriction = 1;
        $CFG->enableglobalrestrictions = 0;

        $this->setUser($this->user);
        $_GET['globalrestrictionids'] = $this->restr1->id;
        $_GET['sesskey'] = sesskey();
        $null = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertNull($null);
    }

    public function test_create_from_selected_none() {
        global $SESSION, $DB, $CFG;

        // Show no records if no restriction selected.
        set_config('noactiverestrictionsbehaviour', rb_global_restriction_set::NO_ACTIVE_NONE, 'reportbuilder');

        // Try to create instance with one allowed restriction.
        $this->setUser($this->user);
        unset($SESSION->rb_global_restriction);
        $_GET['globalrestrictionids'] = $this->restr1->id;
        $_GET['sesskey'] = sesskey();
        $one = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertCount(1, $one->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $one->get_current_restriction_ids());
        $this->assertEquals(array($this->restr1->id), $SESSION->rb_global_restriction);

        unset($_GET['globalrestrictionids']);
        unset($_GET['sesskey']);
        $one = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertCount(1, $one->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $one->get_current_restriction_ids());
        $this->assertEquals(array($this->restr1->id), $SESSION->rb_global_restriction);

        // Make sure CSRF is not possible.
        // Try to create instance with one allowed restriction.
        $this->setUser($this->user);
        unset($SESSION->rb_global_restriction);
        $_GET['globalrestrictionids'] = $this->restr1->id;
        $_GET['sesskey'] = 'xxx';
        try {
            rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
            $this->fail('Exception expected when sesskey incorrect');
        } catch (moodle_exception $e) {
            $this->assertSame('invalidsesskey', $e->errorcode);
        }
        unset($_GET['sesskey']);
        try {
            rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
            $this->fail('Exception expected when sesskey missing');
        } catch (moodle_exception $e) {
            $this->assertSame('missingparam', $e->errorcode);
        }

        // Try to create instance with two allowed restrictions.
        $this->setUser($this->user);
        unset($SESSION->rb_global_restriction);
        $_GET['globalrestrictionids'] = $this->restr1->id . ',' . $this->restr2->id;
        $_GET['sesskey'] = sesskey();
        $both = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertCount(2, $both->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $both->get_current_restriction_ids());
        $this->assertContains($this->restr2->id, $both->get_current_restriction_ids());
        $this->assertEquals(array($this->restr1->id, $this->restr2->id), $SESSION->rb_global_restriction);

        unset($_GET['globalrestrictionids']);
        unset($_GET['sesskey']);
        $both = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertCount(2, $both->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $both->get_current_restriction_ids());
        $this->assertContains($this->restr2->id, $both->get_current_restriction_ids());
        $this->assertEquals(array($this->restr1->id, $this->restr2->id), $SESSION->rb_global_restriction);

        // Deal with deleted instances in session.
        $SESSION->rb_global_restriction[] = -1;
        $both = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertCount(2, $both->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $both->get_current_restriction_ids());
        $this->assertContains($this->restr2->id, $both->get_current_restriction_ids());
        $this->assertEquals(array($this->restr1->id, $this->restr2->id), $SESSION->rb_global_restriction);

        // Try to create instance with allowed for all restrictions.
        $this->setUser($this->user);
        unset($SESSION->rb_global_restriction);
        $_GET['globalrestrictionids'] = $this->restr1->id . ',' . $this->restrallusers->id;
        $_GET['sesskey'] = sesskey();
        $both = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertCount(2, $both->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $both->get_current_restriction_ids());
        $this->assertContains($this->restrallusers->id, $both->get_current_restriction_ids());
        $this->assertEquals(array($this->restr1->id, $this->restrallusers->id), $SESSION->rb_global_restriction);

        unset($_GET['globalrestrictionids']);
        unset($_GET['sesskey']);
        $both = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertCount(2, $both->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $both->get_current_restriction_ids());
        $this->assertContains($this->restrallusers->id, $both->get_current_restriction_ids());
        $this->assertEquals(array($this->restr1->id, $this->restrallusers->id), $SESSION->rb_global_restriction);

        // Try restriction that gives access to all records.
        $this->setUser($this->user);
        unset($SESSION->rb_global_restriction);
        $_GET['globalrestrictionids'] = $this->restr1->id . ',' . $this->restrallrecords->id;
        $_GET['sesskey'] = sesskey();
        $null = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertNull($null);
        $this->assertEquals(array($this->restr1->id, $this->restrallrecords->id), $SESSION->rb_global_restriction);

        unset($_GET['globalrestrictionids']);
        unset($_GET['sesskey']);
        $null = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertNull($null);
        $this->assertEquals(array($this->restr1->id, $this->restrallrecords->id), $SESSION->rb_global_restriction);

        // Try to create instance with one non-allowed restriction, first should be selected instead.
        $this->setUser($this->user);
        unset($SESSION->rb_global_restriction);
        $_GET['globalrestrictionids'] = $this->restrnotassign->id;
        $_GET['sesskey'] = sesskey();
        $other = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertCount(1, $other->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $other->get_current_restriction_ids());
        $this->assertEquals(array($this->restr1->id), $SESSION->rb_global_restriction);

        unset($_GET['globalrestrictionids']);
        unset($_GET['sesskey']);
        $other = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertCount(1, $other->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $other->get_current_restriction_ids());
        $this->assertEquals(array($this->restr1->id), $SESSION->rb_global_restriction);

        // First should be assigned to user with restriction as default.
        $this->setUser($this->user);
        unset($SESSION->rb_global_restriction);
        unset($_GET['globalrestrictionids']);
        unset($_GET['sesskey']);
        $one = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertCount(1, $one->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $one->get_current_restriction_ids());
        $this->assertEquals(array($this->restr1->id), $SESSION->rb_global_restriction);

        // Try to create instance with inactive allowed restriction, first should be selected instead.
        $this->setUser($this->user);
        unset($SESSION->rb_global_restriction);
        $_GET['globalrestrictionids'] = $this->restroff->id;
        $_GET['sesskey'] = sesskey();
        $other = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertCount(1, $other->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $other->get_current_restriction_ids());
        $this->assertEquals(array($this->restr1->id), $SESSION->rb_global_restriction);

        unset($_GET['globalrestrictionids']);
        unset($_GET['sesskey']);
        $other = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertCount(1, $other->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $other->get_current_restriction_ids());
        $this->assertEquals(array($this->restr1->id), $SESSION->rb_global_restriction);

        // Nothing should not be assigned to user with no restrictions automatically.
        $DB->delete_records('report_builder_global_restriction', array('id' => $this->restrallusers->id));
        rb_global_restriction_set::get_user_all_restrictions($this->usernone->id, true);
        rb_global_restriction_set::get_user_all_restrictions($this->user->id, true);

        $this->setUser($this->usernone);
        unset($SESSION->rb_global_restriction);
        unset($_GET['globalrestrictionids']);
        unset($_GET['sesskey']);
        $none = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertCount(0, $none->get_current_restriction_ids());
        $this->assertEquals(array(), $SESSION->rb_global_restriction);

        // First should be picked automatically if restriction deleted.
        $this->setUser($this->user);
        unset($_GET['globalrestrictionids']);
        unset($_GET['sesskey']);
        $SESSION->rb_global_restriction = array($this->restrallusers->id);
        $one = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertCount(1, $one->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $one->get_current_restriction_ids());
        $this->assertEquals(array($this->restr1->id), $SESSION->rb_global_restriction);

        // Disable report restriction.
        $this->reportrecord->globalrestriction = 0;
        $CFG->enableglobalrestrictions = 1;
        $this->setUser($this->user);
        $_GET['globalrestrictionids'] = $this->restr1->id;
        $_GET['sesskey'] = sesskey();
        $null = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertNull($null);

        // Disable the whole thing.
        $this->reportrecord->globalrestriction = 1;
        $CFG->enableglobalrestrictions = 0;
        $this->setUser($this->user);
        $_GET['globalrestrictionids'] = $this->restr1->id;
        $_GET['sesskey'] = sesskey();
        $null = rb_global_restriction_set::create_from_page_parameters($this->reportrecord);
        $this->assertNull($null);
    }

    public function test_create_from_ids_all() {
        global $CFG;

        // Show all records if no restriction selected.
        set_config('noactiverestrictionsbehaviour', rb_global_restriction_set::NO_ACTIVE_ALL, 'reportbuilder');

        $one = rb_global_restriction_set::create_from_ids($this->reportrecord, array($this->restr1->id, -1));
        $this->assertCount(1, $one->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $one->get_current_restriction_ids());

        $null = rb_global_restriction_set::create_from_ids($this->reportrecord, null);
        $this->assertNull($null);

        $none = rb_global_restriction_set::create_from_ids($this->reportrecord, array());
        $this->assertCount(0, $none->get_current_restriction_ids());

        $null = rb_global_restriction_set::create_from_ids($this->reportrecord, array($this->restrallrecords->id));
        $this->assertNull($null);

        $null = rb_global_restriction_set::create_from_ids($this->reportrecord, array($this->restr1->id, $this->restrallrecords->id));
        $this->assertNull($null);

        $whole = rb_global_restriction_set::create_from_ids($this->reportrecord, array($this->restr1->id, $this->restr2->id, $this->restrallusers->id,
            $this->restroff->id, $this->restrnotassign->id));
        $this->assertCount(5, $whole->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $whole->get_current_restriction_ids());
        $this->assertContains($this->restr2->id, $whole->get_current_restriction_ids());
        $this->assertContains($this->restrallusers->id, $whole->get_current_restriction_ids());
        $this->assertContains($this->restroff->id, $whole->get_current_restriction_ids());
        $this->assertContains($this->restrnotassign->id, $whole->get_current_restriction_ids());

        // Disable report restriction.
        $this->reportrecord->globalrestriction = 0;
        $CFG->enableglobalrestrictions = 1;
        $null = rb_global_restriction_set::create_from_ids($this->reportrecord, array($this->restr1->id));
        $this->assertNull($null);

        // Disable the whole thing.
        $this->reportrecord->globalrestriction = 1;
        $CFG->enableglobalrestrictions = 0;
        $null = rb_global_restriction_set::create_from_ids($this->reportrecord, array($this->restr1->id));
        $this->assertNull($null);
    }

    public function test_create_from_ids_none() {
        global $CFG;

        // Show no records if no restriction selected.
        set_config('noactiverestrictionsbehaviour', rb_global_restriction_set::NO_ACTIVE_NONE, 'reportbuilder');

        $one = rb_global_restriction_set::create_from_ids($this->reportrecord, array($this->restr1->id, -1));
        $this->assertCount(1, $one->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $one->get_current_restriction_ids());

        $none = rb_global_restriction_set::create_from_ids($this->reportrecord, array());
        $this->assertCount(0, $none->get_current_restriction_ids());

        $null = rb_global_restriction_set::create_from_ids($this->reportrecord, array($this->restrallrecords->id));
        $this->assertNull($null);

        $null = rb_global_restriction_set::create_from_ids($this->reportrecord, array($this->restr1->id, $this->restrallrecords->id));
        $this->assertNull($null);

        $whole = rb_global_restriction_set::create_from_ids($this->reportrecord, array($this->restr1->id, $this->restr2->id, $this->restrallusers->id,
            $this->restroff->id, $this->restrnotassign->id));
        $this->assertCount(5, $whole->get_current_restriction_ids());
        $this->assertContains($this->restr1->id, $whole->get_current_restriction_ids());
        $this->assertContains($this->restr2->id, $whole->get_current_restriction_ids());
        $this->assertContains($this->restrallusers->id, $whole->get_current_restriction_ids());
        $this->assertContains($this->restroff->id, $whole->get_current_restriction_ids());
        $this->assertContains($this->restrnotassign->id, $whole->get_current_restriction_ids());

        // Disable report restriction.
        $this->reportrecord->globalrestriction = 0;
        $CFG->enableglobalrestrictions = 1;
        $null = rb_global_restriction_set::create_from_ids($this->reportrecord, array($this->restr1->id));
        $this->assertNull($null);

        // Disable the whole thing.
        $this->reportrecord->globalrestriction = 1;
        $CFG->enableglobalrestrictions = 0;
        $null = rb_global_restriction_set::create_from_ids($this->reportrecord, array($this->restr1->id));
        $this->assertNull($null);
    }

    public function test_convert_qm_named_norm() {
        $params = array(-1, 0, "deadbeef", "65536", 9923432942);
        $norm = rb_global_restriction_set::convert_qm_named($this->query, $params, 'test');
        $this->assertFalse(strpos($norm[0], "?"));
        $this->assertContains(-1, $norm[1]);
        $this->assertContains(0, $norm[1]);
        $this->assertContains("deadbeef", $norm[1]);
        $this->assertContains("65536", $norm[1]);
        $this->assertContains(9923432942, $norm[1]);
    }

    /**
     * @expectedException coding_exception
     */
    public function test_convert_qm_named_less() {
        // Test less values.
        $params = array(-1, 0, 2, 9923432942);
        rb_global_restriction_set::convert_qm_named($this->query, $params);
    }

    /**
     * @expectedException coding_exception
     */
    public function test_convert_qm_named_more() {
        // Test more values.
        $params = array(-1, 0, 2, 9923432942, "test", 42);
        rb_global_restriction_set::convert_qm_named($this->query, $params);
    }

    public function test_get_user_all_restrictions() {
        $restrs = rb_global_restriction_set::get_user_all_restrictions($this->user->id, true);
        $restrsids = array_map(function($el) {
            return $el->id;
        }, $restrs);
        $this->assertContains($this->restr1->id, $restrsids);
        $this->assertContains($this->restr2->id, $restrsids);
        $this->assertContains($this->restrallusers->id, $restrsids);
    }
}
