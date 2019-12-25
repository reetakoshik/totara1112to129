@totara @totara_core @totara_core_menu @javascript
Feature: Main menu quick show and hide tests
  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Main menu" node in "Site administration > Navigation"

  Scenario: Test show and hide icons for Main menu work
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
      | Visibility        | Hide                          |
    And I press "Add"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | Parent                        |
      | Parent item       | Top                           |
      | Menu title        | Container 3                   |
      | Visibility        | Use custom access rules       |
    And I press "Add"
    And I press "Cancel"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | Parent                        |
      | Parent item       | Unused                        |
      | Menu title        | Container 4                   |
      | Visibility        | Show                          |
    And I press "Add"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | URL                           |
      | Parent item       | Top                           |
      | Menu title        | Test item 1                   |
      | Visibility        | Show                          |
      | Menu url address  | /xxx                          |
    And I press "Add"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | URL                           |
      | Parent item       | Top                           |
      | Menu title        | Test item 2                   |
      | Visibility        | Hide                          |
      | Menu url address  | /xxx                          |
    And I press "Add"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | URL                           |
      | Parent item       | Top                           |
      | Menu title        | Test item 3                   |
      | Visibility        | Use custom access rules       |
      | Menu url address  | /xxx                          |
    And I press "Add"
    And I press "Cancel"
    And I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | URL                           |
      | Parent item       | Unused                        |
      | Menu title        | Test item 4                   |
      | Visibility        | Show                          |
      | Menu url address  | /xxx                          |
    And I press "Add"
    Then "Container 1" row "Visibility" column of "totaramenutable" table should contain "Show"
    And "Container 1" row "Edit" column of "totaramenutable" table should not contain "Show"
    And "Container 1" row "Edit" column of "totaramenutable" table should contain "Hide"
    And "Container 2" row "Visibility" column of "totaramenutable" table should contain "Hide"
    And "Container 2" row "Edit" column of "totaramenutable" table should contain "Show"
    And "Container 2" row "Edit" column of "totaramenutable" table should not contain "Hide"
    And "Container 3" row "Visibility" column of "totaramenutable" table should contain "Use custom access rules"
    And "Container 4" row "Visibility" column of "totaramenutable" table should contain "Unused"
    And "Container 4" row "Edit" column of "totaramenutable" table should not contain "Show"
    And "Container 4" row "Edit" column of "totaramenutable" table should not contain "Hide"
    And "Test item 1" row "Visibility" column of "totaramenutable" table should contain "Show"
    And "Test item 2" row "Visibility" column of "totaramenutable" table should contain "Hide"
    And "Test item 3" row "Visibility" column of "totaramenutable" table should contain "Use custom access rules"
    And "Test item 4" row "Visibility" column of "totaramenutable" table should contain "Unused"
    And "Test item 4" row "Edit" column of "totaramenutable" table should not contain "Show"
    And "Test item 4" row "Edit" column of "totaramenutable" table should not contain "Hide"

    When I click on "Hide" "link" in the "Container 1" "table_row"
    And "Container 1" row "Visibility" column of "totaramenutable" table should contain "Hide"
    And I click on "Edit" "link" in the "Container 1" "table_row"
    And I should see the following Totara form fields having these values:
      | Visibility        | Hide                          |
    And I press "Cancel"
    And I click on "Show" "link" in the "Container 1" "table_row"
    Then "Container 1" row "Visibility" column of "totaramenutable" table should contain "Show"
    And I click on "Edit" "link" in the "Container 1" "table_row"
    And I should see the following Totara form fields having these values:
      | Visibility        | Show                          |
    When I set the following Totara form fields to these values:
      | Visibility        | Use custom access rules       |
    And I press "Save changes"
    And I press "Cancel"
    And "Container 1" row "Visibility" column of "totaramenutable" table should contain "Use custom access rules"
    And I click on "Hide" "link" in the "Container 1" "table_row"
    And "Container 1" row "Visibility" column of "totaramenutable" table should contain "Hide"
    And I click on "Edit" "link" in the "Container 1" "table_row"
    And I should see the following Totara form fields having these values:
      | Visibility        | Hide                          |
    And I press "Cancel"
    And I click on "Show" "link" in the "Container 1" "table_row"
    Then "Container 1" row "Visibility" column of "totaramenutable" table should contain "Use custom access rules"
    And I click on "Edit" "link" in the "Container 1" "table_row"
    And I should see the following Totara form fields having these values:
      | Visibility        | Use custom access rules       |
    And I press "Cancel"

    When I click on "Show" "link" in the "Test item 2" "table_row"
    And "Test item 2" row "Visibility" column of "totaramenutable" table should contain "Show"
    And I click on "Edit" "link" in the "Test item 2" "table_row"
    And I should see the following Totara form fields having these values:
      | Visibility        | Show                          |
    And I press "Cancel"
    And I click on "Hide" "link" in the "Test item 2" "table_row"
    Then "Test item 2" row "Visibility" column of "totaramenutable" table should contain "Hide"
    And I click on "Edit" "link" in the "Test item 2" "table_row"
    And I should see the following Totara form fields having these values:
      | Visibility        | Hide                          |
    And I press "Cancel"

    When I click on "Hide" "link" in the "Test item 3" "table_row"
    And "Test item 3" row "Visibility" column of "totaramenutable" table should contain "Hide"
    And I click on "Edit" "link" in the "Test item 3" "table_row"
    And I should see the following Totara form fields having these values:
      | Visibility        | Hide                          |
    And I press "Cancel"
    And I click on "Show" "link" in the "Test item 3" "table_row"
    Then "Test item 3" row "Visibility" column of "totaramenutable" table should contain "Use custom access rules"
    And I click on "Edit" "link" in the "Test item 3" "table_row"
    And I should see the following Totara form fields having these values:
      | Visibility        | Use custom access rules       |
    And I press "Cancel"
    And I click on "Hide" "link" in the "Test item 3" "table_row"
    And "Test item 3" row "Visibility" column of "totaramenutable" table should contain "Hide"
    And I click on "Show" "link" in the "Test item 3" "table_row"
    And "Test item 3" row "Visibility" column of "totaramenutable" table should contain "Use custom access rules"
