@mod @mod_assign
Feature: Assignments where the grading type is none

  @javascript
  Scenario: Ensure the correct terminology is being used when the grading type is not set.
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name                               | Test assignment name    |
      | Description                                   | Submit your online text |
      | id_grade_modgrade_type                        | None                    |
      | assignsubmission_onlinetext_enabled           | 1                       |
      | assignsubmission_file_enabled                 | 0                       |
      | assignsubmission_file_enabled                 | 0                       |
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    When I follow "Test assignment name"
    Then I should see "Assignment does not require a grade"
    When I press "Add submission"
    And I set the following fields to these values:
      | Online text | Hello |
    And I press "Save changes"
    Then I should see "Submitted"
    And I should see "Assignment does not require a grade"
    And I should not see "Not graded"
    And I log out
    When I log in as "teacher1"
    And I follow "Course 1"
    And I follow "Test assignment name"
    Then I should see "Review"
    When I click on "Review" "link"
    Then I should see "Assignment does not require a grade"
    Given I follow "Assignment: Test assignment name"
    And I click on "View all submissions" "link"
    Then "Student 1" row "Grade" column of "generaltable" table should contain "Review"
    Given I click on "Review" "link" in the "Student 1" "table_row"
    Then I should see "Assignment does not require a grade"
