@totara @totara_core @totara_core_select
Feature: Test multi-select element

  @javascript
  Scenario: The multi-select element should behave correctly
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to the "select_multi" fixture in the "totara/core" plugin
    Then I should see "Test multi select title 1"
    And I should see "Test multi select title 2"
    And I should not see "Test multi select title 3"
    And the "aria-selected" attribute of "Test 2 option one" "link" should contain "true"
    And the "aria-selected" attribute of "Test 2 option two" "link" should contain "false"
    And the "aria-selected" attribute of "Test 2 option three" "link" should contain "true"
    When I click on "Test 1 option two" "link"
    And I click on "Test 1 option three" "link"
    Then the "aria-selected" attribute of "Test 1 option one" "link" should contain "false"
    And the "aria-selected" attribute of "Test 2 option two" "link" should contain "false"
    And the "aria-selected" attribute of "Test 1 option three" "link" should contain "true"
