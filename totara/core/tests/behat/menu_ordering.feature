@totara @totara_core @totara_core_menu @javascript
Feature: Main menu ordering tests
  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Main menu" node in "Site administration > Navigation"

  Scenario: Test changing order of items in Main menu
    When I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | Parent                        |
      | Parent item       | Top                           |
      | Menu title        | Container 1                   |
      | Visibility        | Show                          |
    And I press "Add"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | Parent                        |
      | Parent item       | Top                           |
      | Menu title        | Container 2                   |
      | Visibility        | Show                          |
    And I press "Add"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | Parent                        |
      | Parent item       | Top                           |
      | Menu title        | Container 3                   |
      | Visibility        | Show                          |
    And I press "Add"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Parent item       | Container 1                   |
      | Menu title        | Test item 1-1                 |
      | Visibility        | Show                          |
      | Menu url address  | /xxx                          |
    And I press "Add"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Parent item       | Container 2                   |
      | Menu title        | Test item 2-1                 |
      | Visibility        | Show                          |
      | Menu url address  | /xxx                          |
    And I press "Add"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Parent item       | Container 2                   |
      | Menu title        | Test item 2-2                 |
      | Visibility        | Show                          |
      | Menu url address  | /xxx                          |
    And I press "Add"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Parent item       | Container 3                   |
      | Menu title        | Test item 3-1                 |
      | Visibility        | Show                          |
      | Menu url address  | /xxx                          |
    And I press "Add"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Parent item       | Container 3                   |
      | Menu title        | Test item 3-2                 |
      | Visibility        | Show                          |
      | Menu url address  | /xxx                          |
    And I press "Add"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Parent item       | Container 3                   |
      | Menu title        | Test item 3-3                 |
      | Visibility        | Show                          |
      | Menu url address  | /xxx                          |
    And I press "Add"
    Then "Container 1" "table_row" should appear before "Test item 1-1" "table_row"
    And I should see "Move up" in the "Container 1" "table_row"
    And I should see "Move down" in the "Container 1" "table_row"
    And "Test item 1-1" "table_row" should appear before "Container 2" "table_row"
    And I should not see "Move up" in the "Test item 1-1" "table_row"
    And I should not see "Move down" in the "Test item 1-1" "table_row"
    And "Container 2" "table_row" should appear before "Test item 2-1" "table_row"
    And I should see "Move up" in the "Container 2" "table_row"
    And I should see "Move down" in the "Container 2" "table_row"
    And "Test item 2-1" "table_row" should appear before "Test item 2-2" "table_row"
    And I should not see "Move up" in the "Test item 2-1" "table_row"
    And I should see "Move down" in the "Test item 2-1" "table_row"
    And "Test item 2-2" "table_row" should appear before "Container 3" "table_row"
    And I should see "Move up" in the "Test item 2-2" "table_row"
    And I should not see "Move down" in the "Test item 2-2" "table_row"
    And "Container 3" "table_row" should appear before "Test item 3-1" "table_row"
    And I should see "Move up" in the "Container 3" "table_row"
    And I should not see "Move down" in the "Container 3" "table_row"
    And "Test item 3-1" "table_row" should appear before "Test item 3-2" "table_row"
    And I should not see "Move up" in the "Test item 3-1" "table_row"
    And I should see "Move down" in the "Test item 3-1" "table_row"
    And "Test item 3-2" "table_row" should appear before "Test item 3-3" "table_row"
    And I should see "Move up" in the "Test item 3-2" "table_row"
    And I should see "Move down" in the "Test item 3-2" "table_row"
    And "Test item 3-3" "table_row" should appear before "Unused" "table_row"
    And I should see "Move up" in the "Test item 3-3" "table_row"
    And I should not see "Move down" in the "Test item 3-3" "table_row"

    When I click on "Move down" "link" in the "Test item 2-1" "table_row"
    Then "Container 1" "table_row" should appear before "Test item 1-1" "table_row"
    And "Test item 1-1" "table_row" should appear before "Container 2" "table_row"
    And "Container 2" "table_row" should appear before "Test item 2-2" "table_row"
    And "Test item 2-2" "table_row" should appear before "Test item 2-1" "table_row"
    And "Test item 2-1" "table_row" should appear before "Container 3" "table_row"
    And "Container 3" "table_row" should appear before "Test item 3-1" "table_row"
    And "Test item 3-1" "table_row" should appear before "Test item 3-2" "table_row"
    And "Test item 3-2" "table_row" should appear before "Test item 3-3" "table_row"
    And "Test item 3-3" "table_row" should appear before "Unused" "table_row"

    When I click on "Move up" "link" in the "Test item 2-1" "table_row"
    Then "Container 1" "table_row" should appear before "Test item 1-1" "table_row"
    And "Test item 1-1" "table_row" should appear before "Container 2" "table_row"
    And "Container 2" "table_row" should appear before "Test item 2-1" "table_row"
    And "Test item 2-1" "table_row" should appear before "Test item 2-2" "table_row"
    And "Test item 2-2" "table_row" should appear before "Container 3" "table_row"
    And "Container 3" "table_row" should appear before "Test item 3-1" "table_row"
    And "Test item 3-1" "table_row" should appear before "Test item 3-2" "table_row"
    And "Test item 3-2" "table_row" should appear before "Test item 3-3" "table_row"
    And "Test item 3-3" "table_row" should appear before "Unused" "table_row"

    When I click on "Move down" "link" in the "Test item 3-1" "table_row"
    Then "Container 1" "table_row" should appear before "Test item 1-1" "table_row"
    And "Test item 1-1" "table_row" should appear before "Container 2" "table_row"
    And "Container 2" "table_row" should appear before "Test item 2-1" "table_row"
    And "Test item 2-1" "table_row" should appear before "Test item 2-2" "table_row"
    And "Test item 2-2" "table_row" should appear before "Container 3" "table_row"
    And "Container 3" "table_row" should appear before "Test item 3-2" "table_row"
    And "Test item 3-2" "table_row" should appear before "Test item 3-1" "table_row"
    And "Test item 3-1" "table_row" should appear before "Test item 3-3" "table_row"
    And "Test item 3-3" "table_row" should appear before "Unused" "table_row"

    When I click on "Move down" "link" in the "Test item 3-1" "table_row"
    Then "Container 1" "table_row" should appear before "Test item 1-1" "table_row"
    And "Test item 1-1" "table_row" should appear before "Container 2" "table_row"
    And "Container 2" "table_row" should appear before "Test item 2-1" "table_row"
    And "Test item 2-1" "table_row" should appear before "Test item 2-2" "table_row"
    And "Test item 2-2" "table_row" should appear before "Container 3" "table_row"
    And "Container 3" "table_row" should appear before "Test item 3-2" "table_row"
    And "Test item 3-2" "table_row" should appear before "Test item 3-3" "table_row"
    And "Test item 3-3" "table_row" should appear before "Test item 3-1" "table_row"
    And "Test item 3-1" "table_row" should appear before "Unused" "table_row"

    When I click on "Move up" "link" in the "Test item 3-1" "table_row"
    Then "Container 1" "table_row" should appear before "Test item 1-1" "table_row"
    And "Test item 1-1" "table_row" should appear before "Container 2" "table_row"
    And "Container 2" "table_row" should appear before "Test item 2-1" "table_row"
    And "Test item 2-1" "table_row" should appear before "Test item 2-2" "table_row"
    And "Test item 2-2" "table_row" should appear before "Container 3" "table_row"
    And "Container 3" "table_row" should appear before "Test item 3-2" "table_row"
    And "Test item 3-2" "table_row" should appear before "Test item 3-1" "table_row"
    And "Test item 3-1" "table_row" should appear before "Test item 3-3" "table_row"
    And "Test item 3-3" "table_row" should appear before "Unused" "table_row"

    When I click on "Move up" "link" in the "Test item 3-1" "table_row"
    Then "Container 1" "table_row" should appear before "Test item 1-1" "table_row"
    And "Test item 1-1" "table_row" should appear before "Container 2" "table_row"
    And "Container 2" "table_row" should appear before "Test item 2-1" "table_row"
    And "Test item 2-1" "table_row" should appear before "Test item 2-2" "table_row"
    And "Test item 2-2" "table_row" should appear before "Container 3" "table_row"
    And "Container 3" "table_row" should appear before "Test item 3-1" "table_row"
    And "Test item 3-1" "table_row" should appear before "Test item 3-2" "table_row"
    And "Test item 3-2" "table_row" should appear before "Test item 3-3" "table_row"
    And "Test item 3-3" "table_row" should appear before "Unused" "table_row"

    When I click on "Move down" "link" in the "Container 1" "table_row"
    And "Container 2" "table_row" should appear before "Test item 2-1" "table_row"
    And "Test item 2-1" "table_row" should appear before "Test item 2-2" "table_row"
    And "Test item 2-2" "table_row" should appear before "Container 1" "table_row"
    Then "Container 1" "table_row" should appear before "Test item 1-1" "table_row"
    And "Test item 1-1" "table_row" should appear before "Container 3" "table_row"
    And "Container 3" "table_row" should appear before "Test item 3-1" "table_row"
    And "Test item 3-1" "table_row" should appear before "Test item 3-2" "table_row"
    And "Test item 3-2" "table_row" should appear before "Test item 3-3" "table_row"
    And "Test item 3-3" "table_row" should appear before "Unused" "table_row"
