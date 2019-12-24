@totara @core @core_course
Feature: Set enrolled learning for a course
  In order to test I can set enrolled learning for a course
  As an admin I edit the course
  And add audiences to the Enrolled Learning controls.

  @javascript
  Scenario: Create a course and enrol audiences
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
      | Visibility        | Enrolled users only |
    When I navigate to "Edit settings" node in "Course administration"
    Then I should see "Enrolled audiences"

    When I press "Add enrolled audiences"
    Then I should see "Course audiences (enrolled)"
    And I should see "Audience 1"
    When I click on "Audience 1" "link"
    And I click on "OK" "link_or_button" in the "div[aria-describedby='course-cohorts-enrolled-dialog']" "css_element"
    And I wait "1" seconds
    Then I should not see "Course audiences (enrolled)"
    And I should see "Audience 1"

    When I press "Save and display"
    And I navigate to "Enrolment methods" node in "Course administration > Users"
    Then I should see "Audience sync (Audience 1 - Learner)"

    When I navigate to "Enrolled users" node in "Course administration > Users"
    Then I should see "Student 1"
