@totara @totara_plan
Feature: Learner creates learning plan with objectives

Background:
  Given I am on a totara site
  And the following "users" exist:
    | username | firstname  | lastname  | email                |
    | learner1 | firstname1 | lastname1 | learner1@example.com |
    | manager2 | firstname2 | lastname2 | manager2@example.com |
  And the following job assignments exist:
    | user     | fullname       | manager  |
    | learner1 | jobassignment1 | manager2 |
  And the following "plans" exist in "totara_plan" plugin:
    | user     | name                   |
    | learner1 | learner1 Learning Plan |
  And the following "objectives" exist in "totara_plan" plugin:
    | user     | plan                   | name        |
    | learner1 | learner1 Learning Plan | Objective 1 |
    | learner1 | learner1 Learning Plan | Objective 2 |
    | learner1 | learner1 Learning Plan | Objective 3 |

@javascript
Scenario: Test the learner can add and remove objectives from their learning plan prior to approval.

  # Login as the learner and navigate to the learning plan.
  Given I log in as "learner1"
  And I click on "Dashboard" in the totara menu
  And I click on "Learning Plans" "link"
  And I click on "learner1 Learning Plan" "link"

  # Add an objective to the plan (just to test the interface - rather than using a data generator).
  And I click on "Objectives" "link" in the "#dp-plan-content" "css_element"
  And I press "Add new objective"
  And I set the field "Objective Title" to "Objective 4"
  And I set the field "Objective description" to "Objective 4 description"
  And I press "Add objective"
  Then I should see "Objective created"
  And I should see "Objective 4" in the ".dp-plan-component-items" "css_element"
  # Check the objective 3 is available for us to delete in the next step.
  And I should see "Objective 3" in the ".dp-plan-component-items" "css_element"

  # Delete a competency to make sure it's removed properly.
  When I click on "Delete" "link" in the "#objectivelist_r2_c6" "css_element"
  Then I should see "Are you sure you want to delete this objective?"
  When I press "Continue"
  Then I should not see "Objective 3" in the "#dp-component-update-table" "css_element"

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
