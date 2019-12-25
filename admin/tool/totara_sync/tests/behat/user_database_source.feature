@totara @tool @tool_totara_sync @javascript
Feature: Test the user database source.

  Background:
    Given I am on a totara site
    When I log in as "admin"
    And I navigate to "Default settings" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | File access | Upload Files |
    And I press "Save changes"
    And I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "User" HR Import element
    And I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | External Database | 1 |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

  Scenario: Test table exists check for the user database source
    # Create the external database.
    Given the following "user" HR Import database source exists:
      | idnumber | username  | firstname  | lastname  | email             | deleted | timemodified |
      | 1        | upload1   | Upload     | User 1    | upload1@email.com | 0       |0             |
      | 2        | upload2   | Upload     | User 2    | upload2@email.com | 0       |0             |

    # Run the sync, all should be good.
    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"

    # Update the source database settings to set a non existent table name.
    When I navigate to "External Database" node in "Site administration > HR Import > Sources > User"
    And I set the following fields to these values:
      | Database table | behat_totara_sync_source_user_Does_not_exist |
    And I press "Save changes"
    Then I should see "Settings saved"

    # Run the sync, we expect an error.
    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Error:user - Remote database table \"behat_totara_sync_source_user_Does_not_exist\" does not exist"
    And I should see "Running HR Import cron...Done! However, there have been some problems"

  Scenario: Test table fields exist checks for the user database source
    # Create the external database.
    Given the following "user" HR Import database source exists:
      | idnumber | username  | firstname  | lastname  | email             | deleted | timemodified |
      | 1        | upload1   | Upload     | User 1    | upload1@email.com | 0       |0             |
      | 2        | upload2   | Upload     | User 2    | upload2@email.com | 0       |0             |

    # Run the sync, all should be good.
    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"

    # Update the source database settings to map to non existent table fields.
    When I navigate to "External Database" node in "Site administration > HR Import > Sources > User"
    And I set the following fields to these values:
      | idnumber      | idnumber_does_not_exist     |
      | timemodified  | timemodified_does_not_exist |
      | username      | username_does_not_exist     |
      | deleted       | deleted_does_not_exist      |
      | firstname     | firstname_does_not_exist    |
      | lastname      | lastname_does_not_exist     |
      | email         | email_does_not_exist        |
    And I press "Save changes"
    Then I should see "Settings saved"

    # Run the sync, we expect an error.
    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Error:user - Remote database table does not contain field(s) \"idnumber_does_not_exist, timemodified_does_not_exist, username_does_not_exist, deleted_does_not_exist, firstname_does_not_exist, lastname_does_not_exist, emailstop\""
    And I should see "Running HR Import cron...Done! However, there have been some problems"
