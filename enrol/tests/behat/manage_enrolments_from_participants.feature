@core_enrol
Feature: Manage enrollments from participants page
  In order to manage course participants
  As a teacher
  In need to get to the enrolment page from the course participants page

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@example.com |
      | student2 | Student | 2 | student2@example.com |
      | teacher1 | teacher | 1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | topics |
    And the following "course enrolments" exist:
      | user | course | role |
      | student1 | C1 | student |
      | student2 | C1 | student |
      | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to course participants

  Scenario: Check the participants link when "All partipants" selected
    Given I select "All participants" from the "roleid" singleselect
    When I click on "Edit" "link" in the "region-main" "region"
    Then I should see "Enrolled users" in the "#region-main" "css_element"
    And the field "Role" matches value "All"

  Scenario: Check the participants link when "Student" selected
    Given I select "Student" from the "roleid" singleselect
    When I click on "Edit" "link" in the "region-main" "region"
    Then I should see "Enrolled users" in the "#region-main" "css_element"
    And the field "Role" matches value "Student"

  @javascript
  Scenario: Add and remove roles from course enrolments page
    Given I navigate to "Enrolled users" node in "Course administration > Users"
    And I click on "Assign roles" "link" in the "Student 1" "table_row"
    And I click on "Non-editing teacher" "button"
    Then I should see "Non-editing teacher" in the "Student 1" "table_row"
    And I should not see "Non-editing teacher" in the "Student 2" "table_row"

    When I click on "Unassign role Non-editing teacher" "link" in the "Student 1" "table_row"
    And I click on "Remove" "button"
    Then I should not see "Non-editing teacher" in the "Student 1" "table_row"
