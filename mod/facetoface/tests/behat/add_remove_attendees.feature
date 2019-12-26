@javascript @mod @mod_facetoface @totara
Feature: Add - Remove seminar attendees
  In order to test the add/remove seminar attendees
  As admin
  I need to add and remove attendees to/from a seminar session

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | idnumber | email                |
      | student1 | Sam1      | Student1 | sid#1    | student1@example.com |
      | student2 | Sam2      | Student2 | sid#2    | student2@example.com |
      | student3 | Sam3      | Student3 | sid#3    | student3@example.com |
      | teacher1 | Terry1    | Teacher1 | tid#1    | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity   | name              | course | idnumber |
      | facetoface | Test seminar name | C1     | seminar  |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
    And the following job assignments exist:
      | user     | fullname | idnumber |
      | student1 | job1     | ja1      |
      | student2 | job1     | ja1      |
      | student2 | job2     | ja2      |

  Scenario: Add users to a seminar session with dates
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"
    And I set the following fields to these values:
      | capacity           | 1    |
    And I press "Save changes"

    When I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I set the following fields to these values:
      | searchtext | Sam1 Student1 |
    And I click on "Search" "button" in the "#region-main" "css_element"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    Then I should see "Sam1 Student1"
    # View existing attendees in "Users to add" select box
    When I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    Then I should see "Sam1 Student1, student1@example.com"

  Scenario: Add and remove users to a seminar in past
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2018 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2018 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"
    And I set the following fields to these values:
      | capacity           | 1    |
    And I press "Save changes"

    When I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I set the following fields to these values:
      | searchtext | Sam1 Student1 |
    And I click on "Search" "button" in the "#region-main" "css_element"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    Then I should see "Sam1 Student1"
    # View existing attendees in "Users to add" select box
    And I click on "Remove users" "option" in the "#menuf2f-actions" "css_element"
    And I should see "Sam1 Student1, student1@example.com"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press "Remove"
    And I wait "1" seconds
    And I press "Continue"
    When I press "Confirm"
    Then I should not see "Sam1 Student1"
    And I should see "There are no records in this report"
    And I switch to "Cancellations" tab
    And I should see "Sam1 Student1" in the "User cancellation" "table_row"


  Scenario: Add and remove users to a Seminar session without dates (waitlist)
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I follow "Add a new event"
    And I click on "Delete" "link" in the ".f2fmanagedates" "css_element"
    And I set the following fields to these values:
      | capacity              | 1    |
    And I press "Save changes"

    When I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    And I click on "Wait-list" "link"
    Then I should see "Sam1 Student1"

  Scenario: Add users by username via textarea
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I follow "Add a new event"
    And I click on "Delete" "link" in the ".f2fmanagedates" "css_element"
    And I set the following fields to these values:
      | capacity              | 1    |
    And I press "Save changes"

    When I click on "Attendees" "link"
    And I click on "Add users via list of IDs" "option" in the "#menuf2f-actions" "css_element"
    # By default user is expected to separate ID's by newline, but comma is also supported.
    And I set the following fields to these values:
      | User identifier | Username          |
      | csvinput        | student1,student2 |
    And I press "Continue"
    And I click on "Change selected users" "link"
    Then the following fields match these values:
      | User identifier | Username          |
      | csvinput        | student1,student2 |
    And I press "Continue"
    And I press "Confirm"
    And I click on "Wait-list" "link"
    And I should see "Sam1 Student1"
    And I should see "Sam2 Student2"

  Scenario: Add users by email via textarea
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I follow "Add a new event"
    And I click on "Delete" "link" in the ".f2fmanagedates" "css_element"
    And I set the following fields to these values:
      | capacity              | 1    |
    And I press "Save changes"

    When I click on "Attendees" "link"
    And I click on "Add users via list of IDs" "option" in the "#menuf2f-actions" "css_element"
    # By default user separate ID's by newline, but comma is also supported.
    And I set the following fields to these values:
      | User identifier | Email address |
      | csvinput        | student1@example.com,student2@example.com |
    And I press "Continue"
    And I click on "Change selected users" "link"
    Then the following fields match these values:
      | User identifier | Email address |
      | csvinput        | student1@example.com,student2@example.com |
    And I press "Continue"
    And I press "Confirm"
    And I click on "Wait-list" "link"
    And I should see "Sam1 Student1"
    And I should see "Sam2 Student2"

  @_file_upload
  Scenario: Add users via file upload and then remove
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[day]     | +1               |
      | timestart[month]   | 0                |
      | timestart[year]    | 0                |
      | timestart[hour]    | 0                |
      | timestart[minute]  | 0                |
      | timefinish[day]    | +1               |
      | timefinish[month]  | 0                |
      | timefinish[year]   | 0                |
      | timefinish[hour]   | +1               |
      | timefinish[minute] | 0                |
    And I press "OK"
    And I set the following fields to these values:
      | capacity           | 2                |
    And I press "Save changes"

    When I click on "Attendees" "link"
    And I click on "Add users via file upload" "option" in the "#menuf2f-actions" "css_element"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees.csv" file to "CSV file" filemanager
    And I press "Continue"
    And I press "Confirm"
    And I should see "Sam1 Student1"
    And I should see "Sam2 Student2"

    When I click on "Remove users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press "Remove"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    Then I should see "Sam2 Student2"
    And I should see "Bulk remove users success - Successfully removed 1 attendees."
    And I should not see "Sam1 Student1"

  Scenario: Use the allow scheduling conflicts checkbox
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"
    And I set the following fields to these values:
      | capacity           | 1    |
    And I press "Save changes"

    When I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    Then I should see "Sam1 Student1"

    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name two    |
      | Description | Test seminar description |
    And I follow "Test seminar name two"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"
    And I set the following fields to these values:
      | capacity           | 1    |
    And I press "Save changes"

    When I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I press "Continue"
    Then I should see "1 problem(s) encountered during import."
    When I click on "View results" "link"
    Then I should see "Sam1 Student1"
    And I should see "The signup user has conflicting signups"
    When I press exact "Close"
    And I set the following fields to these values:
      | Allow scheduling conflicts | 1 |
    And I press "Continue"
    Then I should see "Add users (step 2 of 2)"
    When I press "Confirm"
    Then I should see "Bulk add attendees success - Successfully added/edited 1 attendees."
    When I click on "(View results)" "link"
    Then I should see "Added successfully" in the "Bulk add attendees results" "totaradialogue"
    When I press "Cancel"
    Then I should see "Sam1 Student1"

  @_file_upload
  Scenario: Use invalid csv file to test the errors
    Given I log in as "admin"

    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[day]     | +1               |
      | timestart[month]   | 0                |
      | timestart[year]    | 0                |
      | timestart[hour]    | 0                |
      | timestart[minute]  | 0                |
      | timefinish[day]    | +1               |
      | timefinish[month]  | 0                |
      | timefinish[year]   | 0                |
      | timefinish[hour]   | +1               |
      | timefinish[minute] | 0                |
    And I press "OK"
    And I set the following fields to these values:
      | capacity           | 2                |
    And I press "Save changes"

    When I click on "Attendees" "link"
    And I click on "Add users via file upload" "option" in the "#menuf2f-actions" "css_element"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees_invalid_columns.csv" file to "CSV file" filemanager
    And I press "Continue"
    And I should see "Invalid CSV file format - number of columns is not constant!"

  Scenario: Add users with Job Assignments via select
    Given I log in as "admin"
    And I set the following administration settings values:
      | facetoface_selectjobassignmentonsignupglobal | 1 |
    And I log out

    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | Select job assignment on signup | 1 |
    And I press "Save and display"
    And I follow "Add a new event"
    And I press "Save changes"

    And I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam3 Student3, student3@example.com" "option"
    And I click on "Sam2 Student2, student2@example.com" "option"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press "Add"
    And I press "Continue"
    And I click on ".attendee-edit-job-assignment" "css_element" in the "Sam3 Student3" "table_row"
    And I should see "User has no active job assignments"
    And I press "Close"
    And I click on ".attendee-edit-job-assignment" "css_element" in the "Sam2 Student2" "table_row"
    And I set the following fields to these values:
      | Select a job assignment | job2 |
    And I press "Update job assignment"
    And I click on ".attendee-edit-job-assignment" "css_element" in the "Sam1 Student1" "table_row"
    And I set the following fields to these values:
      | Select a job assignment | job1 |
    And I press "Update job assignment"
    When I press "Confirm"
    Then I should see "job1" in the "Sam1 Student1" "table_row"
    And I should see "job2" in the "Sam2 Student2" "table_row"

  @_file_upload
  Scenario: Add users with Job Assignments via CSV
    Given I log in as "admin"
    And I set the following administration settings values:
      | facetoface_selectjobassignmentonsignupglobal | 1 |
    And I log out

    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | Select job assignment on signup | 1 |
    And I press "Save and display"
    And I follow "Add a new event"
    And I press "Save changes"

    And I click on "Attendees" "link"
    And I click on "Add users via file upload" "option" in the "#menuf2f-actions" "css_element"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees_with_ja.csv" file to "CSV file" filemanager
    And I press "Continue"
    When I press "Confirm"
    Then I should see "job1" in the "Sam1 Student1" "table_row"
    And I should see "job2" in the "Sam2 Student2" "table_row"

  Scenario: User identity information is shown to editing trainer when adding and removing attendees
    Given I log in as "admin"
    And I set the following administration settings values:
      | Show user identity | ID number |
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"
    And I set the following fields to these values:
      | capacity           | 1    |
    And I press "Save changes"

    When I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I set the following fields to these values:
      | searchtext | Sam |
    And I press "Search"
    And I click on "Sam1 Student1, sid#1, student1@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I should see "Sam1 Student1"
    And I should see "student1@example.com"
    And I should see "sid#1"
    And I press "Confirm"
    Then I should see "Sam1 Student1"

    # View existing attendees in "Users to add" select box
    When I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    Then I should see "Sam1 Student1, sid#1, student1@example.com"

    When I press "Continue"
    Then I should see "Please select users before continuing."

    When I press "Cancel"
    And I click on "Remove users" "option" in the "#menuf2f-actions" "css_element"
    And I set the following fields to these values:
      | searchtext | Sam |
    And I press "Search"
    And I click on "Sam1 Student1, sid#1, student1@example.com" "option"
    And I press "Remove"
    And I wait "1" seconds
    And I press "Continue"
    And I should see "Sam1 Student1"
    And I should see "student1@example.com"
    And I should see "sid#1"
    And I press "Confirm"
    Then I should not see "Sam1 Student1"

  Scenario: User identity information is not shown to editing trainer when the capability is prohibited
    Given I log in as "admin"
    And I set the following administration settings values:
      | Show user identity | ID number |
    And I set the following system permissions of "Editing Trainer" role:
      | moodle/site:viewuseridentity | Prohibit |
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"
    And I set the following fields to these values:
      | capacity           | 1    |
    And I press "Save changes"

    When I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I set the following fields to these values:
      | searchtext | Sam |
    And I press "Search"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I should see "Sam1 Student1"
    And I should see "student1@example.com"
    And I should see "sid#1"
    And I press "Confirm"
    Then I should see "Sam1 Student1"

  # View existing attendees in "Users to add" select box
    When I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    Then I should see "Sam1 Student1, student1@example.com"

    When I press "Continue"
    Then I should see "Please select users before continuing."

    When I press "Cancel"
    And I click on "Remove users" "option" in the "#menuf2f-actions" "css_element"
    And I set the following fields to these values:
      | searchtext | Sam |
    And I press "Search"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press "Remove"
    And I wait "1" seconds
    And I press "Continue"
    And I should see "Sam1 Student1"
    And I should see "student1@example.com"
    And I should see "sid#1"
    And I press "Confirm"
    Then I should not see "Sam1 Student1"
