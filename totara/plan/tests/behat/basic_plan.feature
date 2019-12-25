@totara @totara_plan
Feature: Learner creates basic learning plan

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

  @javascript
  Scenario: Learner creates empty learning plan.

    Given I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I press "Create new learning plan"
    And I set the field "Plan name" to "My Learning Plan"
    And I set the field "Plan description" to "A short and accurate description of My Learning Plan: Not a lot."
    When I press "Create plan"
    Then I should see "Plan creation successful"
    And I log out

  @javascript
  Scenario: Learner creates empty learning plan which is approved by manager.

    # Login as the learner and navigate to the learning plan.
    Given I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I follow "learner1 Learning Plan"

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
    When I set the field "reasonfordecision" to "Nice plan. Empty, but nice!"
    And I press "Approve"
    Then I should see "You are viewing firstname1 lastname1's plan"
    And I should see "Plan \"learner1 Learning Plan\" has been approved"

  @javascript
  Scenario: Test a manager can complete a learners plan.

    # As the manager, access the learners plans.
    Given I log in as "manager2"
    And I click on "Team" in the totara menu
    And I click on "Plans" "link" in the "firstname1 lastname1" "table_row"

    # Access the learners plans and verify it hasn't been approved.
    When I click on "learner1 Learning Plan" "link"
    Then I should see "You are viewing firstname1 lastname1's plan"
    And I should see "This plan has not yet been approved"

    # Approve the plan.
    When I set the field "reasonfordecision" to "Nice plan. Empty, but nice!"
    And I press "Approve"
    Then I should see "You are viewing firstname1 lastname1's plan"
    And I should see "Plan \"learner1 Learning Plan\" has been approved"

    # Move into the Overview tab to complete the plan
    When I press "Complete plan"
    Then I should see "Are you sure you want to mark the plan \"learner1 Learning Plan\" as complete?"

    When I press "Complete plan"
    Then I should see "Successfully completed plan learner1 Learning Plan"

  @javascript
  Scenario: Test a learner can delete a declined learning plan.

    # Login as the learner and navigate to the learning plan.
    Given I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I click on "learner1 Learning Plan" "link"

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

    # Add a comment to the approval request and decline the approval.
    When I set the field "reasonfordecision" to "Plan appears to be empty!"
    And I press "Decline"
    Then I should see "You are viewing firstname1 lastname1's plan"
    And I should see "Plan \"learner1 Learning Plan\" has been declined"
    And I log out

    # Login as the learner, access the plan and verify it's not been approved.
    When I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    When I click on "learner1 Learning Plan" "link"
    Then I should see "This plan has not yet been approved"

    # Request deletion of the plan.
    When I press "Delete plan"
    Then I should see "Are you sure you want to delete this plan and all its related items?"

    # Confirm deletion of the plan.
    When I press "Yes"
    Then I should see "Successfully deleted plan \"learner1 Learning Plan\""

  @javascript
  Scenario: Test a manager can create a learner's plan and complete it.u

    # As the manager, access the learners plans.
    Given I log in as "manager2"
    And I click on "Team" in the totara menu
    And I click on "Plans" "link" in the "firstname1 lastname1" "table_row"
    And I press "Create new learning plan"
    And I set the field "id_name" to "learner1 Learning Plan"

    When I press "Create plan"
    Then I should see "Plan creation successful"

    # Approve the plan.
    When I set the field "reasonfordecision" to "I wrote this plan. It's brilliant!"
    And I press "Approve"
    Then I should see "You are viewing firstname1 lastname1's plan"
    And I should see "Plan \"learner1 Learning Plan\" has been approved"

    # Move into the Overview tab to complete the plan
    When I press "Complete plan"
    Then I should see "Are you sure you want to mark the plan \"learner1 Learning Plan\" as complete?"

    # Confirm completion of the plan.
    When I press "Complete plan"
    Then I should see "Successfully completed plan learner1 Learning Plan"
