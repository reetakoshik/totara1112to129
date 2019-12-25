@core @core_course
Feature: See restrictions on activities to courses
  In order to assist with students access queries
  As a teacher
  I need to know what restrictions have been placed on activites to a course

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |

  @javascript
  Scenario: Create two activities with restrictions, then view the course.
    # First activity.
    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    When I follow "Add an activity or resource"
    Then "Add an activity or resource" "dialogue" should be visible
    When I click on "Database" "radio" in the "Add an activity or resource" "dialogue"
    And I click on "Add" "button" in the "Add an activity or resource" "dialogue"
    Then I should see "Adding a new Database"
    And I expand all fieldsets
    And I set the field "Name" to "DB1"

    # Add Name restriction - positive test.
    When I click on "Add restriction..." "button"
    Then "Add restriction..." "dialogue" should be visible
    When I click on "User profile" "button" in the "Add restriction..." "dialogue"
    And I select "First name" from the "Choose..." singleselect
    And I select "contains" from the "is equal to" singleselect
    And I set the field "Value to compare against" to "Teacher"
    Then I click on "Save and return to course" "button"
    And I should see "Not available unless: Your First name contains Teacher"

    # Second activity.
    When I follow "Add an activity or resource"
    Then "Add an activity or resource" "dialogue" should be visible
    When I click on "Database" "radio" in the "Add an activity or resource" "dialogue"
    And I click on "Add" "button" in the "Add an activity or resource" "dialogue"
    Then I should see "Adding a new Database"
    And I expand all fieldsets
    And I set the field "Name" to "DB2"

    # Add Name restriction - negative test.
    When I click on "Add restriction..." "button"
    Then "Add restriction..." "dialogue" should be visible
    When I click on "User profile" "button" in the "Add restriction..." "dialogue"
    And I select "First name" from the "Choose..." singleselect
    And I select "doesnotcontain" from the "is equal to" singleselect
    And I set the field "Value to compare against" to "Teacher"
    And I click on "Save and return to course" "button"
    And I should see "Not available unless: Your First name does not contain Teacher"
    And I log out

    # Ensure that editing teacher can see both restriction descriptions.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then I should see "Not available unless: Your First name contains Teacher"
    And I should see "Not available unless: Your First name does not contain Teacher"
