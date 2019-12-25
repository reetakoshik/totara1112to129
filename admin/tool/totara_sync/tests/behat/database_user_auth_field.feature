@totara @tool @tool_totara_sync @javascript
Feature: Test the user database auth field import.

  Background:
    Given I am on a totara site
    When I log in as "admin"
    And I navigate to "General settings" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | File Access | Upload Files |
    And I press "Save changes"
    And I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "User" HR Import element
    And I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | Source | External Database |
    And I press "Save changes"
    Then I should see "Settings saved"

  Scenario: Test user auth field import has case sensitivity checks using database source

    # Create external database with our testing content.
    Given the following "user" HR Import database source exists:
      | idnumber | username  | firstname  | lastname  | email             | deleted | timemodified | auth           |
      | 1        | upload1   | Upload     | User 1    | upload1@email.com | 0       |0             | manual         |
      | 2        | upload2   | Upload     | User 2    | upload2@email.com | 0       |0             | MANUAL         |
      | 3        | upload3   | Upload     | User 3    | upload3@email.com | 0       |0             |                |
      | 4        | upload4   | Upload     | User 4    | upload4@email.com | 0       |0             | shibboleth     |
      | 5        | upload5   | Upload     | User 5    | upload5@email.com | 0       |0             | Shibboleth     |
      | 6        | upload6   | Upload     | User 6    | upload6@email.com | 0       |0             | DOES-NOT-EXIST |

    # Run sync, creating users.
    And I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done! However, there have been some problems"
    When I navigate to "HR Import Log" node in "Site administration > HR Import"
    Then I should see "created user 1"
    And I should see "invalid authentication plugin MANUAL for user 2"
    And I should see "cannot create user 3: invalid authentication plugin"
    And I should see "created user 4"
    And I should see "invalid authentication plugin Shibboleth for user 5"
    And I should see "invalid authentication plugin DOES-NOT-EXIST for user 6"
    When I press "Clear all records"
    And I press "Continue"

    # Check there are no errors in user profiles.
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I should see "Upload User 1"
    And I should not see "Upload User 2"
    And I should not see "Upload User 3"
    And I should see "Upload User 4"
    And I should not see "Upload User 6"
    And I follow "Upload User 1"
    And I follow "Edit profile"
    Then I should see "Upload User 1"
    And I should see "Manual accounts"
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Upload User 4"
    And I follow "Edit profile"
    Then I should see "Upload User 4"
    And I should see "Shibboleth"

    # Run sync, updating users.
    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done! However, there have been some problems"
    When I navigate to "HR Import Log" node in "Site administration > HR Import"
    Then I should see "updated user 1"
    And I should see "invalid authentication plugin MANUAL for user 2"
    And I should see "cannot create user 3: invalid authentication plugin"
    And I should see "updated user 4"
    And I should see "invalid authentication plugin Shibboleth for user 5"
    And I should see "invalid authentication plugin DOES-NOT-EXIST for user 6"

    # Check there are no errors in user profiles.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I should see "Upload User 1"
    And I should not see "Upload User 2"
    And I should not see "Upload User 3"
    And I should see "Upload User 4"
    And I should not see "Upload User 6"
    And I follow "Upload User 1"
    And I follow "Edit profile"
    Then I should see "Upload User 1"
    And I should see "Manual accounts"
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Upload User 4"
    And I follow "Edit profile"
    Then I should see "Upload User 4"
    And I should see "Shibboleth"

  Scenario: Check user auth field can be imported successfully using database source

    # Create external database with our testing content.
    Given the following "user" HR Import database source exists:
      | idnumber | username  | firstname  | lastname  | email             | deleted | timemodified | auth           |
      | 1        | upload1   | Upload     | User 1    | upload1@email.com | 0       |0             | manual         |
      | 2        | upload2   | Upload     | User 2    | upload2@email.com | 0       |0             | shibboleth     |

    # Run sync, creating users
    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"
    And I should not see "However, there have been some problems"
    When I navigate to "HR Import Log" node in "Site administration > HR Import"
    Then I should see "created user 1"
    And I should see "created user 2"
    And I press "Clear all records"
    And I press "Continue"

    # Check there are no errors in user profiles.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Upload User 1"
    And I follow "Edit profile"
    Then I should see "Upload User 1"
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Upload User 2"
    And I follow "Edit profile"
    Then I should see "Upload User 2"

    # Run sync, updating users
    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"
    And I should not see "However, there have been some problems"
    When I navigate to "HR Import Log" node in "Site administration > HR Import"
    Then I should see "updated user 1"
    And I should see "updated user 2"

    # Check there are no errors in user profiles.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Upload User 1"
    And I follow "Edit profile"
    Then I should see "Upload User 1"
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Upload User 2"
    And I follow "Edit profile"
    Then I should see "Upload User 2"
