@totara @totara_plan
Feature: Verify replacement of sql_fullname

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname  | lastname  | email                |
      | learner1 | firstname1 | lastname1 | learner1@example.com |
      | manager2 | firstname2 | lastname2 | manager2@example.com |
    And the following job assignments exist:
      | user     | fullname       | manager  |
      | learner1 | jobassignment1 | manager2 |
    And the following "competency" frameworks exist:
      | fullname               | idnumber | description           |
      | Competency Framework 1 | CF1      | Framework description |
    And the following "competency" hierarchy exists:
      | framework | fullname     | idnumber | description            |
      | CF1       | Competency 1 | C1       | Competency description |
    And the following "plans" exist in "totara_plan" plugin:
      | user     | name                   |
      | learner1 | learner1 Learning Plan |

    # As admin select the custom workflow for learning plans.
    And I log in as "admin"
    And I set the following administration settings values:
      | assessorroleid | Staff Manager (staffmanager) |
    And I navigate to "Manage templates" node in "Site administration > Learning Plans"
    And I click on "Learning Plan" "link" in the ".dp-templates" "css_element"
    And I switch to "Workflow" tab
    And I click on "Custom workflow" "radio"
    And I click on "Advanced workflow settings" "button"

    # Allow the learner to update the competency and set the assessor.
    When I switch to "Competencies" tab
    And I set the field "updatecompetencylearner" to "Allow"
    And I set the field "setproficiencylearner" to "Allow"
    When I click on "Save changes" "button"
    Then I should see "Competency settings successfully updated"
    And I log out

  @javascript
  Scenario: Learner creates plan and add competency evidence.

    # Login as the learner and navigate to the learning plan.
    Given I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I follow "learner1 Learning Plan"

    # Add some competencies to the plan.
    And I click on "Competencies" "link" in the "#dp-plan-content" "css_element"
    And I press "Add competencies"
    And I click on "Competency 1" "link"

    # Check the selected competency appear in the plan.
    When I click on "Continue" "button" in the "Add competencies" "totaradialogue"
    Then I should see "Competency 1" in the ".dp-plan-component-items" "css_element"

    # Delete a competency to make sure it's removed properly.
    When I click on "Set Status" "link" in the "#competencylist_r0_c5" "css_element"
    Then I should see "Set competency status"

    # The assessor name should appear correctly in the assessor name.
    When I set the field "assessorid" to "firstname2 lastname2"
    And I press "Save changes"
    Then I should see "Plan: learner1 Learning Plan"
