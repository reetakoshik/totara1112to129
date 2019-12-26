@javascript @mod @mod_facetoface @totara
Feature: Take attendance for seminar sessions
  In order to take attendance in a seminar session
  As a teacher
  I need to set attendance status for attendees

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | email                |
      | teacher1  | Terry3    | Teacher  | teacher@example.com  |
      | student1  | Sam1      | Student1 | student1@example.com |
      | student2  | Sam2      | Student2 | student2@example.com |
      | student3  | Sam3      | Student3 | student3@example.com |
      | student4  | Sam4      | Student4 | student4@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable restricted access | 1 |
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
      | Completion tracking           | Show activity as complete when conditions are met |
      | completionstatusrequired[100] | 1                                                 |
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Seminar - Test seminar name | 1 |
    And I press "Save changes"
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[day]     | -1               |
      | timestart[month]   | 0                |
      | timestart[year]    | 0                |
      | timestart[hour]    | 0                |
      | timestart[minute]  | 0                |
      | timefinish[day]    | 0                |
      | timefinish[month]  | 0                |
      | timefinish[year]   | 0                |
      | timefinish[hour]   | 0                |
      | timefinish[minute] | -30              |
    And I press "OK"
    And I press "Save changes"
    And I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I click on "Sam2 Student2, student2@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I click on "Sam3 Student3, student3@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I click on "Sam4 Student4, student4@example.com" "option"
    And I press exact "add"
    # We must wait here, because the refresh may not happen before the save button is clicked otherwise.
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    Then I should see "Sam1 Student1"
    And I should see "Sam2 Student2"
    And I should see "Sam3 Student3"
    And I should see "Sam4 Student4"
    And I log out

  Scenario: Set attendance for individual users
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "View all events" "link"
    And I click on "Attendees" "link"
    And I switch to "Take attendance" tab
    And I set the field "Sam1 Student1's attendance" to "Fully attended"
    And I press "Save attendance"
    Then I should see "Successfully updated attendance"
    And I switch to "Attendees" tab
    And I should see "Fully attended" in the "Sam1 Student1" "table_row"
    And I should see "Booked" in the "Sam2 Student2" "table_row"
    And I should see "Booked" in the "Sam3 Student3" "table_row"
    And I should see "Booked" in the "Sam4 Student4" "table_row"
    When I navigate to "Course completion" node in "Course administration > Reports"
    And I click on "Sam1 Student1" "link"
    Then I should see "Completed" in the "#criteriastatus" "css_element"
    And I click on "C1" "link"
    When I navigate to "Course completion" node in "Course administration > Reports"
    And I click on "Sam2 Student2" "link"
    Then I should not see "Completed" in the "#criteriastatus" "css_element"
    And I log out

  Scenario: Set attendance in bulk
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "View all events" "link"
    And I click on "Attendees" "link"
    And I click on "Take attendance" "link"
    And I click on "Select Sam1 Student1" "checkbox"
    And I click on "Select Sam2 Student2" "checkbox"
    And I click on "Fully attended" "option" in the "#menubulkattendanceop" "css_element"
    And I press "Save attendance"
    Then I should see "Successfully updated attendance"
    When I navigate to "Course completion" node in "Course administration > Reports"
    And I click on "Sam1 Student1" "link"
    Then I should see "Completed" in the "#criteriastatus" "css_element"
    And I click on "C1" "link"
    And I navigate to "Course completion" node in "Course administration > Reports"
    And I click on "Sam2 Student2" "link"
    Then I should see "Completed" in the "#criteriastatus" "css_element"
    And I click on "C1" "link"
    And I navigate to "Course completion" node in "Course administration > Reports"
    And I click on "Sam3 Student3" "link"
    Then I should not see "Completed" in the "#criteriastatus" "css_element"
    And I log out

  Scenario: Reset attendance for user
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "View all events" "link"
    And I click on "Attendees" "link"
    And I switch to "Take attendance" tab
    And I set the field "Sam1 Student1's attendance" to "Fully attended"
    And I press "Save attendance"
    Then I should see "Successfully updated attendance"
    And I switch to "Attendees" tab
    And I should see "Fully attended" in the "Sam1 Student1" "table_row"
    And I should see "Booked" in the "Sam2 Student2" "table_row"
    When I switch to "Take attendance" tab
    And I set the field "Sam1 Student1's attendance" to "Partially attended"
    And I press "Save attendance"
    Then I should see "Successfully updated attendance"
    And I switch to "Attendees" tab
    And I should see "Partially attended" in the "Sam1 Student1" "table_row"
    And I should see "Booked" in the "Sam2 Student2" "table_row"
    When I switch to "Take attendance" tab
    And I set the field "Sam1 Student1's attendance" to "Not set"
    And I press "Save attendance"
    Then I should see "Successfully updated attendance"
    And I switch to "Attendees" tab
    And I should see "Booked" in the "Sam1 Student1" "table_row"
    And I should see "Booked" in the "Sam2 Student2" "table_row"
    And I log out
