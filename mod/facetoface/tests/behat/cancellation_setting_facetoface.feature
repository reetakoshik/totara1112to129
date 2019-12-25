@mod @mod_facetoface @totara
Feature: Cancellation for session
  In order to allow or not cancellations in seminar sessions
  As a teacher
  I need to create seminar sessions with different settings (always/never/cut-off period)

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username     | firstname | lastname  | email                 |
      | teacher1     | Terry1    | Teacher   | teacher1@example.com  |
      | teacher2     | Terry2    | Teacher   | teacher2@example.com  |
      | student1     | Sam1      | Student1  | student1@example.com  |
      | student2     | Sam2      | Student2  | student2@example.com  |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher2 | C1     | teacher        |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                                    | Test seminar name        |
      | Description                             | Test seminar description |
      | How many times the user can sign-up?    | Unlimited                |
    And I log out

  @javascript
  Scenario: User can cancel their booking at any time until seminar session starts
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
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
      | capacity           | 3                |
    And I click on "At any time" "radio"
    And I press "Save changes"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I click on the link "Sign-up" in row 1
    And I press "Sign-up"
    Then I should see "Your request was accepted"
    And I should see "Cancel booking"
    When I click on the link "Cancel booking" in row 1
    And I press "Yes"
    And I should not see "Cancel booking"
    And I log out

    # Check that editing teacher can manage cancellation notes
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I click on the link "Attendees" in row 1
    And I follow "Cancellations"
    And I should see "Show cancellation reason"
    And I click on "a.attendee-cancellation-note" "css_element"
    And I should see "Sam1 Student1 - Cancellation note"
    And I am on homepage
    And I log out

    # Check that teacher can not manage cancellation notes
    And I log in as "teacher2"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I click on the link "Attendees" in row 1
    And I follow "Cancellations"
    And I should not see "Show cancellation reason"
    And I am on homepage
    And I log out

  @javascript
  Scenario: User cannot cancel their booking (Never)
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[day]     | +2               |
      | timestart[month]   | 0                |
      | timestart[year]    | 0                |
      | timestart[hour]    | 0                |
      | timestart[minute]  | 0                |
      | timefinish[day]    | +2               |
      | timefinish[month]  | 0                |
      | timefinish[year]   | 0                |
      | timefinish[hour]   | +1               |
      | timefinish[minute] | 0                |
    And I press "OK"
    And I set the following fields to these values:
      | capacity           | 3                |
    And I click on "Never" "radio"
    And I press "Save changes"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I click on the link "Sign-up" in row 1
    And I press "Sign-up"
    Then I should see "Your request was accepted"
    And I should not see "Cancel booking"
    And I log out

  @javascript
  Scenario: User can cancel their booking if cut-off period is not reached
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[day]     | +3               |
      | timestart[month]   | 0                |
      | timestart[year]    | 0                |
      | timestart[hour]    | 0                |
      | timestart[minute]  | 0                |
      | timefinish[day]    | +3               |
      | timefinish[month]  | 0                |
      | timefinish[year]   | 0                |
      | timefinish[hour]   | +1               |
      | timefinish[minute] | 0                |
    And I press "OK"
    And I set the following fields to these values:
      | capacity           | 3                |
    And I click on "Until specified period" "radio"
    And I press "Save changes"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I click on the link "Sign-up" in row 1
    And I press "Sign-up"
    Then I should see "Your request was accepted"
    And I should see "Cancel booking"
    And I log out

    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I click on "Edit event" "link"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[day]     | +1               |
      | timestart[month]   | 0                |
      | timestart[year]    | 0                |
      | timestart[hour]    | -1               |
      | timestart[minute]  | 0                |
      | timefinish[day]    | +1               |
      | timefinish[month]  | 0                |
      | timefinish[year]   | 0                |
      | timefinish[hour]   | -1               |
      | timefinish[minute] | 0                |
    And I press "OK"
    And I set the following fields to these values:
      | cancellationcutoff[number]   | 2      |
      | cancellationcutoff[timeunit] | days   |
    And I press "Save changes"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    Then I should not see "Cancel booking"
    And I follow "More info"
    Then I should not see "Cancel booking"
    And I log out

  @javascript
  Scenario: User can cancel their booking at any time until session starts even when cancellation note field is deleted
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "View all events"
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
      | capacity           | 3                |
    And I click on "At any time" "radio"
    And I press "Save changes"

    And I click on "Home" in the totara menu
    And I navigate to "Custom fields" node in "Site administration > Seminars"
    And I click on "User cancellation" "link"
    And I click on "Hide" "link" in the "Cancellation note" "table_row"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I click on the link "Sign-up" in row 1
    And I press "Sign-up"
    Then I should see "Your request was accepted"
    And I should see "Cancel booking"
    When I click on the link "Cancel booking" in row 1
    And I press "Yes"
    And I should not see "Cancel booking"
    And I log out
