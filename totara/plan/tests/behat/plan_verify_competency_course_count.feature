@totara @totara_plan
Feature: Verify competency course count within learning plan

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname  | lastname  | email                |
      | learner1 | learner1 | learner1 | learner1@example.com |
      | manager1 | manager1 | manager1| manager1@example.com |
    And the following job assignments exist:
      | user     | fullname       | manager  |
      | learner1 | jobassignment1 | admin    |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | Course 1  | 1                |
      | Course 2 | Course 2  | 1                |
      | Course 3 | Course 3  | 1                |
    And the following "competency" frameworks exist:
      | fullname               | idnumber | description           |
      | Competency Framework 1 | CF1      | Competency Framework 1 description |
    And the following "competency" hierarchy exists:
      | framework | fullname     | idnumber | description            |
      | CF1       | Competency 1 | C1       | Competency description |

    # Login as admin, create a competency and assign courses to it
    Given I log in as "admin"
    And I navigate to "Manage competencies" node in "Site administration > Hierarchies > Competencies"
    And I follow "Competency Framework 1"
    And I follow "Competency 1"
    And I click on "Assign course completions" "button"
    And I follow "Miscellaneous"
    And I follow "Course 1"
    And I follow "Course 2"
    And I follow "Course 3"
    And I click on "Save" "button" in the "Assign course completions" "totaradialogue"
    And I wait "1" seconds
    Then I log out

  @javascript
  Scenario: create learning plan

   # Learner adding plan
    Given I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I click on "Create new learning plan" "button"
    And I click on "Create plan" "button"
    And I follow "Competencies"
    And I click on "Add competencies" "button"
    And I follow "Competency 1"
    And I click on "Continue" "button"
    Then I should see "Course 1"
    Then I should see "Course 2"
    Then I should see "Course 3"
    And I click on "Save" "button"
    And I should see "3" in the "//table/tbody/tr/td[3]" "xpath_element"
    And I follow "Competency 1"
    Then I should see "Course 1"
    Then I should see "Course 2"
    Then I should see "Course 3"
    And I switch to "Courses" tab
    Then I should see "Course 1"
    Then I should see "Course 2"
    Then I should see "Course 3"
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    Then I should see "Courses (3)"
    Then I log out

    # admin hiding a course
    Then I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 2"
    And I follow "Edit settings"
    And I set the field "visible" to "Hide"
    And I click on "Save and display" "button"
    Then I log out

    # leaner check plan does not display the hidden course
    Given I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    Then I should see "Courses (2)"
    And I follow "Courses (2)"
    Then I should see "Course 1"
    Then I should see "Course 3"
    And I follow "Competencies"
    And I should see "2" in the "//table/tbody/tr/td[3]" "xpath_element"

    # leaner add a new plan and check hidden courses are not available
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I click on "Create new learning plan" "button"
    And I set the field "name" to "Learning Plan 2"
    And I click on "Create plan" "button"
    And I follow "Competencies"
    And I click on "Add competencies" "button"
    And I follow "Competency 1"
    And I click on "Continue" "button"
    Then I should see "Course 1"
    Then I should not see "Course 2"
    Then I should see "Course 3"
    And I click on "Save" "button"
    And I should see "2" in the "//table/tbody/tr/td[3]" "xpath_element"
    And I follow "Competency 1"
    Then I should see "Course 1"
    Then I should not see "Course 2"
    Then I should see "Course 3"
    And I switch to "Courses" tab
    Then I should see "Course 1"
    Then I should not see "Course 2"
    Then I should see "Course 3"
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    Then I should see "Courses (2)" in the "Learning Plan 2" "table_row"