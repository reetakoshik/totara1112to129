@mod @mod_facetoface @totara
Feature: Delete a course with a seminar
  In order to delete a course
  As a teacher
  I need the seminar to not do silly things with completion during purging of course.

  @javascript
  Scenario: Delete a course with one seminar activity
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | Terry1    | Teacher1 | teacher1@example.com |
      | student1 | Sam1      | Student1 | student1@example.com |
      | student2 | Sam2      | Student2 | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                | Test seminar name                              |
      | Description         | Test seminar description                       |
      | Completion tracking | Show activity as complete when conditions are met |
      | Require grade       | 1                                                 |
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"
    And I press "Save changes"
    When I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I click on "Sam2 Student2, student2@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    Then I wait until "Sam1 Student1" "text" exists
    And I log out

    And I log in as "admin"
    And I navigate to "Courses and categories" node in "Site administration > Courses"
    And I should see "Course 1" in the "#course-listing" "css_element"
    And I click on "delete" action for "Course 1" in management course listing
    And I should see "Delete C1"
    And I should see "Course 1 (C1)"

    When I press "Delete"
    Then I should see "Deleting C1"
    And I should see "C1 has been completely deleted"
    And I press "Continue"
    And I navigate to "Courses and categories" node in "Site administration > Courses"
    And I should not see "Course 1" in the "#course-listing" "css_element"

