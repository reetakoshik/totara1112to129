@totara @totara_plan @totara_program
Feature: Learner creates learning plan with programs

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname  | lastname  | email                |
      | learner1 | firstname1 | lastname1 | learner1@example.com |
      | manager2 | firstname2 | lastname2 | manager2@example.com |
    And the following job assignments exist:
      | user     | fullname       | manager  |
      | learner1 | jobassignment1 | manager2 |
    And the following "programs" exist in "totara_program" plugin:
      | fullname  | shortname |
      | Program 1 | P1   |
      | Program 2 | P2   |
      | Program 3 | P3   |
    And the following "plans" exist in "totara_plan" plugin:
      | user     | name                   |
      | learner1 | learner1 Learning Plan |

  @javascript
  Scenario: Test the learner can add and remove programs from their learning plan prior to approval.

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

    # Delete a competency to make sure it's removed properly.
    When I click on "Delete" "link" in the "#programlist_r2_c4" "css_element"
    Then I should see "Are you sure you want to remove this item?"
    When I press "Continue"
    Then I should not see "Program 3" in the "#dp-component-update-table" "css_element"

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

  @javascript
  Scenario: The learner can request extensions if a learning plan program is also required learning.

    # Login as the learner and navigate to the learning plan.
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

    # Send the plan to the manager for approval.
    When I press "Send approval request"
    And I log out
    And I log in as "manager2"
    And I click on "Team" in the totara menu
    And I click on "Plans" "link" in the "firstname1 lastname1" "table_row"
    And I click on "learner1 Learning Plan" "link"
    And I set the field "reasonfordecision" to "Nice plan!"
    And I press "Approve"
    Then I should see "Plan \"learner1 Learning Plan\" has been approved"

    When I log out
    And I log in as "admin"
    And I click on "Programs" in the totara menu
    And I follow "Program 1"
    And I click on "Edit program details" "button"
    And I click on "Assignments" "link" in the ".tabtree" "css_element"
    And I select "Individuals" from the "Add a new" singleselect
    And I click on "Add" "button"
    And I click on "Add individuals to program" "button"
    And I click on "firstname1 lastname1" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set due date" "link" in the "firstname1 lastname1" "table_row"
    And I set the following fields to these values:
      | completiontime       | 10/11/2030 |
      | completiontimehour   | 15         |
      | completiontimeminute | 45         |
    And I click on "Set fixed completion date" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I click on "Save all changes" "button" in the "Confirm assignment changes" "totaradialogue"
    And I log out
    And I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I click on "learner1 Learning Plan" "link"
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I click on "Program 1" "link" in the "Program 1" "table_row"
    Then I should see "Due date: 10 November 2030, 3:45 PM"

    When I click on "(Request an extension)" "link"
    And I set the following fields to these values:
      | extensiontime       | 15/12/2030     |
      | extensiontimehour   | 18             |
      | extensiontimeminute | 30             |
      | extensionreason     | need more time |
    And I click on "Ok" "button" in the "Request for program extension by firstname1 lastname1" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Request for program extension has been sent to your manager(s)"
    And I should see "(Pending extension request)"
