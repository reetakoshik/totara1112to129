@totara @core @core_course
Feature: Set audience visibility when defining a course
  In order to test audience visibility
  As an admin
  I will enable it and then configure multiple courses to use it

  Scenario: Audience visibility controls are not shown when it is disabled
    Given I am on a totara site
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable audience-based visibility | 0 |
    And I create a course with:
      | Course full name  | Course 1 |
      | Course short name | C1 |
    When I navigate to "Edit settings" node in "Course administration"
    Then I should not see "Audience-based visibility"
    And I should not see "Visibility"
    And I should see "Visible" in the "General" "fieldset"

  @javascript
  Scenario: Create courses with various audience visibility settings
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
      | student2 | Student | 2 | student2@example.com |
      | student3 | Student | 3 | student3@example.com |
    And the following "cohorts" exist:
      | name | idnumber |
      | Audience 1 | a1 |
      | Audience 2 | a2 |
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable audience-based visibility | 1 |
    And I add "Student 1 (student1@example.com)" user to "a1" cohort members
    And I add "Student 2 (student2@example.com)" user to "a2" cohort members

    And I create a course with:
      | Course full name  | Course 1 |
      | Course short name | C1 |
      | Visibility        | All users |
    And I enrol "Teacher 1" user as "Editing Trainer"
    And I enrol "Student 3" user as "Learner"

    And I create a course with:
      | Course full name  | Course 2 |
      | Course short name | C2 |
      | Visibility        | Enrolled users only |
    And I enrol "Teacher 1" user as "Editing Trainer"
    And I enrol "Student 3" user as "Learner"

    And I create a course with:
      | Course full name  | Course 3 |
      | Course short name | C3 |
      | Visibility        | Enrolled users and members of the selected audiences |
    And I enrol "Teacher 1" user as "Editing Trainer"
    And I enrol "Student 3" user as "Learner"
    And I navigate to "Edit settings" node in "Course administration"
    And I should not see "Visible" in the "General" "fieldset"
    And I should see "Visibility"
    And I should see "Audience-based visibility"
    And I press "Add visible audiences"
    And I should see "Course audiences (visible)"
    And I should see "Audience 1"
    And I click on "Audience 1" "link"
    And I click on "OK" "link_or_button" in the "div[aria-describedby='course-cohorts-visible-dialog']" "css_element"
    And I wait "1" seconds
    And I should not see "Course audiences (visible)"
    And I should see "Audience 1"
    And I press "Save and display"

    And I create a course with:
      | Course full name  | Course 4 |
      | Course short name | C4 |
      | Visibility        | No users |
    And I enrol "Teacher 1" user as "Editing Trainer"
    And I enrol "Student 3" user as "Learner"

    When I follow "Find Learning"
    Then I should see "Course 1"
    And I should see "Course 2"
    And I should see "Course 3"
    And I should see "Course 4"
    And I log out

    When I log in as "student1"
    And I follow "Find Learning"
    Then I should see "Course 1"
    And I should not see "Course 2"
    And I should see "Course 3"
    And I should not see "Course 4"
    And I log out

    When I log in as "student2"
    And I follow "Find Learning"
    Then I should see "Course 1"
    And I should not see "Course 2"
    And I should not see "Course 3"
    And I should not see "Course 4"
    And I log out

    When I log in as "student3"
    And I follow "Find Learning"
    Then I should see "Course 1"
    And I should see "Course 2"
    And I should see "Course 3"
    And I should not see "Course 4"
    And I log out
