@totara @tool @tool_totara_sync @javascript
Feature: Verify configuration error handling in HR Import.

  Background:
    Given I am on a totara site
    And I log in as "admin"

  Scenario: Verify configuring CSV encounters file path configuration error.

    Given I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    When I click on "Enable" "link" in the "User" "table_row"
    And I click on "Settings" "link" in the "User" "table_row"
    Then I should see "User element settings"

    When I set the field "CSV" to "1"
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    When I follow "configured here"
    Then I should see "User - CSV source settings"

    When I press "Save changes"
    Then I should see "Settings saved"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    Then I should see "No HR Import files directory configured"
    And I should see "HR Import is not configured properly. Please, fix the issues before running."

  Scenario: Verify configuring database does not encounter file path configuration error.

    Given I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    When I click on "Enable" "link" in the "User" "table_row"
    And I click on "Settings" "link" in the "User" "table_row"
    Then I should see "User element settings"

    When I set the field "External Database" to "1"
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    Given the following "user" HR Import database source exists:
      | idnumber | username | firstname | lastname | email                     | deleted | timemodified |
      | 1        | learner1 | Bob1      | Learner1 | bob1.learner1@example.com | 0       | 0            |

    When I follow "configured here"
    Then I should see "User - external database source settings"

    When I press "Save changes"
    Then I should see "Settings saved"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done"
    And I should see "View the results in the HR Import Log here"
