@totara @totara_cohort
Feature: User with moodle/cohort:view can view but not manage audience details
  In order to view the details of an audience
  As a user with the mooodle/cohort:view permission
  I can view audience management tabs but not change anything

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username    | firstname    | lastname   | email                |
      | audviewcat  | CatAudience  | Viewer     | audview1@example.com |
      | audviewsys  | SysAudience  | Viewer     | audview2@example.com |
      | catmember1  | John         | Catmember  | catmem1@example.com  |
      | catmember2  | Jude         | Sysmember  | catmem2@example.com  |
    And the following "categories" exist:
      | name        | idnumber |
      | CategoryOne | cat1     |
      | CategoryTwo | cat2     |
    And the following "courses" exist:
      | fullname    | shortname | category | description       | idnumber |
      | CourseOne   | Course1   | cat1     | About this course | c1       |
      | CourseTwo   | Course2   | 0        | About this course | c2       |
      | CourseThree | Course3   | cat2     | About this course | c3       |
    And the following "cohorts" exist:
      | name              | idnumber | description         | contextlevel | reference |
      | Category Audience | 1        | About this audience | System       | 0         |
      | System Audience   | 2        | About this audience | System       | 0         |
      | Cat2 Audience     | 3        | About this audience | Category     | cat2      |
    And the following "roles" exist:
      | name            | shortname  |
      | Audience Viewer | audview    |
    And the following "role assigns" exist:
      | user       | role    | contextlevel | reference |
      | audviewcat | audview | Category     | cat1      |
      | audviewsys | audview | System       |           |
    Given the following "goal" frameworks exist:
      | fullname             | idnumber | description           |
      | Goal framework       | FW001    | Framework description |
    And the following "goal" hierarchy exists:
      | framework | fullname         | idnumber | description             |
      | FW001     | GoalOne          | GOAL001  | This is a goal          |
      | FW001     | GoalTwo          | GOAL002  | Also a goal             |
    And I log in as "admin"
    And "Administration" "block" should be visible
    And I set the following system permissions of "Audience Viewer" role:
      | moodle/cohort:view | Allow |
    And I add "John Catmember" user to "Category Audience" cohort members
    And I follow "Category Audience"
    And I switch to "Edit details" tab
    And I set the field "Context" to "CategoryOne"
    And I press "Save changes"
    And I switch to "Enrolled learning" tab
    And I press "Add courses"
    And I click on "CategoryOne" "link" in the "Add Courses to Enrolled Learning" "totaradialogue"
    And I click on "CourseOne" "link" in the "Add Courses to Enrolled Learning" "totaradialogue"
    And I press "Save"
    And I wait "1" seconds
    And "Delete" "link" in the "CourseOne" "table_row" should be visible
    And I switch to "Goals" tab
    And I press "Add Goal"
    And I click on "GoalOne" "link" in the "Assign goals" "totaradialogue"
    And I press "Save"
    And I wait "1" seconds
    And "Remove" "link" in the "GoalOne" "table_row" should be visible
    And I add "Jude Sysmember" user to "System Audience" cohort members
    And I follow "System Audience"
    And I switch to "Enrolled learning" tab
    And I press "Add courses"
    And I click on "Miscellaneous" "link" in the "Add Courses to Enrolled Learning" "totaradialogue"
    And I click on "CourseTwo" "link" in the "Add Courses to Enrolled Learning" "totaradialogue"
    And I press "Save"
    And I wait "1" seconds
    And "Delete" "link" in the "CourseTwo" "table_row" should be visible
    And I switch to "Goals" tab
    And I press "Add Goal"
    And I click on "GoalTwo" "link" in the "Assign goals" "totaradialogue"
    And I press "Save"
    And I wait "1" seconds
    And "Remove" "link" in the "GoalTwo" "table_row" should be visible
    And the following config values are set as admin:
      | audiencevisibility | 0 |
    And I log out

  @javascript
  Scenario: View audience management tabs with moodle/cohort:view capability in category context
    Given I log in as "audviewcat"
    And I am on site homepage
    And I should not see "Audiences"
    And I follow "CourseThree"
    And I follow "CategoryTwo"
    And I should not see "Audiences"
    And I am on site homepage
    And I follow "CourseOne"
    And I follow "CategoryOne"
    And I navigate to "Audiences" node in "Category: CategoryOne"
    And I follow "Category Audience"
    Then I should see "Overview"
    And I should not see "Edit details"
    And I should not see "Edit members"
    And I should not see "Visible learning"
    And I should not see "Learning Plan"
    And I should not see "Assign Roles"
    And I should not see "Clone this audience"
    And I should not see "Delete this audience"
    When I switch to "Members" tab
    Then I should see "John Catmember"
    When I switch to "Enrolled learning" tab
    Then I should not see "Add courses"
    And I should not see "Add programs"
    And I should not see "Add certifications"
    And I should see "CourseOne"
    And "Delete" "link" should not exist in the "CourseOne" "table_row"
    When I switch to "Goals" tab
    Then I should not see "Add Goal"
    And I should see "GoalOne"
    And "Remove" "link" should not exist in the "GoalOne" "table_row"
    When I log out
    And I log in as "admin"
    And the following config values are set as admin:
      | audiencevisibility | 1 |
    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "All audiences"
    And I follow "Category Audience"
    And I switch to "Visible learning" tab
    And I press "Add courses"
    And I click on "Miscellaneous" "link" in the "Add Courses to Visible Learning" "totaradialogue"
    And I click on "CourseTwo" "link" in the "Add Courses to Visible Learning" "totaradialogue"
    And I press "Save"
    And I wait "1" seconds
    And "All users" "option" in the "CourseTwo" "table_row" should be visible
    And "Delete" "link" in the "CourseTwo" "table_row" should be visible
    And I log out
    And I log in as "audviewcat"
    And I am on site homepage
    And I follow "CourseOne"
    And I follow "CategoryOne"
    And I navigate to "Audiences" node in "Category: CategoryOne"
    And I follow "Category Audience"
    Then I should see "Overview"
    And I should not see "Edit details"
    And I should not see "Edit members"
    And I should not see "Learning Plan"
    And I should not see "Assign Roles"
    And I should not see "Clone this audience"
    And I should not see "Delete this audience"
    When I switch to "Visible learning" tab
    Then I should not see "Add courses"
    And I should not see "Add programs"
    And I should not see "Add certifications"
    And I should see "CourseTwo"
    And "All users" "option" should not exist in the "CourseTwo" "table_row"
    And "Delete" "link" should not exist in the "CourseTwo" "table_row"

  @javascript
  Scenario: View audience management tabs with moodle/cohort:view capability in system context
    Given I log in as "audviewsys"
    And I am on site homepage
    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "System Audience"
    Then I should see "Overview"
    And I should not see "Edit details"
    And I should not see "Edit members"
    And I should not see "Visible learning"
    And I should not see "Learning Plan"
    And I should not see "Assign Roles"
    And I should not see "Clone this audience"
    And I should not see "Delete this audience"
    When I switch to "Members" tab
    Then I should see "Jude Sysmember"
    When I switch to "Enrolled learning" tab
    Then I should not see "Add courses"
    And I should not see "Add programs"
    And I should not see "Add certifications"
    And I should see "CourseTwo"
    And "Delete" "link" should not exist in the "CourseTwo" "table_row"
    When I switch to "Goals" tab
    Then I should not see "Add Goal"
    And I should see "GoalTwo"
    And "Remove" "link" should not exist in the "GoalTwo" "table_row"
    When I log out
    And I log in as "admin"
    And the following config values are set as admin:
      | audiencevisibility | 1 |
    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "System Audience"
    And I switch to "Visible learning" tab
    And I press "Add courses"
    And I click on "CategoryOne" "link" in the "Add Courses to Visible Learning" "totaradialogue"
    And I click on "CourseOne" "link" in the "Add Courses to Visible Learning" "totaradialogue"
    And I press "Save"
    And I wait "1" seconds
    And "All users" "option" in the "CourseOne" "table_row" should be visible
    And "Delete" "link" in the "CourseOne" "table_row" should be visible
    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Add new audience"
    And I set the following fields to these values:
      | Name | Dynamic Audience |
      | Type | Dynamic          |
    And I press "Save changes"
    And I log out
    And I log in as "audviewsys"
    And I am on site homepage
    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "System Audience"
    Then I should see "Overview"
    And I should not see "Edit details"
    And I should not see "Edit members"
    And I should not see "Learning Plan"
    And I should not see "Assign Roles"
    And I should not see "Clone this audience"
    And I should not see "Delete this audience"
    When I switch to "Visible learning" tab
    Then I should not see "Add courses"
    And I should not see "Add programs"
    And I should not see "Add certifications"
    And I should see "CourseOne"
    And "All users" "option" should not exist in the "CourseOne" "table_row"
    And "Delete" "link" should not exist in the "CourseOne" "table_row"
    When I am on site homepage
    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "All audiences"
    Then I should see "Category Audience"
    And I should see "Cat2 Audience"
    When I follow "Category Audience"
    Then I should see "Members" in the ".tabtree" "css_element"
    And I should see "Enrolled learning" in the ".tabtree" "css_element"
    And I should see "Visible learning" in the ".tabtree" "css_element"
    And I should see "Goals" in the ".tabtree" "css_element"
    And I should not see "Edit details"
    And I should not see "Edit members"
    And I should not see "Learning Plan"
    And I should not see "Assign Roles"
    And I should not see "Clone this audience"
    And I should not see "Delete this audience"
    When I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I follow "Dynamic Audience"
    Then I should see "Members" in the ".tabtree" "css_element"
    And I should see "Enrolled learning" in the ".tabtree" "css_element"
    And I should see "Visible learning" in the ".tabtree" "css_element"
    And I should see "Goals" in the ".tabtree" "css_element"
    And I should not see "Rule sets" in the ".tabtree" "css_element"
    And I should not see "Edit details"
    And I should not see "Edit members"
    And I should not see "Learning Plan"
    And I should not see "Assign Roles"
    And I should not see "Clone this audience"
    And I should not see "Delete this audience"
