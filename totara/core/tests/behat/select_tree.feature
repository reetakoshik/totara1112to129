@totara @totara_core @totara_core_select
Feature: Test tree select element

  @javascript
  Scenario: The tree select element should behave correctly
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to the "select_tree" fixture in the "totara/core" plugin
    Then I should see "Test tree list title 1"
    And I should see "Level 4"
    And I should not see "All"
    And I should not see "Test tree list title 2 single level"
    And I should see "Self Combustion"
    And I should not see "Earthquake"
    And I should see "Test tree list title 3 no selection"
    And I should see "Volcano"
    And I should not see "Heatwave"
    When I click on "Level 4" "link"
    Then I should see "All"
    And I should see "Level 3"
    When I click on "Flooding" "link"
    Then I should see "Flooding"
    When I click on "Flooding" "link"
    Then I should not see "Level 4"
    And I should see "All"
    And I should see "Self Combustion"
    And I should see "Earthquake"
    Then I click on "Earthquake" "link"

    # Call to action text
    Then I should see "Please select colour"
    When I click on "Please select colour" "text"
    Then I should see "Blue"
    When I click on "Blue" "link"
    Then I should not see "Please select colour"

    # Toggle on tree label
    When I click on "Blue" "text"
    Then I should see "Rainbow"
    When I click on "Rainbow" "link"
    Then I should see "Yellow"
    When I click on "Yellow" "link"
    Then I should not see "Rainbow"

    # Close popup
    When I click on "Test tree" "text"
    Then I should not see "Level 2"
    And I should not see "All"
    And I should not see "Level 4"
