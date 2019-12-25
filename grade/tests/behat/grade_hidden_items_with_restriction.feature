@core @core_grades @availability @availability_restriction @mod @mod_assign @javascript
Feature: Grade visibility with audience restriction set
  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Tea       | Cher     | tea.cher@example.com |
      | student1 | Stu       | Dent     | stu.dent@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "cohorts" exist:
      | name      | idnumber |
      | Audience1 | aud1     |
    And the following "cohort members" exist:
      | user     | cohort |
      | teacher1 | aud1   |
    And the following "activities" exist:
      | activity | course | idnumber | name     | intro    |
      | assign   | C1     | assign1  | Homework | Homework |

    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Homework"
    And I click on "Grade" "link" in the ".submissionlinks" "css_element"
    And I set the field "Grade out of 100" to "42"
    And I press "Save changes"
    And I click on "Ok" "button" in the "Changes saved" "dialogue"
    And I am on "Course 1" course homepage
    And I log out

  Scenario: Student and teacher can see student's grade when activity has no access restriction
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Grades" node in "Course administration"
    And I follow "User report"
    And I set the field "Select all or one user" to "Stu Dent"
    Then I should see "42.00" in the "Homework" "table_row"
    And I log out

    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Grades" node in "Course administration"
    And I follow "User report"
    And I set the field "Select all or one user" to "Stu Dent"
    Then I should see "42.00" in the "Homework" "table_row"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I navigate to "Grades" node in "Course administration"
    And I follow "User report"
    Then I should see "42.00" in the "Homework" "table_row"

  Scenario: Student and teacher can see student's grade when activity has access restriction
    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I follow "Homework"
    And I navigate to "Edit settings" node in "Assignment administration"
    And I follow "Restrict access"
    And I click on "Add restriction..." "button"
    And I click on "Member of Audience" "button" in the "Add restriction..." "dialogue"
    And I set the field "Member of Audience" to "Audience1"
    And I press key "13" in the field "Member of Audience"
    When I press "Save and return to course"
    Then I should see "Not available unless: You are a member of the Audience: Audience1"
    And I should not see "Not available unless: You are a member of the Audience: Audience1 (hidden otherwise)"

    And I navigate to "Grades" node in "Course administration"
    And I follow "User report"
    And I set the field "Select all or one user" to "Stu Dent"
    Then I should see "42.00" in the "Homework" "table_row"
    And I log out

    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Grades" node in "Course administration"
    And I follow "User report"
    And I set the field "Select all or one user" to "Stu Dent"
    Then I should see "42.00" in the "Homework" "table_row"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I navigate to "Grades" node in "Course administration"
    And I follow "User report"
    Then I should see "42.00" in the "Homework" "table_row"

  Scenario: Only teacher can see student's grade when activity has invisible access restriction
    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I follow "Homework"
    And I navigate to "Edit settings" node in "Assignment administration"
    And I follow "Restrict access"
    And I click on "Add restriction..." "button"
    And I click on "Member of Audience" "button" in the "Add restriction..." "dialogue"
    And I set the field "Member of Audience" to "Audience1"
    And I press key "13" in the field "Member of Audience"
    And I click on "Displayed greyed-out if user does not meet this condition" "link"
    When I press "Save and return to course"
    Then I should see "Not available unless: You are a member of the Audience: Audience1 (hidden otherwise)"

    And I navigate to "Grades" node in "Course administration"
    And I follow "User report"
    And I set the field "Select all or one user" to "Stu Dent"
    Then I should see "42.00" in the "Homework" "table_row"
    And I log out

    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Grades" node in "Course administration"
    And I follow "User report"
    And I set the field "Select all or one user" to "Stu Dent"
    Then I should see "42.00" in the "Homework" "table_row"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I navigate to "Grades" node in "Course administration"
    And I follow "User report"
    Then I should not see "Homework" in the ".user-grade" "css_element"
