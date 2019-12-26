@mod @mod_facetoface @totara @javascript
Feature: Seminar event cancellation basic
  After seminar events have been created
  As a user
  I need to be able to cancel them.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | learner1 | Learner   | One      | learner1@example.com |
      | learner2 | Learner   | Two      | learner2@example.com |

    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |

    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | learner1 | C1     | student        |
      | learner2 | C1     | student        |

    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test Seminar |
      | Description | Test Seminar |
    And I follow "View all events"

  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_100: cancel event with single future date, with attendees and confirm booking status.
    Given I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 39 |
    And I follow "show-selectdate0-dialog"
    And I set the following fields to these values:
      | sessiontimezone     | Pacific/Auckland |
      | timestart[day]      | 10               |
      | timestart[month]    | 2                |
      | timestart[year]     | 2025             |
      | timestart[hour]     | 9                |
      | timestart[minute]   | 0                |
      | timestart[timezone] | Pacific/Auckland |
      | timefinish[day]     | 10               |
      | timefinish[month]   | 2                |
      | timefinish[year]    | 2025             |
      | timefinish[hour]    | 15               |
      | timefinish[minute]  | 0                |
      | timefinish[timezone]| Pacific/Auckland |
    And I press "OK"
    And I press "Save changes"

    Given I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Learner One, learner1@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I click on "Learner Two, learner2@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    And I follow "Go back"

    When I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    Then I should see "9:00 AM - 3:00 PM Pacific/Auckland" in the "10 February 2025" "table_row"
    And I should see "2 / 39" in the "10 February 2025" "table_row"
    And I should see "Booking open" in the "10 February 2025" "table_row"
    And "Cancel event" "link" should exist in the "10 February 2025" "table_row"

    When I click on "Cancel event" "link" in the "10 February 2025" "table_row"
    Then I should see "Cancelling event in Test Seminar"
    And I should see "10 February 2025, 9:00 AM - 3:00 PM Pacific/Auckland"

    When I press "No"
    Then I should see "9:00 AM - 3:00 PM Pacific/Auckland" in the "10 February 2025" "table_row"
    And I should see "2 / 39" in the "10 February 2025" "table_row"
    And I should see "Booking open" in the "10 February 2025" "table_row"

    When I click on "Cancel event" "link" in the "10 February 2025" "table_row"
    And I press "Yes"
    Then I should see "9:00 AM - 3:00 PM Pacific/Auckland" in the "10 February 2025" "table_row"
    And I should see "2 / 39" in the "10 February 2025" "table_row"
    And I should see "Event cancelled" in the "10 February 2025" "table_row"
    And I should see "Sign-up unavailable" in the "10 February 2025" "table_row"
    And "Cancel event" "link" should not exist in the "10 February 2025" "table_row"
    And "Copy event" "link" should exist in the "10 February 2025" "table_row"
    And "Delete event" "link" should exist in the "10 February 2025" "table_row"
    And "Edit event" "link" should not exist in the "10 February 2025" "table_row"

    And I navigate to "Events report" node in "Site administration > Seminars"
    And I should see "N/A" in the ".session_bookingstatus div span" "css_element"
    And I should see "N/A" in the "Test Seminar" "table_row"

    When I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    Then I should see "9:00 AM - 3:00 PM Pacific/Auckland" in the "10 February 2025" "table_row"
    And I should see "2 / 39" in the "10 February 2025" "table_row"
    And I should see "Sign-up unavailable" in the "10 February 2025" "table_row"
    And I should see "Event cancelled" in the "10 February 2025" "table_row"
    And "Cancel event" "link" should not exist in the "10 February 2025" "table_row"
    And "Copy event" "link" should exist in the "10 February 2025" "table_row"
    And "Delete event" "link" should exist in the "10 February 2025" "table_row"
    And "Edit event" "link" should not exist in the "10 February 2025" "table_row"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_101: cancel event with multiple future dates, with attendees.
    Given I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 39 |
    And I follow "show-selectdate0-dialog"
    And I set the following fields to these values:
      | sessiontimezone     | Pacific/Auckland |
      | timestart[day]      | 10               |
      | timestart[month]    | 2                |
      | timestart[year]     | 2025             |
      | timestart[hour]     | 9                |
      | timestart[minute]   | 0                |
      | timestart[timezone] | Pacific/Auckland |
      | timefinish[day]     | 10               |
      | timefinish[month]   | 2                |
      | timefinish[year]    | 2025             |
      | timefinish[hour]    | 15               |
      | timefinish[minute]  | 0                |
      | timefinish[timezone]| Pacific/Auckland |
    And I press "OK"

    Given I press "Add a new session"
    And I follow "show-selectdate1-dialog"
    And I set the following fields to these values:
      | sessiontimezone     | Pacific/Auckland |
      | timestart[day]      | 11               |
      | timestart[month]    | 3                |
      | timestart[year]     | 2026             |
      | timestart[hour]     | 10               |
      | timestart[minute]   | 0                |
      | timestart[timezone] | Pacific/Auckland |
      | timefinish[day]     | 11               |
      | timefinish[month]   | 3                |
      | timefinish[year]    | 2026             |
      | timefinish[hour]    | 16               |
      | timefinish[minute]  | 0                |
      | timefinish[timezone]| Pacific/Auckland |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"

    Given I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Learner One, learner1@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I click on "Learner Two, learner2@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"

    When I follow "Go back"
    Then I should see "9:00 AM - 3:00 PM Pacific/Auckland" in the "10 February 2025" "table_row"
    And I should see "10:00 AM - 4:00 PM Pacific/Auckland" in the "11 March 2026" "table_row"
    And I should see "2 / 39" in the "10 February 2025" "table_row"
    And I should see "Booking open" in the "10 February 2025" "table_row"
    And "Cancel event" "link" should exist in the "10 February 2025" "table_row"

    When I click on "Cancel event" "link" in the "10 February 2025" "table_row"
    And I press "Yes"
    Then I should see "9:00 AM - 3:00 PM Pacific/Auckland" in the "10 February 2025" "table_row"
    And I should see "10:00 AM - 4:00 PM Pacific/Auckland" in the "11 March 2026" "table_row"
    And I should see "2 / 39" in the "10 February 2025" "table_row"
    And I should see "Event cancelled" in the "10 February 2025" "table_row"
    And I should see "Sign-up unavailable" in the "10 February 2025" "table_row"
    And "Cancel event" "link" should not exist in the "10 February 2025" "table_row"
    And "Copy event" "link" should exist in the "10 February 2025" "table_row"
    And "Delete event" "link" should exist in the "10 February 2025" "table_row"
    And "Edit event" "link" should not exist in the "10 February 2025" "table_row"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_102: cancel event with future and past dates, with attendees.
    Given I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 39 |
    And I follow "show-selectdate0-dialog"
    And I set the following fields to these values:
      | sessiontimezone     | Pacific/Auckland |
      | timestart[day]      | 10               |
      | timestart[month]    | 2                |
      | timestart[year]     | 2025             |
      | timestart[hour]     | 9                |
      | timestart[minute]   | 0                |
      | timestart[timezone] | Pacific/Auckland |
      | timefinish[day]     | 10               |
      | timefinish[month]   | 2                |
      | timefinish[year]    | 2025             |
      | timefinish[hour]    | 15               |
      | timefinish[minute]  | 0                |
      | timefinish[timezone]| Pacific/Auckland |
    And I press "OK"

    Given I press "Add a new session"
    And I follow "show-selectdate1-dialog"
    And I fill seminar session with relative date in form data:
      | sessiontimezone     | Pacific/Auckland |
      | timestart[day]      | -10              |
      | timestart[timezone] | Pacific/Auckland |
      | timefinish[day]     | -10              |
      | timefinish[timezone]| Pacific/Auckland |
    And I click on "OK" "button" in the "Select date" "totaradialogue"

    Given I follow "show-selectdate1-dialog"
    And I set the following fields to these values:
      | sessiontimezone     | Pacific/Auckland |
      | timestart[hour]     | 10               |
      | timestart[minute]   | 0                |
      | timestart[timezone] | Pacific/Auckland |
      | timefinish[hour]    | 16               |
      | timefinish[minute]  | 0                |
      | timefinish[timezone]| Pacific/Auckland |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"

    Given I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Learner One, learner1@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I click on "Learner Two, learner2@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    And I follow "Go back"

    When I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    Then I should see "9:00 AM - 3:00 PM Pacific/Auckland" in the "10 February 2025" "table_row"
    And I should see date "-10 day Pacific/Auckland" formatted "%d %B %Y"
    And I should see "10:00 AM - 4:00 PM Pacific/Auckland"
    And I should see "2 / 39" in the "10:00 AM - 4:00 PM Pacific/Auckland" "table_row"
    And I should see "Event in progress" in the "10:00 AM - 4:00 PM Pacific/Auckland" "table_row"
    And "Cancel event" "link" should not exist in the "10:00 AM - 4:00 PM Pacific/Auckland" "table_row"
    And "Edit event" "link" should exist in the "10:00 AM - 4:00 PM Pacific/Auckland" "table_row"
    And "Copy event" "link" should exist in the "10:00 AM - 4:00 PM Pacific/Auckland" "table_row"
    And "Delete event" "link" should exist in the "10:00 AM - 4:00 PM Pacific/Auckland" "table_row"

    When I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    Then I should see "9:00 AM - 3:00 PM Pacific/Auckland" in the "10 February 2025" "table_row"
    And I should see date "-10 day Pacific/Auckland" formatted "%d %B %Y"
    And I should see "10:00 AM - 4:00 PM Pacific/Auckland"
    And I should see "2 / 39" in the "10:00 AM - 4:00 PM Pacific/Auckland" "table_row"
    And I should see "Event in progress" in the "10:00 AM - 4:00 PM Pacific/Auckland" "table_row"
    And "Cancel event" "link" should not exist in the "10:00 AM - 4:00 PM Pacific/Auckland" "table_row"
    And "Copy event" "link" should exist in the "10:00 AM - 4:00 PM Pacific/Auckland" "table_row"
    And "Edit event" "link" should exist in the "10:00 AM - 4:00 PM Pacific/Auckland" "table_row"
    And "Delete event" "link" should exist in the "10:00 AM - 4:00 PM Pacific/Auckland" "table_row"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_103: cancel event with today and future dates, with attendees.
    Given I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 39 |
    And I follow "show-selectdate0-dialog"
    And I set the following fields to these values:
      | sessiontimezone     | Pacific/Auckland |
      | timestart[day]      | 10               |
      | timestart[month]    | 2                |
      | timestart[year]     | 2025             |
      | timestart[hour]     | 9                |
      | timestart[minute]   | 0                |
      | timestart[timezone] | Pacific/Auckland |
      | timefinish[day]     | 10               |
      | timefinish[month]   | 2                |
      | timefinish[year]    | 2025             |
      | timefinish[hour]    | 15               |
      | timefinish[minute]  | 0                |
      | timefinish[timezone]| Pacific/Auckland |
    And I press "OK"

    Given I press "Add a new session"
    And I follow "show-selectdate1-dialog"
    And I fill seminar session with relative date in form data:
      | sessiontimezone     | Pacific/Auckland |
      | timestart[day]      | 0              |
      | timestart[timezone] | Pacific/Auckland |
      | timefinish[day]     | 0              |
      | timefinish[timezone]| Pacific/Auckland |
    And I click on "OK" "button" in the "Select date" "totaradialogue"

    Given I follow "show-selectdate1-dialog"
    And I set the following fields to these values:
      | sessiontimezone     | Pacific/Auckland |
      | timestart[hour]     | 0                |
      | timestart[minute]   | 5                |
      | timestart[timezone] | Pacific/Auckland |
      | timefinish[hour]    | 23               |
      | timefinish[minute]  | 55               |
      | timefinish[timezone]| Pacific/Auckland |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"

    Given I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Learner One, learner1@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I click on "Learner Two, learner2@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"

    When I follow "Go back"
    Then I should see "9:00 AM - 3:00 PM Pacific/Auckland" in the "10 February 2025" "table_row"
    And I should see date "0 day Pacific/Auckland" formatted "%d %B %Y"
    And I should see "12:05 AM - 11:55 PM Pacific/Auckland"
    And I should see "2 / 39" in the "12:05 AM - 11:55 PM Pacific/Auckland" "table_row"
    And I should see "Event in progress" in the "12:05 AM - 11:55 PM Pacific/Auckland" "table_row"
    And "Cancel event" "link" should not exist in the "12:05 AM - 11:55 PM Pacific/Auckland" "table_row"
    And "Edit event" "link" should exist in the "12:05 AM - 11:55 PM Pacific/Auckland" "table_row"
    And "Copy event" "link" should exist in the "12:05 AM - 11:55 PM Pacific/Auckland" "table_row"
    And "Delete event" "link" should exist in the "12:05 AM - 11:55 PM Pacific/Auckland" "table_row"

    When I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    Then I should see "9:00 AM - 3:00 PM Pacific/Auckland" in the "10 February 2025" "table_row"
    And I should see date "0 day Pacific/Auckland" formatted "%d %B %Y"
    And I should see "12:05 AM - 11:55 PM Pacific/Auckland"
    And I should see "2 / 39" in the "12:05 AM - 11:55 PM Pacific/Auckland" "table_row"
    And I should see "Event in progress" in the "12:05 AM - 11:55 PM Pacific/Auckland" "table_row"
    And "Cancel event" "link" should not exist in the "12:05 AM - 11:55 PM Pacific/Auckland" "table_row"
    And "Edit event" "link" should exist in the "12:05 AM - 11:55 PM Pacific/Auckland" "table_row"
    And "Copy event" "link" should exist in the "12:05 AM - 11:55 PM Pacific/Auckland" "table_row"
    And "Delete event" "link" should exist in the "12:05 AM - 11:55 PM Pacific/Auckland" "table_row"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_104: cancel event with today, in 1 hr, with attendees.
    Given I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 39 |
    And I follow "show-selectdate0-dialog"
    And I fill seminar session with relative date in form data:
      | sessiontimezone     | Australia/Perth |
      | timestart[day]      | 0               |
      | timestart[month]    | 0               |
      | timestart[year]     | 0               |
      | timestart[hour]     | 1               |
      | timestart[minute]   | 0               |
      | timestart[timezone] | Australia/Perth |
      | timefinish[day]     | 0               |
      | timefinish[month]   | 0               |
      | timefinish[year]    | 0               |
      | timefinish[hour]    | 2               |
      | timefinish[minute]  | 0               |
      | timefinish[timezone]| Australia/Perth |
    And I press "OK"
    And I press "Save changes"

    Given I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Learner One, learner1@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I click on "Learner Two, learner2@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"

    When I follow "Go back"
    Then I should see date "0 day Australia/Perth" formatted "%d %B %Y"
    And I should see "Booking open"
    And I should see "2 / 39" in the "Booking open" "table_row"
    And "Cancel event" "link" should exist in the "2 / 39" "table_row"

    When I click on "Cancel event" "link" in the "2 / 39" "table_row"
    And I press "Yes"
    Then I should see "2 / 39" in the "Event cancelled" "table_row"
    And I should see "Sign-up unavailable" in the "Event cancelled" "table_row"
    And "Cancel event" "link" should not exist in the "Event cancelled" "table_row"
    And "Edit event" "link" should not exist in the "Event cancelled" "table_row"
    And "Copy event" "link" should exist in the "Event cancelled" "table_row"
    And "Delete event" "link" should exist in the "Event cancelled" "table_row"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_105: cancel event with single past date with no attendees.
    Given I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 39 |
    And I follow "show-selectdate0-dialog"
    And I fill seminar session with relative date in form data:
      | sessiontimezone     | Australia/Perth |
      | timestart[day]      | 0               |
      | timestart[month]    | 0               |
      | timestart[year]     | 0               |
      | timestart[hour]     | -2              |
      | timestart[minute]   | 0               |
      | timestart[timezone] | Australia/Perth |
      | timefinish[day]     | 0               |
      | timefinish[month]   | 0               |
      | timefinish[year]    | 0               |
      | timefinish[hour]    | 2               |
      | timefinish[minute]  | 0               |
      | timefinish[timezone]| Australia/Perth |
    And I press "OK"

    When I press "Save changes"
    Then I should see date "0 day Australia/Perth" formatted "%d %B %Y"
    And I should see "Event in progress"
    And I should see "0 / 39" in the "Event in progress" "table_row"
    And "Cancel event" "link" should not exist in the "Event in progress" "table_row"
    And "Edit event" "link" should exist in the "Event in progress" "table_row"
    And "Copy event" "link" should exist in the "Event in progress" "table_row"
    And "Delete event" "link" should exist in the "Event in progress" "table_row"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_106: cancel event with single future date with no attendees.
    Given I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 39 |
    And I follow "show-selectdate0-dialog"
    And I fill seminar session with relative date in form data:
      | sessiontimezone     | Pacific/Auckland |
      | timestart[day]      | 10               |
      | timestart[month]    | 0                |
      | timestart[year]     | 0                |
      | timestart[hour]     | 0                |
      | timestart[minute]   | 0                |
      | timestart[timezone] | Pacific/Auckland |
      | timefinish[day]     | 10               |
      | timefinish[month]   | 0                |
      | timefinish[year]    | 0                |
      | timefinish[hour]    | 0                |
      | timefinish[minute]  | 0                |
      | timefinish[timezone]| Pacific/Auckland |
    And I press "OK"
    And I press "Save changes"

    When I click on "Cancel event" "link" in the "Booking open" "table_row"
    And I press "Yes"
    Then I should see date "10 day Pacific/Auckland" formatted "%d %B %Y"
    And I should see "Event cancelled" in the "0 / 39" "table_row"
    And I should see "Sign-up unavailable" in the "Event cancelled" "table_row"
    And "Cancel event" "link" should not exist in the "Event cancelled" "table_row"
    And "Copy event" "link" should exist in the "Event cancelled" "table_row"
    And "Delete event" "link" should exist in the "Event cancelled" "table_row"
    And "Edit event" "link" should not exist in the "Event cancelled" "table_row"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_107: cancel and delete the whole seminar event
    Given I follow "Add a new event"
    And I set the field "Maximum bookings" to "20"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
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
    And I press "Save changes"
    And I follow "Add a new event"
    And I set the field "Maximum bookings" to "30"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
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
    And I press "Save changes"

    When I click on "Cancel event" "link" in the "0 / 30" "table_row"
    And I should see "Cancelling event in"
    And I should see "Are you completely sure you want to cancel this event?"
    And I press "Yes"
    Then I should see "Event cancelled" in the ".alert-success" "css_element"
    And I should see "Event cancelled" in the "0 / 30" "table_row"
    And I should not see "Edit event" in the "0 / 30" "table_row"
    And I should see "Booking open" in the "0 / 20" "table_row"

    When I click on "Delete event" "link" in the "0 / 30" "table_row"
    And I should see "Deleting event in"
    And I press "Continue"
    Then I should not see "0 / 30"
    And I should see "0 / 20"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_108: cancel and clone cancelled event
    Given I follow "Add a new event"
    And I set the field "Maximum bookings" to "20"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
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
    And I press "Save changes"
    And I follow "Add a new event"
    And I set the field "Maximum bookings" to "30"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
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
    And I press "Save changes"

    When I click on "Cancel event" "link" in the "0 / 30" "table_row"
    And I should see "Cancelling event in"
    And I should see "Are you completely sure you want to cancel this event?"
    And I press "Yes"
    Then I should see "Event cancelled" in the ".alert-success" "css_element"
    And I should see "Event cancelled" in the "0 / 30" "table_row"
    And I should not see "Edit event" in the "0 / 30" "table_row"
    And I should see "Booking open" in the "0 / 20" "table_row"

    # --------------------------------------------------------------------------
    # THIS PART WILL FAIL WITH THE CURRENT SEMINAR CANCELLATION CODE. This is
    # due to a regression from TL-9110.
    # --------------------------------------------------------------------------
    Given I skip the scenario until issue "TL-9478" lands
    When I click on "Copy event" "link" in the "0 / 30" "table_row"
    And I set the field "Maximum bookings" to "99"
    And I press "Save changes"
    Then I should see "Event cancelled" in the "0 / 30" "table_row"
    And I should see "Booking open" in the "0 / 99" "table_row"
