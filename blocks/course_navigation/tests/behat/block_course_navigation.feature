@block @block_course_navigation @javascript
Feature: The course navigation block can be added to the course page
  In order to enable navigation through the course activities
  As a teacher
  I can add the course navigation block to a course and view the course structure

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                | idnumber |
      | teacher1 | Teacher   | 1        | teacher1@example.com | T1       |
      | student1 | Student   | 1        | student1@example.com | S1       |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name               | Assignment 1     |
      | Description                   | Description text |
      | assignsubmission_file_enabled | 0                |
    And I add a "Forum" to section "2" and I fill the form with:
      | name        | Forum 1     |
      | Description | Description |
    And I log out

  Scenario: Add the block and make sure it displays the current course.
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "Topic 1" in the "Course 1" "block"
    When I click on "Topic 1" "text" in the ".block_course_navigation" "css_element"
    Then I should see "Assignment 1"
    And I should see "Topic 2" in the "Course 1" "block"
    When I click on "Topic 2" "text" in the ".block_course_navigation" "css_element"
    Then I should see "Forum 1"
