@mod @mod_facetoface @totara @javascript
Feature: Seminar event cancellation notifications
  After seminar events have been cancelled
  As an learner
  I need to be notified of the cancellations

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | learner1 | Learner   | One      | learner1@example.com |
      | learner2 | Learner   | Two      | learner2@example.com |
      | learner3 | Learner   | Three    | learner3@example.com |
      | learner4 | Learner   | Four     | learner4@example.com |
      | manager4 | Manager   | Four     | manager4@example.com |

    And the following job assignments exist:
      | user     | manager  |
      | learner4 | manager4 |

    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |

    And the following "roles" exist:
      | name              | shortname         | archetype | contextlevel |
      | ReservationRole   | ReservationRole   |           | System       |

    And the following "permission overrides" exist:
      | capability                         | permission | role            | contextlevel | reference |
      | mod/facetoface:reservespace        | Allow      | ReservationRole | System       |           |
      | mod/facetoface:view                | Allow      | ReservationRole | System       |           |
      | mod/facetoface:viewcancellations   | Allow      | ReservationRole | System       |           |
      | mod/facetoface:viewemptyactivities | Allow      | ReservationRole | System       |           |

    Given the following "course enrolments" exist:
      | user     | course | role            |
      | teacher1 | C1     | editingteacher  |
      | learner1 | C1     | student         |
      | learner2 | C1     | student         |
      | learner3 | C1     | student         |
      | manager4 | C1     | ReservationRole |

    Given I log in as "admin"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "Editing Trainer" "text" in the "#admin-facetoface_session_roles" "css_element"
    And I click on "ReservationRole" "text" in the "#admin-facetoface_session_roles" "css_element"
    And I click on "Editing Trainer" "text" in the "#admin-facetoface_session_rolesnotify" "css_element"
    And I click on "ReservationRole" "text" in the "#admin-facetoface_session_rolesnotify" "css_element"
    And I press "Save changes"
    And I log out

    Given I log in as "teacher1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                               | Test Seminar |
      | Description                        | Test Seminar |
      | Allow manager reservations         | Yes          |
      | Maximum reservations               | 1            |
      | Automatically cancel reservations  | No           |
    And I follow "View all events"

    Given I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 2 |
      | Enable waitlist  | 1 |
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
    And I click on "Teacher One" "checkbox"
    And I click on "Manager Four" "checkbox"
    And I press "Save changes"

    Given I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Learner One, learner1@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I click on "Learner Two, learner2@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    And I follow "Go back"

    Given I log out
    And I log in as "learner3"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "Join waitlist"
    And I press "Sign-up"

  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_200: people notified of cancelled event with single future date.
    When I log out
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "View all events"
    Then I should see "9:00 AM - 3:00 PM Pacific/Auckland" in the "10 February 2025" "table_row"
    And I should see "2 / 2" in the "10 February 2025" "table_row"
    And I should see "Booking full" in the "10 February 2025" "table_row"
    And "Cancel event" "link" should exist in the "10 February 2025" "table_row"

    When I click on "Cancel event" "link" in the "10 February 2025" "table_row"
    Then I should see "Cancelling event in Test Seminar"
    And I should see "10 February 2025, 9:00 AM - 3:00 PM Pacific/Auckland"
    And I press "Yes"
    Then I should see "9:00 AM - 3:00 PM Pacific/Auckland" in the "10 February 2025" "table_row"
    And I should see "3 / 2 (Overbooked)" in the "10 February 2025" "table_row"
    And I should see "Event cancelled" in the "10 February 2025" "table_row"
    And "Cancel event" "link" should not exist in the "10 February 2025" "table_row"

    When I log out
    And I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    Then I should see "Seminar event cancellation"

    When I click on "View all alerts" "link"
    And I follow "Show more..."
    And I set the field "Message Content value" to "CANCELLED"
    And I click on "input[value=Search]" "css_element"
    Then I should see "***EVENT CANCELLED***"

    And I should see "Course:   Course 1"
    And I should see "Seminar:   Test Seminar"
    And I should see "10 February 2025, 9:00 AM - 10 February 2025, 3:00 PM Pacific/Auckland"

    When I log out
    And I log in as "learner2"
    And I click on "Dashboard" in the totara menu
    Then I should see "Seminar event cancellation"

    When I click on "View all alerts" "link"
    And I follow "Show more..."
    And I set the field "Message Content value" to "CANCELLED"
    And I click on "input[value=Search]" "css_element"
    Then I should see "***EVENT CANCELLED***"

    And I should see "Course:   Course 1"
    And I should see "Seminar:   Test Seminar"
    And I should see "10 February 2025, 9:00 AM - 10 February 2025, 3:00 PM Pacific/Auckland"

    When I log out
    And I log in as "learner3"
    And I click on "Dashboard" in the totara menu
    Then I should see "Seminar event cancellation"

    When I click on "View all alerts" "link"
    And I follow "Show more..."
    And I set the field "Message Content value" to "CANCELLED"
    And I click on "input[value=Search]" "css_element"
    Then I should see "***EVENT CANCELLED***"

    And I should see "Course:   Course 1"
    And I should see "Seminar:   Test Seminar"
    And I should see "10 February 2025, 9:00 AM - 10 February 2025, 3:00 PM Pacific/Auckland"

    When I log out
    And I log in as "teacher1"
    And I click on "Dashboard" in the totara menu
    Then I should see "Seminar event cancellation"

    When I click on "View all alerts" "link"
    And I follow "Show more..."
    And I set the field "Message Content value" to "CANCELLED"
    And I click on "input[value=Search]" "css_element"
    Then I should see "***EVENT CANCELLED***"

    And I should see "Course:   Course 1"
    And I should see "Seminar:   Test Seminar"
    And I should see "10 February 2025, 9:00 AM - 10 February 2025, 3:00 PM Pacific/Auckland"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_201: people notified of cancelled event with multiple future dates.
    Given I log out
    And I log in as "teacher1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I click on "Edit event" "link" in the "10 February 2025" "table_row"
    And I press "Add a new session"
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

    When I log out
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "View all events"
    Then I should see "9:00 AM - 3:00 PM Pacific/Auckland" in the "10 February 2025" "table_row"
    And I should see "10:00 AM - 4:00 PM Pacific/Auckland" in the "11 March 2026" "table_row"
    And I should see "2 / 2" in the "10 February 2025" "table_row"
    And I should see "Booking full" in the "10 February 2025" "table_row"
    And "Cancel event" "link" should exist in the "10 February 2025" "table_row"

    When I click on "Cancel event" "link" in the "10 February 2025" "table_row"
    And I press "Yes"
    Then I should see "9:00 AM - 3:00 PM Pacific/Auckland" in the "10 February 2025" "table_row"
    And I should see "10:00 AM - 4:00 PM Pacific/Auckland" in the "11 March 2026" "table_row"
    And I should see "3 / 2 (Overbooked)" in the "10 February 2025" "table_row"
    And I should see "Event cancelled" in the "10 February 2025" "table_row"
    And "Cancel event" "link" should not exist in the "10 February 2025" "table_row"

    When I log out
    And I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    Then I should see "Seminar event cancellation"

    When I click on "View all alerts" "link"
    And I follow "Show more..."
    And I set the field "Message Content value" to "CANCELLED"
    And I click on "input[value=Search]" "css_element"
    Then I should see "***EVENT CANCELLED***"

    And I should see "Course:   Course 1"
    And I should see "Seminar:   Test Seminar"
    And I should see "10 February 2025, 9:00 AM - 10 February 2025, 3:00 PM Pacific/Auckland"
    And I should see "11 March 2026, 10:00 AM - 11 March 2026, 4:00 PM Pacific/Auckland"

    When I log out
    And I log in as "learner2"
    And I click on "Dashboard" in the totara menu
    Then I should see "Seminar event cancellation"

    When I click on "View all alerts" "link"
    And I follow "Show more..."
    And I set the field "Message Content value" to "CANCELLED"
    And I click on "input[value=Search]" "css_element"
    Then I should see "***EVENT CANCELLED***"

    And I should see "Course:   Course 1"
    And I should see "Seminar:   Test Seminar"
    And I should see "10 February 2025, 9:00 AM - 10 February 2025, 3:00 PM Pacific/Auckland"
    And I should see "11 March 2026, 10:00 AM - 11 March 2026, 4:00 PM Pacific/Auckland"

    When I log out
    And I log in as "learner3"
    And I click on "Dashboard" in the totara menu
    Then I should see "Seminar event cancellation"

    When I click on "View all alerts" "link"
    And I follow "Show more..."
    And I set the field "Message Content value" to "CANCELLED"
    And I click on "input[value=Search]" "css_element"
    Then I should see "***EVENT CANCELLED***"

    And I should see "Course:   Course 1"
    And I should see "Seminar:   Test Seminar"
    And I should see "10 February 2025, 9:00 AM - 10 February 2025, 3:00 PM Pacific/Auckland"
    And I should see "11 March 2026, 10:00 AM - 11 March 2026, 4:00 PM Pacific/Auckland"

    When I log out
    And I log in as "teacher1"
    And I click on "Dashboard" in the totara menu
    Then I should see "Seminar event cancellation"

    When I click on "View all alerts" "link"
    And I follow "Show more..."
    And I set the field "Message Content value" to "CANCELLED"
    And I click on "input[value=Search]" "css_element"
    Then I should see "***EVENT CANCELLED***"

    And I should see "Course:   Course 1"
    And I should see "Seminar:   Test Seminar"
    And I should see "10 February 2025, 9:00 AM - 10 February 2025, 3:00 PM Pacific/Auckland"
    And I should see "11 March 2026, 10:00 AM - 11 March 2026, 4:00 PM Pacific/Auckland"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_202: deleting a cancelled event does not resend cancellation messages.
    When I log out
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "View all events"
    Then I should see "9:00 AM - 3:00 PM Pacific/Auckland" in the "10 February 2025" "table_row"
    And I should see "2 / 2" in the "10 February 2025" "table_row"
    And I should see "Booking full" in the "10 February 2025" "table_row"
    And "Cancel event" "link" should exist in the "10 February 2025" "table_row"
    And I click on "Cancel event" "link" in the "10 February 2025" "table_row"
    And I should see "Cancelling event in Test Seminar"
    And I should see "10 February 2025, 9:00 AM - 3:00 PM Pacific/Auckland"
    And I press "Yes"

    When I log out
    And I log in as "learner1"
    And I click on "Dashboard" in the totara menu

    When I click on "View all alerts" "link"
    And I click on "All" "link"
    And I press "Dismiss"
    And I click on "Dismiss" "button" in the "Dismiss" "totaradialogue"
    Then I should see "0 records shown"

    Given I log out
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "View all events"
    And I click on "Delete event" "link" in the "10 February 2025" "table_row"
    And I press "Continue"

    When I log out
    And I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    Then I should not see "Seminar event cancellation"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_203: manager with reservations notified of cancelled event.
    When I log out
    And I log in as "manager4"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "View all events"
    Then I should see "9:00 AM - 3:00 PM Pacific/Auckland" in the "10 February 2025" "table_row"
    And I should see "Booking full" in the "10 February 2025" "table_row"
    And I should see "Reserve spaces for team (0/1)" in the "10 February 2025" "table_row"

    When I follow "Reserve spaces for team"
    And I set the field "reserve" to "1*"
    And I press "Update"
    Then I should see "Reserve spaces for team (1/1)"

    Given I log out
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "View all events"
    And I click on "Cancel event" "link" in the "10 February 2025" "table_row"
    And I press "Yes"

    When I log out
    And I log in as "manager4"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "View all events"
    Then I should see "9:00 AM - 3:00 PM Pacific/Auckland" in the "10 February 2025" "table_row"
    And I should see "Event cancelled" in the "10 February 2025" "table_row"
    And I should see "Sign-up unavailable" in the "10 February 2025" "table_row"

    When I click on "Dashboard" in the totara menu
    Then I should see "Seminar event cancellation"

    When I click on "View all alerts" "link"
    And I follow "Show more..."
    And I set the field "Message Content value" to "CANCELLED"
    And I click on "input[value=Search]" "css_element"
    Then I should see "***EVENT CANCELLED***"

    And I should see "Course:   Course 1"
    And I should see "Seminar:   Test Seminar"
    And I should see "10 February 2025, 9:00 AM - 10 February 2025, 3:00 PM Pacific/Auckland"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_204: manager with allocations notified of cancelled event.
    When I log out
    And I log in as "manager4"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "View all events"
    Then I should see "9:00 AM - 3:00 PM Pacific/Auckland" in the "10 February 2025" "table_row"
    And I should see "Booking full" in the "10 February 2025" "table_row"
    And I should see "Allocate spaces for team (0/1)" in the "10 February 2025" "table_row"

    When I follow "Allocate spaces for team"
    And I click on "Learner Four" "option"
    And I press "Add"
    And I wait "1" seconds
    Then I should see "Allocate spaces for team (1/1)"

    Given I log out
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "View all events"
    And I click on "Cancel event" "link" in the "10 February 2025" "table_row"
    And I press "Yes"

    When I log out
    And I log in as "learner4"
    And I click on "Dashboard" in the totara menu
    Then I should see "Seminar event cancellation"

    When I click on "View all alerts" "link"
    And I follow "Show more..."
    And I set the field "Message Content value" to "CANCELLED"
    And I click on "input[value=Search]" "css_element"
    Then I should see "***EVENT CANCELLED***"

    And I should see "Course:   Course 1"
    And I should see "Seminar:   Test Seminar"
    And I should see "10 February 2025, 9:00 AM - 10 February 2025, 3:00 PM Pacific/Auckland"

    When I log out
    And I log in as "manager4"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "View all events"
    Then I should see "9:00 AM - 3:00 PM Pacific/Auckland" in the "10 February 2025" "table_row"
    And I should see "Event cancelled" in the "10 February 2025" "table_row"
    And I should see "Sign-up unavailable" in the "10 February 2025" "table_row"

    When I click on "Dashboard" in the totara menu
    Then I should see "Seminar event cancellation"

    When I click on "View all alerts" "link"
    And I follow "Show more..."
    And I set the field "Message Content value" to "CANCELLED"
    And I click on "input[value=Search]" "css_element"
    Then I should see "***EVENT CANCELLED***"

    And I should see "Course:   Course 1"
    And I should see "Seminar:   Test Seminar"
    And I should see "10 February 2025, 9:00 AM - 10 February 2025, 3:00 PM Pacific/Auckland"