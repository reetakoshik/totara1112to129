@totara @totara_appraisal @totara_core_menu
Feature: Appraisal question: Competencies from Learning Plan - test that changing proficiency is working

  Background:
    Given I am on a totara site
    And the following "competency" frameworks exist:
      | fullname               | idnumber | description           |
      | Competency Framework 1 | CF1      | Framework description |
    And the following "competency" hierarchy exists:
      | framework | fullname     | idnumber | description            |
      | CF1       | Competency 1 | C1       | Competency description |
    And the following "users" exist:
      | username   | firstname  | lastname  | email                  |
      | learner1   | firstname1 | lastname1 | learner1@example.com   |
      | manager2   | firstname2 | lastname2 | manager2@example.com   |
      | teamlead3  | firstname3 | lastname3 | teamlead3@example.com  |
      | appraiser4 | firstname4 | lastname4 | appraiser4@example.com |
    And the following job assignments exist:
      | user       | fullname | idnumber | manager   | appraiser  |
      | appraiser4 | Day Job  | ja       |           |            |
      | teamlead3  | Day Job  | ja       |           |            |
      | manager2   | Day Job  | ja       | teamlead3 |            |
      | learner1   | Day Job  | ja       | manager2  | appraiser4 |
    And the following "plans" exist in "totara_plan" plugin:
      | user     | name                   |
      | learner1 | learner1 Learning Plan |
    And the following "cohorts" exist:
      | name                | idnumber | description            | contextlevel | reference |
      | Appraisals Audience | AppAud   | Appraisals Assignments | System       | 0         |
    And the following "cohort members" exist:
      | user     | cohort |
      | learner1 | AppAud |

    # Set up appraisal.
    And I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I press "Create appraisal"
    And I set the following fields to these values:
      | Name        | Appraisal competency test |
      | Description | Description               |
    And I press "Create appraisal"
    And I press "Add stage"
    And I set the following fields to these values:
      | Name                  | Behat Appraisal stage   |
      | Description           | Behat stage description |
      | timedue[enabled]      | 1                       |
      | timedue[day]          | 31                      |
      | timedue[month]        | 12                      |
      | timedue[year]         | 2037                    |
      | Page names (optional) | Page1                   |
    And I click on "Add stage" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "Behat Appraisal stage" in the ".appraisal-stages" "css_element"
    And I click on "Behat Appraisal stage" "link" in the ".appraisal-stages" "css_element"
    And I click on "Page1" "link" in the ".appraisal-page-list" "css_element"

    # Add the competency question.
    And I set the field "id_datatype" to "Competencies from Learning plan"
    And I click on "Add" "button" in the "#fgroup_id_addquestgroup" "css_element"
    And I set the following fields to these values:
      | Question       | Comp question    |
      | Include rating | 1                |
      | id_roles_1_2   | 1                |
      | id_roles_1_1   | 1                |
      | id_roles_2_2   | 1                |
      | id_roles_2_1   | 1                |
      | id_roles_4_2   | 1                |
      | id_roles_4_1   | 1                |
      | id_roles_8_2   | 1                |
      | id_roles_8_1   | 1                |
    And I press "Save changes"
    And I wait "1" seconds
    And I switch to "Assignments" tab
    And I select "Audience" from the "groupselector" singleselect
    And I click on "Appraisals Audience (AppAud)" "link" in the "Assign Learner Group To Appraisal" "totaradialogue"
    And I click on "Save" "button" in the "Assign Learner Group To Appraisal" "totaradialogue"
    And I wait "1" seconds
    Then I should see "firstname1 lastname1" in the "#assignedusers" "css_element"
    When I click on "Activate now" "link"
    And I press "Activate"
    Then I should see "Appraisal competency test activated"
    And I log out

  @javascript
  Scenario: Test that competency proficiency can be updated in appraisal questions

    # Login as the manager and navigate to the learning plan.
    Given I log in as "manager2"
    And I click on "Team" in the totara menu
    And I click on "Plans" "link" in the "firstname1 lastname1" "table_row"
    And I click on "learner1 Learning Plan" "link"

    # Add some competencies to the plan.
    And I click on "Competencies" "link" in the "#dp-plan-content" "css_element"
    And I press "Add competencies"
    And I click on "Competency 1" "link"

    # Check the selected competency appear in the plan.
    When I click on "Continue" "button" in the "Add competencies" "totaradialogue"
    Then I should see "Competency 1" in the ".dp-plan-component-items" "css_element"
    And I press "Approve"
    And I log out

    # Log in as learner and navigate to the appraisal.
    Given I log in as "learner1"
    And I click on "Latest Appraisal" in the totara menu
    And I press "Start"

    # Set the competency in the question.
    And I press "Choose competencies to review"
    And I click on "Competency 1" "link" in the "Choose competencies to review" "totaradialogue"
    And I click on "Save" "button" in the "Choose competencies to review" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Competency 1"
    And I should see "Not competent"
    And "Current competency status" "select" should not exist

    # Give an answer and make sure that it is saved.
    When I set the field "Your answer" to "This is learner's answer"
    And I press "Complete stage"
    And I press "View"
    Then I should see "Competency 1"
    And I should see "Not competent"
    And I should see "This is learner's answer"
    And I log out

    # Log in as manager and go into the appraisal.
    Given I log in as "manager2"
    And I click on "Team" in the totara menu
    And I click on "Appraisals" "link" in the "firstname1 lastname1" "table_row"
    And I click on "Appraisal competency test" "link"
    And I press "Start"
    Then I should see "This is learner's answer"
    And I should see "Not competent"

    # Make sure manager can change the proficiency and it is saved.
    When I set the field "Current competency status" to "Competent with supervision"
    And I set the field "Your answer" to "This is manager's answer"
    And I press "Complete stage"
    And I press "View"
    Then I should see "Competency 1"
    And I should see "Competent with supervision"
    And I should see "This is learner's answer"
    And I should see "This is manager's answer"
    And I log out

    # Make sure manager's manager can see the appraisal but can't change proficiency.
    Given I log in as "teamlead3"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal competency test" "link"
    And I press "Start"
    Then I should see "This is learner's answer"
    And I should see "This is manager's answer"
    And I should see "Competent with supervision"
    And "Current competency status" "select" should not exist
    When I set the field "Your answer" to "This is manager's manager's answer"
    And I press "Save progress"
    And I should see "This is manager's manager's answer"
    And I log out

    # Make sure appraiser can see the appraisal but can't change proficiency.
    Given I log in as "appraiser4"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal competency test" "link"
    And I press "Start"
    Then I should see "This is learner's answer"
    And I should see "This is manager's answer"
    And I should see "This is manager's manager's answer"
    And I should see "Competent with supervision"
    And "Current competency status" "select" should not exist
    When I set the field "Your answer" to "This is appraiser's answer"
    And I press "Complete stage"
    And I press "View"
    And I should see "This is appraiser's answer"
    And I log out
