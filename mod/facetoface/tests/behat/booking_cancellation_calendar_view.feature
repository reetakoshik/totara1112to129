@mod @mod_facetoface @totara @core_calendar @javascript
Feature: Seminar event booking cancellation calendar views
  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | student  | Stu       | Dent     | student@example.com |
      | teacher  | Tea       | Cher     | teacher@example.com |
    And the following "course enrolments" exist:
      | user      | course | role           |
      | student   | C1     | student        |
      | teacher   | C1     | editingteacher |

  Scenario: Calendar view after booking cancellation - multi session single event
    And I log in as "teacher"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                                 | Seminar 1 |
      | How many times the user can sign-up? | Unlimited |
      | Show entry on user's calendar        | 1         |
    And I follow "View all events"
    And I follow "Add a new event"
    And I follow "show-selectdate0-dialog"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[day]     | +1               |
      | timestart[hour]    | +1               |
      | timefinish[day]    | +1               |
      | timefinish[hour]   | +2               |
    And I click on "OK" "button" in the "Select date" "totaradialogue"

    And I press "Add a new session"
    And I follow "show-selectdate1-dialog"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[day]     | +2               |
      | timestart[hour]    | 20               |
      | timefinish[day]    | +3               |
      | timefinish[hour]   |  6               |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    And I log out

    # NOTE: Coalescing duplicated calendar events is out of scope for now
    # If it is fixed, all the following "exactly X times" will need to be divided by two

    And I log in as "student"
    When I click on "Go to calendar" "link"
    Then I should see "Go to this Seminar event" exactly "2" times
    And I should not see "You are booked for this Seminar event"

    When I follow "Seminar event"
    And I press "Sign-up"
    Then I should see "Your request was accepted"

    When I click on "Go to calendar" "link"
    Then I should see "You are booked for this Seminar event" exactly "4" times
    And I should not see "Go to this Seminar event"

    When I follow "Seminar event"
    And I follow "Cancel booking"
    And I press "Yes"
    Then I should see "Your booking has been cancelled"

    When I click on "Go to calendar" "link"
    Then I should see "Go to this Seminar event" exactly "2" times
    And I should not see "You are booked for this Seminar event"

  Scenario: Calendar view after booking cancellation - single event with manager approval
    And the following job assignments exist:
      | user    | fullname | idnumber | manager |
      | student | jajaja   | 1        | teacher |
    And I log in as "teacher"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                                 | Seminar 1 |
      | How many times the user can sign-up? | Unlimited |
      | Manager Approval                     | 1         |
      | Show entry on user's calendar        | 1         |
    And I follow "View all events"
    And I follow "Add a new event"
    And I follow "show-selectdate0-dialog"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[day]     | +1               |
      | timestart[hour]    | +1               |
      | timefinish[day]    | +1               |
      | timefinish[hour]   | +2               |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    And I log out

    And I log in as "student"
    When I click on "Go to calendar" "link"
    Then I should see "Go to this Seminar event" exactly "1" times
    And I should not see "You are booked for this Seminar event"

    When I follow "Seminar event"
    And I press "Request approval"
    Then I should see "Your request was sent to your manager for approval"

    When I click on "Go to calendar" "link"
    Then I should see "You are booked for this Seminar event" exactly "1" times
    And I should not see "Go to this Seminar event"

    When I follow "Seminar event"
    And I follow "Cancel booking"
    And I press "Yes"
    Then I should see "Your booking has been cancelled"

    When I click on "Go to calendar" "link"
    Then I should see "Go to this Seminar event" exactly "1" times
    And I should not see "You are booked for this Seminar event"

    When I follow "Seminar event"
    And I press "Request approval"
    Then I should see "Your request was sent to your manager for approval"

    And I log out
    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I follow "Seminar 1"
    And I follow "Attendees"
    And I switch to "Approval required" tab
    And I click on "input[value='2']" "css_element" in the "Stu Dent" "table_row"
    And I press "Update requests"

    And I log out
    And I log in as "student"
    When I click on "Go to calendar" "link"
    Then I should see "You are booked for this Seminar event" exactly "2" times

    When I follow "Seminar event"
    And I follow "Cancel booking"
    And I press "Yes"
    Then I should see "Your booking has been cancelled"

    When I click on "Go to calendar" "link"
    Then I should see "Go to this Seminar event" exactly "1" times
    And I should not see "You are booked for this Seminar event"
