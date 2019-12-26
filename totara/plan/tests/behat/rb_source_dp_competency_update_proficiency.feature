@totara @totara_plan
Feature: See that competency proficiency can be updated in Record of Learning: Competency report

  Background:
    Given I am on a totara site
    And the following "competency" frameworks exist:
      | fullname               | idnumber | description           |
      | Competency Framework 1 | CF1      | Framework description |
    And the following "competency" hierarchy exists:
      | framework | fullname     | idnumber | description            |
      | CF1       | Competency 1 | C1       | Competency description |
      | CF1       | Competency 2 | C2       | Competency description |
    And the following "users" exist:
      | username | firstname  | lastname  | email                |
      | learner1 | firstname1 | lastname1 | learner1@example.com |
    And the following "plans" exist in "totara_plan" plugin:
      | user     | name                   |
      | learner1 | learner1 Learning Plan |

  @javascript
  Scenario: Test that competency proficiency can be updated in Record of Learning: Competency report

    # Login as the learner and navigate to the learning plan.
    Given I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I click on "learner1 Learning Plan" "link"

    # Add some competencies to the plan.
    And I click on "Competencies" "link" in the "#dp-plan-content" "css_element"
    And I press "Add competencies"
    And I click on "Competency 1" "link"
    And I click on "Competency 2" "link"

    # Check the selected competency appear in the plan.
    When I click on "Continue" "button" in the "Add competencies" "totaradialogue"
    Then I should see "Competency 1" in the ".dp-plan-component-items" "css_element"
    And I log out

    # Create the report.
    When I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | fullname | Record of learning: All user competencies |
      | source   | dp_competency                             |
    And I press "Create report"

    # Check the report content.
    When I click on "View This Report" "link"
    Then I should see "Not Set" in the "learner1 Learning Plan" "table_row"

    # Check that the value can be changed.
    When I set the field "Status of Competency 1" to "Not competent"
    And I reload the page
    Then the following fields match these values:
      | Status of Competency 1 | Not competent |
      | Status of Competency 2 | Not Set       |
