@totara @totara_core @totara_core_menu @javascript
Feature: Main menu advanced add items tests
  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Main menu" node in "Site administration > Navigation"

  Scenario: Add custom items into Main menu
    Given I press "Add new menu item"
    And I should see the following Totara form fields having these values:
      | Type              | URL                           |
    And I should see Totara form label "Menu url address"
    And I should see Totara form label "Open link in new window"
    When I set the following Totara form fields to these values:
      | Type              | Parent                        |
    Then I should not see Totara form label "Menu url address"
    And I should not see Totara form label "Open link in new window"
    And the "Parent item" select box should contain "Top"
    And the "Parent item" select box should contain "Performance"
    And the "Parent item" select box should contain "Find Learning (Legacy catalogues)"
    And the "Parent item" select box should contain "Unused"

    When I press "Add"
    Then I should see "There are required fields in this form marked"

    When I set the following Totara form fields to these values:
      | Parent item       | Top                           |
      | Menu title        | Top container                 |
      | Visibility        | Show                          |
    And I press "Add"
    Then I should see "Main menu has been updated successfully"
    And "Top container" row "Visibility" column of "totaramenutable" table should contain "Show"
    And I should not see "Top container" in the totara menu

    When I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | Parent                        |
      | Parent item       | Top container                 |
      | Menu title        | Sub container                 |
      | Visibility        | Show                          |
    And I press "Add"
    Then I should see "Main menu has been updated successfully"
    And "Sub container" row "Visibility" column of "totaramenutable" table should contain "Show"
    And I should not see "Top container" in the totara menu
    And I should not see "Sub container" in the totara menu

    When I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | Parent                        |
      | Parent item       | Top container / Sub container |
      | Menu title        | Sub sub container             |
      | Visibility        | Show                          |
    And I press "Add"
    Then I should see "Main menu has been updated successfully"
    And "Sub sub container" row "Visibility" column of "totaramenutable" table should contain "Hidden: menu limit exceeded"
    And I should not see "Top container" in the totara menu
    And I should not see "Sub container" in the totara menu
    And I should not see "Sub sub container" in the totara menu

    When I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | Parent                        |
      | Parent item       | Unused                        |
      | Menu title        | Unused container              |
      | Visibility        | Show                          |
    And I press "Add"
    Then I should see "Main menu has been updated successfully"
    And "Unused container" row "Visibility" column of "totaramenutable" table should contain "Unused"
    And I should not see "Top container" in the totara menu
    And I should not see "Sub container" in the totara menu
    And I should not see "Sub sub container" in the totara menu
    And I should not see "Unused container" in the totara menu

    When I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | Parent                        |
      | Parent item       | Top                           |
      | Menu title        | Hidden container              |
      | Visibility        | Hide                          |
    And I press "Add"
    Then I should see "Main menu has been updated successfully"
    And "Hidden container" row "Visibility" column of "totaramenutable" table should contain "Hide"
    And I should not see "Top container" in the totara menu
    And I should not see "Sub container" in the totara menu
    And I should not see "Sub sub container" in the totara menu
    And I should not see "Hidden container" in the totara menu

    When I press "Add new menu item"
    Then I should see the following Totara form fields having these values:
      | Type              | URL                           |
      | Open link in new window |0                        |
    And I should see Totara form label "Menu url address"
    And I should see Totara form label "Open link in new window"
    And the "Parent item" select box should contain "Top"
    And the "Parent item" select box should contain "Performance"
    And the "Parent item" select box should contain "Find Learning (Legacy catalogues)"
    And the "Parent item" select box should contain "Top container"
    And the "Parent item" select box should contain "Top container / Sub container"
    And the "Parent item" select box should contain "Hidden container"
    And the "Parent item" select box should contain "Unused"
    And the "Parent item" select box should not contain "Top container / Sub container / Sub sub container"
    And the "Parent item" select box should not contain "Unused / Unused container"

    When I press "Add"
    Then I should see "There are required fields in this form marked"

    When I set the following Totara form fields to these values:
      | Parent item       | Top                           |
      | Menu title        | Top test item                 |
      | Visibility        | Show                          |
    And I press "Add"
    Then I should see "Form could not be submitted, validation failed"

    When I set the following Totara form fields to these values:
      | Parent item       | Top                           |
      | Menu title        | Top test item                 |
      | Visibility        | Show                          |
      | Menu url address  | xxx                           |
    And I press "Add"
    Then I should see "Form could not be submitted, validation failed"
    And I should see "Menu url address is invalid."

    When I set the following Totara form fields to these values:
      | Parent item       | Top                           |
      | Menu title        | Top test item                 |
      | Visibility        | Show                          |
      | Menu url address  | /xxx                          |
    And I press "Add"
    Then I should see "Main menu has been updated successfully"
    And "Top test item" row "Visibility" column of "totaramenutable" table should contain "Show"
    And "Top test item" row "URL address" column of "totaramenutable" table should contain "/xxx"
    And I should not see "Top container" in the totara menu
    And I should not see "Sub container" in the totara menu
    And I should not see "Sub sub container" in the totara menu
    And I should not see "Hidden container" in the totara menu
    And I should see "Top test item" in the totara menu

    When I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | URL                           |
      | Parent item       | Top container                 |
      | Menu title        | Sub test item                 |
      | Visibility        | Show                          |
      | Menu url address  | http://x.y.z/                 |
      | Open link in new window  | 0                      |
    And I press "Add"
    Then I should see "Main menu has been updated successfully"
    And "Sub test item" row "Visibility" column of "totaramenutable" table should contain "Show"
    And "Sub test item" row "URL address" column of "totaramenutable" table should contain "http://x.y.z/"
    And I should see "Top container" in the totara menu
    And I should not see "Sub container" in the totara menu
    And I should not see "Sub sub container" in the totara menu
    And I should not see "Hidden container" in the totara menu
    And I should see "Top test item" in the totara menu
    And I should see "Sub test item" in the totara menu

    When I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | URL                           |
      | Parent item       | Top container / Sub container |
      | Menu title        | Sub lower test item           |
      | Visibility        | Show                          |
      | Menu url address  | /yyyy                         |
      | Open link in new window  | 1                      |
    And I press "Add"
    Then I should see "Main menu has been updated successfully"
    And "Sub lower test item" row "Visibility" column of "totaramenutable" table should contain "Show"
    And "Sub lower test item" row "URL address" column of "totaramenutable" table should contain "/yyyy"
    And I should see "Top container" in the totara menu
    And I should see "Sub container" in the totara menu
    And I should not see "Sub sub container" in the totara menu
    And I should not see "Hidden container" in the totara menu
    And I should see "Top test item" in the totara menu
    And I should see "Sub test item" in the totara menu
    And I should see "Sub lower test item" in the totara menu

    When I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | URL                           |
      | Parent item       | Unused                        |
      | Menu title        | Unused test item              |
      | Visibility        | Show                          |
      | Menu url address  | /zzzz                         |
    And I press "Add"
    Then I should see "Main menu has been updated successfully"
    And "Unused test item" row "Visibility" column of "totaramenutable" table should contain "Unused"
    And "Unused test item" row "URL address" column of "totaramenutable" table should contain "/zzzz"
    And I should see "Top container" in the totara menu
    And I should see "Sub container" in the totara menu
    And I should not see "Sub sub container" in the totara menu
    And I should not see "Hidden container" in the totara menu
    And I should see "Top test item" in the totara menu
    And I should see "Sub test item" in the totara menu
    And I should see "Sub lower test item" in the totara menu
    And I should not see "Unused test item" in the totara menu

    When I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | URL                           |
      | Parent item       | Top                           |
      | Menu title        | Hidden test item              |
      | Visibility        | Hide                          |
      | Menu url address  | /aaaa                         |
    And I press "Add"
    Then I should see "Main menu has been updated successfully"
    And "Hidden test item" row "Visibility" column of "totaramenutable" table should contain "Hide"
    And "Hidden test item" row "URL address" column of "totaramenutable" table should contain "/aaaa"
    And I should see "Top container" in the totara menu
    And I should see "Sub container" in the totara menu
    And I should not see "Sub sub container" in the totara menu
    And I should not see "Hidden container" in the totara menu
    And I should see "Top test item" in the totara menu
    And I should see "Sub test item" in the totara menu
    And I should see "Sub lower test item" in the totara menu
    And I should not see "Unused test item" in the totara menu
    And I should not see "Hidden test item" in the totara menu

    When I press "Add new menu item"
    And I set the following Totara form fields to these values:
      | Type              | URL                           |
      | Parent item       | Hidden container              |
      | Menu title        | Test item in hidden container |
      | Visibility        | Show                          |
      | Menu url address  | /bbbb                         |
    And I press "Add"
    Then I should see "Main menu has been updated successfully"
    And "Test item in hidden container" row "Visibility" column of "totaramenutable" table should contain "Show"
    And "Test item in hidden container" row "URL address" column of "totaramenutable" table should contain "/bbbb"
    And I should see "Top container" in the totara menu
    And I should see "Sub container" in the totara menu
    And I should not see "Sub sub container" in the totara menu
    And I should not see "Hidden container" in the totara menu
    And I should see "Top test item" in the totara menu
    And I should see "Sub test item" in the totara menu
    And I should see "Sub lower test item" in the totara menu
    And I should not see "Unused test item" in the totara menu
    And I should not see "Test item in hidden container" in the totara menu
