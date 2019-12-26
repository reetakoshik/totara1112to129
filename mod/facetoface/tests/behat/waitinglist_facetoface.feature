@mod @mod_facetoface @totara
Feature: Seminar Manager approval of waiting list
  In order to control seminar attendance
  As a manager
  I need to authorise seminar signups

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | Terry1    | Teacher1 | teacher1@moodle.com |
      | student1 | Sam1      | Student1 | student1@moodle.com |
      | student2 | Sam2      | Student2 | student2@moodle.com |
      | student3 | Sam3      | Student3 | student3@moodle.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |

    And I log in as "admin"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I set the following fields to these values:
      | Everyone on waiting list | Yes  |
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: The second student to sign up to the session should go on waiting list
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name              | Test seminar name        |
      | Description       | Test seminar description |
    And I follow "View all events"
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
      | capacity                       | 1    |
      | Enable waitlist                | 1    |
      | Send all bookings to the waiting list | 0    |
    And I press "Save changes"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Sign-up"
    And I press "Sign-up"
    And I should see "Your request was accepted"
    And I log out

    When I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Join waitlist"
    Then I should see "This event is currently full. Upon successful sign-up, you will be placed on the event's waitlist."
    And I press "Join waitlist"
    And I should see "You have been placed on the waitlist for this event."
    And I log out

    When I log in as "student3"
    And I am on "Course 1" course homepage
    And I follow "Join waitlist"
    Then I should see "This event is currently full. Upon successful sign-up, you will be placed on the event's waitlist."
    And I press "Join waitlist"
    And I should see "You have been placed on the waitlist for this event."
    And I log out

    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I follow "Attendees"
    Then I should see "Sam1 Student1"
    And I follow "Wait-list"
    Then I should see "Sam2 Student2"
    And I click on "input[type=checkbox]" "css_element" in the "Sam2 Student2" "table_row"
    And I set the field "menuf2f-actions" to "Confirm"
    And I press "Yes"
    And I should see "Successfully updated attendance"
    Then I should not see "Sam2 Student2"
    And I click on "input[type=checkbox]" "css_element" in the "Sam3 Student3" "table_row"
    And I set the field "menuf2f-actions" to "Remove from waitlist"
    And I should see "Successfully updated attendance"
    Then I should not see "Sam3 Student3"
    And I follow "Attendees"
    Then I should see "Sam2 Student2"
    And I follow "Cancellations"
    Then I should see "Sam3 Student3"
