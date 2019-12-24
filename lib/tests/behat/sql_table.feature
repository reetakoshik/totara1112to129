@core @javascript
Feature: Expand and collapse sql table columns
  For columns to be hidden in a SQL table
  I need to be able to press the hide and show buttons

  Scenario: Hide and show columns in the tags page
    Given I log in as "admin"
    And the following "tags" exist:
        | name  |
        | One   |
        | Two   |
        | Three |
    And I navigate to "Manage tags" node in "Site administration > Appearance"
    And I click on "Default collection" "link"
    # Use an expanded name as there is also a select tag x in a different column.
    Then I should see "One"
    And I should see "Two"
    And I should see "Three"

    When I click on "Hide Tag name" "link"
    Then I should not see "One"
    And I should not see "Two"
    And I should not see "Three"

    When I click on "Show Tag name" "link"
    Then I should see "One"
    And I should see "Two"
    And I should see "Three"