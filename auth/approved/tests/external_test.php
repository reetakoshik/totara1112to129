<?php

class auth_approved_external_testcase extends advanced_testcase {

    public function test_job_assignment_by_user_names_parameters() {

        $params = \auth_approved_external::job_assignment_by_user_names_parameters();
        $this->assertInstanceOf('external_function_parameters', $params);
        $this->assertCount(4, $params->keys);
        $this->assertArrayHasKey('searchquery', $params->keys);
        $this->assertArrayHasKey('page', $params->keys);
        $this->assertArrayHasKey('perpage', $params->keys);
        $this->assertArrayHasKey('termaggregation', $params->keys);

    }

    public function job_assignment_by_user_names_returns() {

        $returns = \auth_approved_external::job_assignment_by_user_names_parameters();
        $this->assertInstanceOf('external_single_structure', $returns);
        $this->assertCount(3, $returns->keys);
        $this->assertArrayHasKey('total', $returns->keys);
        $this->assertArrayHasKey('managers', $returns->keys);
        $this->assertArrayHasKey('warnings', $returns->keys);

    }

    public function test_job_assignment_by_user_names() {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        /** @var totara_hierarchy_generator $hierarchygen */
        $hierarchygen = $generator->get_plugin_generator('totara_hierarchy');

        $positionframeworks = [
            'technical' => $hierarchygen->create_pos_frame(['fullname' => 'Technical']),
            'triage' => $hierarchygen->create_pos_frame(['fullname' => 'Triage']),
            'management' => $hierarchygen->create_pos_frame(['fullname' => 'Managers']),
        ];
        $positions = [
            'management' => $hierarchygen->create_pos(['frameworkid' => $positionframeworks['management']->id, 'fullname' => 'Management']),
            'developers' => $hierarchygen->create_pos(['frameworkid' => $positionframeworks['technical']->id, 'fullname' => 'Developer']),
            'hubs' => $hierarchygen->create_pos(['frameworkid' => $positionframeworks['technical']->id, 'fullname' => 'Hubs']),
            'designers' => $hierarchygen->create_pos(['frameworkid' => $positionframeworks['technical']->id, 'fullname' => 'Designer']),
            'triage' => $hierarchygen->create_pos(['frameworkid' => $positionframeworks['triage']->id, 'fullname' => 'Support']),
            'testers' => $hierarchygen->create_pos(['frameworkid' => $positionframeworks['triage']->id, 'fullname' => 'Tester']),
        ];

        $organisationframeworks = [
            'nz' => $hierarchygen->create_org_frame(['fullname' => 'Totara Learning Solutions NZ']),
            'other' => $hierarchygen->create_org_frame(['fullname' => 'Totara Learning Solutions International']),
        ];
        $organisations = [
            'nz' => $hierarchygen->create_org(['frameworkid' => $organisationframeworks['nz']->id, 'fullname' => 'NZ Office']),
            'uk' => $hierarchygen->create_org(['frameworkid' => $organisationframeworks['other']->id, 'fullname' => 'UK Office']),
            'us' => $hierarchygen->create_org(['frameworkid' => $organisationframeworks['other']->id, 'fullname' => 'US Office']),
        ];

        $usersdata = [
            ['alastair', 'Alastair', 'Munro', [
                ['hubs', 'nz', 'Segundo hub member', null],
                ['developers', 'uk', 'Totara Learn Developer', 'samh-developers']
            ]],
            ['andrewm', 'Andrew', 'Mcghie', [
                ['developers', 'uk', 'Totara Learn Developer', 'samh-developers'],
                ['hubs', 'nz', 'Primero hub member', 'nathanl-hubs']
            ]],
            ['bobm', 'Bob', 'Medcalf', [
                ['designers', 'nz', 'Design Lead', 'simonc-management']
            ]],
            ['brendanc', 'Brendan', 'Cox', [
                ['developers', 'uk', 'Totara Learn Developer', 'samh-developers'],
                ['hubs', 'nz', 'Primero hub member', 'nathanl-hubs']
            ]],
            ['brianb', 'Brian', 'Barnes', [
                ['developers', 'nz', 'Totara Learn Developer', 'samh-developers'],
                ['designers', 'nz', 'Designer', 'bobm-designers']
            ]],
            ['carla', 'Carl', 'Anderson', [
                ['developers', 'nz', 'Totara Social Developer', 'yuliyab-developers']
            ]],
            ['craige', 'Craig', 'Eves', [
                ['triage', 'nz', 'Client support', 'tomi-triage']
            ]],
            ['davew', 'Dave', 'Wallace', [
                ['developers', 'nz', 'Totara Social Developer', 'yuliyab-developers'],
                ['designers', 'nz', 'Designer', 'bobm-designers']
            ]],
            ['djcurry', 'David', 'Curry', [
                ['developers', 'uk', 'Totara Learn Developer', 'samh-developers'],
                ['hubs', 'nz', 'Primero hub member', 'nathanl-hubs']
            ]],
            ['georgea', 'George', 'Angus', [
                ['triage', 'nz', 'Client support', 'tomi-triage']
            ]],
            ['iainn', 'Iain', 'Napier', [
                ['management', 'uk', 'Client support manager', 'richardw-management']
            ]],
            ['jobyh', 'Joby', 'Harding', [
                ['developers', 'nz', 'Totara Learn Developer', 'samh-developers'],
                ['designers', 'nz', 'Designer', 'bobm-designers']
            ]],
            ['mariam', 'Maria', 'Torres', [
                ['developers', 'nz', 'Totara Learn Developer', 'samh-developers'],
                ['hubs', 'nz', 'Segundo hub member', 'valeriik-hubs']
            ]],
            ['moisesb', 'Moises', 'Burgos', [
                ['developers', 'nz', 'Totara Social Developer', 'yuliyab-developers']
            ]],
            ['muralin', 'Murali', 'Nair', [
                ['developers', 'nz', 'Totara Learn Developer', 'samh-developers'],
                ['hubs', 'nz', 'Segundo hub member', 'valeriik-hubs']
            ]],
            ['nathanl', 'Nathan', 'Lewis', [
                ['hubs', 'nz', 'Primero hub lead', null],
                ['developers', 'nz', 'Totara Learn Developer', 'samh-developers']
            ]],
            ['olegd', 'Oleg', 'Demeshev', [
                ['developers', 'nz', 'Totara Learn Developer', 'samh-developers'],
                ['hubs', 'nz', 'Segundo hub member', 'valeriik-hubs']
            ]],
            ['petrs', 'Petr', 'Skoda', [
                ['developers', 'nz', 'Totara Learn Developer', 'samh-developers']
            ]],
            ['rianar', 'Riana', 'Rossouw', [
                ['developers', 'nz', 'Totara Learn Developer', 'samh-developers'],
                ['hubs', 'nz', 'Segundo hub member', 'valeriik-hubs']
            ]],
            ['richardw', 'Richard', 'Wyles', [
                ['management', 'nz', 'CEO', null]
            ]],
            ['robt', 'Rob', 'Tyler', [
                ['hubs', 'nz', 'Segundo hub member', null],
                ['developers', 'uk', 'Totara Learn Developer', 'samh-developers']
            ]],
            ['samanthaj', 'Samantha', 'Jayasinghe', [
                ['developers', 'nz', 'Totara Social Developer', 'yuliyab-developers']
            ]],
            ['samh', 'Sam', 'Hemelryk', [
                ['developers', 'nz', 'Totara Learn Lead Developer', 'simonc-management']
            ]],
            ['simonc', 'Simon', 'Coggins', [
                ['management', 'nz', 'CTO', 'richardw-management']
            ]],
            ['simonp', 'Simon', 'Player', [
                ['hubs', 'nz', 'Segundo hub member', null],
                ['developers', 'uk', 'Totara Learn Developer', 'samh-developers']
            ]],
            ['tomi', 'Tom', 'Ireland', [
                ['triage', 'uk', 'Client support lead', 'iainn-management']
            ]],
            ['thomasw', 'Thomas', 'Wood', [
                ['triage', 'uk', 'Client support', 'tomi-triage']
            ]],
            ['valeriik', 'Valerii', 'Kuznetsov', [
                ['hubs', 'nz', 'Segundo hub lead', null],
                ['developers', 'nz', 'Totara Learn Developer', 'samh-developers']
            ]],
            ['yuliyab', 'Yuliya', 'Bozhko', [
                ['developers', 'us', 'Totara Social Lead Developer', 'simonc-management']
            ]]
        ];

        $users = [];
        $managerassignments = [];
        $jobs = [];
        foreach ($usersdata as $userdata) {
            list($username, $firstname, $lastname, $jobsdata) = $userdata;
            $user = $generator->create_user([
                'idnumber' => $username,
                'username' => $username,
                'firstname' => $firstname,
                'lastname' => $lastname,
            ]);
            $user->jobs = [];
            $users[$username] = $user;

            foreach ($jobsdata as $job) {
                list($position, $organisation, $title, $manager) = $job;
                $idnumber = $username . '-' . $position;
                $ja = \totara_job\job_assignment::create([
                    'userid' => $user->id,
                    'idnumber' => $idnumber,
                    'fullname' => $title,
                    'shortname' => $title,
                    'positionid' => $positions[$position]->id,
                    'organisationid' => $organisations[$organisation]->id,
                ]);
                if ($manager !== null) {
                    if (!isset($managerassignments[$manager])) {
                        $managerassignments[$manager] = [$ja];
                    } else {
                        $managerassignments[$manager][] = $ja;
                    }
                }
                $jobs[$idnumber] = $ja;
            }
        }

        foreach ($managerassignments as $managerja => $assignments) {
            $managerja = $jobs[$managerja];
            /** @var \totara_job\job_assignment $ja */
            foreach ($assignments as $ja) {
                $ja->update(['managerjaid' => $managerja->id]);
            }
        };

        // OK we have a big structure now! Lets validate it is as we expect it to be.
        $this->assertCount($DB->count_records('user') - 2, $users);
        $this->assertCount($DB->count_records('pos'), $positions);
        $this->assertCount($DB->count_records('org'), $organisations);
        $this->assertCount($DB->count_records('job_assignment'), $jobs);
        $this->assertCount(29, $users);
        $this->assertCount(44, $jobs);
        $this->assertSame(6, $DB->count_records_sql('SELECT count(*) FROM {job_assignment} WHERE managerjaid IS NULL'));
        $this->assertSame(2, $DB->count_records('job_assignment', ['managerjaid' => $jobs['richardw-management']->id]));
        $this->assertSame(3, $DB->count_records('job_assignment', ['managerjaid' => $jobs['simonc-management']->id]));
        $this->assertSame(4, $DB->count_records('job_assignment', ['managerjaid' => $jobs['yuliyab-developers']->id]));
        $this->assertSame(15, $DB->count_records('job_assignment', ['managerjaid' => $jobs['samh-developers']->id]));
        $this->assertSame(4, $DB->count_records('job_assignment', ['managerjaid' => $jobs['valeriik-hubs']->id]));
        $this->assertSame(3, $DB->count_records('job_assignment', ['managerjaid' => $jobs['nathanl-hubs']->id]));
        $this->assertSame(3, $DB->count_records('job_assignment', ['managerjaid' => $jobs['bobm-designers']->id]));
        $this->assertSame(21, $DB->count_records('job_assignment', ['positionid' => $positions['developers']->id]));
        $this->assertSame(4, $DB->count_records('job_assignment', ['positionid' => $positions['designers']->id]));
        $this->assertSame(12, $DB->count_records('job_assignment', ['positionid' => $positions['hubs']->id]));

        // Now to actually start testing.

        // Test a simple multiple record match.
        $result = \auth_approved_external::job_assignment_by_user_names('Sam');
        $this->assert_search_result_valid($result, 2);
        $this->assert_expected_user(array_shift($result['managers']), 'Sam Hemelryk - Totara Learn Lead Developer', $users['samh'], $jobs['samh-developers']);
        $this->assert_expected_user(array_shift($result['managers']), 'Samantha Jayasinghe - Totara Social Developer', $users['samanthaj'], $jobs['samanthaj-developers']);

        // Test case sensitivity.
        $result = \auth_approved_external::job_assignment_by_user_names('sAM');
        $this->assert_search_result_valid($result, 2);
        $this->assert_expected_user(array_shift($result['managers']), 'Sam Hemelryk - Totara Learn Lead Developer', $users['samh'], $jobs['samh-developers']);
        $this->assert_expected_user(array_shift($result['managers']), 'Samantha Jayasinghe - Totara Social Developer', $users['samanthaj'], $jobs['samanthaj-developers']);

        // Test a partial, but specific match, starting with.
        $result = \auth_approved_external::job_assignment_by_user_names('sama');
        $this->assert_search_result_valid($result, 1);
        $this->assert_expected_user(array_shift($result['managers']), 'Samantha Jayasinghe - Totara Social Developer', $users['samanthaj'], $jobs['samanthaj-developers']);

        // Test a partial, but specific match, ending with.
        $result = \auth_approved_external::job_assignment_by_user_names('ntha');
        $this->assert_search_result_valid($result, 1);
        $this->assert_expected_user(array_shift($result['managers']), 'Samantha Jayasinghe - Totara Social Developer', $users['samanthaj'], $jobs['samanthaj-developers']);

        // Test a partial, but specific match, contains.
        $result = \auth_approved_external::job_assignment_by_user_names('sing');
        $this->assert_search_result_valid($result, 1);
        $this->assert_expected_user(array_shift($result['managers']), 'Samantha Jayasinghe - Totara Social Developer', $users['samanthaj'], $jobs['samanthaj-developers']);

        // Test exact matching on search when using AND aggregation.
        $result = \auth_approved_external::job_assignment_by_user_names('David Curry Primero hub member', 0, 0, 'AND');
        $this->assert_search_result_valid($result, 1);
        $this->assert_expected_user(array_shift($result['managers']), 'David Curry - Primero hub member', $users['djcurry'], $jobs['djcurry-hubs']);

        // Test Dave's other job.
        $result = \auth_approved_external::job_assignment_by_user_names('David Curry Totara Learn Developer', 0, 0, 'AND');
        $this->assert_search_result_valid($result, 1);
        $this->assert_expected_user(array_shift($result['managers']), 'David Curry - Totara Learn Developer', $users['djcurry'], $jobs['djcurry-developers']);

        // Test exact matching with OR aggregation.
        $result = \auth_approved_external::job_assignment_by_user_names('david curry');
        $this->assert_search_result_valid($result, 2);
        $this->assert_expected_user(array_shift($result['managers']), 'David Curry - Primero hub member', $users['djcurry'], $jobs['djcurry-hubs']);
        $this->assert_expected_user(array_shift($result['managers']), 'David Curry - Totara Learn Developer', $users['djcurry'], $jobs['djcurry-developers']);

        // Test matching on first name.
        $result = \auth_approved_external::job_assignment_by_user_names('david');
        $this->assert_search_result_valid($result, 2);
        $this->assert_expected_user(array_shift($result['managers']), 'David Curry - Primero hub member', $users['djcurry'], $jobs['djcurry-hubs']);
        $this->assert_expected_user(array_shift($result['managers']), 'David Curry - Totara Learn Developer', $users['djcurry'], $jobs['djcurry-developers']);

        // Test matching on last name.
        $result = \auth_approved_external::job_assignment_by_user_names('curry');
        $this->assert_search_result_valid($result, 2);
        $this->assert_expected_user(array_shift($result['managers']), 'David Curry - Primero hub member', $users['djcurry'], $jobs['djcurry-hubs']);
        $this->assert_expected_user(array_shift($result['managers']), 'David Curry - Totara Learn Developer', $users['djcurry'], $jobs['djcurry-developers']);

        // Test a multi-user + multi-job search, expected two users with two jobs each.
        $result = \auth_approved_external::job_assignment_by_user_names('dav');
        $this->assert_search_result_valid($result, 4);
        $this->assert_expected_user(array_shift($result['managers']), 'Dave Wallace - Designer', $users['davew'], $jobs['davew-designers']);
        $this->assert_expected_user(array_shift($result['managers']), 'Dave Wallace - Totara Social Developer', $users['davew'], $jobs['davew-developers']);
        $this->assert_expected_user(array_shift($result['managers']), 'David Curry - Primero hub member', $users['djcurry'], $jobs['djcurry-hubs']);
        $this->assert_expected_user(array_shift($result['managers']), 'David Curry - Totara Learn Developer', $users['djcurry'], $jobs['djcurry-developers']);

        // Now a multi-term + multi-user + multi-job search, expecting the same two users + two jobs each.
        $result = \auth_approved_external::job_assignment_by_user_names('david dave');
        $this->assert_search_result_valid($result, 4);
        $this->assert_expected_user(array_shift($result['managers']), 'Dave Wallace - Designer', $users['davew'], $jobs['davew-designers']);
        $this->assert_expected_user(array_shift($result['managers']), 'Dave Wallace - Totara Social Developer', $users['davew'], $jobs['davew-developers']);
        $this->assert_expected_user(array_shift($result['managers']), 'David Curry - Primero hub member', $users['djcurry'], $jobs['djcurry-hubs']);
        $this->assert_expected_user(array_shift($result['managers']), 'David Curry - Totara Learn Developer', $users['djcurry'], $jobs['djcurry-developers']);

        // Test the above again but this time with AND term aggregation, we don't expect either.
        $result = \auth_approved_external::job_assignment_by_user_names('david dave', 0, 0, 'AND');
        $this->assert_search_result_valid($result, 0);
        // And to be sure it works, lets search a multiple firstname, multiple lastname, but single result expected case.
        // "dav" will match David and Dave, "rr" will match Curry and Torres.
        $result = \auth_approved_external::job_assignment_by_user_names('dav rr', 0, 0, 'AND');
        $this->assert_search_result_valid($result, 2);
        $this->assert_expected_user(array_shift($result['managers']), 'David Curry - Primero hub member', $users['djcurry'], $jobs['djcurry-hubs']);
        $this->assert_expected_user(array_shift($result['managers']), 'David Curry - Totara Learn Developer', $users['djcurry'], $jobs['djcurry-developers']);

        // Test searching on Job, there are 5 social developers given we're counting Dave.
        $result = \auth_approved_external::job_assignment_by_user_names('social');
        $this->assert_search_result_valid($result, 5);
        $this->assert_expected_user(array_shift($result['managers']), 'Carl Anderson - Totara Social Developer', $users['carla'], $jobs['carla-developers']);
        $this->assert_expected_user(array_shift($result['managers']), 'Dave Wallace - Totara Social Developer', $users['davew'], $jobs['davew-developers']);
        $this->assert_expected_user(array_shift($result['managers']), 'Moises Burgos - Totara Social Developer', $users['moisesb'], $jobs['moisesb-developers']);
        $this->assert_expected_user(array_shift($result['managers']), 'Samantha Jayasinghe - Totara Social Developer', $users['samanthaj'], $jobs['samanthaj-developers']);
        $this->assert_expected_user(array_shift($result['managers']), 'Yuliya Bozhko - Totara Social Lead Developer', $users['yuliyab'], $jobs['yuliyab-developers']);

        // Test searching on Job, there are 4 designers.
        $result = \auth_approved_external::job_assignment_by_user_names('design');
        $this->assert_search_result_valid($result, 4);
        $this->assert_expected_user(array_shift($result['managers']), 'Bob Medcalf - Design Lead', $users['bobm'], $jobs['bobm-designers']);
        $this->assert_expected_user(array_shift($result['managers']), 'Brian Barnes - Designer', $users['brianb'], $jobs['brianb-designers']);
        $this->assert_expected_user(array_shift($result['managers']), 'Dave Wallace - Designer', $users['davew'], $jobs['davew-designers']);
        $this->assert_expected_user(array_shift($result['managers']), 'Joby Harding - Designer', $users['jobyh'], $jobs['jobyh-designers']);

        // Now test searching Lead, there should be six leads, design, social, and learn, two hubs, and a support.
        $result = \auth_approved_external::job_assignment_by_user_names('lead');
        $this->assert_search_result_valid($result, 6);
        $this->assert_expected_user(array_shift($result['managers']), 'Bob Medcalf - Design Lead', $users['bobm'], $jobs['bobm-designers']);
        $this->assert_expected_user(array_shift($result['managers']), 'Nathan Lewis - Primero hub lead', $users['nathanl'], $jobs['nathanl-hubs']);
        $this->assert_expected_user(array_shift($result['managers']), 'Sam Hemelryk - Totara Learn Lead Developer', $users['samh'], $jobs['samh-developers']);
        $this->assert_expected_user(array_shift($result['managers']), 'Tom Ireland - Client support lead', $users['tomi'], $jobs['tomi-triage']);
        $this->assert_expected_user(array_shift($result['managers']), 'Valerii Kuznetsov - Segundo hub lead', $users['valeriik'], $jobs['valeriik-hubs']);
        $this->assert_expected_user(array_shift($result['managers']), 'Yuliya Bozhko - Totara Social Lead Developer', $users['yuliyab'], $jobs['yuliyab-developers']);

        $result = \auth_approved_external::job_assignment_by_user_names('');
        $this->assert_search_result_valid($result, 44);

        $result = \auth_approved_external::job_assignment_by_user_names('', 0, 10);
        $this->assert_search_result_valid($result, 44, true);
        $this->assertCount(10, $result['managers']);

        $result = \auth_approved_external::job_assignment_by_user_names('', 2, 10);
        $this->assert_search_result_valid($result, 44, true);
        $this->assertCount(10, $result['managers']);

        $result = \auth_approved_external::job_assignment_by_user_names('', 4, 10);
        $this->assert_search_result_valid($result, 44, true);
        $this->assertCount(4, $result['managers']);

        $result = \auth_approved_external::job_assignment_by_user_names('', 5, 10);
        // This is invalid, we expect a 0 count and 0 results.
        $this->assert_search_result_valid($result, 44, true);
        $this->assertCount(0, $result['managers']);

        // Now to test limiting by organisations and hierarchies.
        // This is feature specific to the auth_approved plugin, and affects just its search.

        // First up before we turn on these restrictions lets get a base search verified.
        // Search for jobs with client support:
        //  - Org: we expect 1 in management, and 4 in the triage.
        //  - Pos: we expect 2 in NZ, 3 in the UK.
        $result = \auth_approved_external::job_assignment_by_user_names('client support');
        $this->assert_search_result_valid($result, 5);
        $this->assert_expected_user(array_shift($result['managers']), 'Craig Eves - Client support', $users['craige'], $jobs['craige-triage']);
        $this->assert_expected_user(array_shift($result['managers']), 'George Angus - Client support', $users['georgea'], $jobs['georgea-triage']);
        $this->assert_expected_user(array_shift($result['managers']), 'Iain Napier - Client support manager', $users['iainn'], $jobs['iainn-management']);
        $this->assert_expected_user(array_shift($result['managers']), 'Thomas Wood - Client support', $users['thomasw'], $jobs['thomasw-triage']);
        $this->assert_expected_user(array_shift($result['managers']), 'Tom Ireland - Client support lead', $users['tomi'], $jobs['tomi-triage']);

        // First up limit management.
        set_config('managerpositionframeworks', $positionframeworks['management']->id, 'auth_approved');
        $this->assertEquals(get_config('auth_approved', 'managerpositionframeworks'), $positionframeworks['management']->id);
        $result = \auth_approved_external::job_assignment_by_user_names('client support');
        $this->assert_search_result_valid($result, 1);
        $this->assert_expected_user(array_shift($result['managers']), 'Iain Napier - Client support manager', $users['iainn'], $jobs['iainn-management']);

        // Now limit to triage.
        set_config('managerpositionframeworks', $positionframeworks['triage']->id, 'auth_approved');
        $this->assertEquals(get_config('auth_approved', 'managerpositionframeworks'), $positionframeworks['triage']->id);
        $result = \auth_approved_external::job_assignment_by_user_names('client support');
        $this->assert_search_result_valid($result, 4);
        $this->assert_expected_user(array_shift($result['managers']), 'Craig Eves - Client support', $users['craige'], $jobs['craige-triage']);
        $this->assert_expected_user(array_shift($result['managers']), 'George Angus - Client support', $users['georgea'], $jobs['georgea-triage']);
        $this->assert_expected_user(array_shift($result['managers']), 'Thomas Wood - Client support', $users['thomasw'], $jobs['thomasw-triage']);
        $this->assert_expected_user(array_shift($result['managers']), 'Tom Ireland - Client support lead', $users['tomi'], $jobs['tomi-triage']);

        // Finally limit to both.
        // Now limit to triage.
        set_config('managerpositionframeworks', $positionframeworks['triage']->id . ',' .  $positionframeworks['management']->id, 'auth_approved');
        $result = \auth_approved_external::job_assignment_by_user_names('client support');
        $this->assert_search_result_valid($result, 5);
        $this->assert_expected_user(array_shift($result['managers']), 'Craig Eves - Client support', $users['craige'], $jobs['craige-triage']);
        $this->assert_expected_user(array_shift($result['managers']), 'George Angus - Client support', $users['georgea'], $jobs['georgea-triage']);
        $this->assert_expected_user(array_shift($result['managers']), 'Iain Napier - Client support manager', $users['iainn'], $jobs['iainn-management']);
        $this->assert_expected_user(array_shift($result['managers']), 'Thomas Wood - Client support', $users['thomasw'], $jobs['thomasw-triage']);
        $this->assert_expected_user(array_shift($result['managers']), 'Tom Ireland - Client support lead', $users['tomi'], $jobs['tomi-triage']);

        // A quick little test, by default this is -1, lets test it when it is empty.
        set_config('managerpositionframeworks', '', 'auth_approved');
        $this->assertEquals(get_config('auth_approved', 'managerpositionframeworks'), '');
        $result = \auth_approved_external::job_assignment_by_user_names('client support');
        $this->assert_search_result_valid($result, 5);
        $this->assert_expected_user(array_shift($result['managers']), 'Craig Eves - Client support', $users['craige'], $jobs['craige-triage']);
        $this->assert_expected_user(array_shift($result['managers']), 'George Angus - Client support', $users['georgea'], $jobs['georgea-triage']);
        $this->assert_expected_user(array_shift($result['managers']), 'Iain Napier - Client support manager', $users['iainn'], $jobs['iainn-management']);
        $this->assert_expected_user(array_shift($result['managers']), 'Thomas Wood - Client support', $users['thomasw'], $jobs['thomasw-triage']);
        $this->assert_expected_user(array_shift($result['managers']), 'Tom Ireland - Client support lead', $users['tomi'], $jobs['tomi-triage']);

        // Now repeat these but with organisations.
        set_config('managerorganisationframeworks', $organisationframeworks['nz']->id, 'auth_approved');
        $this->assertEquals(get_config('auth_approved', 'managerorganisationframeworks'), $organisationframeworks['nz']->id);
        $result = \auth_approved_external::job_assignment_by_user_names('client support');
        $this->assert_search_result_valid($result, 2);
        $this->assert_expected_user(array_shift($result['managers']), 'Craig Eves - Client support', $users['craige'], $jobs['craige-triage']);
        $this->assert_expected_user(array_shift($result['managers']), 'George Angus - Client support', $users['georgea'], $jobs['georgea-triage']);

        // And for the UK.
        set_config('managerorganisationframeworks', $organisationframeworks['other']->id, 'auth_approved');
        $this->assertEquals(get_config('auth_approved', 'managerorganisationframeworks'), $organisationframeworks['other']->id);
        $result = \auth_approved_external::job_assignment_by_user_names('client support');
        $this->assert_search_result_valid($result, 3);
        $this->assert_expected_user(array_shift($result['managers']), 'Iain Napier - Client support manager', $users['iainn'], $jobs['iainn-management']);
        $this->assert_expected_user(array_shift($result['managers']), 'Thomas Wood - Client support', $users['thomasw'], $jobs['thomasw-triage']);
        $this->assert_expected_user(array_shift($result['managers']), 'Tom Ireland - Client support lead', $users['tomi'], $jobs['tomi-triage']);

        // And combined.
        set_config('managerorganisationframeworks', $organisationframeworks['other']->id.','.$organisationframeworks['nz']->id, 'auth_approved');
        $result = \auth_approved_external::job_assignment_by_user_names('client support');
        $this->assert_search_result_valid($result, 5);
        $this->assert_expected_user(array_shift($result['managers']), 'Craig Eves - Client support', $users['craige'], $jobs['craige-triage']);
        $this->assert_expected_user(array_shift($result['managers']), 'George Angus - Client support', $users['georgea'], $jobs['georgea-triage']);
        $this->assert_expected_user(array_shift($result['managers']), 'Iain Napier - Client support manager', $users['iainn'], $jobs['iainn-management']);
        $this->assert_expected_user(array_shift($result['managers']), 'Thomas Wood - Client support', $users['thomasw'], $jobs['thomasw-triage']);
        $this->assert_expected_user(array_shift($result['managers']), 'Tom Ireland - Client support lead', $users['tomi'], $jobs['tomi-triage']);

        // Finally with our empty value.
        set_config('managerorganisationframeworks', '', 'auth_approved');
        $this->assertEquals(get_config('auth_approved', 'managerorganisationframeworks'), '');
        $result = \auth_approved_external::job_assignment_by_user_names('client support');
        $this->assert_search_result_valid($result, 5);
        $this->assert_expected_user(array_shift($result['managers']), 'Craig Eves - Client support', $users['craige'], $jobs['craige-triage']);
        $this->assert_expected_user(array_shift($result['managers']), 'George Angus - Client support', $users['georgea'], $jobs['georgea-triage']);
        $this->assert_expected_user(array_shift($result['managers']), 'Iain Napier - Client support manager', $users['iainn'], $jobs['iainn-management']);
        $this->assert_expected_user(array_shift($result['managers']), 'Thomas Wood - Client support', $users['thomasw'], $jobs['thomasw-triage']);
        $this->assert_expected_user(array_shift($result['managers']), 'Tom Ireland - Client support lead', $users['tomi'], $jobs['tomi-triage']);

        // The final test cases, with both position and organisation restriction on.
        // If we set management and NZ we expect 0, if we set management and UK we expect just iain, if we set triage and UK we expected just tom and tom.

        // Management OR nz.
        set_config('managerorganisationframeworks', $organisationframeworks['nz']->id, 'auth_approved');
        set_config('managerpositionframeworks', $positionframeworks['management']->id, 'auth_approved');
        $result = \auth_approved_external::job_assignment_by_user_names('client support');
        $this->assert_search_result_valid($result, 3);
        $this->assert_expected_user(array_shift($result['managers']), 'Craig Eves - Client support', $users['craige'], $jobs['craige-triage']);
        $this->assert_expected_user(array_shift($result['managers']), 'George Angus - Client support', $users['georgea'], $jobs['georgea-triage']);
        $this->assert_expected_user(array_shift($result['managers']), 'Iain Napier - Client support manager', $users['iainn'], $jobs['iainn-management']);

        // Management OR UK.
        set_config('managerorganisationframeworks', $organisationframeworks['other']->id, 'auth_approved');
        set_config('managerpositionframeworks', $positionframeworks['management']->id, 'auth_approved');
        $result = \auth_approved_external::job_assignment_by_user_names('client support');
        $this->assert_search_result_valid($result, 3);
        $this->assert_expected_user(array_shift($result['managers']), 'Iain Napier - Client support manager', $users['iainn'], $jobs['iainn-management']);
        $this->assert_expected_user(array_shift($result['managers']), 'Thomas Wood - Client support', $users['thomasw'], $jobs['thomasw-triage']);
        $this->assert_expected_user(array_shift($result['managers']), 'Tom Ireland - Client support lead', $users['tomi'], $jobs['tomi-triage']);

        // Triage OR UK.
        set_config('managerorganisationframeworks', $organisationframeworks['other']->id, 'auth_approved');
        set_config('managerpositionframeworks', $positionframeworks['triage']->id, 'auth_approved');
        $result = \auth_approved_external::job_assignment_by_user_names('client support');
        $this->assert_search_result_valid($result, 5);
        $this->assert_expected_user(array_shift($result['managers']), 'Craig Eves - Client support', $users['craige'], $jobs['craige-triage']);
        $this->assert_expected_user(array_shift($result['managers']), 'George Angus - Client support', $users['georgea'], $jobs['georgea-triage']);
        $this->assert_expected_user(array_shift($result['managers']), 'Iain Napier - Client support manager', $users['iainn'], $jobs['iainn-management']);
        $this->assert_expected_user(array_shift($result['managers']), 'Thomas Wood - Client support', $users['thomasw'], $jobs['thomasw-triage']);
        $this->assert_expected_user(array_shift($result['managers']), 'Tom Ireland - Client support lead', $users['tomi'], $jobs['tomi-triage']);
    }

    private function assert_search_result_valid($result, $totalcount, $paginated = false) {
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('managers', $result);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertIsInt($result['total']);
        $this->assertGreaterThanOrEqual(0, $result['total']);
        $this->assertIsArray($result['managers']);
        if (!$paginated) {
            $this->assertCount($result['total'], $result['managers']);
            $this->assertCount($totalcount, $result['managers']);
        }
        $this->assertSame($totalcount, $result['total']);
        $this->assertIsArray($result['warnings']);
        $this->assertCount(0, $result['warnings']);
    }

    private function assert_expected_user($result, $title, $user, $job) {
        $this->assertIsArray($result);
        $this->assertArrayHasKey('displayname', $result);
        $this->assertArrayHasKey('jaid', $result);
        $this->assertEquals($title, $result['displayname']);
        $this->assertEquals($user->id, $result['userid']);
        $this->assertEquals($job->id, $result['jaid']);
    }




}
