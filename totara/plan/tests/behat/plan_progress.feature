@totara @totara_plan @totara_program @totara_programprogressbar
Feature: Progress is shown for a learning plan
In order to see how far I have progressed towards completing a learning plan
As a learner
I should see my learning plan progress

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname  | lastname  | email                |
      | learner1 | firstname1 | lastname1 | learner1@example.com |
      | manager2 | firstname2 | lastname2 | manager2@example.com |
    And the following job assignments exist:
      | user     | fullname       | manager  |
      | learner1 | jobassignment1 | manager2 |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | Course 1  | 1                |
      | Course 2 | Course 2  | 1                |
      | Course 3 | Course 3  | 1                |
    And the following "programs" exist in "totara_program" plugin:
      | fullname  | shortname |
      | Program 1 | P1   |
      | Program 2 | P2   |
      | Program 3 | P3   |
    And the following "plans" exist in "totara_plan" plugin:
      | user     | name                   |
      | learner1 | learner1 Learning Plan |

  @javascript
  Scenario: Test progress displayed in learning plans

    # Login as the learner and navigate to the learning plan.
    Given I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I click on "learner1 Learning Plan" "link"

    # Add some programs to the plan.
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I press "Add programs"
    And I click on "Miscellaneous" "link"
    And I click on "Program 1" "link"
    And I click on "Program 2" "link"
    And I click on "Program 3" "link"

    # Check the selected competency appear in the plan.
    When I click on "Save" "button" in the "Add programs" "totaradialogue"
    Then I should see "Program 1" in the ".dp-plan-component-items" "css_element"
    And I should see "Program 2" in the ".dp-plan-component-items" "css_element"
    And I should see "Program 3" in the ".dp-plan-component-items" "css_element"

    # Add some course to the plan.
    And I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I click on "Add courses" "button"
    And I click on "Miscellaneous" "link"
    And I click on "Course 1" "link"
    And I click on "Course 2" "link"
    And I click on "Course 3" "link"

    # Check the selected courses appear in the plan.
    When I click on "Save" "button" in the "Add courses" "totaradialogue"
    Then I should see "Course 1" in the "#dp-component-update-table" "css_element"
    And I should see "Course 2" in the "#dp-component-update-table" "css_element"
    And I should see "Course 3" in the "#dp-component-update-table" "css_element"

    # Send the plan to the manager for approval.
    When I press "Send approval request"
    Then I should see "Approval request sent for plan \"learner1 Learning Plan\""
    And I should see "This plan has not yet been approved (Approval Requested)"
    And I log out

    # As the manager, access the learners plans.
    When I log in as "manager2"
    And I click on "Team" in the totara menu
    And I click on "Plans" "link" in the "firstname1 lastname1" "table_row"

    # Access the learners plans and verify it hasn't been approved.
    When I click on "learner1 Learning Plan" "link"
    Then I should see "You are viewing firstname1 lastname1's plan"
    And I should see "This plan has not yet been approved"

    # Approve the plan.
    When I set the field "reasonfordecision" to "Nice plan!"
    And I press "Approve"
    Then I should see "You are viewing firstname1 lastname1's plan"
    And I should see "Plan \"learner1 Learning Plan\" has been approved"
    And I log out

    # Learner check progress
    Given I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    Then I should see "Courses (3)"
    And I should see "Programs (3)"
    And I should see "0%" in the "learner1 Learning Plan" "table_row"

    When I click on "//div[contains(@class, 'progressbar_container')]/div[contains(@class, 'progress')]" "xpath_element" in the "learner1 Learning Plan" "table_row"
    Then I should see "0/3 Courses complete"
    And I should see "0 / 0 Competencies achieved"
    And I should see "0/0 Objectives met"
    And I should see "0/3 Programs complete"
