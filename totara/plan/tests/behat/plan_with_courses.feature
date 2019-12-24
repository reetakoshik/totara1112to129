@totara @totara_plan
Feature: Learner creates learning plan with courses

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
  Scenario: Test the learner can add and remove courses from their learning plan prior to approval.

    # Login as the learner and navigate to the learning plan.
    Given I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I click on "learner1 Learning Plan" "link"

    # Add some courses to the plan.
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

    # Delete a course to make sure it's removed properly.
    When I click on "Delete" "link" in the "#courselist_r2_c4" "css_element"
    Then I should see "Are you sure you want to remove this item?"
    When I click on "Continue" "button"
    Then I should not see "Course 3" in the "#dp-component-update-table" "css_element"

    # Send the plan to the manager for approval.
    When I click on "Send approval request" "button"
    Then I should see "Approval request sent for plan \"learner1 Learning Plan\""
    And I should see "This plan has not yet been approved (Approval Requested)"
    And I log out

    # As the manager, access the learners plans.
    When I log in as "manager2"
    And I click on "Team" in the totara menu
    And I click on "Plans" "link" in the "firstname1 lastname1" "table_row"

    # Access the learners plans and verify it hasn't been approved.
    And I click on "learner1 Learning Plan" "link"
    Then I should see "You are viewing firstname1 lastname1's plan"
    And I should see "This plan has not yet been approved"

    # Approve the plan.
    When I set the field "reasonfordecision" to "Nice plan!"
    And I click on "Approve" "button"
    Then I should see "You are viewing firstname1 lastname1's plan"
    And I should see "Plan \"learner1 Learning Plan\" has been approved"

  @javascript
  Scenario: Test RPL completion by manager of a learners learning plan.

    # Login as the learner and navigate to the learning plan.
    Given I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I click on "learner1 Learning Plan" "link"

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

    # Send the plan to the manager for approval.
    When I click on "Send approval request" "button"
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

    # Add record of prior learning to the first course.
    When I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I click on "Add RPL" "link" in the "#courselist_r0_c5" "css_element"
    And I set the field "id_rpl" to "Course has been completed"
    And I click on "Save changes" "button"
    Then I should see "Recognition of Prior Learning updated"

    # Add record of prior learning to the second course.
    When I click on "Add RPL" "link" in the "#courselist_r1_c5" "css_element"
    And I set the field "id_rpl" to "Course has been completed"
    And I click on "Save changes" "button"
    Then I should see "Recognition of Prior Learning updated"

    # Approve the plan.
    When I set the field "reasonfordecision" to "Courses done, plan completed!"
    And I click on "Approve" "button"
    Then I should see "You are viewing firstname1 lastname1's plan"
    And I should see "Plan \"learner1 Learning Plan\" has been approved"

    # Move into the Overview tab to complete the plan.
    When I click on "Overview" "link" in the "#dp-plan-content" "css_element"
    And I click on "Complete plan" "button"
    Then I should see "Are you sure you want to mark the plan \"learner1 Learning Plan\" as complete?"

    # Confirm plan completion.
    When I click on "Complete plan" "button"
    Then I should see "Successfully completed plan learner1 Learning Plan"
    And I log out

    # As the user, Verify the plan is marked as complete.
    When I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I click on "learner1 Learning Plan" "link"
    Then I should see "This plan has been marked as complete"
