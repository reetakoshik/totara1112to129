@totara @totara_core @totara_core_select
Feature: Test panel select region element

  @javascript
  Scenario: The panel select region element should behave correctly
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to the "select_region_panel" fixture in the "totara/core" plugin
    Then I should see "Test filter region panel main title"
    And I should see "(3)" in the "#region-main" "css_element"
    And I should see "Clear all"
    And I should see "Test multi select title 1"
    And I should see "Test full text search title"
    And I should see "Test tree list title 1"
    When I click on "Clear all" "link"
    Then I should not see "Clear all"
    And I should not see "(" in the "#region-main" "css_element"
    And I should not see ")" in the "#region-main" "css_element"
    And I should see "Test filter region panel main title"
    And the "aria-selected" attribute of "Test 1 option two" "link" should contain "false"
    And the field "Test full text search title" matches value ""
    And I should see "All"
    When I click on "Test 1 option two" "link"
    And I click on "Test 1 option three" "link"
    And I set the field "Test full text search title" to "asdf"
    And I press key "13" in the field "Test full text search title"
    And I click on "All" "link"
    And I click on "Plague" "link"
    Then I should see "(4)" in the "#region-main" "css_element"
    When I click on "Test 1 option two" "link"
    Then I should see "(3)" in the "#region-main" "css_element"
    When I click on "Please select colour" "link"
    And I click on "Rainbow" "link"
    And I click on "Green" "link"
    And I should not see "Please select colour"
    Then I should see "(4)" in the "#region-main" "css_element"
    When I click on "Clear all" "link"
    Then I should see "Please select colour"
