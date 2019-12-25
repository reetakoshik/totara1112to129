@totara @totara_reportbuilder @javascript
Feature: Statistics columns in user reports should show data correctly
  The user report source contains several columns
  under the heading statistics
  which should be displayed and ordered correctly.

  Scenario: The Users Course Completed Count column is ordered correctly
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname  | lastname  | email                |
      | learner1 | Learner1   | One       | learner1@example.com |
      | learner2 | Learner2   | Two       | learner2@example.com |
      | learner3 | Learner3   | Three     | learner3@example.com |
      | learner4 | Learner4   | Four      | learner4@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
      | Course 2 | C2        | 1                |
      | Course 3 | C3        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | C1     | student |
      | learner1 | C2     | student |
      | learner1 | C3     | student |
      | learner2 | C1     | student |
      | learner2 | C2     | student |
      | learner2 | C3     | student |
      | learner3 | C1     | student |
      | learner3 | C2     | student |
      | learner4 | C1     | student |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I click on "Condition: Manual completion by others" "link"
    And I set the following fields to these values:
      | Site Manager | 1 |
    And I press "Save changes"
    And I navigate to "Course completion" node in "Course administration > Reports"
    And I complete the course via rpl for "Learner1 One" with text "RPL"
    And I complete the course via rpl for "Learner2 Two" with text "RPL"
    And I complete the course via rpl for "Learner3 Three" with text "RPL"
    And I complete the course via rpl for "Learner4 Four" with text "RPL"
    And I am on "Course 2" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I click on "Condition: Manual completion by others" "link"
    And I set the following fields to these values:
      | Site Manager | 1 |
    And I press "Save changes"
    And I navigate to "Course completion" node in "Course administration > Reports"
    And I complete the course via rpl for "Learner1 One" with text "RPL"
    And I complete the course via rpl for "Learner2 Two" with text "RPL"
    And I complete the course via rpl for "Learner3 Three" with text "RPL"
    And I am on "Course 3" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I click on "Condition: Manual completion by others" "link"
    And I set the following fields to these values:
      | Site Manager | 1 |
    And I press "Save changes"
    And I navigate to "Course completion" node in "Course administration > Reports"
    And I complete the course via rpl for "Learner1 One" with text "RPL"
    And I complete the course via rpl for "Learner2 Two" with text "RPL"
    When I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | User Report |
      | Source      | User        |
    And I click on "Create report" "button"
    And I set the following fields to these values:
      | Number of records per page | 3 |
    And I press "Save changes"
    And I switch to "Columns" tab
    And I add the "User's Courses Completed Count" column to the report
    And I click on "View This Report" "link"
    And I click on "User's Courses Completed Count" "link"

    # As such, we're not testing sort order precisely. But ensuring that 0 values are not
    # appearing as if they were the highest value (as was happening previously).
    # We've set there to be 3 records per page, so if we see 0, but don't see 3, then we don't have that bug.
    Then I should see "0" in the "Admin User" "table_row"
    And I should see "0" in the "Guest user" "table_row"
    And I should see "1" in the "Learner4 Four" "table_row"
    And I should not see "Learner1 One" in the ".reportbuilder-table" "css_element"
    And I should not see "Learner2 Two" in the ".reportbuilder-table" "css_element"
    And I should not see "Learner3 Three" in the ".reportbuilder-table" "css_element"

    # Now sort the column in descending order.
    When I click on "User's Courses Completed Count" "link"
    Then I should see "3" in the "Learner1 One" "table_row"
    And I should see "3" in the "Learner2 Two" "table_row"
    And I should see "2" in the "Learner3 Three" "table_row"
    And I should not see "Admin User" in the ".reportbuilder-table" "css_element"
    And I should not see "Guest user" in the ".reportbuilder-table" "css_element"
    And I should not see "Learner4 Four" in the ".reportbuilder-table" "css_element"
