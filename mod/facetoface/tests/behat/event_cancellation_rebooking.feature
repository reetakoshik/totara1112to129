@mod @mod_facetoface @totara @javascript
Feature: Seminar event cancellation rebooking
  After seminar events have been cancelled
  As a learner
  I need to be able to rebook events.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | learner1 | Learner   | One      | learner1@example.com |
      | learner2 | Learner   | Two      | learner2@example.com |
      | learner3 | Learner   | Three    | learner3@example.com |

    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |

    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | learner1 | C1     | student        |
      | learner2 | C1     | student        |
      | learner3 | C1     | student        |

    Given I log in as "teacher1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                                   | Test Seminar |
      | Description                            | Test Seminar |
      | Users can sign-up to multiple events   | 0            |
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
    And I set the following fields to these values:
      | Maximum bookings | 39 |
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
    And I follow "Sign-up"
    And I press "Sign-up"

    Given I log out
    And I log in as "teacher1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "View all events"
    And I follow "Add a new event"
    And I follow "show-selectdate0-dialog"
    And I set the following fields to these values:
      | sessiontimezone     | Pacific/Auckland |
      | timestart[day]      | 10               |
      | timestart[month]    | 2                |
      | timestart[year]     | 2030             |
      | timestart[hour]     | 9                |
      | timestart[minute]   | 0                |
      | timestart[timezone] | Pacific/Auckland |
      | timefinish[day]     | 10               |
      | timefinish[month]   | 2                |
      | timefinish[year]    | 2030             |
      | timefinish[hour]    | 15               |
      | timefinish[minute]  | 0                |
      | timefinish[timezone]| Pacific/Auckland |
    And I press "OK"
    And I set the following fields to these values:
      | Maximum bookings | 19 |
    And I press "Save changes"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_600: Mass rebooking after a cancelled event
    Given I click on "Attendees" "link" in the "10 February 2030" "table_row"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Learner One, learner1@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I click on "Learner Two, learner2@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"

    When I follow "View results"
    Then I should see "This user is already signed-up" in the "Learner One" "table_row"
    And I should see "This user is already signed-up" in the "Learner Two" "table_row"
    And I press "Close"

    Given I log out
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "View all events"
    And I click on "Cancel event" "link" in the "10 February 2025" "table_row"
    And I press "Yes"

    Given I log out
    And I log in as "teacher1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "View all events"
    Then I should see "Booking open" in the "10 February 2030" "table_row"
    And I should see "Event cancelled" in the "10 February 2025" "table_row"

    When I click on "Attendees" "link" in the "10 February 2030" "table_row"
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
    Then I should see "2 / 19" in the "10 February 2030" "table_row"

  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_601: Individual learner rebooking after a cancelled event
    Given I log out
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "View all events"
    And I click on "Cancel event" "link" in the "10 February 2025" "table_row"
    And I press "Yes"

    Given I log out
    And I log in as "learner1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "View all events"
    Then I should see "Booking open" in the "10 February 2030" "table_row"
    Then I should see "19" in the "10 February 2030" "table_row"
    And I should see "Event cancelled" in the "10 February 2025" "table_row"

    When I click on "Sign-up" "link" in the "10 February 2030" "table_row"
    And I press "Sign-up"
    Then I should see "Booked" in the "10 February 2030" "table_row"
    Then I should see "18" in the "10 February 2030" "table_row"

    Given I log out
    And I log in as "learner2"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "View all events"
    Then I should see "Booking open" in the "10 February 2030" "table_row"
    Then I should see "18" in the "10 February 2030" "table_row"
    And I should see "Event cancelled" in the "10 February 2025" "table_row"

    When I click on "Sign-up" "link" in the "10 February 2030" "table_row"
    And I press "Sign-up"
    Then I should see "Booked" in the "10 February 2030" "table_row"
    Then I should see "17" in the "10 February 2030" "table_row"

    Given I log out
    And I log in as "learner3"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "View all events"
    Then I should see "Booking open" in the "10 February 2030" "table_row"
    Then I should see "17" in the "10 February 2030" "table_row"
    And I should see "Event cancelled" in the "10 February 2025" "table_row"

    When I click on "Sign-up" "link" in the "10 February 2030" "table_row"
    And I press "Sign-up"
    Then I should see "Booked" in the "10 February 2030" "table_row"
    Then I should see "16" in the "10 February 2030" "table_row"