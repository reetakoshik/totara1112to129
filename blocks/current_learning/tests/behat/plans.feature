@totara @block @block_current_learning @totara_plan @totara_program
Feature: User courses and programs when added to a plan appear correctly in the current learning block

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
      | fullname | shortname   |
      | Course 1 | course1     |
      | Course 2 | course2     |
    And the following "programs" exist in "totara_program" plugin:
      | fullname  | shortname  |
      | Program 1 | program1   |
      | Program 2 | program2   |
    And I add a courseset with courses "course1" to "program1":
      | Set name              | set1        |
      | Learner must complete | All courses |
      | Minimum time required | 1           |
    And I add a courseset with courses "course2" to "program2":
      | Set name              | set1        |
      | Learner must complete | All courses |
      | Minimum time required | 1           |
    And the following "plans" exist in "totara_plan" plugin:
      | user     | name                   |
      | learner1 | learner1 Learning Plan |

  @javascript
  Scenario: Programs appear in the current learning block when they have been added to a plan.

    # Login as the learner and add a program to the plan.
    Given I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I click on "learner1 Learning Plan" "link"
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I press "Add programs"
    And I click on "Miscellaneous" "link"
    And I click on "Program 1" "link"
    When I click on "Save" "button" in the "Add programs" "totaradialogue"
    Then I should see "Program 1" in the ".dp-plan-component-items" "css_element"

    # The program should not appear in the block as the plan has not been activated.
    When I click on "Dashboard" in the totara menu
    Then I should not see "Program 1" in the "Current Learning" "block"

    # Send the plan to the manager for approval.
    When I click on "Learning Plans" "link"
    And I click on "learner1 Learning Plan" "link"
    And I press "Send approval request"
    And I log out
    And I log in as "manager2"
    And I click on "Team" in the totara menu
    And I click on "Plans" "link" in the "firstname1 lastname1" "table_row"
    And I click on "learner1 Learning Plan" "link"
    And I set the field "reasonfordecision" to "Nice plan man!"
    And I press "Approve"
    Then I should see "Plan \"learner1 Learning Plan\" has been approved"
    And I log out

    # The program and it's contents should now appear in the block.
    When I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    Then I should see "Program 1" in the "Current Learning" "block"
    When I toggle "Program 1" in the current learning block
    Then I should see "Course 1" in "Program 1" within the current learning block
