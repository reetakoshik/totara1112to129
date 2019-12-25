@totara @totara_core @totara_core_select
Feature: Test primary select region element

  @javascript
  Scenario: The primary select region element should behave correctly
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to the "select_region_primary" fixture in the "totara/core" plugin
    Then I should see "Test tree list title 1"
    And I should see "Level 4"
    And I should see "Test full text search 1"
