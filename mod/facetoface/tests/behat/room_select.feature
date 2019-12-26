@mod @mod_facetoface @totara @totara_customfield
Feature: Display the rooms in select room dialog when room is booked and hidden
  In order to test seminar rooms
  As a site manager
  I need to create rooms, add rooms to events and hide one of the room

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity   | name            | course | idnumber |
      | facetoface | Seminar TL-9152 | C1     | S9152    |

    And I log in as "admin"
    And I navigate to "Rooms" node in "Site administration > Seminars"
    And I press "Add a new room"
    And I set the following fields to these values:
      | Name              | Room 1          |
      | Building          | Building 123    |
      | Address           | 123 Tory street |
      | Room capacity     | 10              |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I press "Add a room"

    And I press "Add a new room"
    And I set the following fields to these values:
      | Name              | Room 2          |
      | Building          | Building 234    |
      | Address           | 234 Tory street |
      | Room capacity     | 10              |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I press "Add a room"
    And I press "Add a new room"
    And I set the following fields to these values:
      | Name              | Room 3          |
      | Building          | Building 345    |
      | Address           | 345 Tory street |
      | Room capacity     | 10              |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I press "Add a room"

  @javascript
  Scenario: Add sessions with different rooms, hide one of the room, check select room dialog
    And I am on "Course 1" course homepage
    And I follow "Seminar TL-9152"

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

    When I click on "Select room" "link"
    Then I should see "Room 1, Building 123, 123 Tory street (Capacity: 10)" in the "Choose a room" "totaradialogue"
    And I should see "Room 2, Building 234, 234 Tory street (Capacity: 10)" in the "Choose a room" "totaradialogue"
    And I should see "Room 3, Building 345, 345 Tory street (Capacity: 10)" in the "Choose a room" "totaradialogue"

    And I click on "Room 1, Building 123, 123 Tory street (Capacity: 10)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I press "Save changes"

    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 2    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 2    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"

    When I click on "Select room" "link"
    Then I should see "Room 1, Building 123, 123 Tory street (Capacity: 10)" in the "Choose a room" "totaradialogue"
    And I should see "Room 2, Building 234, 234 Tory street (Capacity: 10)" in the "Choose a room" "totaradialogue"
    And I should see "Room 3, Building 345, 345 Tory street (Capacity: 10)" in the "Choose a room" "totaradialogue"

    And I click on "Room 2, Building 234, 234 Tory street (Capacity: 10)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I press "Save changes"

    And I navigate to "Rooms" node in "Site administration > Seminars"
    And I click on "Hide from users when choosing a room on the Add/Edit event page" "link" in the "Room 2" "table_row"

    And I am on "Course 1" course homepage
    And I follow "Seminar TL-9152"

    And I click on "Edit event" "link" in the "Room 2" "table_row"
    When I click on "Select room" "link"
    Then I should see "Room 1, Building 123, 123 Tory street (Capacity: 10)" in the "Choose a room" "totaradialogue"
    And I should see "Room 2, Building 234, 234 Tory street (Capacity: 10)" in the "Choose a room" "totaradialogue"
    And I should see "Room 3, Building 345, 345 Tory street (Capacity: 10)" in the "Choose a room" "totaradialogue"

    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I click on "#id_cancel" "css_element"

    And I click on "Edit event" "link" in the "Room 1" "table_row"
    When I click on "Select room" "link"
    Then I should see "Room 1, Building 123, 123 Tory street (Capacity: 10)" in the "Choose a room" "totaradialogue"
    And I should not see "Room 2, Building 234, 234 Tory street (Capacity: 10)" in the "Choose a room" "totaradialogue"
    And I should see "Room 3, Building 345, 345 Tory street (Capacity: 10)" in the "Choose a room" "totaradialogue"
