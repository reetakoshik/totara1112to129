@mod @mod_facetoface @totara @javascript
Feature: Seminar event cancellation learner views
  After seminar events have been cancelled
  As a learner
  I need to see cancellation summaries

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | learner1 | Learner   | One      | learner1@example.com |
      | learner2 | Learner   | Two      | learner2@example.com |
      | learner3 | Learner   | Three    | learner2@example.com |
      | learner4 | Learner   | Four     | learner2@example.com |

    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |

    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | learner1 | C1     | student        |
      | learner2 | C1     | student        |
      | learner3 | C1     | student        |
      | learner4 | C1     | student        |

    Given I log in as "admin"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "Editing Trainer" "text" in the "#admin-facetoface_session_roles" "css_element"
    And I click on "Editing Trainer" "text" in the "#admin-facetoface_session_rolesnotify" "css_element"
    And I press "Save changes"
    And I log out

    Given I log in as "teacher1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test Seminar |
      | Description | Test Seminar |
    And I follow "View all events"

    Given I follow "Add a new event"
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
    And I press "Save changes"

    Given I follow "Add a new event"
    And I follow "show-selectdate0-dialog"
    And I set the following fields to these values:
      | sessiontimezone     | Pacific/Auckland |
      | timestart[day]      | 10               |
      | timestart[month]    | 2                |
      | timestart[year]     | 2026             |
      | timestart[hour]     | 10               |
      | timestart[minute]   | 0                |
      | timestart[timezone] | Pacific/Auckland |
      | timefinish[day]     | 10               |
      | timefinish[month]   | 2                |
      | timefinish[year]    | 2026             |
      | timefinish[hour]    | 16               |
      | timefinish[minute]  | 0                |
      | timefinish[timezone]| Pacific/Auckland |
    And I press "OK"
    And I click on "Teacher One" "checkbox"
    And I press "Save changes"

    Given I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 29 |
    And I follow "show-selectdate0-dialog"
    And I set the following fields to these values:
      | sessiontimezone     | Pacific/Auckland |
      | timestart[day]      | 10               |
      | timestart[month]    | 4                |
      | timestart[year]     | 2030             |
      | timestart[hour]     | 17               |
      | timestart[minute]   | 0                |
      | timestart[timezone] | Pacific/Auckland |
      | timefinish[day]     | 10               |
      | timefinish[month]   | 4                |
      | timefinish[year]    | 2030             |
      | timefinish[hour]    | 18               |
      | timefinish[minute]  | 0                |
      | timefinish[timezone]| Pacific/Auckland |
    And I press "OK"
    And I click on "Teacher One" "checkbox"
    And I press "Save changes"

  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_300: cancelled booking (course view).
    Given I click on "Attendees" "link" in the "10 February 2025" "table_row"
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
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "View all events"
    And I click on "Cancel event" "link" in the "10 February 2025" "table_row"
    And I press "Yes"

    When I log out
    And I log in as "learner1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "View all events"
    Then I should see "9:00 AM - 3:00 PM Pacific/Auckland" in the "10 February 2025" "table_row"
    And I should see "Event cancelled" in the "10 February 2025" "table_row"
    And I should see "Sign-up unavailable" in the "10 February 2025" "table_row"
    And I should see "10:00 AM - 4:00 PM Pacific/Auckland" in the "10 February 2026" "table_row"
    And I should see "Booking open" in the "10 February 2026" "table_row"
    And I should see "Sign-up" in the "10 February 2026" "table_row"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_301: cancelled booking (future bookings view).
    Given I click on "Attendees" "link" in the "10 February 2025" "table_row"
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
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "View all events"
    And I click on "Cancel event" "link" in the "10 February 2025" "table_row"
    And I press "Yes"

    When I log out
    And I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I click on "Bookings" "link"
    Then I should see "Course 1" in the "Test Seminar" "table_row"
    And I should see "10 February 2025" in the "Test Seminar" "table_row"
    And I should see "9:00 AM Pacific/Auckland" in the "Test Seminar" "table_row"
    And I should see "3:00 PM Pacific/Auckland" in the "Test Seminar" "table_row"
    And I should see "Event Cancelled" in the "Test Seminar" "table_row"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_302: cancelled booking (past bookings view).
    Given I click on "Attendees" "link" in the "10 April 2030" "table_row"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Learner One, learner1@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I click on "Learner Two, learner2@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I click on "Learner Three, learner2@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I click on "Learner Four, learner2@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    And I follow "Go back"
    And I click on "Cancel event" "link" in the "10 April 2030" "table_row"
    And I press "Yes"

    # Magic needed here since only a future event can be cancelled and we don't
    # want to wait until that future time comes.
    Given I use magic to adjust the seminar event "start" from "10/04/2030 17:00" "Pacific/Auckland" to "10/04/2015 09:00"
    And I use magic to adjust the seminar event "end" from "10/04/2030 18:00" "Pacific/Auckland" to "10/04/2015 14:00"
    And I log out

    And I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I click on "Bookings" "link"
    And I click on "Past Bookings" "link"
    Then I should see "Course 1" in the "Test Seminar" "table_row"
    And I should see "10 April 2015" in the "Test Seminar" "table_row"
    And I should see "9:00 AM Pacific/Auckland" in the "Test Seminar" "table_row"
    And I should see "2:00 PM Pacific/Auckland" in the "Test Seminar" "table_row"
    And I should see "Event Cancelled" in the "Test Seminar" "table_row"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_303: remove cancelled sessions from learner views.
    Given I click on "Attendees" "link" in the "10 February 2025" "table_row"
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
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "View all events"
    And I click on "Cancel event" "link" in the "10 February 2025" "table_row"
    And I press "Yes"

    When I log out
    And I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I click on "Bookings" "link"

    # --------------------------------------------------------------------------
    # THIS PART WILL FAIL WITH THE CURRENT SEMINAR CANCELLATION CODE. This is
    # because there does not seem to be a way to enable this mechanism, as per
    # v1.6 specs (background section, 2nd para, #7) which says there should be a
    # way.
    # --------------------------------------------------------------------------
    Given I skip the scenario until issue "TL-9482" lands

    Then I should not see "Course 1" in the "Test Seminar" "table_row"
    And I should not see "10 February 2025" in the "Test Seminar" "table_row"
    And I should not see "9:00 AM Pacific/Auckland" in the "Test Seminar" "table_row"
    And I should not see "3:00 PM Pacific/Auckland" in the "Test Seminar" "table_row"
    And I should not see "Event Cancelled" in the "Test Seminar" "table_row"
