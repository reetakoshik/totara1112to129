@mod @mod_facetoface @totara @javascript
Feature: Seminar event cancellation trainer can be reassigned
  After seminar event has been cancelled the trainer can be
  reassigned to a new event with the same dates

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |

    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |

    And the following "course enrolments" exist:
      | user     | course | role    |
      | teacher1 | C1     | teacher |

    Given I log in as "admin"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I wait "1" seconds
    And I click on "#id_s__facetoface_session_roles_4" "css_element"
    And I press "Save changes"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test Seminar |
      | Description | Test Seminar |
    And I follow "View all events"
    And I follow "Add a new event"
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

    And I log out

  # ----------------------------------------------------------------------------

  # Check that the functionality has not been broken.
  Scenario: Trainer should not be able to be assigned for a new event with the same date if he is already assigned for an event with the same dates
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test Seminar"
    And I follow "Add a new event"
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
    When I press "Save changes"
    Then I should see "Saving this event as it is will cause a scheduling conflict"
    And I click on "Cancel" "button" in the ".modal" "css_element"
    And I log out

  # Check that the when an event was cancelled the trainer can be reassigned to a new event with the same dates.
  Scenario: Trainer should be able to be assigned for a new event with the same date if he is already assigned for a cancelled event with the same dates
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test Seminar"
    And I follow "Cancel event"
    And I press "Yes"
    And I follow "Add a new event"
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
    When I press "Save changes"
    Then I should see "All events in Test Seminar"
    And I log out