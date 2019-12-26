@totara @tool @tool_totara_sync @totara_hierarchy @totara_hierarchy_position @javascript
Feature: Test the position database source.

  Background:
    Given I am on a totara site
    And the following "position" frameworks exist:
      | fullname           | idnumber   |
      | Test Pos Framework | tstposfw   |
    When I log in as "admin"
    And I navigate to "Default settings" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | File access | Upload Files |
    And I press "Save changes"
    And I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "Position" HR Import element
    And I navigate to "Position" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | External Database | 1 |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

  Scenario: Test table exists check for the position database source
    # Create the external database.
    Given the following "position" HR Import database source exists:
      | idnumber | fullname  | deleted  | frameworkidnumber | timemodified   |
      | 1        | pos1      | 0        | tstposfw          | 0              |
      | 2        | pos2      | 0        | tstposfw          | 0              |

    # Run the sync, all should be good.
    When I navigate to "External Database" node in "Site administration > HR Import > Sources > Position"
    And I press "Save changes"
    Then I should see "Settings saved"
    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"

    # Update the source database settings to set a non existent table name.
    When I navigate to "External Database" node in "Site administration > HR Import > Sources > Position"
    And I set the following fields to these values:
      | Database table | behat_totara_sync_source_pos_Does_not_exist |
    And I press "Save changes"
    Then I should see "Settings saved"

    # Run the sync, we expect an error.
    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Error:pos - Remote database table \"behat_totara_sync_source_pos_Does_not_exist\" does not exist"
    And I should see "Running HR Import cron...Done! However, there have been some problems"

  Scenario: Test table fields exist checks for the position database source
    # Create the external database.
    Given the following "position" HR Import database source exists:
      | idnumber | fullname  | deleted  | frameworkidnumber | timemodified   |
      | 1        | pos1      | 0        | tstposfw          | 0              |
      | 2        | pos2      | 0        | tstposfw          | 0              |

    # Run the sync, all should be good.
    When I navigate to "External Database" node in "Site administration > HR Import > Sources > Position"
    And I press "Save changes"
    Then I should see "Settings saved"
    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"

    # Update the source database settings to map to non existent table fields.
    When I navigate to "External Database" node in "Site administration > HR Import > Sources > Position"
    And I set the following fields to these values:
      | idnumber          | idnumber_does_not_exist          |
      | fullname          | fullname_does_not_exist          |
      | deleted           | deleted_does_not_exist           |
      | frameworkidnumber | frameworkidnumber_does_not_exist |
    And I press "Save changes"
    Then I should see "Settings saved"

    # Run the sync, we expect an error.
    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Error:pos - Remote database table does not contain field(s) \"idnumber_does_not_exist, fullname_does_not_exist, deleted_does_not_exist, frameworkidnumber_does_not_exist\""
    And I should see "Running HR Import cron...Done! However, there have been some problems"
