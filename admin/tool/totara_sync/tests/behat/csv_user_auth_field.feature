@totara @tool @tool_totara_sync @javascript
Feature: Test the user csv auth field import.

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
      | CSV | 1 |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

  Scenario: Test user auth field import has case sensitivity checks using csv source

    # Setup HR Import
    Given I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    When I click on "Auth" "checkbox"
    And I press "Save changes"
    Then I should see "Settings saved"
    And I should see "\"auth\""

    # Run sync, creating users.
    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/user_auth_1.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"
    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done! However, there have been some problems"
    When I navigate to "HR Import Log" node in "Site administration > HR Import"
    Then I should see "created user 1"
    And I should see "invalid authentication plugin MANUAL for user 2"
    And I should see "Auth cannot be empty. Skipped user 3"
    And I should see "created user 4"
    And I should see "invalid authentication plugin Shibboleth for user 5"
    And I should see "invalid authentication plugin DOES-NOT-EXIST for user 6"
    When I press "Clear all records"
    And I press "Continue"

    # Check there are no errors in user profiles.
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I should see "Upload User 1"
    And I should not see "Upload User 2"
    And I should not see "Upload User 3"
    And I should see "Upload User 4"
    And I should not see "Upload User 6"
    And I follow "Upload User 1"
    And I follow "Edit profile"
    Then I should see "Upload User 1"
    And I should see "Manual accounts"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Upload User 4"
    And I follow "Edit profile"
    Then I should see "Upload User 4"
    And I should see "Shibboleth"

    # Run sync, updating users.
    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/user_auth_1.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"
    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done! However, there have been some problems"
    When I navigate to "HR Import Log" node in "Site administration > HR Import"
    Then I should see "updated user 1"
    And I should see "invalid authentication plugin MANUAL for user 2"
    And I should see "Auth cannot be empty. Skipped user 3"
    And I should see "updated user 4"
    And I should see "invalid authentication plugin Shibboleth for user 5"
    And I should see "invalid authentication plugin DOES-NOT-EXIST for user 6"

    # Check there are no errors in user profiles.
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I should see "Upload User 1"
    And I should not see "Upload User 2"
    And I should not see "Upload User 3"
    And I should see "Upload User 4"
    And I should not see "Upload User 6"
    And I follow "Upload User 1"
    And I follow "Edit profile"
    Then I should see "Upload User 1"
    And I should see "Manual accounts"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Upload User 4"
    And I follow "Edit profile"
    Then I should see "Upload User 4"
    And I should see "Shibboleth"

  Scenario: Check user auth field can be imported successfully using csv source

    # Setup HR Import
    When I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    And I click on "Auth" "checkbox"
    And I press "Save changes"
    Then I should see "Settings saved"
    And I should see "\"auth\""

    # Run sync, creating users.
    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/user_auth_2.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"
    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"
    And I should not see "However, there have been some problems"
    When I navigate to "HR Import Log" node in "Site administration > HR Import"
    Then I should see "created user 1"
    And I should see "created user 2"
    When I press "Clear all records"
    And I press "Continue"

    # Check there are no errors in user profiles.
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Upload User 1"
    And I follow "Edit profile"
    Then I should see "Upload User 1"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Upload User 2"
    And I follow "Edit profile"
    Then I should see "Upload User 2"

    # Run sync, updating users.
    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/user_auth_2.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"
    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"
    And I should not see "However, there have been some problems"
    When I navigate to "HR Import Log" node in "Site administration > HR Import"
    Then I should see "updated user 1"
    And I should see "updated user 2"

    # Check there are no errors in user profiles.
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Upload User 1"
    And I follow "Edit profile"
    Then I should see "Upload User 1"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Upload User 2"
    And I follow "Edit profile"
    Then I should see "Upload User 2"
