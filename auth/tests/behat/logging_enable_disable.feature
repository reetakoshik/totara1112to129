@core @core_auth @totara @javascript
Feature: Enable or disable the authentication method should fire the event and logged the action.
  Scenario: Enable authentication method should fire an event
    Given I log in as "admin"
    And I navigate to "Plugins > Authentication > Manage authentication" in site administration
    And I click on "Enable" "link" in the "Self-registration with approval" "table_row"
    And I navigate to "Server > Logs" in site administration
    When I click on "Get these logs" "button"
    Then I should see "Authentication methods updated"
    And I should see "enabled the authentication method: 'approved'"

  Scenario: Disable authentication method should fire an event
    Given I log in as "admin"
    And I navigate to "Plugins > Authentication > Manage authentication" in site administration
    And I click on "Enable" "link" in the "Self-registration with approval" "table_row"
    And I click on "Disable" "link" in the "Self-registration with approval" "table_row"
    And I navigate to "Server > Logs" in site administration
    When I click on "Get these logs" "button"
    Then I should see "Authentication methods updated" exactly "2" times
    And I should see "enabled the authentication method: 'approved'"
    And I should see "disabled the authentication method: 'approved'"