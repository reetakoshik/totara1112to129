@totara @tool @tool_totara_sync @totara_hierarchy_position @totara_hierarchy_organisation @totara_job @javascript
Feature: Test HR Import with multiple database sources.

  Background:
    Given I am on a totara site
    When I log in as "admin"
    And I navigate to "Default settings" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | File access | Upload Files |
    And I press "Save changes"

    # Setup the organisation.
    And the following "organisation" frameworks exist:
      | fullname           | idnumber   |
      | Test Org Framework | tstorgfw   |
    And I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "Organisation" HR Import element
    And I navigate to "Organisation" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | External Database | 1 |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    # Setup the position.
    And the following "position" frameworks exist:
      | fullname           | idnumber   |
      | Test Pos Framework | tstposfw   |
    And I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "Position" HR Import element
    And I navigate to "Position" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | External Database | 1 |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    # Setup the user.
    And I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "User" HR Import element
    And I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | External Database | 1 |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    # Setup the job assignments.
    And I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "Job assignment" HR Import element
    And I navigate to "Job assignment" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | External Database | 1 |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

  Scenario: Test all sources run successfully when there are no errors or warnings.
    #
    # All the data is correct so we expect all the sources to run and sync successfully.
    #

    # Create the external database for the organisation.
    Given the following "organisation" HR Import database source exists:
      | idnumber | fullname  | deleted  | frameworkidnumber | timemodified   |
      | 1        | org1      | 0        | tstorgfw          | 0              |
      | 2        | org2      | 0        | tstorgfw          | 0              |
    When I navigate to "External Database" node in "Site administration > HR Import > Sources > Organisation"
    And I press "Save changes"
    Then I should see "Settings saved"

    # Create the external database for the position.
    Given the following "position" HR Import database source exists:
      | idnumber | fullname  | deleted  | frameworkidnumber | timemodified   |
      | 1        | pos1      | 0        | tstposfw          | 0              |
      | 2        | pos2      | 0        | tstposfw          | 0              |
    When I navigate to "External Database" node in "Site administration > HR Import > Sources > Position"
    And I press "Save changes"
    Then I should see "Settings saved"

    # Create the external database for the user.
    Given the following "user" HR Import database source exists:
      | idnumber | username  | firstname  | lastname  | email             | deleted | timemodified |
      | 1        | upload1   | Upload     | User 1    | upload1@email.com | 0       | 0            |
      | 2        | upload2   | Upload     | User 2    | upload2@email.com | 0       | 0            |
    When I navigate to "External Database" node in "Site administration > HR Import > Sources > User"
    And I press "Save changes"
    Then I should see "Settings saved"

    # Create the external database for the job assignments.
    Given the following "jobassignment" HR Import database source exists:
      | idnumber | useridnumber  | timemodified  | deleted  |
      | 1        | 1             | 0             | 0        |
      | 2        | 2             | 0             | 0        |
    When I navigate to "External Database" node in "Site administration > HR Import > Sources > Job assignment"
    And I press "Save changes"
    Then I should see "Settings saved"

    # Run the sync, all sources should have synced successfully.
    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"
    And I should not see "However, there have been some problems"
    When I navigate to "HR Import Log" node in "Site administration > HR Import"
    Then I should see "HR Import Log: 16 records shown"
    And the following should exist in the "totarasynclog" table:
      | -2-     | -4-            | -5-       | -6-          | -7-                                             |
      | 1       | pos            | Info      | possync      | HR Import started                               |
      | 1       | pos            | Info      | syncitem     | created pos 1                                   |
      | 1       | pos            | Info      | syncitem     | created pos 2                                   |
      | 1       | pos            | Info      | possync      | HR Import finished                              |
      | 1       | org            | Info      | orgsync      | HR Import started                               |
      | 1       | org            | Info      | syncitem     | created org 1                                   |
      | 1       | org            | Info      | syncitem     | created org 2                                   |
      | 1       | org            | Info      | orgsync      | HR Import finished                              |
      | 1       | user           | Info      | usersync     | HR Import started                               |
      | 1       | user           | Info      | createuser   | created user 1                                  |
      | 1       | user           | Info      | createuser   | created user 2                                  |
      | 1       | user           | Info      | usersync     | HR Import finished                              |
      | 1       | jobassignment  | Info      | sync         | HR Import started                               |
      | 1       | jobassignment  | Info      | create       | Created job assignment '1' for user '1'.        |
      | 1       | jobassignment  | Info      | create       | Created job assignment '2' for user '2'.        |
      | 1       | jobassignment  | Info      | sync         | HR Import finished                              |

  Scenario: Test all sources run when the user source has an error or warning.
    #
    # The user source has an error in the data but we still expect all the other sources to run and in this test
    # they should sync successfully.
    #

    # Create the external database for the organisation.
    Given the following "organisation" HR Import database source exists:
      | idnumber | fullname  | deleted  | frameworkidnumber | timemodified   |
      | 1        | org1      | 0        | tstorgfw          | 0              |
      | 2        | org2      | 0        | tstorgfw          | 0              |
    When I navigate to "External Database" node in "Site administration > HR Import > Sources > Organisation"
    And I press "Save changes"
    Then I should see "Settings saved"

    # Create the external database for the position.
    Given the following "position" HR Import database source exists:
      | idnumber | fullname  | deleted  | frameworkidnumber | timemodified   |
      | 1        | pos1      | 0        | tstposfw          | 0              |
      | 2        | pos2      | 0        | tstposfw          | 0              |
    When I navigate to "External Database" node in "Site administration > HR Import > Sources > Position"
    And I press "Save changes"
    Then I should see "Settings saved"

    # Create the external database for the user that contains an error, (duplicate of user idnumber 3).
    Given the following "user" HR Import database source exists:
      | idnumber | username  | firstname  | lastname  | email             | deleted | timemodified |
      | 1        | upload1   | Upload     | User 1    | upload1@email.com | 0       | 0            |
      | 2        | upload2   | Upload     | User 2    | upload2@email.com | 0       | 0            |
      | 3        | upload3   | Upload     | User 3    | upload3@email.com | 0       | 0            |
      | 3        | upload4   | Upload     | User 4    | upload4@email.com | 0       | 0            |
    When I navigate to "External Database" node in "Site administration > HR Import > Sources > User"
    And I press "Save changes"
    Then I should see "Settings saved"

    # Create the external database for the job assignments.
    Given the following "jobassignment" HR Import database source exists:
      | idnumber | useridnumber  | timemodified  | deleted  |
      | 1        | 1             | 0             | 0        |
      | 2        | 2             | 0             | 0        |
    When I navigate to "External Database" node in "Site administration > HR Import > Sources > Job assignment"
    And I press "Save changes"
    Then I should see "Settings saved"

    # Run the sync, all sources should have synced successfully.
    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"
    And I should see "However, there have been some problems"
    When I navigate to "HR Import Log" node in "Site administration > HR Import"
    Then I should see "HR Import Log: 18 records shown"
    And the following should exist in the "totarasynclog" table:
      | -2-     | -4-            | -5-       | -6-          | -7-                                             |
      | 1       | pos            | Info      | possync      | HR Import started                               |
      | 1       | pos            | Info      | syncitem     | created pos 1                                   |
      | 1       | pos            | Info      | syncitem     | created pos 2                                   |
      | 1       | pos            | Info      | possync      | HR Import finished                              |
      | 1       | org            | Info      | orgsync      | HR Import started                               |
      | 1       | org            | Info      | syncitem     | created org 1                                   |
      | 1       | org            | Info      | syncitem     | created org 2                                   |
      | 1       | org            | Info      | orgsync      | HR Import finished                              |
      | 1       | user           | Info      | usersync     | HR Import started                               |
      | 1       | user           | Error     | checksanity  | Duplicate users with idnumber 3. Skipped user 3 |
      | 1       | user           | Error     | checksanity  | Duplicate users with idnumber 3. Skipped user 3 |
      | 1       | user           | Info      | createuser   | created user 1                                  |
      | 1       | user           | Info      | createuser   | created user 2                                  |
      | 1       | user           | Info      | usersync     | HR Import finished                              |
      | 1       | jobassignment  | Info      | sync         | HR Import started                               |
      | 1       | jobassignment  | Info      | create       | Created job assignment '1' for user '1'.        |
      | 1       | jobassignment  | Info      | create       | Created job assignment '2' for user '2'.        |
      | 1       | jobassignment  | Info      | sync         | HR Import finished                              |
