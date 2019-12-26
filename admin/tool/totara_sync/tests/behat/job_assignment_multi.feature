@_file_upload @javascript @tool @tool_totara_sync @totara @totara_job
Feature: Use user source to import multiple job assignments data in HR sync
  In order to test HR import of users with multiple job assignments
  I must log in as an admin and import from a CSV file

  Background:
    # Site data.
    Given I log in as "admin"
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname        | idnumber |
      | Organisation FW | OFW001   |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | org_framework | fullname      | idnumber |
      | OFW001        | OrganisationX | orgx     |
      | OFW001        | OrganisationY | orgy     |
      | OFW001        | OrganisationZ | orgz     |
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname    | idnumber |
      | Position FW | PFW001   |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | pos_framework | fullname  | idnumber |
      | PFW001        | PositionX | posx     |
      | PFW001        | PositionY | posy     |
      | PFW001        | PositionZ | posz     |

    # Pre-create some user and job assignment data, to test update (and not-update).
    And the following "users" exist:
      | username   | idnumber   | firstname | lastname | email                  | totarasync |
      | manager1   | manager1   | man1      | man1     | manager1@example.com   | 0          |
      | manager2   | manager2   | man2      | man2     | manager2@example.com   | 0          |
      | manager3   | manager3   | man3      | man3     | manager3@example.com   | 0          |
      | manager4   | manager4   | man4      | man4     | manager4@example.com   | 0          |
      | manager5   | manager5   | man5      | man5     | manager5@example.com   | 1          |
      | appraiserx | appraiserx | appx      | appx     | appraiserx@example.com | 0          |
      | appraisery | appraisery | appy      | appy     | appraisery@example.com | 0          |
      | appraiserz | appraiserz | appz      | appz     | appraiserz@example.com | 0          |
      | username1  | user1      | first1    | last1    | user1@example.com      | 1          |
      | username2  | user2      | first2    | last2    | user2@example.com      | 0          |
      | username3  | user3      | first3    | last3    | user3@example.com      | 1          |
      | username4  | user4      | first4    | last4    | user4@example.com      | 1          |
      | username5  | user5      | first5    | last5    | user5@example.com      | 0          |
      | username6  | user6      | first6    | last6    | user6@example.com      | 1          |
      | username7  | user7      | first7    | last7    | user7@example.com      | 1          |
      | username8  | user8      | first8    | last8    | user8@example.com      | 1          |
      | username9  | user9      | first9    | last9    | user9@example.com      | 1          |
      | username10 | user10     | first10   | last10   | user10@example.com     | 1          |
      | username11 | user11     | first11   | last11   | user11@example.com     | 1          |
      | username12 | user12     | first12   | last12   | user12@example.com     | 1          |
      | username13 | user13     | first13   | last13   | user13@example.com     | 1          |
      | username14 | user14     | first14   | last14   | user14@example.com     | 1          |
      | username15 | user15     | first15   | last15   | user15@example.com     | 1          |
    And the following job assignments exist:
      | user       | idnumber      | fullname | shortname | startdate  | enddate    | organisation | position | manager  | managerjaidnumber | appraiser  | totarasync |
      | manager1   | jaidx         | full1    |           |            |            |              |          |          |                   |            | 1          |
      | manager2   | jaidx         | full2    |           |            |            |              |          |          |                   |            | 1          |
      | manager5   | jaidx         | full5x   |           |            |            |              |          |          |                   |            | 1          |
      | manager5   | jaidy         | full5y   |           |            |            |              |          |          |                   |            | 1          |
      | username1  | jaidx         | fullx    | short1    | 1426820400 | 1434772800 | orgx         | posx     | manager1 | jaidx             | appraiserx | 1          |
      | username3  | jaidx         | fullx    | short3    | 1426820400 | 1434772800 | orgx         | posx     | manager1 | jaidx             | appraiserx | 1          |
      | username4  | jaidx         | fullx    | short4    | 1426820400 | 1434772800 | orgx         | posx     | manager1 | jaidx             | appraiserx | 1          |
      | username6  | jaidx         | fullx    | short6    | 1426820400 | 1434772800 | orgx         | posx     | manager1 | jaidx             | appraiserx | 1          |
      | username6  | jaidy         | fully    | short6    | 1426820400 | 1434772800 | orgy         | posy     | manager1 | jaidx             | appraisery | 1          |
      | username7  | jaidx         | fullx    | short7    | 1426820400 | 1434772800 | orgx         | posx     | manager2 | jaidx             | appraiserx | 1          |
      | username9  | jaidx         | fullx    | short9    | 1426820400 | 1434772800 | orgx         | posx     | manager2 | jaidx             | appraiserx | 1          |
      | username9  | jaidy         | fully    |           |            |            | orgz         | posz     |          |                   |            | 1          |
      | username10 | jaidx         | fullx    | short10   | 1426820400 | 1434772800 | orgx         | posx     | manager2 | jaidx             | appraiserx | 1          |
      | username10 | jaidy         | fully    |           |            |            | orgz         | posz     |          |                   |            | 1          |
      | username11 | jaidx         | fullx    |           |            |            | orgz         | posz     |          |                   |            | 1          |
      | username12 | jaidx         | fullx    |           |            |            | orgz         | posz     |          |                   |            | 1          |
      | username13 | jaidx         | fullx    |           |            |            | orgz         | posz     |          |                   |            | 0          |
      | username14 | jaidx         | fullx    |           |            |            | orgz         | posz     |          |                   |            | 1          |
      | username14 | jaidy         | fullx    |           |            |            | orgz         | posz     |          |                   |            | 1          |

    # User source setup.
    When I navigate to "Default settings" node in "Site administration > HR Import"
    And I set the following fields to these values:
        | File access | Upload Files |
    And I press "Save changes"
    And I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "Job assignment" HR Import element
    And I navigate to "Job assignment" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | CSV | 1 |
    And I press "Save changes"

    # Enable all job assignment fields.
    When I navigate to "CSV" node in "Site administration > HR Import > Sources > Job assignment"
    And I set the following fields to these values:
      | Full name    | 1 |
      | Start date   | 1 |
      | End date     | 1 |
      | Organisation | 1 |
      | Position     | 1 |
      | Manager      | 1 |
      | Appraiser    | 1 |
    And I press "Save changes"

  Scenario: Update ID numbers is on, multiple jobs enabled, empty strings will not erase existing data
    # Configure.
    Given I set the following administration settings values:
      | totara_job_allowmultiplejobs | 1 |
    And I navigate to "Job assignment" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | Empty string behaviour | Empty strings are ignored |
      | Update ID numbers      | Yes                       |
    And I press "Save changes"

    # Import.
    And I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/jobassignment/fulltest_updateidnumbers_on.csv" file to "CSV" filemanager
    And I press "Upload"
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    And I navigate to "HR Import Log" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | totara_sync_log-logtype_op | 2    |
      | totara_sync_log-logtype    | info |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"

    And I should see "Position 'notrealpos' does not exist. Skipped job assignment 'nonexistentposition' for user 'user1'."
    And I should see "Organisation 'notrealorg' does not exist. Skipped job assignment 'nonexistentorganisation' for user 'user2'."
    And I should see "User 'notrealmanager' does not exist and was set to be assigned as manager. Skipped job assignment 'nonexistentmanager' for user 'user3'."
    And I should see "User 'notrealappraiser' does not exist and was set to be assigned as appraiser. Skipped job assignment 'nonexistentappraiser' for user 'user4'."

    And I should see "Position 'notrealpos' does not exist. Skipped job assignment 'nonexistentanything' for user 'user5'."
    And I should see "Organisation 'notrealorg' does not exist. Skipped job assignment 'nonexistentanything' for user 'user5'."
    And I should see "User 'notrealmanager' does not exist and was set to be assigned as manager. Skipped job assignment 'nonexistentanything' for user 'user5'."
    And I should see "User 'notrealappraiser' does not exist and was set to be assigned as appraiser. Skipped job assignment 'nonexistentanything' for user 'user5'."

    # User 6 failed because it tried to update the first job assignment record to same id as the second.
    And I should see "User 'user6' has another job assignment with the same idnumber as what is being updated. Skipped job assignment 'jaidy' for user 'user6'."

    # User 11 failed because they had no jaid.
    And I should see "Some records are missing their idnumber and/or useridnumber. These records were skipped."

    # User 12 failed because the manager to be assigned was never given a job assignment by the end of the import.
    And I should see "User 'manager4' does not have a job assignment and was set to be assigned as manager. Skipped job assignment 'jaidx' for user 'user12'."

    # User 13 failed because the 'totarasync' flag (shown in the interface as its HR Import setting) was set to 0.
    And I should see "Skipped job assignment 'jaidx' for user 'user13' as the HR Import setting for that job assignment is disabled."

    # User14 failed because there were multiple entries with the same job assignment idnumber and user idnumber.
    And I should see "Multiple entries found for job assignment 'jaidy' for user 'user14'. No updates made to this job assignment."

    # Some users have manager3 as their manager, but the line to create a job for manager3 was at the end of the file.
    # Therefore, their job assignments were likely deferred until later in the process when manager3 did have a job.
    # But this should not have generated any notices.
    And I should not see "User 'manager3' does not have a job assignment and was set to be assigned as manager."

    # User 5 - imports failed and had no previous job assignments, so none should exist now.
    When I am on profile page for user "username5"
    And I should see "This user has no job assignments"

    # User 7 - had nothing in import file, but did have existing which should be unchanged.
    When I am on profile page for user "username7"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short7  |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "man2 man2 (manager2@example.com) - full2"
    And I should see "appx appx"

    # User 8 - Had no existing job assignment. One should have been created.
    When I am on profile page for user "username8"
    And I click on "Valid and uses most fields" "link"
    Then the following fields match these values:
      | Full name          | Valid and uses most fields |
      | Short name         |                            |
      | ID Number          | newjaid                    |
      | startdate[enabled] | 1                          |
      | startdate[year]    | 2015                       |
      | startdate[month]   | March                      |
      | startdate[day]     | 20                         |
      | enddate[enabled]   | 0                          |
    And I should see "PositionY"
    And I should see "OrganisationZ"
    And I should see "man5 man5 (manager5@example.com) - full5x"
    And I should see "appx appx"

    # User 9 - Had two existing job assignments. First one should have been updated with new id number.
    # Empty strings don't erase, so Full Name should still be there.
    When I am on profile page for user "username9"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short9  |
      | ID Number          | newjaid |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionY"
    And I should see "OrganisationZ"
    And I should see "man3 man3 (manager3@example.com) - New Job Assignment"
    And I should see "appx appx"

    # User 9 - and their second job assignment should have not changed.
    When I am on profile page for user "username9"
    And I click on "fully" "link"
    Then the following fields match these values:
      | Full name          | fully   |
      | Short name         |         |
      | ID Number          | jaidy   |
      | startdate[enabled] | 0       |
      | enddate[enabled]   | 0       |
    And I should see "PositionZ"
    And I should see "OrganisationZ"
    And I should not see "man3 man3 (manager3@example.com) - New Job Assignment"
    And I should not see "appx appx"

    # User 10 - Had two existing job assignments. First one should have been updated. Id number was also the same.
    # Empty strings don't erase, so Full Name should still be there.
    When I am on profile page for user "username10"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short10 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionY"
    And I should see "OrganisationZ"
    And I should see "man3 man3 (manager3@example.com) - New Job Assignment"
    And I should see "appx appx"

    # User 10 - and their second job assignment should have not changed.
    When I am on profile page for user "username10"
    And I click on "fully" "link"
    Then the following fields match these values:
      | Full name          | fully   |
      | Short name         |         |
      | ID Number          | jaidy   |
      | startdate[enabled] | 0       |
      | enddate[enabled]   | 0       |
    And I should see "PositionZ"
    And I should see "OrganisationZ"
    And I should not see "man3 man3 (manager3@example.com) - New Job Assignment"
    And I should not see "appx appx"

    # Manager 3 had a job assignment created.
    When I am on profile page for user "manager3"
    And I click on "New Job Assignment" "link"
    Then the following fields match these values:
      | Full name          | New Job Assignment |
      | Short name         |                    |
      | ID Number          | newjaid            |
      | startdate[enabled] | 0                  |
      | enddate[enabled]   | 0                  |

  Scenario: Update ID numbers is on, multiple jobs enabled, empty string erases existing data
    # Configure.
    Given I set the following administration settings values:
      | totara_job_allowmultiplejobs | 1 |
    And I navigate to "Job assignment" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | Empty string behaviour | Empty strings erase existing data  |
      | Update ID numbers      | Yes                                |
    And I press "Save changes"

    # Import.
    And I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/jobassignment/fulltest_updateidnumbers_on.csv" file to "CSV" filemanager
    And I press "Upload"
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    And I navigate to "HR Import Log" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | totara_sync_log-logtype_op | 2    |
      | totara_sync_log-logtype    | info |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"

    And I should see "Position 'notrealpos' does not exist. Skipped job assignment 'nonexistentposition' for user 'user1'."
    And I should see "Organisation 'notrealorg' does not exist. Skipped job assignment 'nonexistentorganisation' for user 'user2'."
    And I should see "User 'notrealmanager' does not exist and was set to be assigned as manager. Skipped job assignment 'nonexistentmanager' for user 'user3'."
    And I should see "User 'notrealappraiser' does not exist and was set to be assigned as appraiser. Skipped job assignment 'nonexistentappraiser' for user 'user4'."

    And I should see "Position 'notrealpos' does not exist. Skipped job assignment 'nonexistentanything' for user 'user5'."
    And I should see "Organisation 'notrealorg' does not exist. Skipped job assignment 'nonexistentanything' for user 'user5'."
    And I should see "User 'notrealmanager' does not exist and was set to be assigned as manager. Skipped job assignment 'nonexistentanything' for user 'user5'."
    And I should see "User 'notrealappraiser' does not exist and was set to be assigned as appraiser. Skipped job assignment 'nonexistentanything' for user 'user5'."

    # User 6 failed because it tried to update the first job assignment record to same id as the second.
    And I should see "User 'user6' has another job assignment with the same idnumber as what is being updated. Skipped job assignment 'jaidy' for user 'user6'."

    # User 11 failed because they had no jaid.
    And I should see "Some records are missing their idnumber and/or useridnumber. These records were skipped."

    # User 12 failed because the manager to be assigned was never given a job assignment by the end of the import.
    And I should see "User 'manager4' does not have a job assignment and was set to be assigned as manager. Skipped job assignment 'jaidx' for user 'user12'."

    # User 13 failed because the 'totarasync' flag (shown in the interface as its HR Import setting) was set to 0.
    And I should see "Skipped job assignment 'jaidx' for user 'user13' as the HR Import setting for that job assignment is disabled."

    # User14 failed because there were multiple entries with the same job assignment idnumber and user idnumber.
    And I should see "Multiple entries found for job assignment 'jaidy' for user 'user14'. No updates made to this job assignment."

    # Some users have manager3 as their manager, but the line to create a job for manager3 was at the end of the file.
    # Therefore, their job assignments were likely deferred until later in the process when manager3 did have a job.
    # But this should not have generated any notices.
    And I should not see "User 'manager3' does not have a job assignment and was set to be assigned as manager."

    # User 5 - imports failed and had no previous job assignments, so none should exist now.
    When I am on profile page for user "username5"
    Then I should see "Add job assignment"
    And I should see "This user has no job assignments"

    # User 7 - had nothing in import file, but did have existing which should be unchanged.
    When I am on profile page for user "username7"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short7  |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "man2 man2 (manager2@example.com) - full2"
    And I should see "appx appx"

    # User 8 - Had no existing job assignment. One should have been created.
    When I am on profile page for user "username8"
    And I click on "Valid and uses most fields" "link"
    Then the following fields match these values:
      | Full name          | Valid and uses most fields |
      | Short name         |                            |
      | ID Number          | newjaid                    |
      | startdate[enabled] | 1                          |
      | startdate[year]    | 2015                       |
      | startdate[month]   | March                      |
      | startdate[day]     | 20                         |
      | enddate[enabled]   | 0                          |
    And I should see "PositionY"
    And I should see "OrganisationZ"
    And I should see "man5 man5 (manager5@example.com) - full5x"
    And I should see "appx appx"

    # User 9 - Had two existing job assignments. First one should have been updated with new id number.
    # Empty strings erased any existing values.
    When I am on profile page for user "username9"
    And I click on "Unnamed job assignment (ID: newjaid)" "link"
    Then the following fields match these values:
      | Full name          | Unnamed job assignment (ID: newjaid) |
      | Short name         | short9                               |
      | ID Number          | newjaid                              |
      | startdate[enabled] | 1                                    |
      | startdate[year]    | 2015                                 |
      | startdate[month]   | March                                |
      | startdate[day]     | 20                                   |
      | enddate[enabled]   | 0                                    |
    And I should see "PositionY"
    And I should see "OrganisationZ"
    And I should see "man3 man3 (manager3@example.com) - New Job Assignment"
    And I should see "appx appx"

    # User 9 - and their second job assignment should have not changed.
    When I am on profile page for user "username9"
    And I click on "fully" "link"
    Then the following fields match these values:
      | Full name          | fully   |
      | Short name         |         |
      | ID Number          | jaidy   |
      | startdate[enabled] | 0       |
      | enddate[enabled]   | 0       |
    And I should see "PositionZ"
    And I should see "OrganisationZ"
    And I should not see "man3 man3 (manager3@example.com) - New Job Assignment"
    And I should not see "appx appx"

    # User 10 - Had two existing job assignments. First one should have been updated. Id number was also the same.
    # Empty strings erase any existing values.
    When I am on profile page for user "username10"
    And I click on "Unnamed job assignment (ID: jaidx)" "link"
    Then the following fields match these values:
      | Full name          | Unnamed job assignment (ID: jaidx) |
      | Short name         | short10                            |
      | ID Number          | jaidx                              |
      | startdate[enabled] | 1                                  |
      | startdate[year]    | 2015                               |
      | startdate[month]   | March                              |
      | startdate[day]     | 20                                 |
      | enddate[enabled]   | 0                                  |
    And I should see "PositionY"
    And I should see "OrganisationZ"
    And I should see "man3 man3 (manager3@example.com) - New Job Assignment"
    And I should see "appx appx"

    # User 10 - and their second job assignment should have not changed.
    When I am on profile page for user "username10"
    And I click on "fully" "link"
    Then the following fields match these values:
      | Full name          | fully   |
      | Short name         |         |
      | ID Number          | jaidy   |
      | startdate[enabled] | 0       |
      | enddate[enabled]   | 0       |
    And I should see "PositionZ"
    And I should see "OrganisationZ"
    And I should not see "man3 man3 (manager3@example.com) - New Job Assignment"
    And I should not see "appx appx"

    # Manager 3 had a job assignment created.
    When I am on profile page for user "manager3"
    And I click on "New Job Assignment" "link"
    Then the following fields match these values:
      | Full name          | New Job Assignment |
      | Short name         |                    |
      | ID Number          | newjaid            |
      | startdate[enabled] | 0                  |
      | enddate[enabled]   | 0                  |

  Scenario: Update ID numbers is off, multiple jobs enabled, empty strings will not erase existing data
    # Configure.
    Given I set the following administration settings values:
      | totara_job_allowmultiplejobs | 1 |
    And I navigate to "Job assignment" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | Empty string behaviour | Empty strings are ignored |
      | Update ID numbers      | No                        |
    And I press "Save changes"

    # Import.
    And I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/jobassignment/fulltest_updateidnumbers_off.csv" file to "CSV" filemanager
    And I press "Upload"
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    And I navigate to "HR Import Log" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | totara_sync_log-logtype_op | 2    |
      | totara_sync_log-logtype    | info |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"

    And I should see "Position 'notrealpos' does not exist. Skipped job assignment 'nonexistentposition' for user 'user1'."
    And I should see "Organisation 'notrealorg' does not exist. Skipped job assignment 'nonexistentorganisation' for user 'user2'."
    And I should see "User 'notrealmanager' does not exist and was set to be assigned as manager. Skipped job assignment 'nonexistentmanager' for user 'user3'."
    And I should see "User 'notrealappraiser' does not exist and was set to be assigned as appraiser. Skipped job assignment 'nonexistentappraiser' for user 'user4'."

    And I should see "Position 'notrealpos' does not exist. Skipped job assignment 'nonexistentanything' for user 'user5'."
    And I should see "Organisation 'notrealorg' does not exist. Skipped job assignment 'nonexistentanything' for user 'user5'."
    And I should see "User 'notrealmanager' does not exist and was set to be assigned as manager. Skipped job assignment 'nonexistentanything' for user 'user5'."
    And I should see "User 'notrealappraiser' does not exist and was set to be assigned as appraiser. Skipped job assignment 'nonexistentanything' for user 'user5'."

    # User 11 failed because they had no jaid.
    And I should see "Some records are missing their idnumber and/or useridnumber. These records were skipped."

    # User 12 failed because the manager to be assigned was never given a job assignment by the end of the import.
    And I should see "Job assignment 'notreal' for manager 'manager4' does not exist. Skipped job assignment 'jaidx' for user 'user12'."

    # User 13 failed because the 'totarasync' flag (shown in the interface as its HR Import setting) was set to 0.
    And I should see "Skipped job assignment 'jaidx' for user 'user13' as the HR Import setting for that job assignment is disabled."

    # User14 failed because there were multiple entries with the same job assignment idnumber and user idnumber.
    And I should see "Multiple entries found for job assignment 'jaidy' for user 'user14'. No updates made to this job assignment."

    # User 15 failed because they had a manager id number but no manager job assignment id number.
    And I should see "Missing manager's job assignment id number for assigning manager 'manager2'. Skipped job assignment 'newjaid' for user 'user15'."

    # Some users have manager3 as their manager, but the line to create a job for manager3 was at the end of the file.
    # Therefore, their job assignments were likely deferred until later in the process when manager3 did have a job.
    # But this should not have generated any notices.
    And I should not see "User 'manager3' does not have a job assignment and was set to be assigned as manager."

    # User 5 - imports failed and had no previous job assignments, so none should exist now.
    When I am on profile page for user "username5"
    Then I should see "Add job assignment"
    And I should see "This user has no job assignments"

    # User 7 - had nothing in import file, but did have existing which should be unchanged.
    When I am on profile page for user "username7"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short7  |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "man2 man2 (manager2@example.com) - full2"
    And I should see "appx appx"

    # User 8 - Had no existing job assignment. One should have been created.
    When I am on profile page for user "username8"
    And I click on "Valid and uses most fields" "link"
    Then the following fields match these values:
      | Full name          | Valid and uses most fields |
      | Short name         |                            |
      | ID Number          | newjaid                    |
      | startdate[enabled] | 1                          |
      | startdate[year]    | 2015                       |
      | startdate[month]   | March                      |
      | startdate[day]     | 20                         |
      | enddate[enabled]   | 0                          |
    And I should see "PositionY"
    And I should see "OrganisationZ"
    And I should see "man5 man5 (manager5@example.com) - full5y"
    And I should see "appx appx"

    # User 9 - Had two existing job assignments. Nothing in the CSV matched any of them, so no updates for the existing two.
    When I am on profile page for user "username9"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short9  |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "man2 man2 (manager2@example.com) - full2"
    And I should see "appx appx"

    # User 9 - and their second job assignment should have not changed.
    When I am on profile page for user "username9"
    And I click on "fully" "link"
    Then the following fields match these values:
      | Full name          | fully   |
      | Short name         |         |
      | ID Number          | jaidy   |
      | startdate[enabled] | 0       |
      | enddate[enabled]   | 0       |
    And I should see "PositionZ"
    And I should see "OrganisationZ"
    And I should not see "man3 man3 (manager3@example.com) - New Job Assignment"
    And I should not see "appx appx"

    # User 9 - but they do now have a third job assignment.
    When I am on profile page for user "username9"
    And I click on "Unnamed job assignment (ID: newjaid)" "link"
    Then the following fields match these values:
      | Full name          | Unnamed job assignment (ID: newjaid) |
      | Short name         |                                      |
      | ID Number          | newjaid                              |
      | startdate[enabled] | 1                                    |
      | startdate[year]    | 2015                                 |
      | startdate[month]   | March                                |
      | startdate[day]     | 20                                   |
      | enddate[enabled]   | 0                                    |
    And I should see "PositionY"
    And I should see "OrganisationZ"
    And I should see "man3 man3 (manager3@example.com) - New Job Assignment"
    And I should see "appx appx"

    # User 10 - Had two existing job assignments. First one should have been updated given the id numbers matched.
    # Empty strings don't erase, so Full Name should still be there.
    When I am on profile page for user "username10"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short10 |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionY"
    And I should see "OrganisationZ"
    And I should see "man3 man3 (manager3@example.com) - New Job Assignment"
    And I should see "appx appx"

    # User 10 - and their second job assignment should have not changed.
    When I am on profile page for user "username10"
    And I click on "fully" "link"
    Then the following fields match these values:
      | Full name          | fully   |
      | Short name         |         |
      | ID Number          | jaidy   |
      | startdate[enabled] | 0       |
      | enddate[enabled]   | 0       |
    And I should see "PositionZ"
    And I should see "OrganisationZ"
    And I should not see "man3 man3 (manager3@example.com) - New Job Assignment"
    And I should not see "appx appx"

    # Manager 3 had a job assignment created.
    When I am on profile page for user "manager3"
    And I click on "New Job Assignment" "link"
    Then the following fields match these values:
      | Full name          | New Job Assignment |
      | Short name         |                    |
      | ID Number          | newjaid            |
      | startdate[enabled] | 0                  |
      | enddate[enabled]   | 0                  |

  Scenario: Update ID numbers is off, multiple jobs enabled, empty string erases existing data
    # Configure.
    Given I set the following administration settings values:
      | totara_job_allowmultiplejobs | 1 |
    And I navigate to "Job assignment" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | Empty string behaviour | Empty strings erase existing data |
      | Update ID numbers      | No                                |
    And I press "Save changes"

    # Import.
    And I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/jobassignment/fulltest_updateidnumbers_off.csv" file to "CSV" filemanager
    And I press "Upload"
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    And I navigate to "HR Import Log" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | totara_sync_log-logtype_op | 2    |
      | totara_sync_log-logtype    | info |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"

    And I should see "Position 'notrealpos' does not exist. Skipped job assignment 'nonexistentposition' for user 'user1'."
    And I should see "Organisation 'notrealorg' does not exist. Skipped job assignment 'nonexistentorganisation' for user 'user2'."
    And I should see "User 'notrealmanager' does not exist and was set to be assigned as manager. Skipped job assignment 'nonexistentmanager' for user 'user3'."
    And I should see "User 'notrealappraiser' does not exist and was set to be assigned as appraiser. Skipped job assignment 'nonexistentappraiser' for user 'user4'."

    And I should see "Position 'notrealpos' does not exist. Skipped job assignment 'nonexistentanything' for user 'user5'."
    And I should see "Organisation 'notrealorg' does not exist. Skipped job assignment 'nonexistentanything' for user 'user5'."
    And I should see "User 'notrealmanager' does not exist and was set to be assigned as manager. Skipped job assignment 'nonexistentanything' for user 'user5'."
    And I should see "User 'notrealappraiser' does not exist and was set to be assigned as appraiser. Skipped job assignment 'nonexistentanything' for user 'user5'."

    # User 11 failed because they had no jaid.
    And I should see "Some records are missing their idnumber and/or useridnumber. These records were skipped."

    # User 12 failed because the manager to be assigned was never given a job assignment by the end of the import.
    And I should see "Job assignment 'notreal' for manager 'manager4' does not exist. Skipped job assignment 'jaidx' for user 'user12'."

    # User 13 failed because the 'totarasync' flag (shown in the interface as its HR Import setting) was set to 0.
    And I should see "Skipped job assignment 'jaidx' for user 'user13' as the HR Import setting for that job assignment is disabled."

    # User14 failed because there were multiple entries with the same job assignment idnumber and user idnumber.
    And I should see "Multiple entries found for job assignment 'jaidy' for user 'user14'. No updates made to this job assignment."

    # User 15 failed because they had a manager id number but no manager job assignment id number.
    And I should see "Missing manager's job assignment id number for assigning manager 'manager2'. Skipped job assignment 'newjaid' for user 'user15'."

    # Some users have manager3 as their manager, but the line to create a job for manager3 was at the end of the file.
    # Therefore, their job assignments were likely deferred until later in the process when manager3 did have a job.
    # But this should not have generated any notices.
    And I should not see "User 'manager3' does not have a job assignment and was set to be assigned as manager."

    # User 5 - imports failed and had no previous job assignments, so none should exist now.
    When I am on profile page for user "username5"
    Then I should see "Add job assignment"
    And I should see "This user has no job assignments"

    # User 7 - had nothing in import file, but did have existing which should be unchanged.
    When I am on profile page for user "username7"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short7  |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "man2 man2 (manager2@example.com) - full2"
    And I should see "appx appx"

    # User 8 - Had no existing job assignment. One should have been created.
    When I am on profile page for user "username8"
    And I click on "Valid and uses most fields" "link"
    Then the following fields match these values:
      | Full name          | Valid and uses most fields |
      | Short name         |                            |
      | ID Number          | newjaid                    |
      | startdate[enabled] | 1                          |
      | startdate[year]    | 2015                       |
      | startdate[month]   | March                      |
      | startdate[day]     | 20                         |
      | enddate[enabled]   | 0                          |
    And I should see "PositionY"
    And I should see "OrganisationZ"
    And I should see "man5 man5 (manager5@example.com) - full5y"
    And I should see "appx appx"

    # User 9 - Had two existing job assignments. Nothing in the CSV matched any of them, so no updates for the existing two.
    When I am on profile page for user "username9"
    And I click on "fullx" "link"
    Then the following fields match these values:
      | Full name          | fullx   |
      | Short name         | short9  |
      | ID Number          | jaidx   |
      | startdate[enabled] | 1       |
      | startdate[year]    | 2015    |
      | startdate[month]   | March   |
      | startdate[day]     | 20      |
      | enddate[enabled]   | 1       |
      | enddate[year]      | 2015    |
      | enddate[month]     | June    |
      | enddate[day]       | 20      |
    And I should see "PositionX"
    And I should see "OrganisationX"
    And I should see "man2 man2 (manager2@example.com) - full2"
    And I should see "appx appx"

    # User 9 - and their second job assignment should have not changed.
    When I am on profile page for user "username9"
    And I click on "fully" "link"
    Then the following fields match these values:
      | Full name          | fully   |
      | Short name         |         |
      | ID Number          | jaidy   |
      | startdate[enabled] | 0       |
      | enddate[enabled]   | 0       |
    And I should see "PositionZ"
    And I should see "OrganisationZ"
    And I should not see "man3 man3 (manager3@example.com) - New Job Assignment"
    And I should not see "appx appx"

    # User 9 - but they do now have a third job assignment.
    When I am on profile page for user "username9"
    And I click on "Unnamed job assignment (ID: newjaid)" "link"
    Then the following fields match these values:
      | Full name          | Unnamed job assignment (ID: newjaid) |
      | Short name         |                                      |
      | ID Number          | newjaid                              |
      | startdate[enabled] | 1                                    |
      | startdate[year]    | 2015                                 |
      | startdate[month]   | March                                |
      | startdate[day]     | 20                                   |
      | enddate[enabled]   | 0                                    |
    And I should see "PositionY"
    And I should see "OrganisationZ"
    And I should see "man3 man3 (manager3@example.com) - New Job Assignment"
    And I should see "appx appx"

    # User 10 - Had two existing job assignments. First one should have been updated given the id numbers matched.
    # Empty strings erase.
    When I am on profile page for user "username10"
    And I click on "Unnamed job assignment (ID: jaidx)" "link"
    Then the following fields match these values:
      | Full name          | Unnamed job assignment (ID: jaidx) |
      | Short name         | short10                            |
      | ID Number          | jaidx                              |
      | startdate[enabled] | 1                                  |
      | startdate[year]    | 2015                               |
      | startdate[month]   | March                              |
      | startdate[day]     | 20                                 |
      | enddate[enabled]   | 0                                  |
    And I should see "PositionY"
    And I should see "OrganisationZ"
    And I should see "man3 man3 (manager3@example.com) - New Job Assignment"
    And I should see "appx appx"

    # User 10 - and their second job assignment should have not changed.
    When I am on profile page for user "username10"
    And I click on "fully" "link"
    Then the following fields match these values:
      | Full name          | fully   |
      | Short name         |         |
      | ID Number          | jaidy   |
      | startdate[enabled] | 0       |
      | enddate[enabled]   | 0       |
    And I should see "PositionZ"
    And I should see "OrganisationZ"
    And I should not see "man3 man3 (manager3@example.com) - New Job Assignment"
    And I should not see "appx appx"

    # Manager 3 had a job assignment created.
    When I am on profile page for user "manager3"
    And I click on "New Job Assignment" "link"
    Then the following fields match these values:
      | Full name          | New Job Assignment |
      | Short name         |                    |
      | ID Number          | newjaid            |
      | startdate[enabled] | 0                  |
      | enddate[enabled]   | 0                  |

