@mod @mod_facetoface @totara @javascript @totara_customfield
Feature: Clone pre-defined rooms in seminar
  In order to test seminar rooms
  As a site manager
  I need to clone event

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And I log in as "admin"
    And I navigate to "Rooms" node in "Site administration > Seminars"
    And I press "Add a new room"
    And I set the following fields to these values:
      | Name              | Room 1          |
      | Building          | That house      |
      | Address           | 123 here street |
      | Room capacity     | 5               |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I press "Add a room"

    And I press "Add a new room"
    And I set the following fields to these values:
      | Name              | Room 2          |
      | Building          | Your house      |
      | Address           | 123 near street |
      | Room capacity     | 6               |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I press "Add a room"

  Scenario: Try and clash a room in seminar
    Given I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I wait "2" seconds
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 0    |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"
    And I click on "Select room" "link"
    And I click on "Room 1, That house, 123 here street (Capacity: 5)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I press "Save changes"

    And I click on "Copy" "link"
    When I click on "Select room" "link"
    Then I should see "Room 1, That house, 123 here street (Capacity: 5) (Room unavailable)" in the "Choose a room" "totaradialogue"
    And I click on "Cancel" "button" in the "Choose a room" "totaradialogue"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 14   |
      | timestart[minute]  | 0    |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 15   |
      | timefinish[minute] | 0    |
    And I press "OK"
    And I click on "Select room" "link"
    And I click on "Room 1, That house, 123 here street (Capacity: 5)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 0    |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 0    |
    And I press "OK"
    And I should see "The new dates you have selected are unavailable due to a scheduling conflict"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 14   |
      | timestart[minute]  | 0    |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 15   |
      | timefinish[minute] | 0    |
    And I press "OK"
    And I click on "Select room" "link"
    And I click on "Room 1, That house, 123 here street (Capacity: 5)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    When I press "Save changes"
    Then I should see "Room 1" in the "2:00 PM - 3:00 PM Australia/Perth" "table_row"
