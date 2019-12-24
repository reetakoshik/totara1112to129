@totara @totara_plan
Feature: Verify user prompts to progress plan are correct

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
    And the following "plans" exist in "totara_plan" plugin:
      | user     | name                   |
      | learner1 | learner1 Learning Plan |

  @javascript
  Scenario: Test the manager can approve the plan before it's sent for approval.

    # As the manager, access the learners plans.
    Given I log in as "manager2"
    And I click on "Team" in the totara menu
    And I click on "Plans" "link" in the "firstname1 lastname1" "table_row"

    # Access the learners plans and verify it hasn't been approved.
    When I click on "learner1 Learning Plan" "link"
    Then I should see "You are viewing firstname1 lastname1's plan"
    And I should see "This plan has not yet been approved"
    And I should see "Reason (Grant/Deny)"

  @javascript
  Scenario: Test the learner can approve their own plan but approval is required for updates.

    # As admin select the custom workflow for learning plans.
    Given I log in as "admin"
    And I navigate to "Manage templates" node in "Site administration > Learning Plans"
    And I click on "Learning Plan" "link" in the ".dp-templates" "css_element"
    And I switch to "Workflow" tab
    And I click on "Custom workflow" "radio"
    And I press "Advanced workflow settings"
    # Update plan settings to allow self approval.
    # All plan settings should allow the user to manage the plan.
    And I set the field "approvelearner" to "Approve"
    And I set the field "completereactivatelearner" to "Allow"
    When I press "Save changes"
    Then I should see "Plan settings successfully updated"
    And I log out

    # Login as the learner and navigate to the learning plan.
    When I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I click on "learner1 Learning Plan" "link"

    # Activate the plan.
    And I press "Activate Plan"
    Then I should see "Plan \"learner1 Learning Plan\" has been activated"

    # Add some courses to the plan.
    And I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I press "Add courses"
    And I click on "Miscellaneous" "link"
    And I click on "Course 1" "link"
    And I click on "Course 2" "link"

    # Check the selected courses appear in the plan.
    When I click on "Save" "button" in the "Add courses" "totaradialogue"
    Then I should see "Course 1" in the "#dp-component-update-table" "css_element"
    And I should see "Course 2" in the "#dp-component-update-table" "css_element"
    And "Send approval request" "button" should be visible
