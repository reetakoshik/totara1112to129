@javascript @tool @tool_totara_sync @totara @totara_job
Feature: Configure user source to import job assignment data in HR sync
  In order to test HR import of users with job assignments
  I must log in as an admin and configure the user source

  Background:
    Given I log in as "admin"
    And I navigate to "Default settings" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | File access | Upload Files |
    And I press "Save changes"
    And I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "Job assignment" HR Import element
    And I navigate to "Job assignment" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | CSV | 1 |
    And I press "Save changes"

  Scenario: Configure HR import source link jaidnumber off
    Given I set the following fields to these values:
      | Update ID numbers | Yes |
    And I press "Save changes"
    And I navigate to "CSV" node in "Site administration > HR Import > Sources > Job assignment"
    And I should see "\"idnumber\""
    And I should see "\"useridnumber\""
    And I should see "\"timemodified\""
    And I should not see "\"fullname\""
    And I should not see "\"startdate\""
    And I should not see "\"enddate\""
    And I should not see "\"orgidnumber\""
    And I should not see "\"posidnumber\""
    And I should not see "\"manageridnumber\""
    And I should not see "\"managerjobassignmentidnumber\""
    And I should not see "\"appraiseridnumber\""
    When I set the following fields to these values:
      | Full name    | 1 |
      | Start date   | 1 |
      | End date     | 1 |
      | Organisation | 1 |
      | Position     | 1 |
      | Manager      | 1 |
      | Appraiser    | 1 |
    And I press "Save changes"
    And I should see "\"fullname\""
    And I should see "\"startdate\""
    And I should see "\"enddate\""
    And I should see "\"orgidnumber\""
    And I should see "\"posidnumber\""
    And I should see "\"manageridnumber\""
    And I should not see "\"managerjobassignmentidnumber\""
    And I should see "\"appraiseridnumber\""

  Scenario: Configure HR import source link jaidnumber on
    Given I set the following fields to these values:
      | Update ID numbers | No |
    And I press "Save changes"
    And I navigate to "CSV" node in "Site administration > HR Import > Sources > Job assignment"
    And I should not see "\"fullname\""
    And I should not see "\"startdate\""
    And I should not see "\"enddate\""
    And I should not see "\"orgidnumber\""
    And I should not see "\"posidnumber\""
    And I should not see "\"manageridnumber\""
    And I should not see "\"managerjobassignmentidnumber\""
    And I should not see "\"appraiseridnumber\""
    When I set the following fields to these values:
      | Full name    | 1 |
      | Start date   | 1 |
      | End date     | 1 |
      | Organisation | 1 |
      | Position     | 1 |
      | Manager      | 1 |
      | Appraiser    | 1 |
    And I press "Save changes"
    And I should see "\"fullname\""
    And I should see "\"startdate\""
    And I should see "\"enddate\""
    And I should see "\"orgidnumber\""
    And I should see "\"posidnumber\""
    And I should see "\"manageridnumber\""
    And I should see "\"managerjobassignmentidnumber\""
    And I should see "\"appraiseridnumber\""

  Scenario: Configure HR import source link jaidnumber cannot be turned off after run with setting on
    Given the following "users" exist:
      | username | firstname | lastname | email                | idnumber |
      | learner1 | Learner   | One      | learner1@example.com | learner1 |
      | manager1 | Manager   | One      | manager1@example.com | manager1 |
      | manager2 | Manager   | Two      | manager2@example.com | manager2 |
    And I set the following fields to these values:
      | Update ID numbers | No |
    And I press "Save changes"
    And I navigate to "CSV" node in "Site administration > HR Import > Sources > Job assignment"
    And I should not see "\"managerjobassignmentidnumber\""
    When I set the following fields to these values:
      | Full name    | 1 |
      | Manager      | 1 |
    And I press "Save changes"
    And I should see "\"managerjobassignmentidnumber\""

    # Setting can still be changed before first run with setting on.
    When I navigate to "Job assignment" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | Update ID numbers | Yes |
    And I press "Save changes"
    Then the following fields match these values:
      | Update ID numbers | Yes |
    When I set the following fields to these values:
      | Update ID numbers | No  |
    And I press "Save changes"
    Then the following fields match these values:
      | Update ID numbers | No  |

    # Run HR Import now.
    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/jobassignment/managers_1.csv" file to "CSV" filemanager
    And I press "Upload"
    And I should see "HR Import files uploaded successfully"
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"

    # Make sure data was imported correctly.
    When I navigate to "HR Import Log" node in "Site administration > HR Import"
    Then I should see "Created job assignment 'learnerjaid1' for user 'learner1'."
    And I should see "Created job assignment 'managerjaid1' for user 'manager1'."
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "Learner One" "link"
    And I click on "Learner1 JA1" "link"
    Then I should not see "Manager One (manager1@example.com) - Manager1 JA2"
    When I press "Cancel"
    And I click on "Learner1 JA2" "link"
    Then I should see "Manager One (manager1@example.com) - Manager1 JA2"
    When I press "Cancel"
    And I click on "Learner1 JA3" "link"
    Then I should not see "Manager One (manager1@example.com) - Manager1 JA2"

    # Change an irrelevant setting in the user element config and save.
    # This is checking for bug TL-12312 where the link to job assigment setting is updated unintentionally.
    When I navigate to "Job assignment" node in "Site administration > HR Import > Elements"
    Then I should not see "Update ID numbers"
    When I set the following fields to these values:
      | Empty string behaviour in CSV | Empty strings erase existing data |
    And I press "Save changes"
    Then I should not see "Update ID numbers"

    # Now check that the manager job assignment id number setting is still in the source config.
    # This should be the case if the Link job assignments setting is still what it was when we set it.
    When I navigate to "CSV" node in "Site administration > HR Import > Sources > Job assignment"
    Then I should see "\"managerjobassignmentidnumber\""
    And the following fields match these values:
      | Full name  | 1 |
      | Manager    | 1 |

    # Now run HR Import again just to make sure the correct setting is still applied there.
    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/jobassignment/managers_2.csv" file to "CSV" filemanager
    And I press "Upload"
    And I should see "HR Import files uploaded successfully"
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"

    # Manager Two should have been created and added to the learner's 3rd job assignment.
    When I navigate to "HR Import Log" node in "Site administration > HR Import"
    Then I should see "Updated job assignment 'learnerjaid1' for user 'learner1'."
    And I should see "Updated job assignment 'managerjaid1' for user 'manager1'."
    And I should see "Created job assignment 'managerjaid2' for user 'manager2'."
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "Learner One" "link"
    And I click on "Learner1 JA1" "link"
    Then I should not see "Manager One (manager1@example.com) - Manager1 JA2"
    And I should not see "Manager Two (manager2@example.com) - Manager2 JA2"
    When I press "Cancel"
    And I click on "Learner1 JA2" "link"
    Then I should see "Manager One (manager1@example.com) - Manager1 JA2"
    When I press "Cancel"
    And I click on "Learner1 JA3" "link"
    Then I should see "Manager Two (manager2@example.com) - Manager2 JA2"
