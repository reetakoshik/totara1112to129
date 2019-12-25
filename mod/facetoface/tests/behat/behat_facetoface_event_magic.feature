@mod @mod_facetoface @totara @javascript
Feature: Test steps in behat_facetoface.php
  In order to test facetoface
  The defined generator steps need to work correctly

  Scenario: Test adjusting seminar timestamps
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |

    Given I log in as "admin"
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

    When I press "Save changes"
    Then I should see "9:00 AM - 3:00 PM Pacific/Auckland" in the "10 February 2025" "table_row"
    And I should see "10:00 AM - 4:00 PM Pacific/Auckland" in the "11 March 2026" "table_row"

    When I use magic to adjust the seminar event "start" from "11/03/2026 10:00" "Pacific/Auckland" to "11/03/2000 09:00"
    And I use magic to adjust the seminar event "end" from "11/03/2026 16:00" "Pacific/Auckland" to "11/03/2000 14:00"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    Then I should see "9:00 AM - 3:00 PM Pacific/Auckland" in the "10 February 2025" "table_row"
    And I should see "9:00 AM - 2:00 PM Pacific/Auckland" in the "11 March 2000" "table_row"
