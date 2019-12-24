@totara @totara_plan
Feature: Learner self approves plan with updated template.

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
    And the following "plans" exist in "totara_plan" plugin:
      | user     | name                   |
      | learner1 | learner1 Learning Plan |

    # As admin select the custom workflow for learning plans.
    And I log in as "admin"
    And I navigate to "Manage templates" node in "Site administration > Learning Plans"
    And I click on "Learning Plan" "link" in the ".dp-templates" "css_element"
    And I switch to "Workflow" tab
    And I click on "Custom workflow" "radio"
    And I click on "Advanced workflow settings" "button"
    # Update plan settings to allow self approval.
    And I set the field "approvelearner" to "Approve"
    And I set the field "completereactivatelearner" to "Allow"
    When I click on "Save changes" "button"
    Then I should see "Plan settings successfully updated"

    # Allow the learner to add RPL.
    When I switch to "Courses" tab
    And I set the field "updatecourselearner" to "Allow"
    And I set the field "setcompletionstatuslearner" to "Allow"
    When I click on "Save changes" "button"
    Then I should see "Course settings successfully updated"
    And I log out

  @javascript
  Scenario: Learner activates and completes own plan.

    # Login as the learner and navigate to the learning plan.
    Given I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I click on "learner1 Learning Plan" "link"

    # Activate the plan.
    When I click on "Activate Plan" "button"
    Then I should see "Plan \"learner1 Learning Plan\" has been activated"

    # Complete the plan.
    When I click on "Complete plan" "button"
    Then I should see "Are you sure you want to mark the plan \"learner1 Learning Plan\" as complete?"
    # Confirm completion of the plan.
    When I click on "Complete plan" "button"
    Then I should see "Successfully completed plan learner1 Learning Plan"

  @javascript
  Scenario: Learner create and completes own plan with using custom template.

    # Login as the learner and navigate to the learning plan.
    Given I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I click on "learner1 Learning Plan" "link"

    # Activate the plan.
    When I click on "Activate Plan" "button"
    Then I should see "Plan \"learner1 Learning Plan\" has been activated"

    # Add some courses to the plan.
    And I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I click on "Add courses" "button"
    And I click on "Miscellaneous" "link"
    And I click on "Course 1" "link"
    And I click on "Course 2" "link"

    # Check the selected courses appear in the plan.
    When I click on "Save" "button" in the "Add courses" "totaradialogue"
    Then I should see "Course 1" in the "#dp-component-update-table" "css_element"
    And I should see "Course 2" in the "#dp-component-update-table" "css_element"

    # Add record of prior learning to the first course.
    When I click on "Add RPL" "link" in the "#courselist_r0" "css_element"
    And I set the field "id_rpl" to "Course has been completed"
    And I click on "Save changes" "button"
    Then I should see "Recognition of Prior Learning updated"

    # Add record of prior learning to the second course.
    When I click on "Add RPL" "link" in the "#courselist_r1" "css_element"
    And I set the field "id_rpl" to "Course has been completed"
    And I click on "Save changes" "button"
    Then I should see "Recognition of Prior Learning updated"

    # Move into the Overview tab to complete the plan
    When I click on "Overview" "link" in the "#dp-plan-content" "css_element"
    And I click on "Complete plan" "button"
    Then I should see "Are you sure you want to mark the plan \"learner1 Learning Plan\" as complete?"

    # Confirm plan completion.
    When I click on "Complete plan" "button"
    Then I should see "Successfully completed plan learner1 Learning Plan"
    And I log out
