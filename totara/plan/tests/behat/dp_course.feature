@totara @totara_plan @report @javascript
Feature: Check the 'Record of Learning: Courses' report displays content correctly.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | user001  | fn_001    | ln_001   | user001@example.com |
      | user002  | fn_002    | ln_002   | user002@example.com |
      | mana003  | fn_003    | ln_003   | user003@example.com |
    And the following "courses" exist:
      | fullname      | shortname   |
      | Test Course 1 | testcourse1 |
      | Test Course 2 | testcourse2 |
    And the following job assignments exist:
      | user    | fullname       | manager |
      | user001 | jobassignment1 | mana003 |
      | user002 | jobassignment2 | mana003 |
    And the following "plans" exist in "totara_plan" plugin:
      | user    | name            |
      | user001 | Learning Plan 1 |
      | user001 | Learning Plan 2 |

  Scenario: Check that rows are only duplicated for each learning plan when there are plan related columns added to the report
    Given I log in as "user001"

    # Add course to plan 1.
    When I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I click on "Learning Plan 1" "link"
    And I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I click on "Add courses" "button"
    And I click on "Miscellaneous" "link"
    And I click on "Test Course 1" "link"
    And I click on "Save" "button" in the "Add courses" "totaradialogue"
    Then I should see "Test Course 1" in the "#dp-component-update-table" "css_element"
    When I press "Send approval request"
    Then I should see "Approval request sent for plan \"Learning Plan 1\""

    # Add same course to plan 2.
    When I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I click on "Learning Plan 2" "link"
    And I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I click on "Add courses" "button"
    And I click on "Miscellaneous" "link"
    And I click on "Test Course 1" "link"
    And I click on "Save" "button" in the "Add courses" "totaradialogue"
    Then I should see "Test Course 1" in the "#dp-component-update-table" "css_element"
    When I press "Send approval request"
    Then I should see "Approval request sent for plan \"Learning Plan 2\""
    And I log out

    # Login as the manager and approve both plans.
    When I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "Plans" "link" in the "1" "table_row"
    And I follow "Learning Plan 1"
    And I press "Approve"
    Then I should see "Plan \"Learning Plan 1\" has been approved by fn_003 ln_003"
    When I click on "Team" in the totara menu
    And I click on "Plans" "link" in the "1" "table_row"
    And I follow "Learning Plan 2"
    And I press "Approve"
    Then I should see "Plan \"Learning Plan 2\" has been approved by fn_003 ln_003"
    And I log out

    # Login as the user and check the course report shows both plans, duplicating the course.
    When I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Record of Learning : All Courses"
    And I should see "2 records shown"
    And I should see "Test Course 1" in the "Learning Plan 1" "table_row"
    And I should see "Test Course 1" in the "Learning Plan 2" "table_row"
    And I log out

    # As admin, update the report to remove all Plan columns.
    When I log in as "admin"
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I click on "Record of Learning: Courses" "link"
    And I switch to "Columns" tab
    And I delete the "Plan name (linked to plan page)" column from the report
    And I delete the "Course due date" column from the report
    And I delete the "Progress (and approval status)" column from the report
    Then I log out

    # Login as the user and check the course report shows just one row.
    When I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Record of Learning : All Courses"
    And I should see "1 record shown"
    And I should see "Test Course 1"
