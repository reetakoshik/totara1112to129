@mod @mod_facetoface @totara @javascript
Feature: Check previous and upcomings sections are right populated
  In order to see if all events are in their right section (previous and upcomings)
  As admin
  I need to create sessions with different status

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | One      | student1@example.com |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                                 | Test seminar in progress |
      | Description                          | Test seminar in progress |
      | How many times the user can sign-up? | Unlimited                |
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[day]     | -2               |
      | timestart[month]   | 0                |
      | timestart[year]    | 0                |
      | timestart[hour]    | 0                |
      | timestart[minute]  | 0                |
      | timefinish[day]    | -2               |
      | timefinish[month]  | 0                |
      | timefinish[year]   | 0                |
      | timefinish[hour]   | +1               |
      | timefinish[minute] | 0                |
    And I press "OK"

    And I press "Add a new session"
    And I follow "show-selectdate1-dialog"
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
    And I click on "OK" "button" in the "Select date" "totaradialogue"

    And I press "Add a new session"
    And I follow "show-selectdate2-dialog"
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
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"

    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 1999 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 1999 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"
    And I press "Save changes"

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
    And I press "Save changes"
    And I log out

  Scenario: Check upcoming and previous events are displayed accordingly
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    Then I should see "Event in progress" in the ".upcomingsessionlist" "css_element"
    And I should see "1 January 2020" in the ".upcomingsessionlist" "css_element"
    And I should see "1 January 1999" in the ".previoussessionlist" "css_element"

    When I follow "C1"
    Then I should see "Event in progress"
    And I should see "1 January 2020"
    And I should not see "1 January 1999"

    # Sign up for a session and make sure it is displayed in the course page.
    And I click on "Sign-up" "link" in the "1 January 2020" "table_row"
    And I press "Sign-up"
    When I follow "C1"
    Then I should see "Booked"
    And I should not see "Event in progress"
    And I should not see "Event over"
    And I follow "View all events"
    Then I should see "Booked"
    And I should see "Event in progress"
    And I should see "Event over"
    And I log out

    # Change sign up for multiple events setting.
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I navigate to "Edit settings" in current page administration
    And I expand all fieldsets
    And I set the field "How many times the user can sign-up?" to "1"
    And I press "Save and return to course"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "1 January 2020"
    And I should not see "1 January 1999"
    And I should not see "Event in progress"
    And I log out
