@totara @totara_core
Feature: Test grid element

  @javascript
  Scenario: The grid element should behave correctly
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to the "grid" fixture in the "totara/core" plugin
    Then I should see "Grid 1 Tile 1"
    And I should see "Grid 1 Tile 7"
    And I should see "Grid 2 Tile 1"
    And I should see "Grid 2 Tile 3"
