@totara @totara_plan @javascript
Feature: Test plan manager driven workflow settings
  In order to control how plan is being set up for my staff
  As a manager
  I need to be able to create manager driven plans

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
    And the following "programs" exist in "totara_program" plugin:
      | fullname                | shortname |
      | Program 1          | program1  |
      | Program 2          | program2  |
    And the following "competency frameworks" exist in "totara_hierarchy" plugin:
      | fullname                 | idnumber |
      | Competency Framework 001 | cf1      |
    And the following "competencies" exist in "totara_hierarchy" plugin:
      | comp_framework | fullname     | idnumber |
      | cf1            | Competency 1 | comp1    |
    And I log in as "admin"
    And I navigate to "Manage templates" node in "Site administration > Learning Plans"
    And I set the following fields to these values:
      | Name             | template 1 |
      | id_enddate_month | December   |
      | id_enddate_day   | 31         |
      | id_enddate_year  | 2021       |
    And I press "Save changes"
    And I switch to "Workflow" tab
    And I click on "Manager driven workflow" "radio"
    And I press "Save changes"
    And I log out

  Scenario: Learner to request content added to a manager driven plan
    Given I log in as "manager2"
    And I click on "Team" in the totara menu
    And I click on the link "Plans" in row 1
    And I press "Create new learning plan"
    And I set the field "Plan template" to "template 1"
    And I set the field "Plan name" to "Learner1 plan"
    When I press "Create plan"
    Then I should see "Plan creation successful"
    When I set the field "reasonfordecision" to "This will be a great plan for you!"
    And I press "Approve"
    Then I should see "Plan \"Learner1 plan\" has been approved by firstname2 lastname2"
    And I log out

    # Check permissions for manager
    Then I log in as "manager2"
    And I click on "Team" in the totara menu
    And I click on "Plans" "link"
    And I should see "You are viewing firstname1 lastname1's plans."
    And I click on "Learner1 plan" "link"
    And I switch to "Courses" tab
    And "Add courses" "button" should exist
    And I click on "Add courses" "button"
    And I click on "Miscellaneous" "link"
    And I click on "Course 2" "link"
    When I click on "Save" "button" in the "Add courses" "totaradialogue"
    Then the "dp-plan-component-items" table should contain the following:
      | Course Name |
      | Course 2    |
    And I should not see "Approval status"
    And I log out

    # Check permissions for learner in each component type
    Then I log in as "learner1"
    Then I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I click on "Learner1 plan" "link"
    Then I should see "Learner1 plan"

    And I switch to "Competencies" tab
    And "Add competencies" "button" should exist
    When I click on "Add competencies" "button"
    And I click on "Competency 1" "link"
    And I click on "Save" "button" in the "Add competencies" "totaradialogue"
    Then the "dp-plan-component-items" table should contain the following:
      | Competency Name | Approval status |
      | Competency 1    | Draft           |

    And I switch to "Programs" tab
    And "Add programs" "button" should exist
    When I click on "Add programs" "button"
    And I click on "Miscellaneous" "link"
    And I click on "Program 1" "link"
    And I click on "Save" "button" in the "Add programs" "totaradialogue"
    Then the "dp-plan-component-items" table should contain the following:
      | Program Name | Approval status |
      | Program 1    | Draft           |

    And I switch to "Courses" tab
    And "Add courses" "button" should exist
    And the "dp-plan-component-items" table should contain the following:
      | Course Name |
      | Course 2    |
    When I click on "Add courses" "button"
    And I click on "Miscellaneous" "link"
    And I click on "Course 1" "link"
    When I click on "Save" "button" in the "Add courses" "totaradialogue"
    Then the "dp-plan-component-items" table should contain the following:
      | Course Name | Approval status |
      | Course 2    |                 |
      | Course 1    | Draft           |

    And I should see "This plan has draft items"
    And I should see "1 Course"
    And I should see "1 Competency"
    And I should see "1 Program"
    And "Send approval request" "button" should exist
    When I click on "Send approval request" "button"
    Then I should see "Approval request sent for plan \"Learner1 plan\""
    And the "dp-plan-component-items" table should contain the following:
      | Course Name | Approval status  |
      | Course 2    |                  |
      | Course 1    | Pending approval |
    And I log out
