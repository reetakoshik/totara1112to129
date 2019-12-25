<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Chris Wharton <chrisw@catalyst.net.nz>
 * @author Aaron Barnes <aaronb@catalyst.net.nz>
 * @author David Curry <david.curry@totaralms.com>
 * @package mod_facetoface
 */

/*
 * Unit tests for mod/facetoface/lib.php
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page.
}

global $CFG;
require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/completion/cron.php');
require_once($CFG->dirroot . '/completion/criteria/completion_criteria_activity.php');
require_once($CFG->dirroot . '/mod/facetoface/tests/facetoface_testcase.php');

class mod_facetoface_lib_testcase extends facetoface_testcase {

    /** @var mod_facetoface_generator */
    protected $facetoface_generator;

    /** @var totara_customfield_generator */
    protected $customfield_generator;

    protected function tearDown() {
        $this->facetoface_generator = null;
        $this->customfield_generator = null;
        $this->facetoface_data = null;
        $this->facetoface_sessions_data = null;
        $this->session_info_field = null;
        $this->session_info_data = null;
        $this->facetoface_sessions_dates_data = null;
        $this->facetoface_signups_data = null;
        $this->facetoface_signups_status_data = null;
        $this->course_data = null;
        $this->event_data = null;
        $this->role_assignments_data = null;
        $this->course_modules_data = null;
        $this->grade_items_data = null;
        $this->grade_categories_data = null;
        $this->user_data = null;
        $this->grade_grades_data = null;
        $this->user_info_field_data = null;
        $this->user_info_data_data = null;
        $this->user_info_category_data = null;
        $this->course_categories_data = null;
        $this->facetoface_session_roles_data = null;
        $this->user_preferences_data = null;
        $this->facetoface = null;
        $this->sessions = null;
        $this->sessiondata = null;
        $this->msgtrue = null;
        $this->msgfalse = null;
        $this->user1 = null;
        $this->user2 = null;
        $this->user3 = null;
        $this->user4 = null;
        $this->course1 = null;
        $this->course2 = null;
        $this->course3 = null;
        $this->course4 = null;
        parent::tearDown();
    }

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest();

        $this->facetoface_generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $this->customfield_generator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');
    }

    // Test database data.
    // TODO TL-8860: We should change the 'id' values below to be unique to each table.
    protected $facetoface_data = array(
        array('id',                     'course',           'name',                     'thirdparty',
              'thirdpartywaitlist',     'display',          'timecreated',              'timemodified',
              'shortname',              'description',      'showoncalendar',           'approvaltype'
            ),
        array(1,                        123071,             'name1',                    'thirdparty1',
              0,                        0,                  0,                          0,
              'short1',                 'desc1',            1,                          APPROVAL_NONE
            ),
        array(2,                        123072,             'name2',                    'thirdparty2',
              0,                        0,                  0,                          0,
              'short2',                 'desc2',            1,                          APPROVAL_NONE
            ),
        array(3,                        123073,             'name3',                    'thirdparty3',
              0,                        0,                  0,                          0,
              'short3',                 'desc3',            1,                          APPROVAL_NONE
            ),
        array(4,                        123074,             'name4',                    'thirdparty4',
              0,                        0,                  0,                          0,
              'short4',                 'desc4',            1,                          APPROVAL_NONE
            ),
        array(5,                        123074,             'name5',                    'thirdparty5',
              0,                        0,                  0,                          0,
             'short5',                  'desc5',            1,                          APPROVAL_MANAGER
            ),
        array(6,                        123074,             'name6',                    'thirdparty6',
              0,                        0,                  0,                          0,
             'short6',                  'desc6',            1,                          APPROVAL_MANAGER
            ),
    );

    protected $facetoface_sessions_data = array(
        array('id', 'facetoface', 'capacity', 'allowoverbook', 'details',
              'duration', 'normalcost', 'discountcost', 'timecreated', 'timemodified', 'usermodified'),
        array(1,    1,   100,    1,  'dtl1',     14400,    '$75',     '$60',     1500,   1600, 2),
        array(2,    2,    50,    0,  'dtl2',     0,        '$90',     '$0',     1400,   1500, 2),
        array(3,    3,    10,    1,  'dtl3',     25200,    '$100',    '$80',     1500,   1500, 2),
        array(4,    4,    1,     0,  'dtl4',     0,        '$10',     '$8',      500,   1900, 2),
        array(5,    5,    10,    0,  'dtl5',     0,        '$10',     '$8',      500,   1900, 2),
        array(6,    6,    10,    0,  'dtl6',     25200,    '$10',     '$8',      500,   1900, 2),
        );

    protected $session_info_field = array(
        array('id', 'shortname', 'datatype', 'description', 'sortorder', 'hidden', 'locked', 'required',
            'forceunique', 'defaultdata', 'param1', 'fullname'),
        array(1, 'shortname1', 'text', '', 1, 0, 0, 0, 0, 'defaultvalue1',  'defaultvalue1', 'fullname1'),
        array(2, 'shortname2', 'menu', '',  2, 0, 0, 0, 0, 'possible2', "possible1\npossible2", 'fullname2'),
        array(3, 'shortname3', 'menu', '', 3, 0, 1, 0, 0, 'possible3',  'possible3', 'fullname3'),
        array(4, 'shortname4', 'menu', '', 4, 0, 1, 0, 0, 'possible4',  'possible4', 'fullname4'),
    );

    protected $session_info_data = array(
        array('id', 'fieldid', 'facetofacesessionid', 'data'),
        array(1,    1,  0,  'test data1'),
        array(2,    2,  1,  'test data2'),
        array(3,    3,  2,  'test data3'),
        array(4,    4,  3,  'test data4'),
    );

    protected $facetoface_sessions_dates_data = array(
        array('id',     'sessionid',    'timestart',    'timefinish'),
        array(1,        1,              1100,           1300),
        array(2,        2,              1900,           2100),
        array(3,        3,               900,           1100),
        array(4,        3,              1200,           1400),
        array(5,        6,              1200,           1400),
    );

    protected $facetoface_signups_data = array(
        array('id', 'sessionid', 'userid', 'mailedreminder', 'discountcode', 'notificationtype'),
        array(1,    1,  1,  1,  'disc1',    7),
        array(2,    2,  2,  0,  NULL,       6),
        array(3,    2,  3,  0,  NULL,       5),
        array(4,    2,  4,  0,  'disc4',   11),
        array(5,    5,  1,  0,  'disc5',   11),
        array(6,    6,  1,  0,  'disc6',   11),
    );

    protected $facetoface_signups_status_data = array(
        array('id',     'signupid',     'statuscode',   'superceded',   'grade',
            'note',     'advice',       'createdby',    'timecreated'),
        array(1,        1,              70,             0,              99.12345,
            'note1',    'advice1',      '1',      1600),
        array(2,        2,              70,             0,              32.5,
            'note2',    'advice2',      '2',      1700),
        array(3,        3,              70,             0,              88,
            'note3',    'advice3',      '3',       700),
        array(4,        4,              70,             0,              12.5,
            'note4',    'advice4',      '4',      1100),
        array(5,        5,              40,             0,              11,
            'note5',    'advice5',      '1',      1200),
        array(6,        6,              40,             0,              11,
            'note6',    'advice6',      '1',      1200)
    );

    protected $course_data = array(
        array('id',         'category',     'sortorder',    'password',
            'fullname',    'shortname',    'idnumber',     'summary',
            'format',      'showgrades',   'modinfo',      'newsitems',
            'teacher',     'teachers',     'student',      'students',
            'guest',       'startdate',    'enrolperiod',  'numsections',
            'marker',      'maxbytes',     'showreports',  'visible',
            'hiddensections','groupmode',  'groupmodeforce','defaultgroupid',
            'lang',        'theme',        'cost',         'currency',
            'timecreated', 'timemodified', 'metacourse',   'requested',
            'restrictmodules','expirynotify','expirythreshold','notifystudents',
            'enrollable',  'enrolstartdate','enrolenddate','enrol',
            'defaultrole', 'enablecompletion','completionstartenrol',  'icon'
            ),
        array(123071,       0,              0,              'pw1',
            'name1',        'sn1',          '101',          'summary1',
            'format1',      1,              'mod1',         1,
            'teacher1',     'teachers1',    'student1',     'students1',
            0,              0,              0,              1,
            0,              0,              0,              1,
            0,              0,              0,              0,
            'lang1',        'theme1',       'cost1',        'cu1',
            0,              0,              0,              0,
            0,              0,              0,              0,
            1,              0,              0,              'enrol1',
            0,              0,              0,              'icon1'
            ),
        array(123072,       0,              0,              'pw2',
            'name2',        'sn2',          '102',          'summary2',
            'format2',      1,              'mod2',         1,
            'teacher2',     'teachers2',    'student2',     'students2',
            0,              0,              0,              1,
            0,              0,              0,              1,
            0,              0,              0,              0,
            'lang2',        'theme2',       'cost2',        'cu2',
            0,              0,              0,              0,
            0,              0,              0,              0,
            1,              0,              0,              'enrol2',
            0,              0,              0,              'icon2'
            ),
        array(123073,       0,              0,              'pw3',
            'name3',        'sn3',          '103',          'summary3',
            'format3',      1,              'mod3',         1,
            'teacher3',     'teachers3',    'student3',     'students3',
            0,              0,              0,              1,
            0,              0,              0,              1,
            0,              0,              0,              0,
            'lang3',        'theme3',       'cost3',        'cu3',
            0,              0,              0,              0,
            0,              0,              0,              0,
            1,              0,              0,              'enrol3',
            0,              0,              0,              'icon3'
            ),
        array(123074,       0,              0,              'pw4',
            'name4',        'sn4',          '104',          'summary4',
            'format4',      1,              'mod4',         1,
            'teacher4',     'teachers4',    'student4',     'students4',
            0,              0,              0,              1,
            0,              0,              0,              1,
            0,              0,              0,              0,
            'lang4',        'theme4',       'cost4',        'cu4',
            0,              0,              0,              0,
            0,              0,              0,              0,
            1,              0,              0,              'enrol4',
            0,              0,              0,              'icon4'
            ),
    );

    protected $event_data = array(
        array('id',         'name',     'description',      'format',
            'courseid',     'groupid',  'userid',           'repeatid',
            'modulename',   'instance', 'eventtype',        'timestart',
            'timeduration', 'visible',  'uuid',             'sequence',
            'timemodified'),
        array(1,            'name1',    'desc1',            0,
            123071,         1,          1,                  0,
            'facetoface',   1,          'facetofacesession',1300,
            3,              1,          'uuid1',            1,
            0),
        array(2,            'name2',    'desc2',            0,
            123072,         2,          2,                  0,
            'facetoface',   2,          'facetofacesession',2300,
            3,              2,          'uuid2',            2,
            0),
        array(3,            'name3',    'desc3',            0,
            123073,         3,          3,                  0,
            'facetoface',   3,          'facetofacesession',3300,
            3,              3,          'uuid3',            3,
            0),
        array(4,            'name4',    'desc4',            0,
            123074,         4,          4,                  0,
            'facetoface',   4,          'facetofacesession',4300,
            3,              4,          'uuid4',            4,
            0),
    );

    protected $role_assignments_data = array(
        array('id', 'roleid', 'contextid', 'userid', 'hidden',
            'timestart', 'timeend'),
        array(1,  1,  1,  1,  0,  0,  0),
        array(2,  4,  2,  2,  1,  0,  0),
        array(3,  5,  3,  3,  0,  0,  0),
        array(4,  4,  3,  2,  0,  0,  0),
    );


    // The module is always 8 as this is the f2f module. They are inserted
    // into the mdl_modules table by the unit tests in alphabetical order and
    // f2f is the eighth module (for now).
    protected $course_modules_data = array(
        array('id', 'course', 'module', 'instance', 'section', 'idnumber',
            'added', 'score', 'indent', 'visible', 'visibleold', 'groupmode',
            'groupingid', 'groupmembersonly', 'completion', 'completiongradeitemnumber',
            'completionview', 'completionview', 'completionexpected', 'availablefrom',
            'availableuntil', 'showavailability'),
        array(1, 123072, 8, 4, 5, '1001',
            6, 1, 7, 1, 1, 0,
            8, 0, 0, 10,
            0, 11, 12, 13,
            14, 1),
        array(2, 123072, 8, 4, 5, '1002',
            6, 1, 7, 1, 1, 0,
            8, 0, 0, 10,
            0, 11, 12, 13,
            14, 1),
        array(3, 123072, 8, 4, 5, '1003',
            6, 1, 7, 1, 1, 0,
            8, 0, 0, 10,
            0, 11, 12, 13,
            14, 1),
        array(4, 123072, 8, 4, 5, '1004',
            6, 1, 7, 1, 1, 0,
            8, 0, 0, 10,
            0, 11, 12, 13,
            14, 1),
        array(5, 123071, 8, 1, 5, '1005',
            6, 1, 7, 1, 1, 0,
            8, 0, 0, 10,
            0, 11, 12, 13,
            14, 1),
        array(6, 123074, 8, 5, 5, '1006',
            6, 1, 7, 1, 1, 0,
            8, 0, 0, 10,
            0, 11, 12, 13,
            14, 1),
        array(7, 123074, 8, 6, 5, '1006',
            6, 1, 7, 1, 1, 0,
            8, 0, 0, 10,
            0, 11, 12, 13,
            14, 1),
    );

    protected $grade_items_data = array(
        array('id', 'courseid', 'categoryid', 'itemname', 'itemtype',
            'itemmodule', 'iteminstance', 'itemnumber', 'iteminfo', 'idnumber',
            'calculation', 'gradetype', 'grademax', 'grademin', 'scaleid',
            'outcomeid', 'gradepass', 'multfactor', 'plusfactor', 'aggregationcoef',
            'sortorder', 'display', 'decimals', 'hidden', 'locked',
            'locktime', 'needsupdate', 'timecreated', 'timemodified'),
        array(1, 123071, 1, 'itemname1', 'type1',
            'module1', 1, 100, 'info1', '10012',
            'calc1', 1, 100, 0, 70,
            80, 0, 1.0, 0, 0,
            0, 0, 1, 0, 0,
            0, 0, 0, 0),
        array(2, 123071, 1, 'itemname1', 'type1',
            'module1', 1, 100, 'info1', '10012',
            'calc1', 1, 100, 0, 70,
            80, 0, 1.0, 0, 0,
            0, 0, 1, 0, 0,
            0, 0, 0, 0),
        array(3, 123071, 1, 'itemname1', 'type1',
            'module1', 1, 100, 'info1', '10012',
            'calc1', 1, 100, 0, 70,
            80, 0, 1.0, 0, 0,
            0, 0, 1, 0, 0,
            0, 0, 0, 0),
        array(4, 123071, 1, 'itemname1', 'type1',
            'module1', 1, 100, 'info1', '10012',
            'calc1', 1, 100, 0, 70,
            80, 0, 1.0, 0, 0,
            0, 0, 1, 0, 0,
            0, 0, 0, 0),
    );

    protected $grade_categories_data = array(
        array('id', 'courseid', 'parent', 'depth', 'path',
            'fullname', 'aggregation', 'keephigh', 'droplow',
            'aggregateonlygraded', 'aggregateoutcomes', 'aggregatesubcats',
            'timecreated', 'timemodified'),
        array(1, 123071, 1, 1, 'path1',
            'fullname1', 0, 0, 0,
            0, 0, 0,
            1300, 1400),
        array(2, 123071, 1, 1, 'path1',
            'fullname1', 0, 0, 0,
            0, 0, 0,
            1300, 1400),
        array(3, 123071, 1, 1, 'path1',
            'fullname1', 0, 0, 0,
            0, 0, 0,
            1300, 1400),
        array(4, 123071, 1, 1, 'path1',
            'fullname1', 0, 0, 0,
            0, 0, 0,
            1300, 1400),
    );

    protected $user_data = array(
        array('id',                 'auth',             'confirmed',
            'policyagreed',         'deleted',          'mnethostid',
            'username',             'password',         'idnumber',
            'firstname',            'lastname',         'email',
            'emailstop',            'icq',              'skype',
            'yahoo',                'aim',              'msn',
            'phone1',               'phone2',           'institution',
            'department',           'address',          'city',
            'country',              'lang',             'theme',
            'timezone',             'firstaccess',      'lastaccess',
            'lastlogin',            'currentlogin',     'lastip',
            'secret',               'picture',          'url',
            'description',          'mailformat',       'maildigest',
            'maildisplay',          'htmleditor',       'ajax',
            'autosubscribe',        'trackforums',      'timemodified',
            'trustbitmask',         'imagealt',         'screenreader',
            ),
        array(1,                    'auth1',            0,
            0,                      0,                  1,
            'user1',                'test',             '10011',
            'fname1',               'lname1',           'user1@example.com',
            1,                      0,                  'test',
            'test',                 'test',             'test',
            'test',                 'test',             'test',
            'test',                 'test',             'test',
            'NZ',                   'en_utf8',          'default',
            'default',              1,                  2,
            2,                      1,                  1,
            0,                      2,                  1,
            'desc1',                1,                  0,
            0,                      0,                  0,
            0,                      0,                  0,
            0,                      'imagealt1',        0
            ),
        array(2,                    'auth2',            0,
            0,                      0,                  1,
            'user2',                'test',             '20022',
            'fname2',               'lname2',           'user2@example.com',
            1,                      0,                  'test',
            'test',                 'test',             'test',
            'test',                 'test',             'test',
            'test',                 'test',             'test',
            'NZ',                   'en_utf8',          'default',
            'default',              '22',               0,
            0,                      1,                  2,
            0,                      2,                  2,
            'desc2',                2,                  0,
            0,                      0,                  0,
            0,                      0,                  0,
            0,                      'imagealt2',        0
            ),
        array(3,                    'auth3',            0,
            0,                      0,                  1,
            'user3',                'test',             '30033',
            'fname3',               'lname3',           'user3@example.com',
            1,                      0,                  'test',
            'test',                 'test',             'test',
            'test',                 'test',             'test',
            'test',                 'test',             'test',
            'NZ',                   'en_utf8',          'default',
            'default',              '32',               0,
            0,                      1,                  3,
            0,                      2,                  3,
            'desc3',                3,                  0,
            0,                      0,                  0,
            0,                      0,                  0,
            0,                      'imagealt3',        0
            ),
        array(4,                    'auth4',            0,
            0,                      0,                  1,
            'user4',                'test',             '40044',
            'fname4',               'lname4',           'user4@example.com',
            1,                      0,                  'test',
            'test',                 'test',             'test',
            'test',                 'test',             'test',
            'test',                 'test',             'test',
            'NZ',                   'en_utf8',          'default',
            'default',              '42',               0,
            0,                      1,                  4,
            0,                      2,                  4,
            'desc4',                4,                  0,
            0,                      0,                  0,
            0,                      0,                  0,
            0,                      'imagealt4',        0
            ),
    );

    protected $grade_grades_data = array(
        array('id',                 'itemid',           'userid',
            'rawgrade',             'rawgrademax',      'rawgrademin',
            'rawscaleid',           'usermodified',     'finalgrade',
            'hidden',               'locked',           'locktime',
            'exported',             'overridden',       'excluded',
            'feedback',             'feedbackformat',   'information',
            'informationformat',    'timecreated',      'timemodified'
            ),
        array(1,                    1,                  3,
            50,                     100,                0,
            30,                     1 ,                 80.2,
            0,                      0,                  0,
            0,                      0,                  0,
            'feedback1',            0,                  'info1',
            0,                      1300,               1400
        ),
        array(2,                    2,                  3,
            50,                     200,                0,
            30,                     2 ,                 80.2,
            0,                      0,                  0,
            0,                      0,                  0,
            'feedback2',            0,                  'info2',
            0,                      2300,               2400
        ),
        array(3,                    3,                  3,
            50,                     300,                0,
            30,                     3 ,                 80.2,
            0,                      0,                  0,
            0,                      0,                  0,
            'feedback3',            0,                  'info3',
            0,                      3300,               3400
        ),
        array(4,                    2,                  1,
            50,                     400,                0,
            30,                     4 ,                 80.2,
            0,                      0,                  0,
            0,                      0,                  0,
            'feedback4',            0,                  'info4',
            0,                      4300,               4400
        ),
    );

    protected $user_info_field_data = array(
        array('id',                 'shortname',         'name',
            'datatype',             'description',      'categoryid',
            'sortorder',            'required',         'locked',
            'visible',              'forceunique',      'signup',
            'defaultdata',          'param1',           'param2',
            'param3',               'param4',           'param5'
            ),
        array(1,                    'shortname1',       'name1',
            'text',                 'desc1',            0,
            0,                      0,                  0,
            0,                      0,                  0,
            0,                      'param1',           'param2',
            'param3',               'param4',           'param5'
            ),
        array(2,                    'shortname2',       'name2',
            'text',                 'desc2',            0,
            0,                      0,                  0,
            0,                      0,                  0,
            0,                      'param1',           'param2',
            'param3',               'param4',           'param5'
            ),
        array(3,                    'shortname3',       'name3',
            'text',                 'desc3',            0,
            0,                      0,                  0,
            0,                      0,                  0,
            0,                      'param1',           'param2',
            'param3',               'param4',           'param5'
            ),
        array(4,                    'shortname4',       'name4',
            'text',                 'desc4',            0,
            0,                      0,                  0,
            0,                      0,                  0,
            0,                      'param1',           'param2',
            'param4',               'param4',           'param5'
            ),
    );

    protected $user_info_data_data = array(
        array('id',    'userid',   'fieldid',  'data'),
        array(1,    1,  1,  'data1'),
        array(2,    2,  2,  'data2'),
        array(3,    3,  3,  'data3'),
        array(4,    4,  4,  'data4'),
    );

    protected $user_info_category_data = array(
        array('id', 'name', 'sortorder'),
        array(1,    'name1',          0),
        array(2,    'name2',          0),
        array(3,    'name3',          0),
        array(4,    'name4',          0),
    );

    protected $course_categories_data = array(
        array('id',     'name', 'description',  'parent',   'sortorder',
            'coursecount',  'visible',  'timemodified', 'depth',
            'path', 'theme',    'icon'),
        array(2,    'name2',    'desc2',    0,  0,
            0,    2,          0,          0,
            'path2',    'theme2',   'icon2'),
        array(3,    'name3',    'desc3',    0,  0,
            0,    3,          0,          0,
            'path3',    'theme3',   'icon3'),
        array(4,    'name4',    'desc4',    0,  0,
            0,    4,          0,          0,
            'path4',    'theme4',   'icon4'),
    );

    protected $facetoface_session_roles_data = array (
        array('id', 'sessionid', 'roleid', 'userid'),
        array(1,    1,  1,  1),
        array(2,    2,  4,  2),
        array(3,    3,  1,  3),
        array(4,    4,  4,  4),
    );

    protected $user_preferences_data = array (
        array('id',     'userid',   'name',     'value'),
        array(1,        1,          'name1',    'val1'),
        array(2,        2,          'name2',    'val2'),
        array(3,        3,          'name3',    'val3'),
        array(4,        4,          'name4',    'val4'),
    );

    protected $facetoface = array(
        'f2f0' => array(
            'id' => 1,
            'instance' => 1,
            'course' => 123074,
            'name' => 'name1',
            'thirdparty' => 'thirdparty1',
            'thirdpartywaitlist' => 0,
            'display' => 1,
            'confirmationsubject' => 'consub1',
            'confirmationinstrmngr' => '',
            'confirmationmessage' => 'conmsg1',
            'reminderinstrmngr' => '',
            'reminderperiod' => 0,
            'waitlistedsubject' => 'waitsub1',
            'cancellationinstrmngr' => '',
            'showoncalendar' => 1,
            'shortname' => 'shortname1',
            'description' => 'description1',
            'timestart' => 1300,
            'timefinish' => 1500,
            'emailmanagerconfirmation' => 'test1',
            'emailmanagerreminder' => 'test2',
            'emailmanagercancellation' => 'test3',
            'showcalendar' => 1,
            'approvaloptions' => 'approval_none',
            'approvaltype' => APPROVAL_NONE,
            'requestsubject' => 'reqsub1',
            'requestmessage' => 'reqmsg1',
            'requestinstrmngr' => '',
            'usercalentry' => false,
            'multiplesessions' => 0,
            'managerreserve' => 0,
            'maxmanagerreserves' => 1,
            'reservecanceldays' => 1,
            'reservedays' => 2
        ),
        'f2f1' => array(
            'id' => 2,
            'instance' => 2,
            'course' => 123073,
            'name' => 'name2',
            'thirdparty' => 'thirdparty2',
            'thirdpartywaitlist' => 0,
            'display' => 0,
            'confirmationsubject' => 'consub2',
            'confirmationinstrmngr' => 'conins2',
            'confirmationmessage' => 'conmsg2',
            'reminderinstrmngr' => 'remmngr2',
            'reminderperiod' => 1,
            'waitlistedsubject' => 'waitsub2',
            'cancellationinstrmngr' => 'canintmngr2',
            'showoncalendar' => 1,
            'shortname' => 'shortname2',
            'description' => 'description2',
            'timestart' => 2300,
            'timefinish' => 2330,
            'emailmanagerconfirmation' => 'test2',
            'emailmanagerreminder' => 'test2',
            'emailmanagercancellation' => 'test3',
            'showcalendar' => 1,
            'approvaloptions' => 'approval_manager',
            'approvaltype' => APPROVAL_MANAGER,
            'requestsubject' => 'reqsub2',
            'requestmessage' => 'reqmsg2',
            'requestinstrmngr' => 'reqinstmngr2',
            'usercalentry' => true,
            'multiplesessions' => 0,
            'managerreserve' => 0,
            'maxmanagerreserves' => 1,
            'reservecanceldays' => 1,
            'reservedays' => 2
        ),
    );

    protected $sessions = array(
        'sess0' => array(
            'id' => 1,
            'facetoface' => 1,
            'capacity' => 0,
            'allowoverbook' => 1,
            'details' => 'details1',
            'sessiondates' => array(
                array(
                    'id' => 20,
                    'timestart' => 0,
                    'timefinish' => 0,
                )
            ),
            'duration' => 10800,
            'normalcost' => '$100',
            'discountcost' => '$75',
            'timecreated' => 1300,
            'timemodified' => 1400,
        ),
        'sess1' => array(
            'id' => 2,
            'facetoface' => 2,
            'capacity' => 3,
            'allowoverbook' => 0,
            'details' => 'details2',
            'sessiondates' => array(),
            'duration' => 21600,
            'normalcost' => '$100',
            'discountcost' => '$75',
            'timecreated' => 1300,
            'timemodified' => 1400,
        ),
    );

    protected $sessiondata = array(
        'sess0' => array(
            'id' => 1,
            'fieldid' => 1,
            'sessionid' => 1,
            'data' => 'testdata1',
            'discountcost' => '$60',
            'normalcost' => '$75',
        ),
        'sess1' => array(
            'id' => 2,
            'fieldid' => 2,
            'sessionid' => 2,
            'data' => 'testdata2',
            'discountcost' => '',
            'normalcost' => '$90',
        ),
    );

    /** @var string */
    protected $msgtrue = 'should be true';

    /** @var string */
    protected $msgfalse = 'should be false';

    /** @var stdClass */
    protected $user1;

    /** @var stdClass */
    protected $user2;

    /** @var stdClass */
    protected $user3;

    /** @var stdClass */
    protected $user4;

    /** @var stdClass */
    protected $course1;

    /** @var stdClass */
    protected $course2;

    /** @var stdClass */
    protected $course3;

    /** @var stdClass */
    protected $course4;

    /**
     * Set up site with some very basic basic seminar data.
     *
     * NOTE: please set up your site with generators for more complex testing scenarios.
     */
    protected function init_sample_data() {
        global $DB;

        // Fix the facetoface module id, as other non-core modules might be installed that could change the id.
        $f2fmid = $DB->get_field('modules', 'id', array('name' => 'facetoface'));
        foreach ($this->course_modules_data as $i => $md) {
            if ($i == 0) {
                // Skip table headers.
                continue;
            }
            $this->course_modules_data[$i][2] = $f2fmid;
        }

        $this->loadDataSet(
            $this->createArrayDataSet(
                array(
                    'course'            => $this->course_data,
                    'facetoface_signups'            => $this->facetoface_signups_data,
                    'facetoface_sessions'           => $this->facetoface_sessions_data,
                    'facetoface_session_info_field' => $this->session_info_field,
                    'facetoface_session_info_data'  => $this->session_info_data,
                    'facetoface'                    => $this->facetoface_data,
                    'facetoface_sessions_dates'     => $this->facetoface_sessions_dates_data,
                    'facetoface_signups_status'     => $this->facetoface_signups_status_data,
                    'event'                         => $this->event_data,
                    'role_assignments'              => $this->role_assignments_data,
                    'course_modules'                => $this->course_modules_data,
                    'grade_items'                   => $this->grade_items_data,
                    'grade_categories'              => $this->grade_categories_data,
                    'grade_grades'                  => $this->grade_grades_data,
                    'user_info_field'               => $this->user_info_field_data,
                    'user_info_data'                => $this->user_info_data_data,
                    'user_info_category'            => $this->user_info_category_data,
                    'course_categories'             => $this->course_categories_data,
                    'facetoface_session_roles'      => $this->facetoface_session_roles_data,
                    'user_preferences'              => $this->user_preferences_data,
                )
            )
        );

        $this->user1 = $this->getDataGenerator()->create_user();
        $this->user2 = $this->getDataGenerator()->create_user();
        $this->user3 = $this->getDataGenerator()->create_user();
        $this->user4 = $this->getDataGenerator()->create_user();

        $this->course1 = $this->getDataGenerator()->create_course(array('fullname'=> 'Intro'));
        $this->course2 = $this->getDataGenerator()->create_course(array('fullname'=> 'Basics'));
        $this->course3 = $this->getDataGenerator()->create_course(array('fullname'=> 'Advanced'));
        $this->course4 = $this->getDataGenerator()->create_course(array('fullname'=> 'Pro'));

        // Set up stuff which couldn't be done above.
        $guestja = \totara_job\job_assignment::create_default(1);
        $adminja = \totara_job\job_assignment::create_default(2);
        $data = array(
            'userid' => $this->user1->id,
            'fullname' => 'fullname1',
            'shortname' => 'shortname1',
            'idnumber' => 'idnumber1',
            'description' => 'desc1',
            'startdate' => 900,
            'enddate' => 1000,
            'organisationid' => 1122,
            'positionid' => 2,
            'managerjaid' => $adminja->id);
        \totara_job\job_assignment::create($data);
        $data = array(
            'userid' => $this->user2->id,
            'fullname' => 'fullname2',
            'shortname' => 'shortname2',
            'idnumber' => 'idnumber2',
            'description' => 'desc2',
            'startdate' => 900,
            'enddate' => 2000,
            'organisationid' => 2222,
            'positionid' => 2,
            'managerjaid' => $guestja->id);
        \totara_job\job_assignment::create($data);
        $data = array(
            'userid' => $this->user3->id,
            'fullname' => 'fullname3',
            'shortname' => 'shortname3',
            'idnumber' => 'idnumber3',
            'description' => 'desc3',
            'startdate' => 900,
            'enddate' => 3000,
            'organisationid' => 3322,
            'positionid' => 2,
            'managerjaid' => $guestja->id);
        \totara_job\job_assignment::create($data);
        $data = array(
            'userid' => $this->user4->id,
            'fullname' => 'fullname4',
            'shortname' => 'shortname4',
            'idnumber' => 'idnumber4',
            'description' => 'desc4',
            'startdate' => 900,
            'enddate' => 4000,
            'organisationid' => 4422,
            'positionid' => 2,
            'managerjaid' => $guestja->id);
        \totara_job\job_assignment::create($data);
    }

    function test_facetoface_get_status() {
        $this->init_sample_data();

        // check for valid status codes
        $this->assertEquals(facetoface_get_status(10), 'user_cancelled');
        //$this->assertEquals(facetoface_get_status(20), 'session_cancelled'); //not yet implemented
        $this->assertEquals(facetoface_get_status(30), 'declined');
        $this->assertEquals(facetoface_get_status(40), 'requested');
        $this->assertEquals(facetoface_get_status(50), 'approved');
        $this->assertEquals(facetoface_get_status(60), 'waitlisted');
        $this->assertEquals(facetoface_get_status(70), 'booked');
        $this->assertEquals(facetoface_get_status(80), 'no_show');
        $this->assertEquals(facetoface_get_status(90), 'partially_attended');
        $this->assertEquals(facetoface_get_status(100), 'fully_attended');
    }

    function test_facetoface_cost() {
        $this->init_sample_data();

        // Test variables - case WITH discount.
        $sessiondata = $this->sessiondata['sess0'];
        $sess0 = (object)$sessiondata;

        $userid1 = 1;
        $sessionid1 = 1;

        // Variable for test case NO discount.
        $sessiondata1 = $this->sessiondata['sess1'];
        $sess1 = (object)$sessiondata1;

        $userid2 = 2;
        $sessionid2 = 2;

        // Test WITH discount.
        $this->assertEquals(facetoface_cost($userid1, $sessionid1, $sess0), '$60');

        // Test NO discount case.
        $this->assertEquals(facetoface_cost($userid2, $sessionid2, $sess1), '$90');
    }

    function test_facetoface_fix_settings() {
        $this->init_sample_data();

        // test for facetoface object
        $facetoface1 = $this->facetoface['f2f0'];
        $f2f = (object)$facetoface1;

        // Test - for empty values.
        $this->assertEquals(facetoface_fix_settings($f2f), null);
    }

    function test_facetoface_add_instance() {
        $this->init_sample_data();

        // Define test variables.
        $facetoface1 = $this->facetoface['f2f0'];
        $f2f = (object)$facetoface1;

        $this->assertEquals(facetoface_add_instance($f2f), 7);
    }

    function test_facetoface_update_instance() {
        $this->init_sample_data();

        // Define test variables.
        $facetoface1 = $this->facetoface['f2f0'];
        $f2f = (object)$facetoface1;

        // Test.
        $this->assertTrue((bool)facetoface_update_instance($f2f));
    }

    function test_facetoface_delete_instance() {
        global $DB;

        $this->init_sample_data();

        // Test variables.
        $id = 1;

        // Test.
        $sink = $this->redirectMessages();
        $this->assertTrue((bool)facetoface_delete_instance($id));
        $sink->close();

        $this->assertEquals(0, $DB->count_records_select('facetoface', 'id = ?', array($id)));

        $this->assertEquals(0, $DB->count_records_select('facetoface_interest', 'facetoface = ?', array($id)));

        // Notifications.
        $this->assertEquals(0, $DB->count_records_select('facetoface_notification', 'facetofaceid = ?', array($id)));

        $this->assertEquals(0, $DB->count_records_select('facetoface_sessions', 'facetoface = ?', array($id)));

        $this->assertEquals(0, $DB->count_records_select('event', 'modulename = ? AND instance = ?', array('facetoface', $id)));

    }

    function test_facetoface_cleanup_session_data() {
        $this->init_sample_data();

        //define session object for test
        //valid values
        $sessionValid = new stdClass();
        $sessionValid->capacity = '250';
        $sessionValid->normalcost = '70';
        $sessionValid->discountcost = '50';

        //invalid values
        $sessionInvalid = new stdClass();
        $sessionInvalid->capacity = '100999';
        $sessionInvalid->normalcost = '-7';
        $sessionInvalid->discountcost = 'b';

        // Test - for valid values.
        $this->assertEquals(facetoface_cleanup_session_data($sessionValid), $sessionValid);

        // Test - for invalid values.
        $this->assertEquals(facetoface_cleanup_session_data($sessionInvalid), $sessionInvalid);
    }

    function test_facetoface_add_session() {
        $this->init_sample_data();

        // Variable for test.
        $session1 = $this->sessions['sess0'];
        $sess0 = (object)$session1;
        $sess0->usermodified = time();

        // Test.
        $this->assertNotEmpty(facetoface_add_session($sess0, null));
    }

    function test_facetoface_update_session() {
        $this->init_sample_data();

        // Test variables.
        $session1 = $this->sessions['sess0'];
        $sess0 = (object)$session1;

        $sessiondates = new stdClass();
        $sessiondates->sessionid = 1;
        $sessiondates->timestart = 1300;
        $sessiondates->timefinish = 1400;
        $sessiondates->sessionid = 1;

        // Test.
        $this->assertTrue((bool)facetoface_update_session($sess0, array($sessiondates)), $this->msgtrue);
    }

    function test_facetoface_update_attendees() {
        $this->init_sample_data();

        // Test variables.
        $session1 = $this->sessions['sess0'];
        $sess0 = (object)$session1;
        $sess0->sessiondates[0] = (object)($sess0->sessiondates[0]);

        $this->assertTrue((bool)facetoface_update_attendees($sess0), $this->msgtrue);
    }

    function test_facetoface_get_facetoface_menu() {
        $this->init_sample_data();

        // positive test
        $menu = facetoface_get_facetoface_menu();
        $this->assertEquals('array', gettype($menu));
    }

    function test_facetoface_delete_session() {
        global $DB, $CFG;
        require_once("$CFG->dirroot/totara/hierarchy/prefix/position/lib.php");

        $this->init_sample_data();

        // Set up some users.
        $teacher1 = $this->getDataGenerator()->create_user();
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();
        $student3 = $this->getDataGenerator()->create_user(); // Signup and cancel.
        $manager = $this->getDataGenerator()->create_user();

        $managerja = \totara_job\job_assignment::create_default($manager->id);
        $data = array(
            'userid' => $student1->id,
            'fullname' => 'student1ja',
            'shortname' => 'student1ja',
            'idnumber' => 'student1ja',
            'managerjaid' => $managerja->id);
        \totara_job\job_assignment::create($data);
        $data = array(
            'userid' => $student2->id,
            'fullname' => 'student2ja',
            'shortname' => 'student2ja',
            'idnumber' => 'student2ja',
            'managerjaid' => $managerja->id);
        \totara_job\job_assignment::create($data);
        $data = array(
            'userid' => $student3->id,
            'fullname' => 'student3ja',
            'shortname' => 'student3ja',
            'idnumber' => 'student3ja',
            'managerjaid' => $managerja->id);
        \totara_job\job_assignment::create($data);

        // Set up a course.
        $course1 = $this->getDataGenerator()->create_course();
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        // Set up some user enrolments.
        $this->getDataGenerator()->enrol_user($teacher1->id, $course1->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($student1->id, $course1->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course1->id, $studentrole->id);

        // Set up some facetoface Event customfields.
        $cfids = array();
        $cfgenerator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');
        $cfids = array_merge($cfids, $cfgenerator->create_text('facetoface_session', array('event_text')));
        $cfids = array_merge($cfids, $cfgenerator->create_datetime('facetoface_session', array('event_date' => array())));
        $cfids = array_merge($cfids, $cfgenerator->create_multiselect('facetoface_session', array('event_multi' => array('opt1', 'opt2'))));

        // Set up some facetoface Signup customfields.
        $cfids = array_merge($cfids, $cfgenerator->create_text('facetoface_signup', array('signup_text')));
        $cfids = array_merge($cfids, $cfgenerator->create_datetime('facetoface_signup', array('signup_date' => array('shortname' => 'signupdate'))));
        $cfids = array_merge($cfids, $cfgenerator->create_multiselect('facetoface_signup', array('signup_multi' => array('opt1', 'opt2'))));

        // Set up some facetoface Cancellation customfields.
        $cfids = array_merge($cfids, $cfgenerator->create_text('facetoface_cancellation', array('cancellation_text')));
        $cfids = array_merge($cfids, $cfgenerator->create_datetime('facetoface_cancellation', array('cancellation_date' => array('shortname' => 'cancellationdate'))));
        $cfids = array_merge($cfids, $cfgenerator->create_multiselect('facetoface_cancellation', array('cancellation_multi' => array('opt1', 'opt2'))));

        // Set up some facetoface Session Cancellation customfields.
        $cfids = array_merge($cfids, $cfgenerator->create_text('facetoface_sessioncancel', array('sessioncancel_text')));
        $cfids = array_merge($cfids, $cfgenerator->create_datetime('facetoface_sessioncancel', array('sessioncancel_date' => array('shortname' => 'sessioncanceldate'))));
        $cfids = array_merge($cfids, $cfgenerator->create_multiselect('facetoface_sessioncancel', array('sessioncancel_multi' => array('opt1', 'opt2'))));

        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface1',
            'course' => $course1->id
        );
        $facetoface1 = $facetofacegenerator->create_instance($facetofacedata);

        // Session that starts in 24hrs time.
        // This session should trigger a mincapacity warning now as cutoff is 24:01 hrs before start time.
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + DAYSECS;
        $sessiondate->timefinish = time() + DAYSECS + 60;
        $sessiondate->sessiontimezone = 'Pacific/Auckland';

        $session1data = array(
            'facetoface' => $facetoface1->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        );

        $session2data = array(
            'facetoface' => $facetoface1->id,
            'capacity' => 5,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
            'mincapacity' => '1',
            'cutoff' => DAYSECS
        );
        $session1id = $facetofacegenerator->add_session($session1data);
        $session2id = $facetofacegenerator->add_session($session2data);

        $session1 = facetoface_get_session($session1id);

        $session2 = facetoface_get_session($session2id);

        // Add customfields data to these facetoface events.
        $cfgenerator->set_text($session1, $cfids['event_text'], 'value1', 'facetofacesession', 'facetoface_session');
        $cfgenerator->set_multiselect($session1, $cfids['event_multi'], array('opt1', 'opt2'), 'facetofacesession', 'facetoface_session');
        $cfgenerator->set_datetime($session1, $cfids['event_date'], time(), 'facetofacesession', 'facetoface_session');

        $cfgenerator->set_text($session2, $cfids['event_text'], 'value2', 'facetofacesession', 'facetoface_session');
        $cfgenerator->set_multiselect($session2, $cfids['event_multi'], array('opt1'), 'facetofacesession', 'facetoface_session');
        $cfgenerator->set_datetime($session2, $cfids['event_date'], time(), 'facetofacesession', 'facetoface_session');

        $discountcode1 = 'disc1';
        $notificationtype1 = 1;
        $statuscode1 = MDL_F2F_STATUS_REQUESTED;

        $sink = $this->redirectMessages();
        $this->setUser($student1);
        // Signup user1 to session 1.
        $this->assertTrue((bool)facetoface_user_signup($session1, $facetoface1, $course1, $discountcode1, $notificationtype1, $statuscode1), $this->msgtrue);

        $signup11 = $DB->get_record('facetoface_signups', array('userid' => $student1->id, 'sessionid' => $session1->id));
        $cfgenerator->set_text($signup11, $cfids['signup_text'], 'value2', 'facetofacesignup', 'facetoface_signup');
        $cfgenerator->set_multiselect($signup11, $cfids['signup_multi'], array('opt1'), 'facetofacesignup', 'facetoface_signup');
        $cfgenerator->set_datetime($signup11, $cfids['signup_date'], time(), 'facetofacesignup', 'facetoface_signup');

        $this->setUser($student2);
        // Signup user2 to session 1.
        $this->assertTrue((bool)facetoface_user_signup($session1, $facetoface1, $course1, $discountcode1, $notificationtype1, $statuscode1), $this->msgtrue);

        $signup21 = $DB->get_record('facetoface_signups', array('userid' => $student2->id, 'sessionid' => $session1->id));
        $cfgenerator->set_text($signup21, $cfids['signup_text'], 'value2', 'facetofacesignup', 'facetoface_signup');
        $cfgenerator->set_multiselect($signup21, $cfids['signup_multi'], array('opt1'), 'facetofacesignup', 'facetoface_signup');
        $cfgenerator->set_datetime($signup21, $cfids['signup_date'], time(), 'facetofacesignup', 'facetoface_signup');

        // Signup user2 to session 2.
        $this->assertTrue((bool)facetoface_user_signup($session2, $facetoface1, $course1, $discountcode1, $notificationtype1, $statuscode1), $this->msgtrue);

        $signup22 = $DB->get_record('facetoface_signups', array('userid' => $student2->id, 'sessionid' => $session2->id));
        $cfgenerator->set_text($signup22, $cfids['signup_text'], 'value2', 'facetofacesignup', 'facetoface_signup');
        $cfgenerator->set_multiselect($signup22, $cfids['signup_multi'], array('opt1'), 'facetofacesignup', 'facetoface_signup');
        $cfgenerator->set_datetime($signup22, $cfids['signup_date'], time(), 'facetofacesignup', 'facetoface_signup');

        $this->setUser($student3);
        // Signup user3 to session 1 then cancel them.
        $this->assertTrue((bool)facetoface_user_signup($session1, $facetoface1, $course1, $discountcode1, $notificationtype1, $statuscode1), $this->msgtrue);
        $this->assertTrue((bool)facetoface_user_cancel($session1, $student3->id));

        $signup31 = $DB->get_record('facetoface_signups', array('userid' => $student3->id, 'sessionid' => $session1->id));
        $cfgenerator->set_text($signup31, $cfids['cancellation_text'], 'value2', 'facetofacecancellation', 'facetoface_cancellation');
        $cfgenerator->set_multiselect($signup31, $cfids['cancellation_multi'], array('opt1'), 'facetofacecancellation', 'facetoface_cancellation');
        $cfgenerator->set_datetime($signup31, $cfids['cancellation_date'], time(), 'facetofacecancellation', 'facetoface_cancellation');

        // Signup user3 to session 2 then cancel them.
        $this->assertTrue((bool)facetoface_user_signup($session2, $facetoface1, $course1, $discountcode1, $notificationtype1, $statuscode1), $this->msgtrue);
        $this->assertTrue((bool)facetoface_user_cancel($session2, $student3->id));

        $signup32 = $DB->get_record('facetoface_signups', array('userid' => $student3->id, 'sessionid' => $session2->id));
        $cfgenerator->set_text($signup32, $cfids['cancellation_text'], 'value2', 'facetofacecancellation', 'facetoface_cancellation');
        $cfgenerator->set_multiselect($signup32, $cfids['cancellation_multi'], array('opt1'), 'facetofacecancellation', 'facetoface_cancellation');
        $cfgenerator->set_datetime($signup32, $cfids['cancellation_date'], time(), 'facetofacecancellation', 'facetoface_cancellation');

        // Add session cancellation data
        $cfgenerator->set_text($session1, $cfids['sessioncancel_text'], 'value2', 'facetofacesessioncancel', 'facetoface_sessioncancel');
        $cfgenerator->set_multiselect($session1, $cfids['sessioncancel_multi'], array('opt1'), 'facetofacesessioncancel', 'facetoface_sessioncancel');
        $cfgenerator->set_datetime($session1, $cfids['sessioncancel_date'], time(), 'facetofacesessioncancel', 'facetoface_sessioncancel');

        $cfgenerator->set_text($session2, $cfids['sessioncancel_text'], 'value2', 'facetofacesessioncancel', 'facetoface_sessioncancel');
        $cfgenerator->set_multiselect($session2, $cfids['sessioncancel_multi'], array('opt1'), 'facetofacesessioncancel', 'facetoface_sessioncancel');
        $cfgenerator->set_datetime($session2, $cfids['sessioncancel_date'], time(), 'facetofacesessioncancel', 'facetoface_sessioncancel');

        $sink->close();

        // Check we have data in before deleting session data.
        $this->assertTrue($DB->record_exists('facetoface_sessions', array('id' => $session1id)));
        $this->assertTrue($DB->record_exists('facetoface_signups', array('id' => $session1id)));
        $this->assertEquals(4, $DB->count_records_select(
            'facetoface_signups_status',
            "signupid IN (SELECT id FROM {facetoface_signups} WHERE sessionid = :sessionid)",
            array('sessionid' => $session1id)));
        $this->assertTrue($DB->record_exists('facetoface_sessions_dates', array('sessionid' => $session1id)));

        // Check customfield data for session1 and session2.
        $cfsession1 = $DB->get_records('facetoface_session_info_data', array('facetofacesessionid' => $session1->id));
        $this->assertCount(3, $cfsession1);
        list($sqlin, $paramin) = $DB->get_in_or_equal(array_keys($cfsession1));
        $sqlparams = 'SELECT id FROM {facetoface_session_info_data_param} WHERE dataid ';
        $session1params = $DB->get_records_sql($sqlparams . $sqlin, $paramin);
        $this->assertCount(2, $session1params);

        $cfsession2 = $DB->get_records('facetoface_session_info_data', array('facetofacesessionid' => $session2->id));
        $this->assertCount(3, $cfsession2);
        list($sqlin2, $paramin2) = $DB->get_in_or_equal(array_keys($cfsession2));
        $session2params = $DB->get_records_sql($sqlparams . $sqlin2, $paramin2);
        $this->assertCount(1, $session2params);

        // Check customfield data for session1 and session2.
        $cfsessioncancel1 = $DB->get_records('facetoface_sessioncancel_info_data', array('facetofacesessioncancelid' => $session1->id));
        $this->assertCount(3, $cfsessioncancel1);

        $cfsessioncancel2 = $DB->get_records('facetoface_sessioncancel_info_data', array('facetofacesessioncancelid' => $session2->id));
        $this->assertCount(3, $cfsessioncancel2);

        $sqlparamssessioncancel = 'SELECT id FROM {facetoface_sessioncancel_info_data_param} WHERE dataid ';

        list($sqlinsessioncancel1, $paraminsessioncancel1) = $DB->get_in_or_equal(array_keys($cfsessioncancel1));
        $this->assertCount(1, $DB->get_records_sql($sqlparamssessioncancel . $sqlinsessioncancel1, $paraminsessioncancel1));

        list($sqlinsessioncancel2, $paraminsessioncancel2) = $DB->get_in_or_equal(array_keys($cfsessioncancel2));
        $this->assertCount(1, $DB->get_records_sql($sqlparamssessioncancel . $sqlinsessioncancel2, $paraminsessioncancel2));

        // Call facetoface_delete_session function for session1.
        $sink = $this->redirectMessages();
        $this->assertTrue((bool)facetoface_delete_session($session1));
        $sink->close();

        // Check data after calling facetoface_delete_session.
        $this->assertFalse($DB->record_exists('facetoface_sessions', array('id' => $session1id)));
        $this->assertFalse($DB->record_exists('facetoface_signups', array('sessionid' => $session1id)));
        $this->assertEquals(0, $DB->count_records_select(
            'facetoface_signups_status',
            "signupid IN (SELECT id FROM {facetoface_signups} WHERE sessionid = :sessionid)",
            array('sessionid' => $session1id)));
        $this->assertFalse($DB->record_exists('facetoface_sessions_dates', array('sessionid' => $session1id)));
        $this->assertEquals(0, $DB->count_records('facetoface_session_info_data', array('facetofacesessionid' => $session1->id)));
        $this->assertEmpty($DB->get_records_sql($sqlparams . $sqlin, $paramin));

        // Check session cancellation data
        $this->assertEquals(0, $DB->count_records('facetoface_sessioncancel_info_data', array('facetofacesessioncancelid' => $session1->id)));
        $this->assertEquals(3, $DB->count_records('facetoface_sessioncancel_info_data', array('facetofacesessioncancelid' => $session2->id)));
        $this->assertCount(0, $DB->get_records_sql($sqlparamssessioncancel . $sqlinsessioncancel1, $paraminsessioncancel1));
        $this->assertCount(1, $DB->get_records_sql($sqlparamssessioncancel . $sqlinsessioncancel2, $paraminsessioncancel2));

        // Check session notification sent and hist.
        $this->assertEquals(0, $DB->count_records('facetoface_notification_sent', array('sessionid' => $session1->id)));
        $this->assertEquals(0, $DB->count_records('facetoface_notification_hist', array('sessionid' => $session1->id)));
        $this->assertEquals(2, $DB->count_records('facetoface_notification_sent', array('sessionid' => $session2->id)));
        $this->assertEquals(2, $DB->count_records('facetoface_notification_hist', array('sessionid' => $session2->id)));

        // Check the customfield data after associated session deletion.
        $this->assertFalse($DB->record_exists('facetoface_signup_info_data', array('facetofacesignupid' => $signup11->id)));
        $this->assertFalse($DB->record_exists('facetoface_signup_info_data', array('facetofacesignupid' => $signup21->id)));
        $this->assertFalse($DB->record_exists('facetoface_signup_info_data', array('facetofacesignupid' => $signup31->id)));
        $this->assertFalse($DB->record_exists('facetoface_cancellation_info_data', array('facetofacecancellationid' => $signup31->id)));

        // Check data for session2 is intact.
        $this->assertTrue($DB->record_exists('facetoface_sessions', array('id' => $session2id)));
        $this->assertTrue($DB->record_exists('facetoface_signups', array('sessionid' => $session2id)));
        $this->assertEquals(3, $DB->count_records_select(
            'facetoface_signups_status',
            "signupid IN (SELECT id FROM {facetoface_signups} WHERE sessionid = :sessionid)",
            array('sessionid' => $session2id)));
        $this->assertTrue($DB->record_exists('facetoface_sessions_dates', array('sessionid' => $session2id)));
        $this->assertEquals(3, $DB->count_records('facetoface_session_info_data', array('facetofacesessionid' => $session2->id)));
        $session2params = $DB->get_records_sql($sqlparams . $sqlin2, $paramin2);
        $this->assertCount(1, $session2params);

        // Check the customfield data for session 2 is intact.
        $this->assertEquals(3, $DB->count_records('facetoface_signup_info_data', array('facetofacesignupid' => $signup22->id)));
        $this->assertEquals(3, $DB->count_records('facetoface_cancellation_info_data', array('facetofacecancellationid' => $signup32->id)));
    }

    function test_facetoface_delete_signups_for_session() {
        global $DB;

        /** @var \mod_facetoface_generator $f2fgenerator */
        $f2fgenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $course = $this->getDataGenerator()->create_course();

        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();

        $session1 = $f2fgenerator->create_session_for_course($course);
        $session2 = $f2fgenerator->create_session_for_course($course);

        $signups = [];
        $signups[11] = $f2fgenerator->create_signup($student1, $session1);

        $f2fgenerator->create_cancellation($student1, $session1);
        $f2fgenerator->create_customfield_data($signups[11], 'cancellation', 1, 2);

        $signups[12] = $f2fgenerator->create_signup($student1, $session2);
        $signups[21] = $f2fgenerator->create_signup($student2, $session1);
        $signups[22] = $f2fgenerator->create_signup($student2, $session2);

        $f2fgenerator->create_customfield_data($signups[12], 'signup', 3, 4);
        $f2fgenerator->create_customfield_data($signups[21], 'signup', 5, 3);
        $f2fgenerator->create_customfield_data($signups[22], 'signup', 2, 1);

        $f2fgenerator->create_cancellation($student2, $session2);
        $f2fgenerator->create_customfield_data($signups[22], 'cancellation', 1, 1);

        // Check initial data.
        $this->assertTrue($DB->record_exists('facetoface_sessions', array('id' => $session1->id)));
        $this->assertTrue($DB->record_exists('facetoface_sessions', array('id' => $session2->id)));
        $this->assertCount(2, $DB->get_records('facetoface_signups', array('sessionid' => $session1->id)));
        $this->assertCount(2, $DB->get_records('facetoface_signups', array('sessionid' => $session2->id)));
        $this->assert_count_signups_status($session1->id, 3);
        $this->assert_count_signups_status($session2->id, 3);
        // Check customfield data for session1.
        $this->assert_count_customfield_data('signup', [$signups[11]->id, $signups[21]->id], 5, 3);
        $this->assert_count_customfield_data('cancellation', [$signups[11]->id, $signups[21]->id], 1, 2);
        // Check customfield data for session2.
        $this->assert_count_customfield_data('signup', [$signups[12]->id, $signups[22]->id], 5, 5);
        $this->assert_count_customfield_data('cancellation', [$signups[12]->id, $signups[22]->id], 1, 1);

        facetoface_delete_signups_for_session($session2->id);

        // Check data after deletion.
        $this->assertTrue($DB->record_exists('facetoface_sessions', array('id' => $session1->id)));
        $this->assertTrue($DB->record_exists('facetoface_sessions', array('id' => $session2->id)));
        $this->assertCount(2, $DB->get_records('facetoface_signups', array('sessionid' => $session1->id)));
        $this->assertCount(0, $DB->get_records('facetoface_signups', array('sessionid' => $session2->id)));
        $this->assert_count_signups_status($session1->id, 3);
        $this->assert_count_signups_status($session2->id, 0);
        // Check customfield data for session1.
        $this->assert_count_customfield_data('signup', [$signups[11]->id, $signups[21]->id], 5, 3);
        $this->assert_count_customfield_data('cancellation', [$signups[11]->id, $signups[21]->id], 1, 2);
        // Check customfield data for session2.
        $this->assert_count_customfield_data('signup', [$signups[12]->id, $signups[22]->id], 0, 0);
        $this->assert_count_customfield_data('cancellation', [$signups[12]->id, $signups[22]->id], 0, 0);
    }

    function test_facetoface_delete_signups() {
        global $DB;

        $f2fgenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $course = $this->getDataGenerator()->create_course();

        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();

        $session1 = $f2fgenerator->create_session_for_course($course);
        $session2 = $f2fgenerator->create_session_for_course($course);

        $signups = [];
        $signups[11] = $f2fgenerator->create_signup($student1, $session1);
        $f2fgenerator->create_customfield_data($signups[11], 'signup', 3, 0);

        $f2fgenerator->create_cancellation($student1, $session1);
        $f2fgenerator->create_customfield_data($signups[11], 'cancellation', 1, 2);

        $signups[12] = $f2fgenerator->create_signup($student1, $session2);
        $signups[21] = $f2fgenerator->create_signup($student2, $session1);
        $signups[22] = $f2fgenerator->create_signup($student2, $session2);
        $f2fgenerator->create_customfield_data($signups[12], 'signup', 4, 1);
        $f2fgenerator->create_customfield_data($signups[21], 'signup', 5, 3);
        $f2fgenerator->create_customfield_data($signups[22], 'signup', 2, 3);

        $f2fgenerator->create_cancellation($student2, $session2);
        $f2fgenerator->create_customfield_data($signups[22], 'cancellation', 2, 1);

        // Check initial data.
        $this->assertTrue($DB->record_exists('facetoface_sessions', array('id' => $session1->id)));
        $this->assertTrue($DB->record_exists('facetoface_sessions', array('id' => $session2->id)));
        $this->assertCount(2, $DB->get_records('facetoface_signups', array('sessionid' => $session1->id)));
        $this->assertCount(2, $DB->get_records('facetoface_signups', array('sessionid' => $session2->id)));
        $this->assert_count_signups_status($session1->id, 3);
        $this->assert_count_signups_status($session2->id, 3);
        // Check customfield data for session1.
        $this->assert_count_customfield_data('signup', [$signups[11]->id, $signups[21]->id], 8, 3);
        $this->assert_count_customfield_data('cancellation', [$signups[11]->id, $signups[21]->id], 1, 2);
        // Check customfield data for session2.
        $this->assert_count_customfield_data('signup', [$signups[12]->id, $signups[22]->id], 6, 4);
        $this->assert_count_customfield_data('cancellation', [$signups[12]->id, $signups[22]->id], 2, 1);

        // Delete two signups covering both users and both sessions.
        facetoface_delete_signups([$signups[11]->id, $signups[22]->id]);

        // Check data after deletion.
        $this->assertCount(1, $DB->get_records('facetoface_signups', array('sessionid' => $session1->id)));
        $this->assertCount(1, $DB->get_records('facetoface_signups', array('sessionid' => $session2->id)));
        $this->assert_count_signups_status($session1->id, 1);
        $this->assert_count_signups_status($session2->id, 1);
        // Check customfield data.
        $this->assert_count_customfield_data('signup', [$signups[11]->id, $signups[22]->id], 0, 0);
        $this->assert_count_customfield_data('signup', [$signups[12]->id], 4, 1);
        $this->assert_count_customfield_data('signup', [$signups[21]->id], 5, 3);
        $this->assert_count_customfield_data('cancellation', [$signups[11]->id, $signups[12]->id, $signups[21]->id, $signups[22]->id], 0, 0);
    }

    function test_facetoface_has_session_started() {
        $this->init_sample_data();

        // Define test variables.
        $session1 = $this->sessions['sess0'];
        $sess0 = (object)$session1;
        $sess0->sessiondates = array(0 => new stdClass());
        $sess0->sessiondates[0]->timestart = time() - 100;
        $sess0->sessiondates[0]->timefinish = time() + 100;

        $session2 = $this->sessions['sess1'];
        $sess1 = (object)$session2;

        $timenow = time();

        // Test for Valid case.
        $this->assertTrue((bool)facetoface_has_session_started($sess0, $timenow), $this->msgtrue);

        // Test for invalid case.
        $this->assertFalse((bool)facetoface_has_session_started($sess1, $timenow), $this->msgfalse);
    }

    function test_facetoface_is_session_in_progress() {
        $this->init_sample_data();

        // Define test variables.
        $session1 = $this->sessions['sess0'];
        $sess0 = (object)$session1;
        $sess0->sessiondates = array(0 => new stdClass());
        $sess0->sessiondates[0]->timestart = time() - 100;
        $sess0->sessiondates[0]->timefinish = time() + 100;

        $session2 = $this->sessions['sess1'];
        $sess1 = (object)$session2;

        $timenow = time();

        // Test for valid case.
        $this->assertTrue((bool)facetoface_is_session_in_progress($sess0, $timenow), $this->msgtrue);

        // Test for invalid case.
        $this->assertFalse((bool)facetoface_is_session_in_progress($sess1, $timenow), $this->msgfalse);
    }

    function test_facetoface_get_session_dates() {
        $this->init_sample_data();

        // Test variables.
        $sessionid1 = 1;
        $sessionid2 = 10;

        // Test for valid case.
        $this->assertTrue((bool)facetoface_get_session_dates($sessionid1), $this->msgtrue);

        // Test for invalid case.
        $this->assertFalse((bool)facetoface_get_session_dates($sessionid2), $this->msgfalse);
    }

    function test_facetoface_get_session() {
        $this->init_sample_data();

        // Test variables.
        $sessionid1 = 1;
        $sessionid2 = 10;

        // test for valid case
        $this->assertTrue((bool)facetoface_get_session($sessionid1), $this->msgtrue);

        // Test for invalid case.
        $this->assertFalse((bool)facetoface_get_session($sessionid2), $this->msgfalse);
    }

    function test_facetoface_get_sessions() {
        $this->resetAfterTest();
        $now = time();

        $sitewideroom1 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 1', 'allowconflicts' => 1));
        $sitewideroom2 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 2', 'allowconflicts' => 1));
        $sitewideroom3 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 3', 'allowconflicts' => 1));

        $course = $this->getDataGenerator()->create_course();
        $facetoface1 = $this->facetoface_generator->create_instance(array('course' => $course->id));
        $facetoface2 = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondates1_1 = array();
        $sessiondates1_1[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), $sitewideroom1->id);
        $sessiondates1_1[] = $this->prepare_date($now + (DAYSECS * 2), $now + (DAYSECS * 3), $sitewideroom1->id);
        $sessiondates1_1[] = $this->prepare_date($now + (DAYSECS * 3), $now + (DAYSECS * 4), $sitewideroom2->id);
        $sessionid1_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates1_1));

        $sessiondates1_2 = array();
        $sessiondates1_2[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 2), $sitewideroom1->id);
        $sessiondates1_2[] = $this->prepare_date($now + (DAYSECS * 2), $now + (DAYSECS * 3), 0);
        $sessionid1_2 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates1_2));

        $sessiondates2_1 = array();
        $sessiondates2_1[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), 0);
        $sessionid2_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface2->id, 'sessiondates' => $sessiondates2_1));

        $sessions = facetoface_get_sessions($facetoface1->id);
        $this->assertCount(2, $sessions);
        $this->assertSame(array($sessionid1_2, $sessionid1_1), array_keys($sessions));
        $this->assertCount(3, $sessions[$sessionid1_1]->sessiondates);
        $this->assertCount(2, $sessions[$sessionid1_2]->sessiondates);
        $this->assertSame($sessiondates1_1[1]->roomid, $sessions[$sessionid1_1]->sessiondates[0]->roomid);
        $this->assertSame($sessiondates1_1[1]->timestart, $sessions[$sessionid1_1]->sessiondates[0]->timestart);
        $this->assertSame($sessiondates1_1[2]->roomid, $sessions[$sessionid1_1]->sessiondates[1]->roomid);
        $this->assertSame($sessiondates1_1[2]->timestart, $sessions[$sessionid1_1]->sessiondates[1]->timestart);
        $this->assertSame($sessiondates1_1[0]->roomid, $sessions[$sessionid1_1]->sessiondates[2]->roomid);
        $this->assertSame($sessiondates1_1[0]->timestart, $sessions[$sessionid1_1]->sessiondates[2]->timestart);
        $this->assertSame($sessiondates1_2[0]->roomid, $sessions[$sessionid1_2]->sessiondates[0]->roomid);
        $this->assertSame($sessiondates1_2[0]->timestart, $sessions[$sessionid1_2]->sessiondates[0]->timestart);
        $this->assertSame($sessiondates1_2[1]->roomid, $sessions[$sessionid1_2]->sessiondates[1]->roomid);
        $this->assertSame($sessiondates1_2[1]->timestart, $sessions[$sessionid1_2]->sessiondates[1]->timestart);

        $sessions = facetoface_get_sessions($facetoface2->id);
        $this->assertCount(1, $sessions);
        $this->assertCount(1, $sessions[$sessionid2_1]->sessiondates);
        $this->assertSame($sessiondates2_1[0]->roomid, $sessions[$sessionid2_1]->sessiondates[0]->roomid);
        $this->assertSame($sessiondates2_1[0]->timestart, $sessions[$sessionid2_1]->sessiondates[0]->timestart);

        // Test room filtering.

        $sessions = facetoface_get_sessions($facetoface1->id, '', $sitewideroom1->id);
        $this->assertCount(2, $sessions);
        $this->assertSame(array($sessionid1_2, $sessionid1_1), array_keys($sessions));
        $this->assertCount(3, $sessions[$sessionid1_1]->sessiondates);
        $this->assertCount(2, $sessions[$sessionid1_2]->sessiondates);
        $this->assertSame($sessiondates1_1[1]->roomid, $sessions[$sessionid1_1]->sessiondates[0]->roomid);
        $this->assertSame($sessiondates1_1[1]->timestart, $sessions[$sessionid1_1]->sessiondates[0]->timestart);
        $this->assertSame($sessiondates1_1[2]->roomid, $sessions[$sessionid1_1]->sessiondates[1]->roomid);
        $this->assertSame($sessiondates1_1[2]->timestart, $sessions[$sessionid1_1]->sessiondates[1]->timestart);
        $this->assertSame($sessiondates1_1[0]->roomid, $sessions[$sessionid1_1]->sessiondates[2]->roomid);
        $this->assertSame($sessiondates1_1[0]->timestart, $sessions[$sessionid1_1]->sessiondates[2]->timestart);
        $this->assertSame($sessiondates1_2[0]->roomid, $sessions[$sessionid1_2]->sessiondates[0]->roomid);
        $this->assertSame($sessiondates1_2[0]->timestart, $sessions[$sessionid1_2]->sessiondates[0]->timestart);
        $this->assertSame($sessiondates1_2[1]->roomid, $sessions[$sessionid1_2]->sessiondates[1]->roomid);
        $this->assertSame($sessiondates1_2[1]->timestart, $sessions[$sessionid1_2]->sessiondates[1]->timestart);

        $sessions = facetoface_get_sessions($facetoface1->id, '', $sitewideroom2->id);
        $this->assertCount(1, $sessions);
        $this->assertSame(array($sessionid1_1), array_keys($sessions));
        $this->assertCount(3, $sessions[$sessionid1_1]->sessiondates);
        $this->assertSame($sessiondates1_1[1]->roomid, $sessions[$sessionid1_1]->sessiondates[0]->roomid);
        $this->assertSame($sessiondates1_1[1]->timestart, $sessions[$sessionid1_1]->sessiondates[0]->timestart);
        $this->assertSame($sessiondates1_1[2]->roomid, $sessions[$sessionid1_1]->sessiondates[1]->roomid);
        $this->assertSame($sessiondates1_1[2]->timestart, $sessions[$sessionid1_1]->sessiondates[1]->timestart);
        $this->assertSame($sessiondates1_1[0]->roomid, $sessions[$sessionid1_1]->sessiondates[2]->roomid);
        $this->assertSame($sessiondates1_1[0]->timestart, $sessions[$sessionid1_1]->sessiondates[2]->timestart);

        $sessions = facetoface_get_sessions($facetoface2->id, '', $sitewideroom1->id);
        $this->assertCount(0, $sessions);

        $sessions = facetoface_get_sessions($facetoface2->id, '', -1);
        $this->assertCount(0, $sessions);

        // TODO: add location search test
    }

    function test_facetoface_get_attendees() {
        $this->init_sample_data();

        // Test variables.
        $sessionid1 = 1;
        $sessionid2 = 42;

        // Test - for valid sessionid.
        $this->assertTrue((bool)count(facetoface_get_attendees($sessionid1)));

        // Test - for invalid sessionid.
        $this->assertEquals(facetoface_get_attendees($sessionid2), array());
    }

    function test_facetoface_get_attendee() {
        $this->init_sample_data();

        // Test variables.
        $sessionid1 = 1;
        $sessionid2 = 42;
        $userid1 = 1;
        $userid2 = 14;

        // Test for valid case.
        $this->assertTrue((bool)is_object(facetoface_get_attendee($sessionid1, $userid1)), $this->msgtrue);

        // Test for invalid case.
        $this->assertFalse((bool)facetoface_get_attendee($sessionid2, $userid2), $this->msgfalse);
    }

    function test_facetoface_get_userfields() {
        $this->init_sample_data();

        $data = facetoface_get_userfields();
        $this->assertTrue((bool)$data, $this->msgtrue);

        ksort($data);
        $this->assertEquals([
            'department',
            'email',
            'firstname',
            'idnumber',
            'institution',
            'lastname',
        ], array_keys($data));

        // Test we can't export sensitive data.
        set_config('facetoface_export_userprofilefields', 'lastname,firstname,password');

        $data = facetoface_get_userfields(true);
        $this->assertEquals([
            'lastname',
            'firstname',
        ], array_keys($data));
    }

    function test_facetoface_get_user_custom_fields() {
        $this->init_sample_data();

        // Test variables.
        $userid1 = 1;
        $userid2 = 42;
        $fieldstoinclude1 = TRUE;

        // Test for valid case.
        $this->assertTrue((bool)facetoface_get_user_customfields($userid1, $fieldstoinclude1), $this->msgtrue);
        $this->assertTrue((bool)facetoface_get_user_customfields($userid1), $this->msgtrue);
        //TODO invalid case
        // Test for invalid case.
    }

    function test_facetoface_user_signup() {
        global $DB, $CFG;
        require_once("$CFG->dirroot/totara/hierarchy/prefix/position/lib.php");

        $this->init_sample_data();

        $teacher1 = $this->getDataGenerator()->create_user();
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();

        $managerja = \totara_job\job_assignment::create_default($manager->id);
        $data = array(
            'userid' => $student2->id,
            'fullname' => 'student2ja',
            'shortname' => 'student2ja',
            'idnumber' => 'student2ja',
            'managerjaid' => $managerja->id);
        \totara_job\job_assignment::create($data);

        $course1 = $this->getDataGenerator()->create_course();

        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        $this->getDataGenerator()->enrol_user($teacher1->id, $course1->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($student1->id, $course1->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course1->id, $studentrole->id);

        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface1',
            'course' => $course1->id
        );
        $facetoface1 = $facetofacegenerator->create_instance($facetofacedata);

        // Session that starts in 24hrs time.
        // This session should trigger a mincapacity warning now as cutoff is 24:01 hrs before start time.
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + DAYSECS;
        $sessiondate->timefinish = time() + DAYSECS + 60;
        $sessiondate->sessiontimezone = 'Pacific/Auckland';

        $sessiondata = array(
            'facetoface' => $facetoface1->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        );
        $sessionid = $facetofacegenerator->add_session($sessiondata);

        $session = $DB->get_record('facetoface_sessions', array('id' => $sessionid));
        $session->sessiondates = facetoface_get_session_dates($session->id);

        $discountcode1 = 'disc1';
        $notificationtype1 = 1;
        $statuscode1 = MDL_F2F_STATUS_REQUESTED;

        // No manager - problem.
        $this->setUser($student1);
        $this->assertTrue((bool)facetoface_user_signup($session, $facetoface1, $course1, $discountcode1, $notificationtype1, $statuscode1), $this->msgtrue);
        $this->assertDebuggingCalled(get_string('error:nomanagersemailset', 'facetoface'));

        // Test for valid case.
        $this->setUser($student2);
        $sink = $this->redirectMessages();
        $this->assertTrue((bool)facetoface_user_signup($session, $facetoface1, $course1, $discountcode1, $notificationtype1, $statuscode1), $this->msgtrue);
        $sink->close();
    }

    public function test_facetoface_user_signup_select_manager_message_manager() {
        global $DB, $CFG;
        $this->init_sample_data();

        set_config('facetoface_selectjobassignmentonsignupglobal', true);

        // Set up three users, one learner, a primary mgr and a secondary mgr.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $user3ja = \totara_job\job_assignment::create_default($user3->id);
        $data = array(
            'userid' => $user2->id,
            'fullname' => 'user2ja',
            'shortname' => 'user2ja',
            'idnumber' => 'user2ja',
            'managerjaid' => $user3ja->id);
        $user2ja = \totara_job\job_assignment::create($data);
        $data = array(
            'userid' => $user1->id,
            'fullname' => 'user1ja',
            'shortname' => 'user1ja',
            'idnumber' => 'user1ja',
            'managerjaid' => $user2ja->id);
        $user1ja = \totara_job\job_assignment::create($data);

        // Set up a face to face session that requires you to get manager approval and select a position.
        $facetofacedata = array(
            'course' => $this->course1->id,
            'multiplesessions' => 1,
            'selectjobassignmentonsignup' => 1,
            'approvalreqd' => 1
        );
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);
        $facetofaces[$facetoface->id] = $facetoface;

        // Create session with capacity and date in 2 years.
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + (DAYSECS * 365 * 2);
        $sessiondate->timefinish = time() + (DAYSECS * 365 * 2 + 60);
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
        );
        $sessionid = $facetofacegenerator->add_session($sessiondata);
        $session = facetoface_get_session($sessionid);

        // Grab any messages that get sent.
        $sink = $this->redirectMessages();

        // Sign the user up to the session with the secondary position.
        facetoface_user_signup(
            $session,
            $facetoface,
            $this->course1,
            'discountcode1',
            MDL_F2F_INVITE,
            MDL_F2F_STATUS_REQUESTED,
            $user1->id,
            true,
            null,
            $user2ja
        );

        // Grab the messages that got sent.
        $messages = $sink->get_messages();

        // Check the expected number of messages got sent.
        $this->assertCount(2, $messages);

        $foundstudent = false;
        $foundmanager = false;

        // Look for user1 and user 3 email addresses.
        foreach ($messages as $message) {
            if ($message->useridto == $user1->id) {
                $foundstudent = true;
            } else if ($message->useridto == $user3->id) {
                $foundmanager = true;
            }
        }

        // TODO - the manager isnt being found.
        $this->assertTrue($foundstudent);
        $this->assertTrue($foundmanager);
    }

    function test_facetoface_send_request_notice() {
        $this->init_sample_data();

        // Set managerroleid to make sure that it
        // matches the role id defined in the unit test
        // role table, as the local install may have a different
        // manager role id
        set_config('managerroleid', 1);

        // Test variables.
        $session1 = $this->sessions['sess0'];
        $sess0 = (object)$session1;
        $facetoface1 = $this->facetoface['f2f0'];
        $f2f = (object)$facetoface1;

        $userid1 = 1;
        $userid2 = 25;

        // Test for valid case. -- need to set manager
        //$this->assertEquals(facetoface_send_request_notice($f2f, $sess0, $userid1), '');

        // Test for invalid case.
        $sink = $this->redirectMessages();
        $this->assertEquals(get_string(facetoface_send_request_notice($f2f, $sess0, $userid2), 'facetoface'), 'No manager email is set');
        $sink->close();
    }

    function test_facetoface_update_signup_status() {
        global $DB;
        $this->init_sample_data();

        $teacher1 = $this->getDataGenerator()->create_user();
        $student1 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();

        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        $this->getDataGenerator()->enrol_user($teacher1->id, $course1->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($student1->id, $course1->id, $studentrole->id);

        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface1',
            'course' => $course1->id
        );
        $facetoface1 = $facetofacegenerator->create_instance($facetofacedata);

        // Session that starts in 24hrs time.
        // This session should trigger a mincapacity warning now as cutoff is 24:01 hrs before start time.
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + DAYSECS;
        $sessiondate->timefinish = time() + DAYSECS + 60;
        $sessiondate->sessiontimezone = 'Pacific/Auckland';

        $sessiondata = array(
            'facetoface' => $facetoface1->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        );
        $sessionid = $facetofacegenerator->add_session($sessiondata);

        $session = $DB->get_record('facetoface_sessions', array('id' => $sessionid));
        $session->sessiondates = facetoface_get_session_dates($session->id);

        $discountcode1 = 'disc1';
        $notificationtype1 = 1;
        $statuscode1 = MDL_F2F_STATUS_BOOKED;

        // Test for valid case.
        $sink = $this->redirectMessages();
        facetoface_user_signup($session, $facetoface1, $course1, $discountcode1, $notificationtype1, $statuscode1, $student1->id);
        $sink->close();

        $params = array('sessionid' => $sessionid, 'userid' => $student1->id);
        $signup = $DB->get_record('facetoface_signups', $params);
        // Test for valid case.
        $sink = $this->redirectMessages();
        $this->assertEquals(facetoface_update_signup_status($signup->id, $statuscode1, $teacher1->id), 8);
        $sink->close();

        // Test for invalid case.
        // TODO invlaid case - how to cause sql error from here?
        //$this->assertFalse((bool)facetoface_update_signup_status($signupid2, $statuscode2, $createdby2, $note2), $this->msgfalse);
    }

    function test_facetoface_user_cancel() {
        $this->init_sample_data();

        // test method - returns boolean
        $this->markTestSkipped('TODO - this test hasn\'t been working since 1.1');

        // Test variables.
        $session1 = $this->sessions['sess0'];
        $sess0 = (object)$session1;
        $facetoface1 = $this->facetoface['f2f0'];
        $f2f = (object)$facetoface1;

        $userid1 = 1;
        $forcecancel1 = TRUE;
        $errorstr1 = 'error1';
        $cancelreason1 = 'cancelreason1';

        $session2 = $this->session[1];

        $userid2 = 42;

        // Test for valid case.
        //$this->assertTrue((bool)facetoface_user_cancel($session1, $userid1, $forcecancel1, $errorstr1, $cancelreason1), $this->msgtrue);

        // Test for invalid case.
        //TODO invalid case?
        //$this->assertFalse((bool)facetoface_user_cancel($session2, $userid2), $this->msgfalse);
    }

    function test_facetoface_user_cancel_submission() {
        global $DB;
        $this->init_sample_data();

        $student1 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        $this->getDataGenerator()->enrol_user($student1->id, $course1->id, $studentrole->id);

        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface1',
            'course' => $course1->id
        );
        $facetoface1 = $facetofacegenerator->create_instance($facetofacedata);

        // Session that starts in 24hrs time.
        // This session should trigger a mincapacity warning now as cutoff is 24:01 hrs before start time.
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + DAYSECS;
        $sessiondate->timefinish = time() + DAYSECS + 60;
        $sessiondate->sessiontimezone = 'Pacific/Auckland';

        $sessiondata = array(
            'facetoface' => $facetoface1->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
            'datetimeknown' => '1',
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        );
        $sessionid = $facetofacegenerator->add_session($sessiondata);

        $session = $DB->get_record('facetoface_sessions', array('id' => $sessionid));
        $session->sessiondates = facetoface_get_session_dates($session->id);

        $discountcode1 = 'disc1';
        $notificationtype1 = 1;
        $statuscode1 = MDL_F2F_STATUS_REQUESTED;

        $this->setUser($student1);
        facetoface_user_signup($session, $facetoface1, $course1, $discountcode1, $notificationtype1, $statuscode1);
        $this->assertDebuggingCalled(get_string('error:nomanagersemailset', 'facetoface'));

        $sink = $this->redirectMessages();
        $cancelreason = 'PHPUnit facetoface user cancellation submission test';
        // Normal test.
        $this->assertTrue((bool)facetoface_user_cancel_submission($sessionid, $student1->id, $cancelreason), $this->msgtrue);

        // Test - No user has signed-up for this session.
        $this->assertTrue((bool)facetoface_user_cancel_submission($sessionid, '999', $cancelreason), $this->msgtrue);
        $this->assertDebuggingCalled("No user with ID: 999 has signed-up for the session ID: {$sessionid}.");

        // Test - user status already changed to cancelled.
        $this->assertTrue((bool)facetoface_user_cancel_submission($sessionid, $student1->id, $cancelreason), $this->msgtrue);
        $this->assertDebuggingCalled('User status already changed to cancelled.');

        $sink->close();
    }

    // Test sending an adhoc notice using message substitution to the users signed for a session.
    function test_facetoface_send_notice() {
        $this->init_sample_data();

        $fields = array('username', 'email', 'institution', 'department', 'city', 'idnumber', 'icq', 'skype',
            'yahoo', 'aim', 'msn', 'phone1', 'phone2', 'address', 'url', 'description');

        $usernamefields = get_all_user_name_fields();
        $fields = array_merge($fields, array_values($usernamefields));

        $noticebody = '';
        foreach ($fields as $field) {
            $noticebody .= get_string('placeholder:'.$field, 'mod_facetoface') . ' ';
        }

        $noticebody .= get_string('placeholder:fullname', 'mod_facetoface') . ' ';

        $userdata = array();
        foreach ($fields as $field) {
            $userdata[$field] = 'display_' . $field;
        }

        // Set up three users, one learner, a primary mgr and a secondary mgr.
        $user1 = $this->getDataGenerator()->create_user($userdata);
        $course1 = $this->getDataGenerator()->create_course();

        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $facetoface = $facetofacegenerator->create_instance(array('course' => $course1->id, 'multiplesessions' => 1));
        $facetofaces[$facetoface->id] = $facetoface;

        // Create session with capacity and date in 2 years.
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + (YEARSECS * 2);
        $sessiondate->timefinish = time() + (YEARSECS * 2 + 60);
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
        );
        $sessionid = $facetofacegenerator->add_session($sessiondata);
        $session = facetoface_get_session($sessionid);

        facetoface_user_signup($session, $facetoface, $course1, 'discountcode1', MDL_F2F_INVITE, MDL_F2F_STATUS_BOOKED, $user1->id, false);

        $notification = new facetoface_notification();
        $notification->booked = 0;
        $notification->courseid = $course1->id;
        $notification->facetofaceid = $facetoface->id;
        $notification->ccmanager = 0;
        $notification->status = 1;
        $notification->title = 'hello';
        $notification->body = $noticebody;
        $notification->managerprefix = '';
        $notification->type = MDL_F2F_NOTIFICATION_MANUAL;
        $notification->save();

        // Grab any messages that get sent.
        $sink = $this->redirectMessages();

        $notification->send_to_users($sessionid);

        // Grab the messages that got sent.
        $messages = $sink->get_messages();

        // Check the expected number of messages got sent.
        $this->assertCount(1, $messages);
        $this->assertEquals($user1->id, $messages[0]->useridto);

        foreach ($fields as $field) {
            $uservalue = 'display_' . $field;
            $this->assertTrue(strpos($messages[0]->fullmessage, $uservalue) !== false, $uservalue);
        }

        $this->assertTrue(strpos($messages[0]->fullmessage, fullname($user1)) !== false, fullname($user1));
    }

    /**
     * Test that sending scheduled notices can't lead to duplicate notices for managers if the user failed to receive it.
     *
     * This is a direct test for the situation described in T-14140.
     */
    function test_facetoface_send_notice_duplicates() {
        global $CFG;
        $this->init_sample_data();

        $fields = array('username', 'email', 'institution', 'department', 'city', 'idnumber', 'icq', 'skype',
            'yahoo', 'aim', 'msn', 'phone1', 'phone2', 'address', 'url', 'description');

        $usernamefields = get_all_user_name_fields();
        $fields = array_merge($fields, array_values($usernamefields));

        $noticebody = '';
        foreach ($fields as $field) {
            $noticebody .= get_string('placeholder:'.$field, 'mod_facetoface') . ' ';
        }

        $noticebody .= get_string('placeholder:fullname', 'mod_facetoface') . ' ';

        $userdata = array();
        foreach ($fields as $field) {
            $userdata[$field] = 'display_' . $field;
        }

        // Set up three users, one learner, a primary mgr and a secondary mgr.
        $userdata['username'] = 'learner';
        $userdata['email'] = 'learner@local.host';
        $user1 = $this->getDataGenerator()->create_user($userdata);
        $userdata['username'] = 'manager';
        $userdata['email'] = 'manager@local.host';
        $user2 = $this->getDataGenerator()->create_user($userdata);

        $managerja = \totara_job\job_assignment::create_default($user2->id);
        $data = array(
            'userid' => $user1->id,
            'fullname' => 'user1ja',
            'shortname' => 'user1ja',
            'idnumber' => 'user1ja',
            'managerjaid' => $managerja->id);
        \totara_job\job_assignment::create($data);

        $course1 = $this->getDataGenerator()->create_course();

        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $facetoface = $facetofacegenerator->create_instance(array('course' => $course1->id, 'multiplesessions' => 1));
        $facetofaces[$facetoface->id] = $facetoface;

        // Create session with capacity and date in 2 years.
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + (YEARSECS * 2);
        $sessiondate->timefinish = time() + (YEARSECS * 2 + 60);
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
        );
        $sessionid = $facetofacegenerator->add_session($sessiondata);
        $session = facetoface_get_session($sessionid);

        $sink = $this->redirectMessages();

        facetoface_user_signup($session, $facetoface, $course1, 'discountcode1', MDL_F2F_INVITE, MDL_F2F_STATUS_BOOKED, $user1->id, false, $user2);

        // Check the manager got their email.
        $messages = $sink->get_messages();
        $this->assertCount(1, $messages);
        $this->assertSame($user2->id, $messages[0]->useridto);

        $notification = new facetoface_notification();
        $notification->booked = 0;
        $notification->courseid = $course1->id;
        $notification->facetofaceid = $facetoface->id;
        $notification->ccmanager = 1;
        $notification->status = 1;
        $notification->title = 'hello';
        $notification->body = $noticebody;
        $notification->managerprefix = '';
        $notification->type = MDL_F2F_NOTIFICATION_MANUAL;
        $notification->save();

        $CFG->facetoface_notificationdisable = true;

        $notification->send_to_users($sessionid);

        // Check the expected number of messages got sent.
        $messages = $sink->get_messages();
        $this->assertCount(1, $messages);
        $this->assertSame($user2->id, $messages[0]->useridto);

        $CFG->facetoface_notificationdisable = false;
        $notification->send_to_users($sessionid);

        // Grab the messages that got sent.
        $messages = $sink->get_messages();

        // Check the expected number of messages got sent.
        $this->assertCount(3, $messages);
        $this->assertSame($user2->id, $messages[0]->useridto);
        $this->assertSame($user1->id, $messages[1]->useridto);

        foreach ($fields as $field) {
            if ($field === 'username' || $field === 'email') {
                continue;
            }
            $uservalue = 'display_' . $field;
            $this->assertTrue(strpos($messages[1]->fullmessage, $uservalue) !== false, $uservalue);
        }

        $this->assertTrue(strpos($messages[1]->fullmessage, fullname($user1)) !== false, fullname($user1));

        $sink->close();
        $CFG->noemailever = true;
    }

    function test_facetoface_send_confirmation_notice() {
        $this->init_sample_data();

        // Set up three users, one learner, a primary mgr and a secondary mgr.
        $user1 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();

        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $facetoface = $facetofacegenerator->create_instance(array('course' => $course1->id, 'multiplesessions' => 1));
        $facetofaces[$facetoface->id] = $facetoface;

        // Create session with capacity and date in 2 years.
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + (YEARSECS * 2);
        $sessiondate->timefinish = time() + (YEARSECS * 2 + 60);
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
        );
        $sessionid = $facetofacegenerator->add_session($sessiondata);
        $session = facetoface_get_session($sessionid);

        // Grab any messages that get sent.
        $sink = $this->redirectMessages();

        facetoface_user_signup($session, $facetoface, $course1, 'discountcode1', MDL_F2F_INVITE, MDL_F2F_STATUS_BOOKED, $user1->id, true);

        // Grab the messages that got sent.
        $messages = $sink->get_messages();

        // Check the expected number of messages got sent.
        $this->assertCount(1, $messages);
        $this->assertEquals($user1->id, $messages[0]->useridto);
    }

    function test_facetoface_send_cancellation_notice() {
        $this->init_sample_data();

        // Test. method - returns string
        $this->markTestSkipped('TODO - this test hasn\'t been working since 1.1');

        // Test variables.
        $facetoface1 = $this->facetoface[0];

        $session1 = $this->session[0];

        $userid1 = 1;

        // Test for valid case.
        //$this->assertEquals(facetoface_send_cancellation_notice($facetoface1, $session1, $userid1), '');
    }

    function test_facetoface_message_substitutions(){
        $this->init_sample_data();

        $user1 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $facetoface = $facetofacegenerator->create_instance(array('course' => $course1->id, 'multiplesessions' => 1));

        //create multiple session dates. Out of order to test that placeholders do pull earliest date and not first date entered.
        $sessiondate1 = new stdClass();
        $sessiondate1->timestart = time() + (DAYSECS * 4) + (HOURSECS);
        $sessiondate1->timefinish = time() + (DAYSECS * 5) + (HOURSECS * 4);
        $sessiondate1->sessiontimezone = 'Pacific/Auckland';
        $sessiondate1->assetids = array();
        $sessiondate2 = new stdClass();
        $sessiondate2->timestart = time() + (HOURSECS);
        $sessiondate2->timefinish = time() + (HOURSECS * 2);
        $sessiondate2->sessiontimezone = 'Pacific/Auckland';
        $sessiondate2->assetids = array();
        $sessiondate3 = new stdClass();
        $sessiondate3->timestart = time() + (DAYSECS) + (HOURSECS * 3);
        $sessiondate3->timefinish = time() + (DAYSECS) + (HOURSECS * 6);
        $sessiondate3->sessiontimezone = 'Pacific/Auckland';
        $sessiondate3->assetids = array();

        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate1, $sessiondate2, $sessiondate3),
            // arbitrary duration as this is a setting that is not automatically adjusted by generator when adding session dates
            'duration' => 97200
        );
        $sessionid = $facetofacegenerator->add_session($sessiondata);
        $session = facetoface_get_session($sessionid);

        // set up a notification that uses all current placeholders
        $legacyfields = array('coursename', 'facetofacename', 'firstname', 'lastname', 'cost',
            'sessiondate', 'startdate', 'finishdate', 'starttime', 'finishtime', 'lateststartdate', 'latestfinishdate', 'lateststarttime',
            'latestfinishtime', 'duration');

        // Create field that should be translated if following hack with string cache works.
        $noticebody = 'borked [borked] ';

        foreach ($legacyfields as $field) {
            // adding name of field in front of placeholder so that tests for starttime etc. don't simply pick
            // up those times within alldates.
            $noticebody .= $field.' '.get_string('placeholder:'.$field, 'facetoface') . ' ';
        }

        $newfields = array('sessions:loopstart' => '[#sessions]', 'session:starttime' => '[session:starttime]',
            'session:startdate' => '[session:startdate]', 'session:finishtime' => '[session:finishtime]',
            'session:finishdate' => '[session:finishdate]', 'session:timezone' => '[session:timezone]',
            'session:duration' => '[session:duration]', 'sessions:loopend' => '[/sessions]');
        foreach ($newfields as $key => $field) {
            // adding name of field in front of placeholder so that tests for starttime etc. don't simply pick
            // up those times within alldates.
            $noticebody .= $key.' '.$field . ' ';
        }

        // Translation problems hack.
        $strmanager = get_string_manager();
        $rp = new ReflectionProperty(get_class($strmanager), 'cache');
        $rp->setAccessible(true);
        $cache = $rp->getValue($strmanager);
        // Clone cache key calculations as it is done in string manager.
        $rev = $strmanager->get_revision();
        $rev = ($rev < 0) ? 0 : $rev;
        $key = 'en_mod_facetoface_' . $rev;

        // Now "translate" placeholder to make it different from used in notification template.
        $strings = $cache->get($key);
        $strings['placeholder:firstname'] = '[borked]';
        $cache->set($key, $strings);
        $this->assertEquals('[borked]', get_string('placeholder:firstname', 'facetoface'));

        $notification = new facetoface_notification();
        $notification->courseid = $course1->id;
        $notification->facetofaceid = $facetoface->id;
        $notification->ccmanager = 0;
        $notification->status = 1;
        $notification->title = 'Confirmation';
        $notification->body = $noticebody;
        $notification->managerprefix = '';
        $notification->type = MDL_F2F_NOTIFICATION_MANUAL;
        $notification->save();

        // Grab any messages that get sent.
        $sink = $this->redirectMessages();

        facetoface_user_signup($session, $facetoface, $course1, NULL, MDL_F2F_INVITE, MDL_F2F_STATUS_BOOKED, $user1->id, true);

        $notification->send_to_users($sessionid);

        // Grab the messages that got sent.
        $messages = $sink->get_messages();

        // Plain text message has been formatted to include new lines at every ~75 characters - removing these as they complicate testing.
        $fullmessage = str_replace("\n", " ", end($messages)->fullmessage);
        $fullmessagehtml = end($messages)->fullmessagehtml;

        // Confirm that hack worked.
        $this->assertContains('borked', $fullmessage);
        $this->assertNotContains('[borked]', $fullmessage);

        // Assertions for values that are already strings
        $this->assertContains('coursename '.$course1->fullname, $fullmessage);
        $this->assertContains('coursename '.$course1->fullname, $fullmessagehtml);
        $this->assertContains('facetofacename '.$facetoface->name, $fullmessage);
        $this->assertContains('facetofacename '.$facetoface->name, $fullmessagehtml);
        $this->assertContains('firstname '.$user1->firstname, $fullmessage);
        $this->assertContains('firstname '.$user1->firstname, $fullmessagehtml);
        $this->assertContains('lastname '.$user1->lastname, $fullmessage);
        $this->assertContains('lastname '.$user1->lastname, $fullmessagehtml);
        $this->assertContains('cost '.$session->normalcost, $fullmessage);
        $this->assertContains('cost '.$session->normalcost, $fullmessagehtml);

        foreach($session->sessiondates as $sessiondate) {
            $this->assertContains('session:starttime '.ltrim(date_format_string($sessiondate->timestart, "%l:%M %p", 'Pacific/Auckland')), $fullmessage);
            $this->assertContains('session:starttime '.ltrim(date_format_string($sessiondate->timestart, "%l:%M %p", 'Pacific/Auckland')), $fullmessagehtml);
            $this->assertContains('session:startdate '.ltrim(date_format_string($sessiondate->timestart, "%e %B %Y", 'Pacific/Auckland')), $fullmessage);
            $this->assertContains('session:startdate '.ltrim(date_format_string($sessiondate->timestart, "%e %B %Y", 'Pacific/Auckland')), $fullmessagehtml);
            $this->assertContains('session:finishtime '.ltrim(date_format_string($sessiondate->timefinish, "%l:%M %p", 'Pacific/Auckland')), $fullmessage);
            $this->assertContains('session:finishtime '.ltrim(date_format_string($sessiondate->timefinish, "%l:%M %p", 'Pacific/Auckland')), $fullmessagehtml);
            $this->assertContains('session:finishdate '.ltrim(date_format_string($sessiondate->timefinish, "%e %B %Y", 'Pacific/Auckland')), $fullmessage);
            $this->assertContains('session:finishdate '.ltrim(date_format_string($sessiondate->timefinish, "%e %B %Y", 'Pacific/Auckland')), $fullmessagehtml);
            $this->assertContains('session:timezone Pacific/Auckland', $fullmessage);
            $this->assertContains('session:timezone Pacific/Auckland', $fullmessagehtml);
        }

        // sessiondate2 is the earliest of the three session dates.
        $firstsessiondate = ltrim(date_format_string($sessiondate2->timestart, "%e %B %Y", 'Pacific/Auckland'));
        if (date_format_string($sessiondate2->timestart, "%e %B %Y", 'Pacific/Auckland') !== date_format_string($sessiondate2->timefinish, "%e %B %Y", 'Pacific/Auckland')){
            $firstsessiondate .= ' - '.ltrim(date_format_string($sessiondate2->timefinish, "%e %B %Y", 'Pacific/Auckland'));
        }
        $this->assertContains('sessiondate '.$firstsessiondate, $fullmessage);
        $this->assertContains('sessiondate '.$firstsessiondate, $fullmessagehtml);

        $this->assertContains('startdate '.ltrim(date_format_string($sessiondate2->timestart, "%e %B %Y", 'Pacific/Auckland')), $fullmessage);
        $this->assertContains('startdate '.ltrim(date_format_string($sessiondate2->timestart, "%e %B %Y", 'Pacific/Auckland')), $fullmessagehtml);
        $this->assertContains('finishdate '.ltrim(date_format_string($sessiondate2->timefinish, "%e %B %Y", 'Pacific/Auckland')), $fullmessage);
        $this->assertContains('finishdate '.ltrim(date_format_string($sessiondate2->timefinish, "%e %B %Y", 'Pacific/Auckland')), $fullmessagehtml);
        $this->assertContains('starttime '.ltrim(date_format_string($sessiondate2->timestart, "%l:%M %p", 'Pacific/Auckland')), $fullmessage);
        $this->assertContains('starttime '.ltrim(date_format_string($sessiondate2->timestart, "%l:%M %p", 'Pacific/Auckland')), $fullmessagehtml);
        $this->assertContains('finishtime '.ltrim(date_format_string($sessiondate2->timefinish, "%l:%M %p", 'Pacific/Auckland')), $fullmessage);
        $this->assertContains('finishtime '.ltrim(date_format_string($sessiondate2->timefinish, "%l:%M %p", 'Pacific/Auckland')), $fullmessagehtml);

        // sessiondate1 is the latest of the three session dates.
        $this->assertContains('lateststartdate '.ltrim(date_format_string($sessiondate1->timestart, "%e %B %Y", 'Pacific/Auckland')), $fullmessage);
        $this->assertContains('lateststartdate '.ltrim(date_format_string($sessiondate1->timestart, "%e %B %Y", 'Pacific/Auckland')), $fullmessagehtml);
        $this->assertContains('latestfinishdate '.ltrim(date_format_string($sessiondate1->timefinish, "%e %B %Y", 'Pacific/Auckland')), $fullmessage);
        $this->assertContains('latestfinishdate '.ltrim(date_format_string($sessiondate1->timefinish, "%e %B %Y", 'Pacific/Auckland')), $fullmessagehtml);
        $this->assertContains('lateststarttime '.ltrim(date_format_string($sessiondate1->timestart, "%l:%M %p", 'Pacific/Auckland')), $fullmessage);
        $this->assertContains('lateststarttime '.ltrim(date_format_string($sessiondate1->timestart, "%l:%M %p", 'Pacific/Auckland')), $fullmessagehtml);
        $this->assertContains('latestfinishtime '.ltrim(date_format_string($sessiondate1->timefinish, "%l:%M %p", 'Pacific/Auckland')), $fullmessage);
        $this->assertContains('latestfinishtime '.ltrim(date_format_string($sessiondate1->timefinish, "%l:%M %p", 'Pacific/Auckland')), $fullmessagehtml);

        // As per duration setting in $sessiondata, durations is a setting that is not currently automatically adjusted
        // by generator when known session dates are added, so duration is not expected to equal difference between starttime
        // and finishtime in this case.
        $this->assertContains('duration 1 day 3 hours', $fullmessage);
        $this->assertContains('duration 1 day 3 hours', $fullmessagehtml);
    }

    function test_facetoface_take_attendance() {
        $this->init_sample_data();

        // Test variables.
        $data1 = new stdClass();
        $data1->s = 1;
        $data1->submissionid = 1;

        // Test for valid case.
        $this->assertTrue((bool)facetoface_take_attendance($data1), $this->msgtrue);
        //TODO invalid case
        // Test for invalid case.
    }

    function test_facetoface_approve_requests() {
        global $DB;

        $this->setAdminUser();
        $this->init_sample_data();

        // Test variables.
        $data1 = new stdClass();
        $data1->s = 1;
        $data1->submissionid = 1;
        $data1->requests = array(0 => new stdClass());
        $data1->requests[0]->request = 1;

        // Test for valid case.
        $this->assertTrue((bool)facetoface_approve_requests($data1), $this->msgtrue);

        // TODO test for invalid case

        $data2 = new stdClass();
        $data2->s = 5; // Seesion ID.
        $data2->submissionid = 5;
        // Array of approvals,
        // Key = userid, value = approval where 1 is decline and 2 is approve.
        $data2->requests = array(1 => 2);

        // Check success.
        $this->assertTrue((bool)facetoface_approve_requests($data2), $this->msgtrue);

        // The session has no date/time so user should be waitlisted.
        $sql = "SELECT * FROM {facetoface_signups} s JOIN {facetoface_signups_status} ss ON s.id = ss.signupid WHERE s.sessionid = :sessionid AND s.userid = :userid AND ss.superceded = :superceded";
        $signupstatus = $DB->get_records_sql($sql, array('sessionid' => 5, 'userid' => 1, 'superceded' => 0));
        $record = array_shift($signupstatus);

        // The user should be waitlisted.
        $this->assertEquals(MDL_F2F_STATUS_WAITLISTED, $record->statuscode);

        $data3 = new stdClass();
        $data3->s = 6;
        $data3->submissionid = 6;
        $data3->requests = array(1 => 2);

        $this->assertTrue((bool)facetoface_approve_requests($data3), $this->msgtrue);

        $sql = "SELECT * FROM {facetoface_signups} s JOIN {facetoface_signups_status} ss ON s.id = ss.signupid WHERE s.sessionid = :sessionid AND s.userid = :userid AND ss.superceded = :superceded";
        $signupstatus = $DB->get_records_sql($sql, array('sessionid' => 6, 'userid' => 1, 'superceded' => 0));
        $record = array_shift($signupstatus);

        // The date/time is known so user should be booked.
        $this->assertEquals(MDL_F2F_STATUS_BOOKED, $record->statuscode);
    }

    function test_facetoface_ical_generate_timestamp() {
        $this->init_sample_data();

        // Test variables.
        $timenow = time();
        $return = gmdate("Ymd\THis\Z", $timenow);
        //TODO check if this is the correct return value to compare
        // Test for valid case.
        $this->assertEquals(facetoface_ical_generate_timestamp($timenow), $return);
    }

    function test_facetoface_ical_escape() {
        $this->init_sample_data();

        // Define test variables.
        $text1 = "this is a test!&nbsp";
        $text2 = NULL;
        $text3 = "This string should start repeating at 75 charaters for three repetitions. "
            . "This string should start repeating at 75 charaters for three repetitions. "
            . "This string should start repeating at 75 charaters for three repetitions.";
        $text4 = "/'s ; \" ' \n , . & &nbsp;";

        $converthtml1 = FALSE;
        $converthtml2 = TRUE;

        // Tests.
        $this->assertEquals(facetoface_ical_escape($text1, $converthtml1), $text1);
        $this->assertEquals(facetoface_ical_escape($text1, $converthtml2), $text1);

        $this->assertEquals(facetoface_ical_escape($text2, $converthtml1), $text2);
        $this->assertEquals(facetoface_ical_escape($text2, $converthtml2), $text2);

        $this->assertEquals(facetoface_ical_escape($text3, $converthtml1),
            "This string should start repeating at 75 charaters for three repetitions. \r\n\t"
            . "This string should start repeating at 75 charaters for three repetitions. \r\n\t"
            . "This string should start repeating at 75 charaters for three repetitions.");
        $this->assertEquals(facetoface_ical_escape($text3, $converthtml2),
            "This string should start repeating at 75 charaters for three repetitions. \r\n\t"
            . "This string should start repeating at 75 charaters for three repetitions. \r\n\t"
            . "This string should start repeating at 75 charaters for three repetitions.");

        $this->assertEquals(facetoface_ical_escape($text4, $converthtml1), "/'s \; \\\" ' \\n \, . & &nbsp\;");
        $this->assertEquals(facetoface_ical_escape($text4, $converthtml2), "/'s \; \\\" ' \, . &  ");
    }

    function test_facetoface_update_grades() {
        $this->init_sample_data();

        // Variables.
        $facetoface1 = $this->facetoface['f2f0'];
        $f2f = (object)$facetoface1;

        $userid = 0;

        $this->assertTrue((bool)facetoface_update_grades($f2f, $userid), $this->msgtrue);
    }

    function test_facetoface_grade_item_update() {
        $this->init_sample_data();

        // Test variables.
        $facetoface1 = $this->facetoface['f2f0'];
        $f2f = (object)$facetoface1;

        $grades = NULL;

        // Test.
        $this->assertTrue((bool)facetoface_grade_item_update($f2f), $this->msgtrue);
    }

    function test_facetoface_grade_item_delete() {
        $this->init_sample_data();

        // Test variables.
        $facetoface1 = $this->facetoface['f2f0'];
        $f2f = (object)$facetoface1;

        // Test for valid case.
        $this->assertTrue((bool)facetoface_grade_item_delete($f2f), $this->msgtrue);
    }

    function test_facetoface_get_num_attendees() {
        $this->init_sample_data();

        // Test variables.
        $sessionid1 = 2;
        $sessionid2 = 42;

        // Test for valid case.
        $this->assertEquals(facetoface_get_num_attendees($sessionid1), 3);

        // Test for invalid case.
        $this->assertEquals(facetoface_get_num_attendees($sessionid2), 0);
    }

    function test_facetoface_get_user_submissions() {
        $this->init_sample_data();

        // Test variables.
        $facetofaceid1 = 1;
        $userid1 = 1;
        $includecancellations1 = TRUE;

        $facetofaceid2 = 11;
        $userid2 = 11;
        $includecancellations2 = TRUE;

        // Test for valid case.
        $this->assertTrue((bool)facetoface_get_user_submissions($facetofaceid1, $userid1, $includecancellations1), $this->msgtrue);

        // Test for invalid case.
        $this->assertFalse((bool)facetoface_get_user_submissions($facetofaceid2, $userid2, $includecancellations2), $this->msgfalse);
    }

    function test_facetoface_get_view_actions() {
        $this->init_sample_data();

        // Define test variables.
        $testArray = array('view', 'view all');

        // Test.
        $this->assertEquals(facetoface_get_view_actions(), $testArray);
    }

    function test_facetoface_get_post_actions() {
        $this->init_sample_data();

        // Test method - returns an array.

        // Define test variables.
        $testArray = array('cancel booking', 'signup');

        // Test.
        $this->assertEquals(facetoface_get_post_actions(), $testArray);
    }

    function test_facetoface_session_has_capacity() {
        $this->init_sample_data();

        // Test method - returns boolean.

        // Test variables.
        $session1 = $this->sessions['sess0'];
        $sess0 = (object)$session1;

        $session2 = $this->sessions['sess1'];
        $sess1 = (object)$session2;

        // Test for valid case.
        $this->assertFalse((bool)facetoface_session_has_capacity($sess0), $this->msgfalse);

        // Test for invalid case.
        $this->assertFalse((bool)facetoface_session_has_capacity($sess1), $this->msgfalse);
    }

    function test_facetoface_get_trainer_roles() {
        $this->init_sample_data();

        // Test method - returns array.

        $context = context_course::instance(123074);

        // No session roles.
        $this->assertFalse((bool)facetoface_get_trainer_roles($context), $this->msgfalse);

        // Add some roles.
        set_config('facetoface_session_roles', "4");

        $result = facetoface_get_trainer_roles($context);
        $this->assertEquals($result[4]->localname, 'Trainer');
    }

    function test_facetoface_get_trainers() {
        $this->init_sample_data();

        // Test variables.
        $sessionid1 = 1;
        $roleid1 = 1;

        // Test for valid case.
        $this->assertTrue((bool)facetoface_get_trainers($sessionid1, $roleid1), $this->msgtrue);

        $this->assertTrue((bool)facetoface_get_trainers($sessionid1), $this->msgtrue);
    }

    function test_facetoface_supports() {
        $this->init_sample_data();

        // Test variables.
        $feature1 = 'grade_has_grade';
        $feature2 = 'UNSUPPORTED_FEATURE';

        // Test for valid case.
        $this->assertTrue((bool)facetoface_supports($feature1), $this->msgtrue);

        // Test for invalid case.
        $this->assertFalse((bool)facetoface_supports($feature2), $this->msgfalse);
    }

    function test_facetoface_manager_needed() {
        $this->init_sample_data();

        // Test variables.
        $facetoface1 = $this->facetoface['f2f1'];
        $f2f1 = (object)$facetoface1;

        $facetoface2 = $this->facetoface['f2f0'];
        $f2f2 = (object)$facetoface2;

        // Test for valid case.
        $this->assertTrue((bool)facetoface_manager_needed($f2f1), $this->msgtrue);

        // Test for invalid case.
        $this->assertFalse((bool)facetoface_manager_needed($f2f2), $this->msgfalse);
    }

    function test_facetoface_notify_under_capacity() {
        global $DB;
        $this->init_sample_data();

        $teacher1 = $this->getDataGenerator()->create_user();
        $student1 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();

        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        $this->getDataGenerator()->enrol_user($teacher1->id, $course1->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($student1->id, $course1->id, $studentrole->id);


        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface1',
            'course' => $course1->id
        );
        $facetoface1 = $facetofacegenerator->create_instance($facetofacedata);

        // Session that starts in 24hrs time.
        // This session should trigger a mincapacity warning now as cutoff is 24:01 hrs before start time.
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + DAYSECS;
        $sessiondate->timefinish = time() + DAYSECS + 60;
        $sessiondate->sessiontimezone = 'Pacific/Auckland';

        $sessiondate2 = new stdClass();
        $sessiondate2->timestart = time() + (DAYSECS * 2);
        $sessiondate2->timefinish = time() + (DAYSECS * 2) + 60;
        $sessiondate2->sessiontimezone = 'Pacific/Auckland';

        $sessiondata = array(
            'facetoface' => $facetoface1->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate, $sessiondate2),
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        );
        $facetofacegenerator->add_session($sessiondata);

        // Session that starts in 24hrs time.
        // This session should not trigger a mincapacity warning now as cutoff is 23:59 hrs before start time.
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + DAYSECS;
        $sessiondate->timefinish = time() + DAYSECS + 60;
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessiondata = array(
            'facetoface' => $facetoface1->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
            'mincapacity' => '1',
            'cutoff' => DAYSECS + 60
        );
        $facetofacegenerator->add_session($sessiondata);

        $sink = $this->redirectMessages();
        facetoface_notify_under_capacity();
        $messages = $sink->get_messages();

        // Only the teacher should get a message.
        $this->assertCount(1, $messages, 'The test suit was expecting one message to be sent out regarding seminar event being under minimum booking.');
        $this->assertEquals($messages[0]->useridto, $teacher1->id);

        // Check they got the right message.
        $this->assertEquals(get_string('sessionundercapacity', 'facetoface', format_string($facetoface1->name)), $messages[0]->subject);
    }

    // Face-to-face minimum bookings specification.
    function test_facetoface_disable_notify_under_capacity() {
        global $DB;
        $this->init_sample_data();

        $teacher1 = $this->getDataGenerator()->create_user();
        $student1 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();

        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        $this->getDataGenerator()->enrol_user($teacher1->id, $course1->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($student1->id, $course1->id, $studentrole->id);

        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface1',
            'course' => $course1->id
        );
        $facetoface1 = $facetofacegenerator->create_instance($facetofacedata);

        // Session that starts in 24hrs time.
        // This session should not trigger a mincapacity warning now as cutoff is 23:59 hrs before start time.
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + DAYSECS;
        $sessiondate->timefinish = time() + DAYSECS + 60;
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessiondata = array(
            'facetoface' => $facetoface1->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
            'mincapacity' => '1',
            'cutoff' => ""
        );
        $facetofacegenerator->add_session($sessiondata);

        $sink = $this->redirectMessages();
        facetoface_notify_under_capacity();
        $messages = $sink->get_messages();

        // There should be no messages received.
        $this->assertCount(0, $messages, 'The test suit was expecting no message to be sent out regarding seminar event being under minimum booking.');
    }

    // Face-to-face minimum bookings specification.
    public function test_under_capacity_notification() {
        global $DB;
        $this->init_sample_data();

        $teacher1 = $this->getDataGenerator()->create_user();
        $student1 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();

        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        $this->getDataGenerator()->enrol_user($teacher1->id, $course1->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($student1->id, $course1->id, $studentrole->id);

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $facetofacedata = array(
            'name' => 'facetoface1',
            'course' => $course1->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        // Session that starts in 24hrs time.
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + DAYSECS;
        $sessiondate->timefinish = time() + DAYSECS + 60;
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 10,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
            'mincapacity' => '4',
            'cutoff' => "86400"
        );
        $sessionid = $facetofacegenerator->add_session($sessiondata);
        $session = facetoface_get_session($sessionid);

        // Sign the user up user 2.
        facetoface_user_signup(
            $session,
            $facetoface,
            $course1,
            'discountcode1',
            MDL_F2F_INVITE,
            MDL_F2F_STATUS_BOOKED,
            $student1->id,
            false
        );

        // Set the session date back an hour, this is enough for facetoface_notify_under_capacity to find this session.
        $sql = 'UPDATE {facetoface_sessions_dates} SET timestart = (timestart - 360) WHERE sessionid = :sessionid';
        $DB->execute($sql, array('sessionid' => $sessionid));

        $sink = $this->redirectMessages();
        facetoface_notify_under_capacity();
        $messages = $sink->get_messages();

        // There should be one messages received.
        $this->assertCount(1, $messages, 'The test suit was expecting one message to be sent out regarding seminar event being under minimum booking.');
        $message = array_pop($messages);
        $this->assertSame('Event under minimum bookings for: facetoface1', $message->subject);
        $this->assertContains('The following event is under minimum bookings:', $message->fullmessage);
        $this->assertContains('Capacity: 1 / 10 (minimum: 4)', $message->fullmessage);
    }

    public function test_facetoface_is_signup_cancelled() {
        $learner = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        // Create a session
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $facetoface = $facetofacegenerator->create_instance(array('course' => $course->id));
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + (YEARSECS * 2);
        $sessiondate->timefinish = time() + (YEARSECS * 2 + 60);
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 5,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
        );
        $sessionid = $facetofacegenerator->add_session($sessiondata);
        $session = facetoface_get_session($sessionid);

        // Sign the user up.
        facetoface_user_signup(
            $session,
            $facetoface,
            $course,
            'discountcode1',
            MDL_F2F_INVITE,
            MDL_F2F_STATUS_BOOKED,
            $learner->id
        );

        // Make sure learner is booked.
        $booked = facetoface_get_attendees($session->id, MDL_F2F_STATUS_BOOKED, true);
        $this->assertCount(1, $booked);
        $booked = reset($booked);
        $this->assertEquals($learner->id, $booked->id);

        $this->assertFalse(facetoface_is_signup_cancelled($booked->signupid));

        facetoface_user_cancel($session, $learner->id);

        $this->assertTrue(facetoface_is_signup_cancelled($booked->signupid));
    }

    public function test_facetoface_waitlist() {
        $this->init_sample_data();

        // Set two users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Set up a face to face session with a capacity of 1 and overbook enabled.
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $facetoface = $facetofacegenerator->create_instance(array('course' => $this->course1->id));

        // Create session with capacity and date in 2 years.
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + (YEARSECS * 2);
        $sessiondate->timefinish = time() + (YEARSECS * 2 + 60);
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 1,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
        );
        $sessionid = $facetofacegenerator->add_session($sessiondata);
        $session = facetoface_get_session($sessionid);

        $sink = $this->redirectMessages();
        // Sign the user up user 2.
        facetoface_user_signup(
            $session,
            $facetoface,
            $this->course1,
            'discountcode1',
            MDL_F2F_INVITE,
            MDL_F2F_STATUS_BOOKED,
            $user1->id,
            true,
            null
        );

        // Sign the user up user 1.
        facetoface_user_signup(
            $session,
            $facetoface,
            $this->course1,
            'discountcode1',
            MDL_F2F_INVITE,
            MDL_F2F_STATUS_WAITLISTED,
            $user2->id,
            true,
            null
        );
        $messages = $sink->get_messages();
        // User 1 and 2 should have received confirmation messages.
        $this->assertCount(2, $messages);

        $founduser1 = false;
        $founduser2 = false;

        // Look for user1 and user 2 email addresses.
        foreach ($messages as $message) {
            if ($message->useridto == $user1->id) {
                $founduser1 = true;
            } else if ($message->useridto == $user2->id) {
                $founduser2 = true;
            }
        }
        $this->assertTrue($founduser1);
        $this->assertTrue($founduser2);

        $sink->clear();

        // User 1 should be booked, user 2 waitlisted.
        $booked = facetoface_get_attendees($session->id, MDL_F2F_STATUS_BOOKED);
        $waitlisted = facetoface_get_attendees($session->id, MDL_F2F_STATUS_WAITLISTED);
        $this->assertCount(1, $booked);
        $this->assertCount(1, $waitlisted);
        $booked = reset($booked);
        $waitlisted = reset($waitlisted);
        $this->assertEquals($user1->id, $booked->id);
        $this->assertEquals($user2->id, $waitlisted->id);

        $sink->clear();

        // Cancel user1's booking.
        facetoface_user_cancel($session, $user1->id);

        $cancelled = facetoface_get_attendees($session->id, MDL_F2F_STATUS_USER_CANCELLED);
        $booked = facetoface_get_attendees($session->id, MDL_F2F_STATUS_BOOKED);
        $waitlisted = facetoface_get_attendees($session->id, MDL_F2F_STATUS_WAITLISTED);

        // User 1 should be cancelled, user 2 should be booked.
        $this->assertCount(1, $cancelled);
        $this->assertCount(1, $booked);
        $this->assertCount(0, $waitlisted);
        $cancelled = reset($cancelled);
        $booked = reset($booked);
        $this->assertEquals($user1->id, $cancelled->id);
        $this->assertEquals($user2->id, $booked->id);

        // User 2 should have had a message from admin.
        $messages = $sink->get_messages();
        $this->assertCount(1, $messages);
        $message = reset($messages);
        $this->assertEquals($user2->id, $message->useridto);
    }

    /**
     * Data provider for the facetoface_messages function.
     *
     * @return array $data Data to be used by test_facetoface_messages.
     */
    public function facetoface_messaging_settings() {
        $data = array(
            array('no-reply@example.com', ''),
            array('no-reply@example.com', 'facetofacesender@example.com'),
            array('', ''),
        );
        return $data;
    }

    /**
     * Test facetoface messaging.
     *
     * @param string $noreplyaddress No-reply address
     * @param string $senderfrom Sender from setting in Face to face
     * @dataProvider facetoface_messaging_settings
     */
    public function test_facetoface_messages($noreplyaddress, $senderfrom) {
        $this->init_sample_data();

        $this->setAdminUser();

        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $user1 = $this->getDataGenerator()->create_user(array('email' => 'user1@example.com'));
        $user2 = $this->getDataGenerator()->create_user(array('email' => 'user2@example.com'));
        $user3 = $this->getDataGenerator()->create_user(array('email' => 'user3@example.com'));

        $manager1 = $this->getDataGenerator()->create_user(array('email' => 'manager1@example.com'));
        $manager2 = $this->getDataGenerator()->create_user(array('email' => 'manager2@example.com'));

        // Assign managers to students.
        $manager1ja = \totara_job\job_assignment::create_default($manager1->id);
        $manager2ja = \totara_job\job_assignment::create_default($manager2->id);
        \totara_job\job_assignment::create_default($user1->id, array('managerjaid' => $manager1ja->id));
        \totara_job\job_assignment::create_default($user2->id, array('managerjaid' => $manager2ja->id));

        set_config('noreplyaddress', $noreplyaddress);
        set_config('facetoface_fromaddress', $senderfrom);

        // Create a facetoface activity and assign it to the course.
        $course = $this->getDataGenerator()->create_course();
        $facetoface = $facetofacegenerator->create_instance(array('course' => $course->id, 'multiplesessions' => 1));

        // Create session with capacity and date in 2 days.
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + (DAYSECS * 2);
        $sessiondate->timefinish = time() + (DAYSECS * 2 + 60);
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
        );
        $sessionid = $facetofacegenerator->add_session($sessiondata);
        $session = facetoface_get_session($sessionid);

        // Grab any messages that get sent.
        $sink = $this->redirectMessages();

        facetoface_user_signup($session,
            $facetoface, $course, 'discountcode1', MDL_F2F_INVITE, MDL_F2F_STATUS_BOOKED, $user1->id, true, $user3);
        facetoface_user_signup($session,
            $facetoface, $course, 'discountcode1', MDL_F2F_INVITE, MDL_F2F_STATUS_BOOKED, $user2->id, true, $user3);

        // Check emails.
        $emails = $sink->get_messages();
        $this->assertCount(4, $emails); // Learners and managers.

        // Set userfrom used for the assertion.
        $userfrom = (!empty($senderfrom)) ? \mod_facetoface\facetoface_user::get_facetoface_user() : $user3;

        $checkformat = '%s %s (%s)';
        $expectedemail = $noreplyaddress;
        $expected = sprintf($checkformat, $userfrom->firstname, $userfrom->lastname, $expectedemail);

        foreach ($emails as $email) {
            $actual = sprintf($checkformat, $email->fromfirstname, $email->fromlastname, $email->fromemail);
            $this->assertEquals($expected, $actual);
        }
        $sink->clear();
    }

    public function test_send_scheduled(){
        global $DB;
        $this->init_sample_data();

        // We need to explicitly declare users' firstnames as these need to be unique - generator may sometimes produce duplicates.
        $user1 = $this->getDataGenerator()->create_user(array('firstname' => 'user1'));
        $user2 = $this->getDataGenerator()->create_user(array('firstname' => 'user2'));
        $user3 = $this->getDataGenerator()->create_user(array('firstname' => 'user3'));
        $user4 = $this->getDataGenerator()->create_user(array('firstname' => 'user4'));
        $user5 = $this->getDataGenerator()->create_user(array('firstname' => 'user5'));
        $user6 = $this->getDataGenerator()->create_user(array('firstname' => 'user6'));
        $user7 = $this->getDataGenerator()->create_user(array('firstname' => 'user7'));

        $course1 = $this->getDataGenerator()->create_course();
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $facetoface = $facetofacegenerator->create_instance(array('course' => $course1->id, 'multiplesessions' => 1));

        $sessiondate1 = new stdClass();
        $sessiondate1->timestart = time() + (HOURSECS * 1);
        $sessiondate1->timefinish = time() + (HOURSECS * 2);
        $sessiondate1->sessiontimezone = 'Australia/Sydney';

        $sessiondata1 = array(
            'facetoface' => $facetoface->id,
            'capacity' => 10,
            'sessiondates' => array($sessiondate1),
        );
        $sessionid1 = $facetofacegenerator->add_session($sessiondata1);
        $session1 = facetoface_get_session($sessionid1);

        $sessiondate2 = new stdClass();
        $sessiondate2->timestart = time() + (HOURSECS * 3);
        $sessiondate2->timefinish = time() + (HOURSECS * 4);
        $sessiondate2->sessiontimezone = 'Australia/Sydney';

        $sessiondata2 = array(
            'facetoface' => $facetoface->id,
            'capacity' => 10,
            'sessiondates' => array($sessiondate2),
        );
        $sessionid2 = $facetofacegenerator->add_session($sessiondata2);
        $session2 = facetoface_get_session($sessionid2);

        $sessiondate3 = new stdClass();
        $sessiondate3->timestart = time() + (HOURSECS * 1);
        $sessiondate3->timefinish = time() + (HOURSECS * 2);
        $sessiondate3->sessiontimezone = 'Australia/Sydney';

        $sessiondata3 = array(
            'facetoface' => $facetoface->id,
            'capacity' => 10,
            'sessiondates' => array($sessiondate3),
        );
        $sessionid3 = $facetofacegenerator->add_session($sessiondata3);
        $session3 = facetoface_get_session($sessionid3);

        // Notification 1 goes to booked and waitlisted users 2 hours before start of session.
        $notification1 = new facetoface_notification();
        $notification1->courseid = $course1->id;
        $notification1->facetofaceid = $facetoface->id;
        $notification1->ccmanager = 0;
        $notification1->status = 1;
        $notification1->title = '2 hours before';
        $notification1->body = get_string('placeholder:firstname', 'facetoface').' 2 hours before';
        $notification1->managerprefix = '';
        $notification1->type = MDL_F2F_NOTIFICATION_SCHEDULED;
        $notification1->conditiontype = MDL_F2F_CONDITION_BEFORE_SESSION;
        $notification1->scheduleunit = MDL_F2F_SCHEDULE_UNIT_HOUR;
        $notification1->scheduleamount = 2;
        $notification1->booked = 1;
        $notification1->waitlisted = 1;
        $notification1->save();

        // Notification 2 goes to booked users 4 hours before start of session.
        $notification2 = new facetoface_notification();
        $notification2->courseid = $course1->id;
        $notification2->facetofaceid = $facetoface->id;
        $notification2->ccmanager = 0;
        $notification2->status = 1;
        $notification2->title = '4 hours before';
        $notification2->body = get_string('placeholder:firstname', 'facetoface').' 4 hours before';
        $notification2->managerprefix = '';
        $notification2->type = MDL_F2F_NOTIFICATION_SCHEDULED;
        $notification2->conditiontype = MDL_F2F_CONDITION_BEFORE_SESSION;
        $notification2->scheduleunit = MDL_F2F_SCHEDULE_UNIT_HOUR;
        $notification2->scheduleamount = 4;
        $notification2->booked = 1;
        $notification2->save();

        // Notification 3 goes to booked users 1 hour after end of session.
        $notification3 = new facetoface_notification();
        $notification3->courseid = $course1->id;
        $notification3->facetofaceid = $facetoface->id;
        $notification3->ccmanager = 0;
        $notification3->status = 1;
        $notification3->title = '1 hour after';
        $notification3->body = get_string('placeholder:firstname', 'facetoface').' 1 hour after';
        $notification3->managerprefix = '';
        $notification3->type = MDL_F2F_NOTIFICATION_SCHEDULED;
        $notification3->conditiontype = MDL_F2F_CONDITION_AFTER_SESSION;
        $notification3->scheduleunit = MDL_F2F_SCHEDULE_UNIT_HOUR;
        $notification3->scheduleamount = 1;
        $notification3->booked = 1;
        $notification3->save();

        // Grab any messages that get sent.
        $sink = $this->redirectMessages();

        // Note that signup times in the database are being edited below. This is necessary to test scheduled notifications.
        facetoface_user_signup($session1, $facetoface, $course1, NULL, MDL_F2F_INVITE, MDL_F2F_STATUS_BOOKED, $user1->id, true);
        $user1signupid = $DB->get_field('facetoface_signups', 'id', array('sessionid' => $session1->id, 'userid' => $user1->id));
        $user1status = $DB->get_record('facetoface_signups_status', array('signupid' => $user1signupid, 'superceded' => 0));
        $user1status->timecreated = time() - HOURSECS * 6;
        $DB->update_record('facetoface_signups_status', $user1status);

        facetoface_user_signup($session2, $facetoface, $course1, NULL, MDL_F2F_INVITE, MDL_F2F_STATUS_BOOKED, $user2->id, true);
        $user2signupid = $DB->get_field('facetoface_signups', 'id', array('sessionid' => $session2->id, 'userid' => $user2->id));
        $user2status = $DB->get_record('facetoface_signups_status', array('signupid' => $user2signupid, 'superceded' => 0));
        $user2status->timecreated = time() - HOURSECS * 6;
        $DB->update_record('facetoface_signups_status', $user2status);

        facetoface_user_signup($session1, $facetoface, $course1, NULL, MDL_F2F_INVITE, MDL_F2F_STATUS_BOOKED, $user3->id, true);
        $user3signupid = $DB->get_field('facetoface_signups', 'id', array('sessionid' => $session1->id, 'userid' => $user3->id));
        $user3status = $DB->get_record('facetoface_signups_status', array('signupid' => $user3signupid, 'superceded' => 0));
        $user3status->timecreated = time() - HOURSECS * 2;
        $DB->update_record('facetoface_signups_status', $user3status);

        facetoface_user_signup($session3, $facetoface, $course1, NULL, MDL_F2F_INVITE, MDL_F2F_STATUS_BOOKED, $user4->id, true);
        $session3date = $DB->get_record('facetoface_sessions_dates', array('sessionid' => $session3->id));
        $session3date->timestart -= HOURSECS * 4;
        $session3date->timefinish -= HOURSECS * 4;
        $DB->update_record('facetoface_sessions_dates', $session3date);
        $user4signupid = $DB->get_field('facetoface_signups', 'id', array('sessionid' => $session3->id, 'userid' => $user4->id));
        $user4status = $DB->get_record('facetoface_signups_status', array('signupid' => $user4signupid, 'superceded' => 0));
        $user4status->timecreated = time() - HOURSECS * 4;
        $DB->update_record('facetoface_signups_status', $user4status);

        facetoface_user_signup($session1, $facetoface, $course1, NULL, MDL_F2F_INVITE, MDL_F2F_STATUS_WAITLISTED, $user5->id, true);
        $user5signupid = $DB->get_field('facetoface_signups', 'id', array('sessionid' => $session1->id, 'userid' => $user5->id));
        $user5status = $DB->get_record('facetoface_signups_status', array('signupid' => $user5signupid, 'superceded' => 0));
        $user5status->timecreated = time() - HOURSECS * 6;
        $DB->update_record('facetoface_signups_status', $user5status);
        facetoface_user_signup($session1, $facetoface, $course1, NULL, MDL_F2F_INVITE, MDL_F2F_STATUS_BOOKED, $user5->id, true);
        $user5signupid = $DB->get_field('facetoface_signups', 'id', array('sessionid' => $session1->id, 'userid' => $user5->id));
        $user5status = $DB->get_record('facetoface_signups_status', array('signupid' => $user5signupid, 'superceded' => 0));
        $user5status->timecreated = time() - MINSECS * 30;
        $DB->update_record('facetoface_signups_status', $user5status);

        facetoface_user_signup($session1, $facetoface, $course1, NULL, MDL_F2F_INVITE, MDL_F2F_STATUS_BOOKED, $user6->id, true);
        $user6signupid = $DB->get_field('facetoface_signups', 'id', array('sessionid' => $session1->id, 'userid' => $user6->id));
        $user6status = $DB->get_record('facetoface_signups_status', array('signupid' => $user6signupid, 'superceded' => 0));
        $user6status->timecreated = time() - HOURSECS * 6;
        $DB->update_record('facetoface_signups_status', $user6status);
        facetoface_user_signup($session1, $facetoface, $course1, NULL, MDL_F2F_INVITE, MDL_F2F_STATUS_USER_CANCELLED, $user6->id, true);

        facetoface_user_signup($session1, $facetoface, $course1, NULL, MDL_F2F_INVITE, MDL_F2F_STATUS_BOOKED, $user7->id, true);
        $user7signupid = $DB->get_field('facetoface_signups', 'id', array('sessionid' => $session1->id, 'userid' => $user7->id));
        $user7status = $DB->get_record('facetoface_signups_status', array('signupid' => $user7signupid, 'superceded' => 0));
        $user7status->timecreated = time() - HOURSECS * 6;
        $DB->update_record('facetoface_signups_status', $user7status);
        facetoface_user_signup($session1, $facetoface, $course1, NULL, MDL_F2F_INVITE, MDL_F2F_STATUS_USER_CANCELLED, $user7->id, true);
        $user7signupid = $DB->get_field('facetoface_signups', 'id', array('sessionid' => $session1->id, 'userid' => $user7->id));
        $user7status = $DB->get_record('facetoface_signups_status', array('signupid' => $user7signupid, 'superceded' => 0));
        $user7status->timecreated = time() - HOURSECS * 2;
        $DB->update_record('facetoface_signups_status', $user7status);
        facetoface_user_signup($session1, $facetoface, $course1, NULL, MDL_F2F_INVITE, MDL_F2F_STATUS_BOOKED, $user7->id, true);
        $user7signupid = $DB->get_field('facetoface_signups', 'id', array('sessionid' => $session1->id, 'userid' => $user7->id));
        $user7status = $DB->get_record('facetoface_signups_status', array('signupid' => $user7signupid, 'superceded' => 0));
        $user7status->timecreated = time() - MINSECS * 30;
        $DB->update_record('facetoface_signups_status', $user7status);

        $notification1->send_scheduled();
        $notification2->send_scheduled();
        $notification3->send_scheduled();

        // Grab the messages that got sent.
        $messages = $sink->get_messages();

        // Put the actual message content into their own array to test against
        $fullmessages = array();
        foreach($messages as $message){
            $fullmessages[] = $message->fullmessage;
        }

        // 6 hours ago, user1 signed up to session that starts 1 hour from now.
        $this->assertContains($user1->firstname.' 2 hours before', $fullmessages);
        $this->assertContains($user1->firstname.' 4 hours before', $fullmessages);
        $this->assertNotContains($user1->firstname.' 1 hour after', $fullmessages);

        // 6 hours ago, user2 signed up to session that starts 3 hours from now.
        $this->assertNotContains($user2->firstname.' 2 hours before', $fullmessages);
        $this->assertContains($user2->firstname.' 4 hours before', $fullmessages);
        $this->assertNotContains($user2->firstname.' 1 hour after', $fullmessages);

        // 2 hours ago, user3 signed up to session that starts 1 hour from now.
        $this->assertContains($user3->firstname.' 2 hours before', $fullmessages);
        $this->assertNotContains($user3->firstname.' 4 hours before', $fullmessages);
        $this->assertNotContains($user3->firstname.' 1 hour after', $fullmessages);

        // user4 has signed up a session an hour before it started. That session finished 2 hours ago.
        $this->assertNotContains($user4->firstname.' 2 hours before', $fullmessages);
        $this->assertNotContains($user4->firstname.' 4 hours before', $fullmessages);
        $this->assertContains($user4->firstname.' 1 hour after', $fullmessages);

        // 6 hours ago, user5 was waitlisted for a session and then became booked half an hour ago. The session starts in one hour.
        $this->assertContains($user5->firstname.' 2 hours before', $fullmessages);
        $this->assertNotContains($user5->firstname.' 4 hours before', $fullmessages);
        $this->assertNotContains($user5->firstname.' 1 hour after', $fullmessages);

        // 6 hours ago, user6 signed up to a session that starts 1 hour from now. But has cancelled just before notifications were sent.
        $this->assertNotContains($user6->firstname.' 2 hours before', $fullmessages);
        $this->assertNotContains($user6->firstname.' 4 hours before', $fullmessages);
        $this->assertNotContains($user6->firstname.' 1 hour after', $fullmessages);

        // 6 hours ago, user7 booked for a session that starts 1 hour from now. Then cancelled 2 hours ago. And then was rebooked
        // 30 minutes ago.  So user7's status was cancelled at the time the '2 hours before' notification was scheduled to go out.
        $this->assertNotContains($user7->firstname.' 2 hours before', $fullmessages);
        $this->assertContains($user7->firstname.' 4 hours before', $fullmessages);
        $this->assertNotContains($user7->firstname.' 1 hour after', $fullmessages);

        // Check that notifications are not sent again.
        $newsink = $this->redirectMessages();
        $notification1->send_scheduled();
        $newmessages = $newsink->get_messages();
        $this->assertCount(0, $newmessages);
    }

    /**
     * Test the function totara_core_update_module_completion_data works with:
     * - Course completion disabled.
     * - Activity completion enabled and based on the learner being fully attended at a session.
     *
     * Reaggregation of activity completion is done via totara_core_reaggregate_course_modules_completion().
     */
    public function test_totara_core_update_module_completion_data_facetoface_fullyattended() {
        global $DB;
        $this->init_sample_data();

        set_config('enablecompletion', '1');

        $generator = $this->getDataGenerator();
        $course = $generator->create_course(array('enablecompletion' => 1));

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $generator->get_plugin_generator('mod_facetoface');

        $f2fdata = new stdClass();
        $f2fdata->course = $course->id;
        $f2foptions = array(
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionstatusrequired' => json_encode(array(MDL_F2F_STATUS_FULLY_ATTENDED))
        );
        $facetoface = $facetofacegenerator->create_instance($f2fdata, $f2foptions);

        $sessiondate1 = (object)($this->facetoface_sessions_dates_data);
        $sessiondata1 = array(
            'facetoface' => $facetoface->id,
            'capacity' => 10,
            'sessiondates' => array($sessiondate1),
            'datetimeknown' => '1',
        );
        $sessionid1 = $facetofacegenerator->add_session($sessiondata1);
        $session1 = facetoface_get_session($sessionid1);

        $generator->enrol_user($this->user1->id, $course->id);
        facetoface_user_signup($session1, $facetoface, $course, NULL, MDL_F2F_INVITE, MDL_F2F_STATUS_BOOKED, $this->user1->id, true);
        // I need the signup id.
        $signups = facetoface_get_user_submissions($facetoface->id, $this->user1->id);
        $signupids = array_keys($signups);
        $signupid = array_shift($signupids);

        // Time to set up what we need to check completion statuses.
        $completion = new completion_info($course);
        $modinfo = get_fast_modinfo($course);
        $cminfo =  $modinfo->instances['facetoface'][$facetoface->id];

        $this->assertEquals(false, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $this->user1->id)));

        $data = new stdClass();
        $data->s = $sessionid1;
        // submissionid_ is required at the beginning of each key.
        $data->{'submissionid_'.$signupid} = MDL_F2F_STATUS_FULLY_ATTENDED;

        facetoface_take_attendance($data);

        $this->assertEquals(true, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $this->user1->id, 'completionstate' => COMPLETION_COMPLETE)));

        // This object is equivalent to what might be returned from a form using $mform->get_data().
        $moduleinfo = new stdClass();
        $moduleinfo->course = $course->id;
        $moduleinfo->coursemodule = $cminfo->id;
        $moduleinfo->modulename = $cminfo->name;
        $moduleinfo->instance = $cminfo->instance;
        $moduleinfo->completionunlocked = 1;
        $moduleinfo->completionunlockednoreset = 0;

        totara_core_update_module_completion_data($cminfo, $moduleinfo, $course, $completion);

        $this->assertEquals(false, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $this->user1->id, 'completionstate' => COMPLETION_COMPLETE)));

        $this->waitForSecond();
        totara_core_reaggregate_course_modules_completion();

        $this->assertEquals(true, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $this->user1->id, 'completionstate' => COMPLETION_COMPLETE)));
    }

    /**
     * Test the function totara_core_update_module_completion_data works with:
     * - Course completion enabled.
     * - Activity completion enabled and based on the learner being fully attended at a session.
     *
     * Reaggregation of activity completion is done via totara_core_reaggregate_course_modules_completion().
     */
    public function test_totara_core_update_module_completion_data_facetoface_fullyattended_course_completion() {
        global $DB;
        $this->init_sample_data();

        set_config('enablecompletion', '1');

        $generator = $this->getDataGenerator();
        $course = $generator->create_course(array('enablecompletion' => 1));

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $generator->get_plugin_generator('mod_facetoface');

        $f2fdata = new stdClass();
        $f2fdata->course = $course->id;
        $f2foptions = array(
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionstatusrequired' => json_encode(array(MDL_F2F_STATUS_FULLY_ATTENDED))
        );
        $facetoface = $facetofacegenerator->create_instance($f2fdata, $f2foptions);

        $course_completion_info = new completion_info($course);
        $this->assertEquals(COMPLETION_ENABLED, $course_completion_info->is_enabled());

        $sessiondate1 = (object)($this->facetoface_sessions_dates_data);
        $sessiondata1 = array(
            'facetoface' => $facetoface->id,
            'capacity' => 10,
            'sessiondates' => array($sessiondate1),
            'datetimeknown' => '1',
        );
        $sessionid1 = $facetofacegenerator->add_session($sessiondata1);
        $session1 = facetoface_get_session($sessionid1);

        $generator->enrol_user($this->user1->id, $course->id);

        facetoface_user_signup($session1, $facetoface, $course, NULL, MDL_F2F_INVITE, MDL_F2F_STATUS_BOOKED, $this->user1->id, true);
        // I need the signup id.
        $signups = facetoface_get_user_submissions($facetoface->id, $this->user1->id);
        $signupids = array_keys($signups);
        $signupid = array_shift($signupids);

        // Time to set up what we need to check completion statuses.
        $completion = new completion_info($course);
        $modinfo = get_fast_modinfo($course);
        $cminfo =  $modinfo->instances['facetoface'][$facetoface->id];

        $data = new stdClass();
        $data->id = $course->id;
        $data->overall_aggregation = COMPLETION_AGGREGATION_ALL;
        $data->criteria_activity_value = array($cminfo->id => 1);
        $criterion = new completion_criteria_activity();
        $criterion->update_config($data);

        $this->assertEquals(false, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $this->user1->id)));

        // facetoface_take_attendance specifies an array and runs foreach on it, but really it expects an object.
        $data = new stdClass();
        $data->s = $sessionid1;
        // submissionid_ is required at the beginning of each key.
        $data->{'submissionid_'.$signupid} = MDL_F2F_STATUS_FULLY_ATTENDED;

        facetoface_take_attendance($data);

        $this->assertEquals(true, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $this->user1->id, 'completionstate' => COMPLETION_COMPLETE)));

        // This object is equivalent to what might be returned from a form using $mform->get_data().
        $moduleinfo = new stdClass();
        $moduleinfo->course = $course->id;
        $moduleinfo->coursemodule = $cminfo->id;
        $moduleinfo->modulename = $cminfo->name;
        $moduleinfo->instance = $cminfo->instance;
        $moduleinfo->completionunlocked = 1;
        $moduleinfo->completionunlockednoreset = 0;

        totara_core_update_module_completion_data($cminfo, $moduleinfo, $course, $completion);

        $this->assertEquals(false, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $this->user1->id, 'completionstate' => COMPLETION_COMPLETE)));

        $this->waitForSecond();
        totara_core_reaggregate_course_modules_completion();

        $this->assertEquals(true, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $this->user1->id, 'completionstate' => COMPLETION_COMPLETE)));
    }

    /**
     * Test the function totara_core_update_module_completion_data works with:
     * - Course completion enabled.
     * - Activity completion enabled and based on the learner being fully attended at a session and viewing the
     *   Face-to-face activity.
     *
     * Reaggregation of activity completion is done via totara_core_reaggregate_course_modules_completion().
     */
    public function test_totara_core_update_module_completion_data_facetoface_fullyattended_viewed() {
        global $DB;
        $this->init_sample_data();

        set_config('enablecompletion', '1');

        $generator = $this->getDataGenerator();
        $course = $generator->create_course(array('enablecompletion' => 1));

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $generator->get_plugin_generator('mod_facetoface');

        $f2fdata = new stdClass();
        $f2fdata->course = $course->id;
        $f2foptions = array(
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => COMPLETION_VIEW_REQUIRED,
            'completionstatusrequired' => json_encode(array(MDL_F2F_STATUS_FULLY_ATTENDED))
        );
        $facetoface = $facetofacegenerator->create_instance($f2fdata, $f2foptions);

        $sessiondate1 = (object)($this->facetoface_sessions_dates_data);
        $sessiondata1 = array(
            'facetoface' => $facetoface->id,
            'capacity' => 10,
            'sessiondates' => array($sessiondate1),
            'datetimeknown' => '1',
        );
        $sessionid1 = $facetofacegenerator->add_session($sessiondata1);
        $session1 = facetoface_get_session($sessionid1);

        $generator->enrol_user($this->user1->id, $course->id);
        facetoface_user_signup($session1, $facetoface, $course, NULL, MDL_F2F_INVITE, MDL_F2F_STATUS_BOOKED, $this->user1->id, true);
        // We need the signup id.
        $signups = facetoface_get_user_submissions($facetoface->id, $this->user1->id);
        $signupids = array_keys($signups);
        $signupid = array_shift($signupids);

        // Time to set up what we need to update and check completion statuses.
        $completion = new completion_info($course);
        $modinfo = get_fast_modinfo($course);
        $cminfo =  $modinfo->instances['facetoface'][$facetoface->id];

        // The user has not attended the course and is not even enrolled, so no completion record should
        // exist for them yet.
        $this->assertEquals(false, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $this->user1->id)));

        // Set the user as having fully attended the face-to-face activity.
        $data = new stdClass();
        $data->s = $sessionid1;
        $data->{'submissionid_'.$signupid} = MDL_F2F_STATUS_FULLY_ATTENDED;
        facetoface_take_attendance($data);

        // Suppressing the debugging message. See TL-8669 for more info - setting $cm->timecompleted in face-to-face.
        $this->assertDebuggingCalled($completion->set_module_viewed($cminfo, $this->user1->id));

        // The activity has been attended and been viewed. It should now be complete.
        $this->assertEquals(true, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $this->user1->id, 'completionstate' => COMPLETION_COMPLETE)));

        // This object is equivalent to what might be returned from a form using $mform->get_data().
        $moduleinfo = new stdClass();
        $moduleinfo->course = $course->id;
        $moduleinfo->coursemodule = $cminfo->id;
        $moduleinfo->modulename = $cminfo->name;
        $moduleinfo->instance = $cminfo->instance;
        $moduleinfo->completionunlocked = 1;
        $moduleinfo->completionunlockednoreset = 0;

        totara_core_update_module_completion_data($cminfo, $moduleinfo, $course, $completion);

        // Immediately after totara_core_update_module_completion_data is called, records for this activity should
        // be set to incomplete.
        $this->assertEquals(false, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $this->user1->id, 'completionstate' => COMPLETION_COMPLETE)));

        $this->waitForSecond();
        totara_core_reaggregate_course_modules_completion();

        // The activity should now be complete.
        $this->assertEquals(true, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $this->user1->id, 'completionstate' => COMPLETION_COMPLETE)));
    }

    protected function prepare_date($timestart, $timeend, $roomid) {
        $sessiondate = new stdClass();
        $sessiondate->timestart = (string)$timestart;
        $sessiondate->timefinish = (string)$timeend;
        $sessiondate->sessiontimezone = '99';
        $sessiondate->roomid = (string)$roomid;
        return $sessiondate;
    }

    /**
     * Test facetoface_activity_can_declare_interest()
     */
    public function test_facetoface_activity_can_declare_interest() {
        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $room = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 1', 'allowconflicts' => 1));

        // Declare interest is enabled, and user is not submitted, and there no upcoming sessions.
        $facetoface1 = $this->facetoface_generator->create_instance(array('course' => $course->id, 'declareinterest' => true));
        $user1 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $sessiondates1 = [$this->prepare_date($now - (DAYSECS * 4), $now - (DAYSECS * 3), $room->id)];
        $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates1));
        $this->assertTrue(facetoface_activity_can_declare_interest($facetoface1, $user1->id));

        // Declare interest is disabled.
        $facetoface2 = $this->facetoface_generator->create_instance(array('course' => $course->id, 'declareinterest' => false));
        $user2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $this->facetoface_generator->add_session(array('facetoface' => $facetoface2->id, 'sessiondates' => $sessiondates1));
        $this->assertFalse(facetoface_activity_can_declare_interest($facetoface2, $user2->id));

        // User already declared interest.
        $facetoface3 = $this->facetoface_generator->create_instance(array('course' => $course->id, 'declareinterest' => true));
        $user3 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);
        $this->facetoface_generator->add_session(array('facetoface' => $facetoface3->id, 'sessiondates' => $sessiondates1));
        $this->assertTrue(facetoface_activity_can_declare_interest($facetoface3, $user3->id));
        facetoface_declare_interest($facetoface3, 'Reason', $user3->id);
        $this->assertFalse(facetoface_activity_can_declare_interest($facetoface3, $user3->id));

        // User already submitted.
        $facetoface4 = $this->facetoface_generator->create_instance(array('course' => $course->id, 'declareinterest' => true, 'interestonlyiffull' => true));
        $user4 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user4->id, $course->id);
        $session4id = $this->facetoface_generator->add_session(array('facetoface' => $facetoface4->id, 'sessiondates' => $sessiondates1));
        $session4 = facetoface_get_session($session4id);
        $this->assertTrue(facetoface_activity_can_declare_interest($facetoface4, $user4->id));
        facetoface_user_signup($session4, $facetoface4, $course, '', MDL_F2F_INVITE, MDL_F2F_STATUS_REQUESTED, $user4->id);
        $this->assertDebuggingCalled(get_string('error:nomanagersemailset', 'facetoface'));
        $this->assertFalse(facetoface_activity_can_declare_interest($facetoface4, $user4->id));
    }

    /**
     * Test facetoface_is_adminapprover
     */
    public function test_facetoface_is_adminapprover() {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $facetoface1 = $this->facetoface_generator->create_instance(array('course' => $course->id, 'approvaltype' => APPROVAL_ADMIN));
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $facetoface2 = $this->facetoface_generator->create_instance(array('course' => $course->id, 'approvaltype' => APPROVAL_ADMIN,
                'approvaladmins' => "{$user2->id},{$user3->id}"));

        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();
        $user6 = $this->getDataGenerator()->create_user();

        // 3,4,5 - system approvers.
        set_config('facetoface_adminapprovers', "{$user3->id},{$user4->id},{$user5->id}");

        // 3,5,6 - has capability.
        $managerrole = $DB->get_record('role', array('shortname' => 'manager'));
        $context = context_system::instance();
        assign_capability('mod/facetoface:approveanyrequest', CAP_ALLOW, $managerrole->id, $context);
        $this->getDataGenerator()->role_assign($managerrole->id, $user3->id, $context->id);
        $this->getDataGenerator()->role_assign($managerrole->id, $user5->id, $context->id);
        $this->getDataGenerator()->role_assign($managerrole->id, $user6->id, $context->id);


        // not listed in activity, not system approver.
        $user1 = $this->getDataGenerator()->create_user();
        $this->assertFalse(facetoface_is_adminapprover($user1->id, $facetoface1));

        // listed in different activity, not system approver.
        $this->assertFalse(facetoface_is_adminapprover($user2->id, $facetoface1));
        // listed in activity, not system approver.
        $this->assertTrue(facetoface_is_adminapprover($user2->id, $facetoface2));

        // not listed in activity, system approver, no capability.
        $this->assertFalse(facetoface_is_adminapprover($user4->id, $facetoface1));

        // not listed in activity, not system approver, has capability.
        $this->assertFalse(facetoface_is_adminapprover($user6->id, $facetoface1));

        // not listed in activity, system approver, has capability.
        $this->assertTrue(facetoface_is_adminapprover($user5->id, $facetoface1));


        // listed in activity, system approver, has capability.
        $this->assertTrue(facetoface_is_adminapprover($user3->id, $facetoface2));
    }

    /**
     * Test facetoface_can_user_signup()
     */
    public function test_facetoface_can_user_signup() {
        global $DB;
        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $room = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 1', 'allowconflicts' => 1));

        // Session in future, there free space, and registration time frame in.
        $facetoface1 = $this->facetoface_generator->create_instance(array('course' => $course->id));
        $user1 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $sessiondates1 = [$this->prepare_date($now + (DAYSECS * 3), $now + (DAYSECS * 4), $room->id)];
        $session1id = $this->facetoface_generator->add_session(array(
            'facetoface' => $facetoface1->id,
            'sessiondates' => $sessiondates1,
            'registrationtimestart' => $now - (DAYSECS * 1),
            'registrationtimefinish' => $now + (DAYSECS * 1),
        ));
        $session1 = facetoface_get_session($session1id);
        $this->assertTrue(facetoface_can_user_signup($session1, $user1->id, $now));

        // Session in future, there no free space, but overbooking is alowed, and registration time frame in.
        $facetoface2 = $this->facetoface_generator->create_instance(array('course' => $course->id));
        $user21 = $this->getDataGenerator()->create_user();
        $user22 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user21->id, $course->id);
        $this->getDataGenerator()->enrol_user($user22->id, $course->id);
        $session2id = $this->facetoface_generator->add_session(array(
            'facetoface' => $facetoface2->id,
            'sessiondates' => $sessiondates1,
            'capacity' => 1,
            'allowoverbook' => true,
        ));
        $session2 = facetoface_get_session($session2id);
        facetoface_user_signup($session2, $facetoface2, $course, '', MDL_F2F_INVITE, MDL_F2F_STATUS_REQUESTED, $user21->id);
        $this->assertDebuggingCalled(get_string('error:nomanagersemailset', 'facetoface'));
        $this->assertTrue(facetoface_can_user_signup($session2, $user22->id, $now));

        // Session in the past.
        $facetoface3 = $this->facetoface_generator->create_instance(array('course' => $course->id));
        $user3 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);
        $sessiondates3 = [$this->prepare_date($now - (DAYSECS * 4), $now - (DAYSECS * 3), $room->id)];
        $session3id = $this->facetoface_generator->add_session(array(
            'facetoface' => $facetoface3->id,
            'sessiondates' => $sessiondates3,
        ));
        $session3 = facetoface_get_session($session3id);
        $this->assertFalse(facetoface_can_user_signup($session3, $user3->id, $now));

        // Session in the middle.
        $facetoface4 = $this->facetoface_generator->create_instance(array('course' => $course->id));
        $user4 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user4->id, $course->id);
        $sessiondates4 = [$this->prepare_date($now - (DAYSECS * 1), $now + (DAYSECS * 1), $room->id)];
        $session4id = $this->facetoface_generator->add_session(array(
            'facetoface' => $facetoface4->id,
            'sessiondates' => $sessiondates4,
        ));
        $session4 = facetoface_get_session($session4id);
        $this->assertFalse(facetoface_can_user_signup($session4, $user4->id, $now));

        // Registration has not started yet.
        $facetoface5 = $this->facetoface_generator->create_instance(array('course' => $course->id));
        $user5 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user5->id, $course->id);
        $session5id = $this->facetoface_generator->add_session(array(
            'facetoface' => $facetoface5->id,
            'sessiondates' => $sessiondates1,
            'registrationtimestart' => $now + (DAYSECS * 1),
            'registrationtimefinish' => $now + (DAYSECS * 2),
        ));
        $session5 = facetoface_get_session($session5id);
        $this->assertFalse(facetoface_can_user_signup($session5, $user5->id, $now));

        // Registration is over.
        $facetoface6 = $this->facetoface_generator->create_instance(array('course' => $course->id));
        $user6 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user6->id, $course->id);
        $session6id = $this->facetoface_generator->add_session(array(
            'facetoface' => $facetoface6->id,
            'sessiondates' => $sessiondates1,
            'registrationtimestart' => $now - (DAYSECS * 2),
            'registrationtimefinish' => $now - (DAYSECS * 1),
        ));
        $session6 = facetoface_get_session($session6id);
        $this->assertFalse(facetoface_can_user_signup($session6, $user6->id, $now));

        // No free space.
        $facetoface7 = $this->facetoface_generator->create_instance(array('course' => $course->id));
        $user71 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user71->id, $course->id);
        $user72 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user72->id, $course->id);
        $session7id = $this->facetoface_generator->add_session(array(
            'facetoface' => $facetoface7->id,
            'sessiondates' => $sessiondates1,
            'capacity' => 1,
        ));
        $session7 = facetoface_get_session($session7id);
        facetoface_user_signup($session7, $facetoface7, $course, '', MDL_F2F_INVITE, MDL_F2F_STATUS_BOOKED, $user71->id);
        $this->assertDebuggingCalled();
        $this->assertFalse(facetoface_can_user_signup($session7, $user72->id, $now));

    }

    /**
     * facetoface_has_unarchived_signups:
     *     returns true if there is another sign up
     *     returns false if there is:
     *         a sign up that was part of a certification that expired
     *         no signups
     */
    public function test_facetoface_has_unarchived_signups_basecase() {
        global $DB, $PAGE;
        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $room = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 1', 'allowconflicts' => 1));
        $facetoface = $this->facetoface_generator->create_instance(array('course' => $course->id));
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $sessiondates = [$this->prepare_date($now + (DAYSECS * 3), $now + (DAYSECS * 4), $room->id)];
        $sessionid = $this->facetoface_generator->add_session(array(
            'facetoface' => $facetoface->id,
            'sessiondates' => $sessiondates,
            'registrationtimestart' => $now - (DAYSECS * 1),
            'registrationtimefinish' => $now + (DAYSECS * 1),
        ));
        $session2id = $this->facetoface_generator->add_session(array(
            'facetoface' => $facetoface->id,
            'sessiondates' => $sessiondates,
            'registrationtimestart' => $now - (DAYSECS * 1),
            'registrationtimefinish' => $now + (DAYSECS * 1),
        ));
        $session = facetoface_get_session($sessionid);
        $session2 = facetoface_get_session($session2id);

        // There are no signups.
        $this->assertFalse(facetoface_has_unarchived_signups($facetoface->id, $user->id));

        facetoface_user_signup($session, $facetoface, $course, '', MDL_F2F_INVITE, MDL_F2F_STATUS_BOOKED, $user->id);
        $this->assertDebuggingCalled(); // To catch to error thown from the page url not being set.

        // There is one unarchived signup.
        $this->assertTrue(facetoface_has_unarchived_signups($facetoface->id, $user->id));
        $this->assertTrue(facetoface_has_unarchived_signups($facetoface->id, $user->id));

        $dataobj = $DB->get_record('facetoface_signups', ['sessionid' => $session->id, 'userid' => $user->id], '*', MUST_EXIST);
        $dataobj->archived = 1;
        $DB->update_record('facetoface_signups', $dataobj);

        // There is one archived signup.
        $this->assertFalse(facetoface_has_unarchived_signups($facetoface->id, $user->id));
    }

    public function test_facetoface_session_dates_check() {
        // Original dates.
        $orignaldates = array(
            (object) array(
                'timestart' => 1512610200,
                'timefinish' => 1512610200,
                'sessiontimezone' => 'Europe/London',
                'roomid' => 5,
            ),
            (object) array(
                'timestart' => 1512123200,
                'timefinish' => 1512124200,
                'sessiontimezone' => 'Europe/London',
                'roomid' => 7,
            ),
            (object) array(
                'timestart' => 1513123200,
                'timefinish' => 1513124200,
                'sessiontimezone' => 'Europe/London',
                'roomid' => 9,
            ),
        );

        array_map(function($test) {
            // Setting assertion method.
            $method = 'assert' . ($test->returns ? 'True' : 'False');

            // Running our defined tests and displaying appropriate failure message in case if it failed.
            $this->$method(facetoface_session_dates_check($test->olddates, $test->newdates), $test->message);
        }, array(
            // No changes.
            (object) array(
                'olddates' => $orignaldates,
                'newdates' => array(
                    (object) array(
                        'timestart' => 1512610200,
                        'timefinish' => 1512610200,
                        'sessiontimezone' => 'Europe/London',
                        'roomid' => 5,
                    ),
                    (object) array(
                        'timestart' => 1512123200,
                        'timefinish' => 1512124200,
                        'sessiontimezone' => 'Europe/London',
                        'roomid' => 7,
                    ),
                    (object) array(
                        'timestart' => 1513123200,
                        'timefinish' => 1513124200,
                        'sessiontimezone' => 'Europe/London',
                        'roomid' => 9,
                    ),
                ),
                'returns' => false,
                'message' => 'Dates not changed, but the check indicates that they have.',
            ),

            // New date added.
            (object) array(
                'olddates' => $orignaldates,
                'newdates' => array(
                    (object) array(
                        'timestart' => 1512610200,
                        'timefinish' => 1512610200,
                        'sessiontimezone' => 'Europe/London',
                        'roomid' => 5,
                    ),
                    (object) array(
                        'timestart' => 1512123200,
                        'timefinish' => 1512124200,
                        'sessiontimezone' => 'Europe/London',
                        'roomid' => 7,
                    ),
                    (object) array(
                        'timestart' => 1513123200,
                        'timefinish' => 1513124200,
                        'sessiontimezone' => 'Europe/London',
                        'roomid' => 9,
                    ),
                    (object) array(
                        'timestart' => 1512153200,
                        'timefinish' => 1512164200,
                        'sessiontimezone' => 'Europe/London',
                        'roomid' => 5,
                    ),
                ),
                'returns' => true,
                'message' => 'Date added, but the check indicates no changes.',
            ),

            // Date removed.
            (object) array(
                'olddates' => $orignaldates,
                'newdates' => array(
                    (object) array(
                        'timestart' => 1512610200,
                        'timefinish' => 1512610200,
                        'sessiontimezone' => 'Europe/London',
                        'roomid' => 5,
                    ),
                    (object) array(
                        'timestart' => 1513123200,
                        'timefinish' => 1513124200,
                        'sessiontimezone' => 'Europe/London',
                        'roomid' => 9,
                    ),
                ),
                'returns' => true,
                'message' => 'Date removed, but the check indicates no changes.',
            ),

            // Same dates in a different order.
            (object) array(
                'olddates' => $orignaldates,
                'newdates' => array(
                    (object) array(
                        'timestart' => 1512123200,
                        'timefinish' => 1512124200,
                        'sessiontimezone' => 'Europe/London',
                        'roomid' => 7,
                    ),
                    (object) array(
                        'timestart' => 1513123200,
                        'timefinish' => 1513124200,
                        'sessiontimezone' => 'Europe/London',
                        'roomid' => 9,
                    ),
                    (object) array(
                        'timestart' => 1512610200,
                        'timefinish' => 1512610200,
                        'sessiontimezone' => 'Europe/London',
                        'roomid' => 5,
                    ),
                ),
                'returns' => false,
                'message' => 'Dates not changed, but the check indicates that they have.',
            ),

            // Timezone changed.
            (object) array(
                'olddates' => $orignaldates,
                'newdates' => array(
                    (object) array(
                        'timestart' => 1512610200,
                        'timefinish' => 1512610200,
                        'sessiontimezone' => 'Europe/London',
                        'roomid' => 5,
                    ),
                    (object) array(
                        'timestart' => 1512123200,
                        'timefinish' => 1512124200,
                        'sessiontimezone' => 'Europe/Bratislava',
                        'roomid' => 7,
                    ),
                    (object) array(
                        'timestart' => 1513123200,
                        'timefinish' => 1513124200,
                        'sessiontimezone' => 'Europe/London',
                        'roomid' => 9,
                    ),
                ),
                'returns' => true,
                'message' => 'Session timezone changed, but the check indicates no changes.',
            ),

            // Start time of the session has changed.
            (object) array(
                'olddates' => $orignaldates,
                'newdates' => array(
                    (object) array(
                        'timestart' => 1512610100,
                        'timefinish' => 1512610200,
                        'sessiontimezone' => 'Europe/London',
                        'roomid' => 5,
                    ),
                    (object) array(
                        'timestart' => 1512123200,
                        'timefinish' => 1512124200,
                        'sessiontimezone' => 'Europe/London',
                        'roomid' => 7,
                    ),
                    (object) array(
                        'timestart' => 1513123200,
                        'timefinish' => 1513124200,
                        'sessiontimezone' => 'Europe/London',
                        'roomid' => 9,
                    ),
                ),
                'returns' => true,
                'message' => 'Session start time changed, but the check indicates no changes.',
            ),

            // End time of the session has changed.
            (object) array(
                'olddates' => $orignaldates,
                'newdates' => array(
                    (object) array(
                        'timestart' => 1512610100,
                        'timefinish' => 1512610200,
                        'sessiontimezone' => 'Europe/London',
                        'roomid' => 5,
                    ),
                    (object) array(
                        'timestart' => 1512123200,
                        'timefinish' => 1512124200,
                        'sessiontimezone' => 'Europe/London',
                        'roomid' => 7,
                    ),
                    (object) array(
                        'timestart' => 1513123200,
                        'timefinish' => 1513124900,
                        'sessiontimezone' => 'Europe/London',
                        'roomid' => 9,
                    ),
                ),
                'returns' => true,
                'message' => 'Session end time changed, but the check indicates no changes.',
            ),

            // The room for the session has changed.
            (object) array(
                'olddates' => $orignaldates,
                'newdates' => array(
                    (object) array(
                        'timestart' => 1512610100,
                        'timefinish' => 1512610200,
                        'sessiontimezone' => 'Europe/London',
                        'roomid' => 5,
                    ),
                    (object) array(
                        'timestart' => 1512123200,
                        'timefinish' => 1512124200,
                        'sessiontimezone' => 'Europe/London',
                        'roomid' => 9,
                    ),
                    (object) array(
                        'timestart' => 1513123200,
                        'timefinish' => 1513124900,
                        'sessiontimezone' => 'Europe/London',
                        'roomid' => 7,
                    ),
                ),
                'returns' => true,
                'message' => 'Room has changed, but the check indicates no changes.',
            ),

            // Works correctly if no dates supplied.
            (object) array(
                'olddates' => array(),
                'newdates' => array(),
                'returns' => false,
                'message' => 'Dates not changed, but the check indicates that they have.',
            ),
        ));

        $this->resetAfterTest(true);
    }

    public function test_facetoface_get_sessions_within() {
        /*
         * What are we testing:
         *  1. Returns false when no dates supplied.
         *  2. Gets session(s) within given dates correctly.
         *  3. Ignores session(s) if they had been cancelled.
         *  4. Works correctly when user id is supplied.
         *  5. Works correctly with custom sql supplied.
         */

        $this->init_sample_data();

        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $user = $this->getDataGenerator()->create_user();

        // A function to quickly create a new session.
        $whip_up_session = function($start = null, $duration = null, $users = array()) use ($facetofacegenerator) {
            // Normalizing input.
            $start = $start ?: time() + (YEARSECS * 69);
            $duration = $duration ?: 3600;
            $finish = $start + $duration;

            $facetoface = $facetofacegenerator->create_instance(array('course' => $this->course1->id));

            $sessiondate = (object) array(
                'timestart' => $start,
                'timefinish' => $finish,
                'sessiontimezone' => 'Pacific/Auckland',
            );
            $sessiondata = array(
                'facetoface' => $facetoface->id,
                'capacity' => 10,
                'allowoverbook' => 0,
                'sessiondates' => array($sessiondate),
            );

            $session = facetoface_get_session($facetofacegenerator->add_session($sessiondata));

            // We have some users to sign up for the session.
            if ($users) {
                array_map(function($user) use ($facetoface, $session) {
                    facetoface_user_signup(
                        $session,
                        $facetoface,
                        $this->course1,
                        '',
                        MDL_F2F_INVITE,
                        MDL_F2F_STATUS_BOOKED,
                        $user->id
                    );
                }, $users);
            }

            return $session;
        };

        $dates = array();

        // 1. Returns false when no dates supplied.
        $this->assertEmpty(facetoface_get_sessions_within(array()));

        // 2. Gets session(s) within given dates correctly.
        $dates[] = (object) array(
            'timestart' => time() + (YEARSECS * 69),
            'timefinish' => time() + (YEARSECS * 69 + 3600),
        );
        $date = end($dates);
        $session = $whip_up_session($date->timestart, 3600);
        $result = facetoface_get_sessions_within(array($date));
        $this->assertAttributeEquals($session->sessiondates[0]->id, 'id', $result);

        // Sanity check - nothing gets returned if there is no sessions for given dates.
        $this->assertEmpty(facetoface_get_sessions_within(array(
                (object) array(
                    'timestart' => $date->timestart + YEARSECS,
                    'timefinish' => $date->timefinish + YEARSECS,
                ))
        ));

        // 3. Ignores cancelled session.
        $dates[] = (object) array(
            'timestart' => time() + (YEARSECS * 71),
            'timefinish' => time() + (YEARSECS * 71 + 3600),
        );
        $date = end($dates);
        $session = $whip_up_session($date->timestart, 3600);
        facetoface_cancel_session($session, false);
        $result = facetoface_get_sessions_within(array($date));
        $this->assertEmpty($result);

        // 4. Works correctly when user id is supplied.
        $dates[] = (object) array(
            'timestart' => time() + (YEARSECS * 73),
            'timefinish' => time() + (YEARSECS * 73 + 3600),
        );
        $date = end($dates);
        $nodate = reset($dates);
        $session = $whip_up_session($date->timestart, 3600, array($user));
        $firstsid = $session->id;

        $emptyresult =  facetoface_get_sessions_within(array($nodate), $user->id);
        $result = facetoface_get_sessions_within(array($date), $user->id);

        $this->assertAttributeEquals($session->sessiondates[0]->id, 'id', $result);
        $this->assertEmpty($emptyresult);

        // 5. Works correctly when custom SQL is supplied.
        // Reusing previously created session to change details.
        $session = $whip_up_session($date->timestart, 3600);

        $firstresult = facetoface_get_sessions_within(array($date), false, ' and s.id = ?', array($firstsid));
        $result = facetoface_get_sessions_within(array($date), false, ' and s.id = ?', array($session->id));

        $this->assertAttributeEquals($firstsid, 'sessionid', $firstresult);
        $this->assertAttributeEquals($session->id, 'sessionid', $result);

        $this->resetAfterTest();
    }

    public function test_save_session_dates() {
        global $DB;

        // First we need a session.
        $now = time();
        $room = $this->facetoface_generator->add_site_wide_room([
            'name' => 'Storage room',
            'allowconflicts' => 1
        ]);

        $f2f = $this->facetoface_generator->create_instance([
            'course' => $this->getDataGenerator()->create_course()->id
        ]);

        $session = facetoface_get_session($this->facetoface_generator->add_session([
            'facetoface' => $f2f->id,
            'sessiondates' => [
                (object) [
                    'timestart' => $now,
                    'timefinish' => $now + WEEKSECS,
                    'sessiontimezone' => 'Europe/London',
                    'roomid' => $room->id,
                ],
                (object) [
                    'timestart' => $now + DAYSECS * 10,
                    'timefinish' => $now + DAYSECS * 10 + HOURSECS,
                    'sessiontimezone' => 'Europe/London',
                    'roomid' => $room->id,
                ],
                (object) [
                    'timestart' => $now + YEARSECS,
                    'timefinish' => $now + YEARSECS + HOURSECS * 2,
                    'sessiontimezone' => 'Europe/London',
                    'roomid' => $room->id,
                ],
            ],
        ]));

        // Gimme some dates, we pretend that we get those from a user supplied form.
        $dates = [
            (object) [
                'timestart' => $now,
                'timefinish' => $now + WEEKSECS,
                'sessiontimezone' => '1Europe/London',
                'roomid' => $room->id,
                'id' => $session->sessiondates[0]->id
            ],
            (object) [
                'timestart' => $now + WEEKSECS * 3,
                'timefinish' => $now + WEEKSECS * 3 + HOURSECS,
                'sessiontimezone' => '2Europe/London',
                'roomid' => $room->id,
            ],
            (object) [
                'timestart' => $now + YEARSECS,
                'timefinish' => $now + YEARSECS + HOURSECS * 2,
                'sessiontimezone' => '3Europe/London',
                'roomid' => $room->id,
                'id' => $session->sessiondates[2]->id
            ],
            // User may try to sneak in the date from another event.
            (object) [
                'timestart' => $now + YEARSECS + 696,
                'timefinish' => $now + YEARSECS + HOURSECS * 2,
                'sessiontimezone' => '4Europe/London',
                'roomid' => $room->id,
                'id' => 123456
            ],
        ];

        facetoface_save_dates($session, $dates);

        $updated = $DB->get_records('facetoface_sessions_dates', [
            'sessionid' => $session->id,
        ]);

        // Removing our sneaky date from the dates array to make sure that it has been filtered out while saving.
        array_pop($dates);

        // Compare dates all the updated should match the dates, except the ids, however if it had the id
        // it should remain the same.
        $updated = array_filter($updated, function($date) use (&$dates) {
            foreach ($dates as $key => $item) {
                if ($item->sessiontimezone == $date->sessiontimezone &&
                    $item->timestart == $date->timestart &&
                    $item->timefinish == $date->timefinish &&
                    $item->roomid == $date->roomid) {
                    unset($dates[$key]);
                    if (isset($item->id)) {
                        return $item->id != $date->id;
                    } else {
                        return false;
                    }
                }
            }

            return true;
        });

        // Assert all the dates match and the dates array is empty.
        $this->assertEmpty($updated);

        $this->resetAfterTest();
    }

    public function test_sync_assets() {
        global $DB;
        $f2f = $this->facetoface_generator->create_instance([
            'course' => $this->getDataGenerator()->create_course()->id
        ]);

        $now = time();

        $session = facetoface_get_session($this->facetoface_generator->add_session([
            'facetoface' => $f2f->id,
            'sessiondates' => [
                (object) [
                    'timestart' => $now,
                    'timefinish' => $now + WEEKSECS,
                    'sessiontimezone' => 'Europe/London',
                    'roomid' => 0,
                    'assetids' => [1,2,3,4,5,6,7,8,9,10]
                ],
            ],
        ]));

        $did = $session->sessiondates[0]->id;

        $testset = [3,7,11,15,19];

        // Assets synced successfully
        $this->assertTrue(facetoface_sync_assets($did, $testset), 'Assets sync failed');

        $assets = $DB->get_fieldset_select('facetoface_asset_dates',
            'assetid',
            'sessionsdateid = :dateid',
            ['dateid' => $did]);

        sort($assets, SORT_NUMERIC);
        $this->assertEquals($testset, $assets, 'Asset sets do not match');

    }
}
