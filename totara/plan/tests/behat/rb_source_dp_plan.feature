@totara @totara_plan @javascript
Feature: Verify the columns and filters of a the Learning plans report source.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Bob1      | Learner1 | learner1@example.com |
      | manager1 | Dave1     | Manager1 | manager1@example.com |
    And the following job assignments exist:
      | user     | fullname | manager  |
      | learner1 | Job 1    | manager1 |
    And the following "plans" exist in "totara_plan" plugin:
      | user     | name                   |
      | learner1 | Learning Plan 1 |
      | learner1 | Learning Plan 2 |
      | learner1 | Learning Plan 3 |
      | learner1 | Learning Plan 4 |

    When I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | Learning Plans |
      | Source      | Learning Plans |
    And I press "Create report"
    Then I should see "Edit Report 'Learning Plans'"

    # Make the report avaialbel to everyone.
    When I switch to "Access" tab
    And I click on "All users can view this report" "radio"
    And I press "Save changes"
    Then I should see "Report Updated"
    And I log out

    # Login as the learner and progress plans 2, 3 and 4 to pending approval.
    When I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    And I follow "Manage plans"
    And I follow "Learning Plan 2"
    And I press "Send approval request"
    Then I should see "Approval request sent for plan \"Learning Plan 2\""

    When I follow "Learning Plan 3"
    And I press "Send approval request"
    Then I should see "Approval request sent for plan \"Learning Plan 3\""

    When I follow "Learning Plan 4"
    And I press "Send approval request"
    Then I should see "Approval request sent for plan \"Learning Plan 4\""
    And I log out

    # Login as the manager and approve Learning Plan 3.
    When I log in as "manager1"
    And I click on "Team" in the totara menu
    And I click on "Plans" "link" in the "1" "table_row"
    And I follow "Learning Plan 3"
    And I press "Approve"
    Then I should see "Plan \"Learning Plan 3\" has been approved by Dave1 Manager1"

    # Complete Learning Plan 4.
    When I follow "Learning Plan 4"
    And I press "Approve"
    And I press "Complete plan"
    Then I should see "Are you sure you want to mark the plan \"Learning Plan 4\" as complete?"

    When I press "Complete plan"
    Then I should see "Successfully completed plan Learning Plan 4"
    And I log out

  Scenario: Verify the Plan Status column and filter presents and functions correctly.

    # Check the report contains all of teh learning plans.
    Given I log in as "manager1"
    When I click on "Reports" in the totara menu
    And I follow "Learning Plans"
    Then I should see "Learning Plans: 4 records shown"
    And I should see "Draft" in the "Learning Plan 1" "table_row"
    And I should see "Pending approval" in the "Learning Plan 2" "table_row"
    And I should see "Approved" in the "Learning Plan 3" "table_row"
    And I should see "Complete" in the "Learning Plan 4" "table_row"

    # Check filtering on plan status = draft.
    When I set the following fields to these values:
      | plan-status_op | is equal to |
      | plan-status    | Draft       |
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "Draft" in the "Learning Plan 1" "table_row"
    And I should not see "Pending approval" in the "#report_learning_plans" "css_element"
    And I should not see "Approved" in the "#report_learning_plans" "css_element"
    And I should not see "Complete" in the "#report_learning_plans" "css_element"

    # Check filtering on plan status = pending approval.
    When I set the following fields to these values:
      | plan-status_op | is equal to      |
      | plan-status    | Pending approval |
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "Pending approval" in the "Learning Plan 2" "table_row"
    And I should not see "Draft" in the "#report_learning_plans" "css_element"
    And I should not see "Approved" in the "#report_learning_plans" "css_element"
    And I should not see "Complete" in the "#report_learning_plans" "css_element"

    # Check filtering on plan status = approved.
    When I set the following fields to these values:
      | plan-status_op | is equal to |
      | plan-status    | Approved    |
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "Approved" in the "Learning Plan 3" "table_row"
    And I should not see "Draft" in the "#report_learning_plans" "css_element"
    And I should not see "Pending approval" in the "#report_learning_plans" "css_element"
    And I should not see "Complete" in the "#report_learning_plans" "css_element"

    # Check filtering on plan status = complete.
    When I set the following fields to these values:
      | plan-status_op | is equal to      |
      | plan-status    | Complete |
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "Complete" in the "Learning Plan 4" "table_row"
    And I should not see "Draft" in the "#report_learning_plans" "css_element"
    And I should not see "Pending approval" in the "#report_learning_plans" "css_element"
    And I should not see "Approved" in the "#report_learning_plans" "css_element"
